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

];
