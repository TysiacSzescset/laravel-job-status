<?php

declare(strict_types=1);

namespace Yannelli\TrackJobStatus\EventManagers;

use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\Events\JobQueueing;
use Illuminate\Queue\Events\JobQueued;
use Illuminate\Queue\Events\JobReleasedAfterException;
use Illuminate\Queue\Events\JobRetryRequested;
use Illuminate\Queue\Events\JobTimedOut;
use Yannelli\TrackJobStatus\JobStatus;
use Yannelli\TrackJobStatus\JobStatusUpdater;

abstract class EventManager
{
    abstract public function queueing(JobQueueing $event): void;

    abstract public function queued(JobQueued $event): void;

    abstract public function before(JobProcessing $event): void;

    abstract public function after(JobProcessed $event): void;

    abstract public function failing(JobFailed $event): void;

    abstract public function exceptionOccurred(JobExceptionOccurred $event): void;

    abstract public function retryRequested(JobRetryRequested $event): void;

    abstract public function releasedAfterException(JobReleasedAfterException $event): void;

    abstract public function timedOut(JobTimedOut $event): void;

    /** @var class-string<JobStatus> */
    private JobStatus $entity;

    public function __construct(private readonly JobStatusUpdater $updater)
    {
        $this->entity = app(config('job-status.model'));
    }

    protected function getUpdater(): JobStatusUpdater
    {
        return $this->updater;
    }

    /**
     * @return class-string<JobStatus>
     */
    protected function getEntity(): string
    {
        return $this->entity;
    }
}
