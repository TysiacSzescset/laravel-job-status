<?php

namespace Yannelli\TrackJobStatus\Tests\Feature;

use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Support\Facades\Artisan;
use Yannelli\TrackJobStatus\EventManagers\DefaultEventManager;
use Yannelli\TrackJobStatus\EventManagers\LegacyEventManager;
use Yannelli\TrackJobStatus\Tests\Data\TestJob;
use Yannelli\TrackJobStatus\Tests\Data\TestJobWithException;

class EventManagerTest extends TestCase
{
    /**
     * @dataProvider managerProvider
     */
    public function testManager(string $class, string $status)
    {
        $this->expectException(\Exception::class);

        config()->set('job-status.event_manager', $class);

        /** @var TestJob $job */
        $job = new TestJobWithException();

        app(Dispatcher::class)->dispatch($job);

        Artisan::call('queue:work', [
            '--once' => 1,
        ]);

        $this->assertDatabaseHas('job_statuses', [
            'id' => $job->getJobStatusId(),
            'status' => $status,
        ]);
    }

    public static function managerProvider(): array
    {
        return [
            [DefaultEventManager::class, 'failed'],
            [LegacyEventManager::class, 'retrying'],
        ];
    }
}
