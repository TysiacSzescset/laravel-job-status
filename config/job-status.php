<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Job Status Model
    |--------------------------------------------------------------------------
    |
    | The model class to use for job status records. You can extend the
    | default model and specify your custom model here.
    |
    */
    'model' => \Yannelli\TrackJobStatus\JobStatus::class,

    /*
    |--------------------------------------------------------------------------
    | Event Manager
    |--------------------------------------------------------------------------
    |
    | The event manager class to handle job queue events. You can create
    | a custom event manager by extending the EventManager class.
    |
    */
    'event_manager' => \Yannelli\TrackJobStatus\EventManagers\DefaultEventManager::class,

    /*
    |--------------------------------------------------------------------------
    | Database Connection
    |--------------------------------------------------------------------------
    |
    | Specify a dedicated database connection for job status tracking.
    | This is useful when you want status updates to be saved immediately
    | even within your application's database transactions.
    |
    | Set to null to use the default connection.
    |
    */
    'database_connection' => null,

    /*
    |--------------------------------------------------------------------------
    | Track Input
    |--------------------------------------------------------------------------
    |
    | Enable or disable tracking of job input data. When disabled, calls to
    | $this->setInput() will be ignored and no input data will be stored.
    |
    */
    'track_input' => true,

    /*
    |--------------------------------------------------------------------------
    | Track Output
    |--------------------------------------------------------------------------
    |
    | Enable or disable tracking of job output data. When disabled, calls to
    | $this->setOutput() will be ignored and no output data will be stored.
    |
    */
    'track_output' => true,

    /*
    |--------------------------------------------------------------------------
    | Track History
    |--------------------------------------------------------------------------
    |
    | Enable or disable automatic logging of status changes to the history
    | table. When enabled, every status or status_message change will be
    | logged to the job_status_histories table.
    |
    */
    'track_history' => true,
];
