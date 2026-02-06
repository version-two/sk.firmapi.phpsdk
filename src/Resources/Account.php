<?php

declare(strict_types=1);

namespace FirmApi\Resources;

use FirmApi\Client;
use FirmApi\Exceptions\ApiException;

class Account
{
    private Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Get current period usage statistics.
     *
     * @return array<string, mixed>
     * @throws ApiException
     */
    public function usage(): array
    {
        return $this->client->get('/account/usage');
    }

    /**
     * Get remaining quota for current period.
     *
     * @return array<string, mixed>
     * @throws ApiException
     */
    public function quota(): array
    {
        return $this->client->get('/account/quota');
    }

    /**
     * Get usage history.
     * History range is determined by your subscription tier.
     *
     * @return array<string, mixed>
     * @throws ApiException
     */
    public function history(): array
    {
        return $this->client->get('/account/history');
    }
}
