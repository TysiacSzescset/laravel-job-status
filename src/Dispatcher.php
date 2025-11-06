<?php

declare(strict_types=1);

namespace Yannelli\TrackJobStatus;

use Closure;
use Illuminate\Contracts\Container\Container;

/**
 * Custom job dispatcher that updates job status after dispatching.
 *
 * This dispatcher extends Laravel's default dispatcher to automatically
 * update the job_id in the job status record after a job is dispatched.
 *
 * @package Yannelli\TrackJobStatus
 */
readonly class Dispatcher extends \Illuminate\Bus\Dispatcher
{
    /**
     * Create a new dispatcher instance.
     *
     * @param Container $container The container instance
     * @param Closure $queueResolver The queue resolver
     * @param JobStatusUpdater $updater The job status updater
     */
    public function __construct(
        Container                $container,
        Closure                  $queueResolver,
        private JobStatusUpdater $updater
    ) {
        parent::__construct($container, $queueResolver);
    }

    /**
     * Dispatch a command and update the job status with the job ID.
     *
     * @param mixed $command The command/job to dispatch
     * @return mixed The result from the parent dispatcher (typically the job ID)
     */
    public function dispatch(mixed $command): mixed
    {
        $result = parent::dispatch($command);

        $this->updater->update($command, [
            'job_id' => $result,
        ]);

        return $result;
    }
}
