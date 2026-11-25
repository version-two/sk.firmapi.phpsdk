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
     * Get current period usage. `data.billing_mode` is `credits` (`data.credits.balance`,
     * `used_this_period`, `monthly_grant`, `rollover`) or `legacy` (`data.usage.this_month`).
     *
     * @return array<string, mixed>
     * @throws ApiException
     */
    public function usage(): array
    {
        return $this->client->get('/account/usage');
    }

    /**
     * Get the remaining quota. `data.billing_mode` is `credits` (`data.credits_remaining`,
     * `credits_limit`, `next_grant`) or `legacy` (`data.monthly.remaining`, `limit`, `used`).
     *
     * @return array<string, mixed>
     * @throws ApiException
     */
    public function quota(): array
    {
        return $this->client->get('/account/quota');
    }

    /**
     * Get daily usage history (`date`, `requests`, `credits`). The range is determined by your
     * subscription tier (`meta.history_months`).
     *
     * @return array<string, mixed>
     * @throws ApiException
     */
    public function history(): array
    {
        return $this->client->get('/account/history');
    }
}
