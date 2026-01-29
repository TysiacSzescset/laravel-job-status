<?php

declare(strict_types=1);

namespace Yannelli\TrackJobStatus\Tests\Data;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Yannelli\TrackJobStatus\Trackable;
use Yannelli\TrackJobStatus\TrackableJob;

class TestJobWithoutTracking implements ShouldQueue, TrackableJob
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use Trackable;

    public function __construct()
    {
        $this->shouldTrack = false;
        $this->prepareStatus();
    }

    public function handle(): void
    {
    }
}
