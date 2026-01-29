<?php

declare(strict_types=1);

namespace Yannelli\TrackJobStatus\Tests\Data;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Connection;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Testing\Concerns\InteractsWithDatabase;
use Illuminate\Queue\InteractsWithQueue;
use Yannelli\TrackJobStatus\Tests\Feature\TestCase;
use Yannelli\TrackJobStatus\Trackable;
use Yannelli\TrackJobStatus\TrackableJob;

class TestJobWithDatabase implements ShouldQueue, TrackableJob
{
    use Dispatchable;
    use InteractsWithDatabase;
    use InteractsWithQueue;
    use Queueable;
    use Trackable;

    public function __construct(protected array $data)
    {
        $this->prepareStatus();
    }

    public function handle(): void
    {
        // Verify the job status record exists with the expected data
        $result = $this->getConnection()
            ->table('job_statuses')
            ->where('id', $this->getJobStatusId())
            ->where($this->data)
            ->exists();

        TestCase::assertTrue($result, 'Job status record not found with expected data');
    }

    protected function getConnection(): Connection
    {
        $database = app('db');

        return $database->connection($database->getDefaultConnection());
    }
}
