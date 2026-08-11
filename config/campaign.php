<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Email Campaign Jitter Percentage
    |--------------------------------------------------------------------------
    |
    | Defines the percentage of random jitter applied to email pacing schedules.
    | Default is 20 (representing +/- 20% jitter variance).
    |
    */

    'jitter_percent' => ((float) env('EMAIL_CAMPAIGN_JITTER_PERCENT', 20)) / 100.0,

    /*
    |--------------------------------------------------------------------------
    | Processing Timeout (Minutes)
    |--------------------------------------------------------------------------
    |
    | Number of minutes after which a lead in 'processing' status is considered
    | stale and eligible for crash recovery.
    |
    */

    'processing_timeout_minutes' => (int) env('CAMPAIGN_PROCESSING_TIMEOUT_MINUTES', 10),

    /*
    |--------------------------------------------------------------------------
    | Scheduler Batch Size
    |--------------------------------------------------------------------------
    |
    | Maximum number of due leads dispatched in a single scheduler cycle.
    | Prevents memory overflow and unbounded scheduler runs.
    |
    */

    'scheduler_batch_size' => (int) env('CAMPAIGN_SCHEDULER_BATCH_SIZE', 500),

];
