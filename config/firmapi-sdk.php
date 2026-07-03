<?php

return [

    /*
    |--------------------------------------------------------------------------
    | FirmAPI Key
    |--------------------------------------------------------------------------
    |
    | Your FirmAPI API key. Get one at https://firmapi.sk/en/dashboard/api-keys
    |
    */

    'api_key' => env('FIRMAPI_API_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | Base URL
    |--------------------------------------------------------------------------
    |
    | The FirmAPI base URL. You should not need to change this unless you
    | are using a self-hosted or staging instance.
    |
    */

    'base_url' => env('FIRMAPI_BASE_URL', 'https://api.firmapi.sk/v1'),

    /*
    |--------------------------------------------------------------------------
    | Timeout
    |--------------------------------------------------------------------------
    |
    | HTTP request timeout in seconds.
    |
    */

    'timeout' => (int) env('FIRMAPI_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Wait for fresh data
    |--------------------------------------------------------------------------
    |
    | When true, company lookups block and re-poll the API until it reports
    | non-stale data. This is OFF by default: the API returns valid,
    | precomputed data immediately and only flags `meta.stale` to indicate a
    | background refresh is queued, so waiting adds latency and extra billed
    | requests for a marginal freshness gain. Leave false and opt in per call
    | with ->fresh() when you truly need post-refresh values.
    |
    */

    'wait_for_fresh_data' => (bool) env('FIRMAPI_WAIT_FOR_FRESH_DATA', false),

    /*
    |--------------------------------------------------------------------------
    | Max stale re-polls
    |--------------------------------------------------------------------------
    |
    | Maximum number of re-polls performed when waiting for fresh data (either
    | globally via wait_for_fresh_data or per-call via ->fresh()).
    |
    */

    'max_stale_retries' => (int) env('FIRMAPI_MAX_STALE_RETRIES', 3),

    /*
    |--------------------------------------------------------------------------
    | Transient retry attempts
    |--------------------------------------------------------------------------
    |
    | Automatic retries for transient failures (HTTP 5xx and network errors)
    | with exponential backoff. HTTP 429 is never silently retried; it is
    | surfaced as a RateLimitException so you control pacing.
    |
    */

    'max_retries' => (int) env('FIRMAPI_MAX_RETRIES', 2),

];
