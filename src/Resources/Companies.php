<?php

declare(strict_types=1);

namespace FirmApi\Resources;

use FirmApi\Client;

class Companies
{
    private Client $client;
    private bool $waitForFreshData;
    private int $maxStaleRetries;

    public function __construct(Client $client, bool $waitForFreshData = false, int $maxStaleRetries = 3)
    {
        $this->client = $client;
        $this->waitForFreshData = $waitForFreshData;
        $this->maxStaleRetries = $maxStaleRetries;
    }

    /**
     * Look up a company by IČO (registration number).
     *
     * Returns a CompanyQuery that can be enriched with fluent methods:
     *   $client->company->byIco('51636549')->get();                    // base data
     *   $client->company->byIco('51636549')->withTax()->get();         // with tax info
     *   $client->company->byIco('51636549')->withAll()->get();         // everything
     *
     * @param string $ico 8-digit company registration number
     */
    public function byIco(string $ico): CompanyQuery
    {
        return new CompanyQuery(
            $this->client,
            "/company/ico/{$ico}",
            $this->waitForFreshData,
            $this->maxStaleRetries,
        );
    }

    /**
     * Look up a company by ORSR ID.
     *
     * @param string $orsrId ORSR internal ID
     */
    public function byOrsrId(string $orsrId): CompanyQuery
    {
        return new CompanyQuery(
            $this->client,
            "/company/id/{$orsrId}",
            $this->waitForFreshData,
            $this->maxStaleRetries,
        );
    }
}
