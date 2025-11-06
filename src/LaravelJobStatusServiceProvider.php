<?php

declare(strict_types=1);

namespace Yannelli\TrackJobStatus;

use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\Events\JobQueueing;
use Illuminate\Queue\Events\JobQueued;
use Illuminate\Queue\Events\JobReleasedAfterException;
use Illuminate\Queue\Events\JobRetryRequested;
use Illuminate\Queue\Events\JobTimedOut;
use Illuminate\Queue\QueueManager;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Yannelli\TrackJobStatus\EventManagers\EventManager;

class LaravelJobStatusServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->mergeConfigFrom(__DIR__.'/../config/job-status.php', 'job-status');

        $this->publishes([
            __DIR__.'/../database/migrations/' => database_path('migrations'),
        ], 'migrations');

        $this->publishes([
            __DIR__.'/../config/' => config_path(),
        ], 'config');

        $this->bootListeners();
    }

    private function bootListeners(): void
    {
        /** @var EventManager $eventManager */
        $eventManager = app(config('job-status.event_manager'));

        $queueManager = app(QueueManager::class);

        // Job lifecycle events
        Event::listen(JobQueueing::class, function (JobQueueing $event) use ($eventManager): void {
            $eventManager->queueing($event);
        });

        Event::listen(JobQueued::class, function (JobQueued $event) use ($eventManager): void {
            $eventManager->queued($event);
        });

        $queueManager->before(function (JobProcessing $event) use ($eventManager): void {
            $eventManager->before($event);
        });

        $queueManager->after(function (JobProcessed $event) use ($eventManager): void {
            $eventManager->after($event);
        });

        $queueManager->failing(function (JobFailed $event) use ($eventManager): void {
            $eventManager->failing($event);
        });

        $queueManager->exceptionOccurred(function (JobExceptionOccurred $event) use ($eventManager): void {
            $eventManager->exceptionOccurred($event);
        });

        // Retry and timeout events
        Event::listen(JobRetryRequested::class, function (JobRetryRequested $event) use ($eventManager): void {
            $eventManager->retryRequested($event);
        });

        Event::listen(JobReleasedAfterException::class, function (JobReleasedAfterException $event) use ($eventManager): void {
            $eventManager->releasedAfterException($event);
        });

        Event::listen(JobTimedOut::class, function (JobTimedOut $event) use ($eventManager): void {
            $eventManager->timedOut($event);
        });
    }
}
