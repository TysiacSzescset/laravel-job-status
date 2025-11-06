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
use Yannelli\TrackJobStatus\Enums\JobStatusEnum;

class DefaultEventManager extends EventManager
{
    public function queueing(JobQueueing $event): void
    {
        // Job is being queued - prepareStatus() in constructor handles initial creation
        // No additional action needed here
    }

    public function queued(JobQueued $event): void
    {
        // Job has been queued - already tracked in prepareStatus()
        // Could update job_id if needed, but typically set when processing starts
    }

    public function before(JobProcessing $event): void
    {
        $this->getUpdater()->update($event, [
            'status' => JobStatusEnum::EXECUTING->value,
            'job_id' => $event->job->getJobId(),
            'queue' => $event->job->getQueue(),
            'started_at' => now(),
        ]);
    }

    public function after(JobProcessed $event): void
    {
        if (!$event->job->hasFailed()) {
            $this->getUpdater()->update($event, [
                'status' => JobStatusEnum::FINISHED->value,
                'finished_at' => now(),
            ]);
        }
    }

    public function failing(JobFailed $event): void
    {
        $status = $event->job->attempts() >= $event->job->maxTries()
            ? JobStatusEnum::FAILED->value
            : JobStatusEnum::RETRYING->value;

        $this->getUpdater()->update($event, [
            'status' => $status,
            'finished_at' => now(),
        ]);
    }

    public function exceptionOccurred(JobExceptionOccurred $event): void
    {
        $status = $event->job->attempts() >= $event->job->maxTries()
            ? JobStatusEnum::FAILED->value
            : JobStatusEnum::RETRYING->value;

        $this->getUpdater()->update($event, [
            'status' => $status,
            'finished_at' => now(),
        ]);
    }

    public function retryRequested(JobRetryRequested $event): void
    {
        $this->getUpdater()->update($event, [
            'status' => JobStatusEnum::RETRYING->value,
        ]);
    }

    public function releasedAfterException(JobReleasedAfterException $event): void
    {
        $this->getUpdater()->update($event, [
            'status' => JobStatusEnum::RETRYING->value,
        ]);
    }

    public function timedOut(JobTimedOut $event): void
    {
        $this->getUpdater()->update($event, [
            'status' => JobStatusEnum::FAILED->value,
            'finished_at' => now(),
        ]);
    }
}
