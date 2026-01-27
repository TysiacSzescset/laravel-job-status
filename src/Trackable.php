<?php

declare (strict_types = 1);

namespace Yannelli\TrackJobStatus;

use Illuminate\Support\Facades\Cache;

/**
 * Trait for tracking job status, progress, and lifecycle events.
 *
 * Add this trait to your job classes to enable automatic tracking of:
 * - Job status (queued, executing, finished, failed, retrying)
 * - Progress tracking with current/max values
 * - Input/output data
 * - Custom status messages
 * - Batch and chain information
 * - Complete history of status changes
 *
 * @package Yannelli\TrackJobStatus
 */
trait Trackable
{
    /** @var int|string|null The ID of the JobStatus record */
    protected int | string | null $statusId = null;

    /** @var int Current progress value */
    protected int $progressNow = 0;

    /** @var int Maximum progress value */
    protected int $progressMax = 0;

    /** @var bool Whether this job should be tracked */
    protected bool $shouldTrack = true;

    /**
     * Set the maximum progress value for this job.
     *
     * @param int $value The maximum progress value
     * @return void
     */
    protected function setProgressMax(int $value): void
    {
        $this->update(['progress_max' => $value]);
        $this->progressMax = $value;
    }

    /**
     * Update the current progress value.
     *
     * @param int $value The current progress value
     * @param int $every Only write to database when $value % $every === 0 (default: 1 = every update)
     * @return void
     */
    protected function setProgressNow(int $value, int $every = 1): void
    {
        // Guard against division by zero
        if ($every <= 0) {
            $every = 1;
        }

        if ($value % $every === 0 || $value === $this->progressMax) {
            $this->update(['progress_now' => $value]);
        }
        $this->progressNow = $value;
    }

    /**
     * Increment the current progress by an offset.
     *
     * @param int $offset Amount to increment progress by (default: 1)
     * @param int $every Only write to database when value % $every === 0 (default: 1 = every update)
     * @return void
     */
    protected function incrementProgress(int $offset = 1, int $every = 1): void
    {
        $value = $this->progressNow + $offset;
        $this->setProgressNow($value, $every);
    }

    /**
     * Store input data for this job.
     * Respects the 'track_input' configuration setting.
     *
     * @param array<string, mixed> $value The input data to store
     * @return void
     */
    protected function setInput(array $value): void
    {
        if (! config('job-status.track_input', true)) {
            return;
        }

        $this->update(['input' => $value]);
    }

    /**
     * Store output/result data for this job.
     * Respects the 'track_output' configuration setting.
     *
     * @param array<string, mixed> $value The output data to store
     * @return void
     */
    protected function setOutput(array $value): void
    {
        if (! config('job-status.track_output', true)) {
            return;
        }

        $this->update(['output' => $value]);
    }

    /**
     * Set a custom status message describing the current job state.
     *
     * @param string $message The status message (e.g., "Processing batch 3 of 10")
     * @return void
     */
    protected function setStatusMessage(string $message): void
    {
        $this->update(['status_message' => $message]);
    }

    /**
     * Manually set chain tracking information.
     *
     * @param string $chainId Unique identifier for the chain
     * @param int $currentStep Current step number in the chain (1-based)
     * @param int $totalJobs Total number of jobs in the chain
     * @return void
     */
    protected function setChain(string $chainId, int $currentStep, int $totalJobs): void
    {
        $this->update([
            'chain_id'     => $chainId,
            'current_step' => $currentStep,
            'total_jobs'   => $totalJobs,
        ]);
    }

    /**
     * Update job status data in the database.
     *
     * @param array<string, mixed> $data Data to update
     * @return void
     */
    protected function update(array $data): void
    {
        // Guard against updates when tracking is disabled
        if (! $this->shouldTrack || $this->statusId === null) {
            return;
        }

        $updater = app(JobStatusUpdater::class);
        $updater->update($this, $data);
    }

    /**
     * Initialize job status tracking.
     * Must be called in the job constructor before any other tracking methods.
     *
     * @param array<string, mixed> $data Optional additional data to store (e.g., custom fields, chain info)
     * @return void
     */
    protected function prepareStatus(array $data = []): void
    {
        if (! $this->shouldTrack) {
            return;
        }

        /** @var class-string<JobStatus> $entityClass */
        $entityClass = app(config('job-status.model'));

        // Determine if we need to synchronize status creation for uniqueness
        $applyUniqueLock = method_exists($this, 'uniqueId') || $this instanceof \Illuminate\Contracts\Queue\ShouldBeUnique;

        $lock = null;
        if ($applyUniqueLock) {
            $uniqueId = method_exists($this, 'uniqueId') ? $this->uniqueId() : '';

            $lockKey = 'laravel_unique_job:' . static::class . ':' . serialize($uniqueId);

            $cache = method_exists($this, 'uniqueVia')
                ? $this->uniqueVia()
                : Cache::getFacadeRoot();

            $lock = $cache->lock($lockKey, 10); // Short-lived lock for synchronization only
        }

        // Use block/acquire pattern – if lock cannot be acquired, skip creation entirely (prevents orphans)
        $acquired = $lock ? $lock->get() : true;

        if (! $acquired) {
            $this->shouldTrack = false;
            return;
        }

        // Execute creation inside the lock scope (auto-releases on exit or expiration)
        try {
            // Capture unique_id from job's uniqueId() method if it exists
            if (method_exists($this, 'uniqueId')) {
                try {
                    $uniqueId = $this->uniqueId();
                    if ($uniqueId !== null && ! isset($data['unique_id'])) {
                        $data['unique_id'] = $uniqueId;
                    }
                } catch (\Throwable $e) {
                    // uniqueId() may throw - gracefully handle
                }
            }

            // Capture batch information if job is part of a batch
            if (method_exists($this, 'batch') && ! isset($data['batch_id'])) {
                try {
                    $batch = $this->batch();
                    if ($batch !== null) {
                        $data['batch_id']   = $batch->id;
                        $data['total_jobs'] = $batch->totalJobs;
                        // Note: processedJobs() may have race conditions in concurrent processing
                        // This is a best-effort counter and may not be perfectly sequential
                        $data['current_step'] = $batch->processedJobs() + 1;
                    }
                } catch (\Throwable $e) {
                    // Batch not available yet, skip
                }
            }

            $data = array_merge(['type' => $this->getDisplayName()], $data);

            /** @var JobStatus $status */
            $status = $entityClass::on(config('job-status.database_connection'))
                ->create($data);

            $this->statusId = $status->getKey();
        } catch (\Throwable $e) {
            // If status creation fails for any reason, disable tracking to prevent further errors
            $this->shouldTrack = false;
        } finally {
            // Ensure lock is released if we acquired it (block get releases automatically, but safety)
            if ($lock && $acquired) {
                $lock->release();
            }
        }
    }

    /**
     * Prepare status but start as 'executing' (for delayed creation in handle()).
     */
    public function prepareForExecution(array $extra = [])
    {
        $this->prepareStatus($extra);

        if ($this->jobStatus) {
            $this->jobStatus->update([
                'status'     => 'executing',
                'started_at' => now(),
            ]);
            $this->jobStatus->refresh();
        }
    }

    /**
     * Get the display name for this job.
     * Uses displayName() method if available, otherwise returns the class name.
     *
     * @return string The job display name
     */
    protected function getDisplayName(): string
    {
        return method_exists($this, 'displayName') ? $this->displayName(): static::class;
    }

    /**
     * Get the ID of the JobStatus record for this job.
     *
     * @return int|string|null The job status ID, or null if not tracking
     */
    public function getJobStatusId(): int | string | null
    {
        return $this->statusId;
    }
}
