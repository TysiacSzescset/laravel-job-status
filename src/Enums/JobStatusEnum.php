<?php

declare(strict_types=1);

namespace Yannelli\TrackJobStatus\Enums;

/**
 * Job status enum representing the current state of a job.
 *
 * @package Yannelli\TrackJobStatus\Enums
 */
enum JobStatusEnum: string
{
    /** Job is queued and waiting to be processed */
    case QUEUED = 'queued';

    /** Job is currently executing */
    case EXECUTING = 'executing';

    /** Job completed successfully */
    case FINISHED = 'finished';

    /** Job failed permanently (all retries exhausted) */
    case FAILED = 'failed';

    /** Job failed but will be retried */
    case RETRYING = 'retrying';

    /**
     * Check if the job has ended (either finished or failed).
     *
     * @return bool True if job is finished or failed
     */
    public function hasEnded(): bool
    {
        return in_array($this, [self::FAILED, self::FINISHED], true);
    }

    /**
     * Check if the job finished successfully.
     *
     * @return bool True if job is finished
     */
    public function isFinished(): bool
    {
        return $this === self::FINISHED;
    }

    /**
     * Check if the job failed permanently.
     *
     * @return bool True if job is failed
     */
    public function isFailed(): bool
    {
        return $this === self::FAILED;
    }

    /**
     * Check if the job is currently executing.
     *
     * @return bool True if job is executing
     */
    public function isExecuting(): bool
    {
        return $this === self::EXECUTING;
    }

    /**
     * Check if the job is queued and waiting.
     *
     * @return bool True if job is queued
     */
    public function isQueued(): bool
    {
        return $this === self::QUEUED;
    }

    /**
     * Check if the job is retrying after a failure.
     *
     * @return bool True if job is retrying
     */
    public function isRetrying(): bool
    {
        return $this === self::RETRYING;
    }

    /**
     * Get all status values as an array of strings.
     *
     * @return array<int, string> Array of status values
     */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }
}
