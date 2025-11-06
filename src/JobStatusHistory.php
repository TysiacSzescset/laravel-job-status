<?php

declare(strict_types=1);

namespace Yannelli\TrackJobStatus;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Yannelli\TrackJobStatus\Enums\JobStatusEnum;

/**
 * JobStatusHistory model - tracks historical changes to job statuses.
 *
 * @property int $id
 * @property int $job_status_id
 * @property JobStatusEnum $status
 * @property string|null $status_message
 * @property int $progress_now
 * @property int $progress_max
 * @property array<string, mixed>|null $metadata
 * @property \Illuminate\Support\Carbon $created_at
 *
 * @package Yannelli\TrackJobStatus
 */
class JobStatusHistory extends Model
{
    protected string $table = 'job_status_histories';

    public const UPDATED_AT = null;

    protected array $fillable = [
        'job_status_id',
        'status',
        'status_message',
        'progress_now',
        'progress_max',
        'metadata',
    ];

    /**
     * Get the casts array.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => JobStatusEnum::class,
            'metadata' => 'array',
            'progress_now' => 'integer',
            'progress_max' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Get the parent job status record.
     *
     * @return BelongsTo<JobStatus, JobStatusHistory>
     */
    public function jobStatus(): BelongsTo
    {
        return $this->belongsTo(JobStatus::class);
    }
}
