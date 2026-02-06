<?php

declare(strict_types=1);

namespace FirmApi\Resources;

use FirmApi\Client;
use FirmApi\Exceptions\ApiException;

class Batch
{
    private Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Batch lookup companies by IČO.
     * Requires Starter plan or higher.
     *
     * @param array<string> $icos Array of 8-digit IČO numbers
     * @return array<string, mixed>
     * @throws ApiException
     */
    public function byIco(array $icos): array
    {
        return $this->client->post('/batch/ico', [
            'icos' => $icos,
        ]);
    }

    /**
     * Batch lookup companies by name.
     * Requires Starter plan or higher.
     *
     * @param array<string> $names Array of company names
     * @return array<string, mixed>
     * @throws ApiException
     */
    public function byNames(array $names): array
    {
        return $this->client->post('/batch/names', [
            'names' => $names,
        ]);
    }

    /**
     * Get batch job status.
     *
     * @param string $batchId Batch job ID
     * @return array<string, mixed>
     * @throws ApiException
     */
    public function status(string $batchId): array
    {
        return $this->client->get("/batch/{$batchId}/status");
    }

    /**
     * Get batch job results.
     *
     * @param string $batchId Batch job ID
     * @return array<string, mixed>
     * @throws ApiException
     */
    public function results(string $batchId): array
    {
        return $this->client->get("/batch/{$batchId}/results");
    }
}
