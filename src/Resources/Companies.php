<?php

declare(strict_types=1);

namespace FirmApi\Resources;

use FirmApi\Client;
use FirmApi\Exceptions\ApiException;

class Companies
{
    private Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Get company by IČO (registration number).
     *
     * @param string $ico 8-digit company registration number
     * @return array<string, mixed>
     * @throws ApiException
     */
    public function byIco(string $ico): array
    {
        return $this->client->get("/company/ico/{$ico}");
    }

    /**
     * Get company by ORSR ID.
     *
     * @param string $orsrId ORSR internal ID
     * @return array<string, mixed>
     * @throws ApiException
     */
    public function byOrsrId(string $orsrId): array
    {
        return $this->client->get("/company/id/{$orsrId}");
    }

    /**
     * Get company by internal database ID.
     *
     * @param int $id Internal database ID
     * @return array<string, mixed>
     * @throws ApiException
     */
    public function byId(int $id): array
    {
        return $this->client->get("/company/{$id}");
    }
}
