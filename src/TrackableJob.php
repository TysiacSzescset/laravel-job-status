<?php

declare(strict_types=1);

namespace Yannelli\TrackJobStatus;

/**
 * Interface for jobs that can be tracked.
 *
 * Jobs implementing this interface can have their status tracked by the package.
 * Typically, you would use the Trackable trait instead of implementing this interface directly.
 *
 * @package Yannelli\TrackJobStatus
 * @see Trackable
 */
interface TrackableJob
{
    /**
     * Get the ID of the JobStatus record for this job.
     *
     * @return int|string|null The job status ID, or null if not tracking
     */
    public function getJobStatusId(): int|string|null;
}
