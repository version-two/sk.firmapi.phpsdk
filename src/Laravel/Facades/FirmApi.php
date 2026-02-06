<?php

declare(strict_types=1);

namespace FirmApi\Laravel\Facades;

use FirmApi\Client;
use Illuminate\Support\Facades\Facade;

/**
 * @method static array get(string $endpoint, array $query = [])
 * @method static array post(string $endpoint, array $data = [])
 * @method static string getApiKey()
 * @method static string getBaseUrl()
 *
 * @see Client
 */
class FirmApi extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return Client::class;
    }
}
