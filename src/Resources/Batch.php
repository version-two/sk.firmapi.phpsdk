<?php

declare(strict_types=1);

namespace FirmApi\Resources;

use FirmApi\Client;
use FirmApi\Exceptions\ApiException;

/**
 * Asynchronous batch lookups. Requires the Professional plan or higher; the batch size
 * limit depends on the plan. A batch costs 1 credit per 2 items, rounded up, and is
 * processed within an hour. Status and results are free and kept for 7 days.
 */
class Batch
{
    private Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Submit a batch of IČOs. Returns the batch (`data.id`, `data.status`); poll status(), then results().
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
     * Submit a batch of company names (up to 5 matches per name).
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
     * Progress of a batch: `data.status` is pending, processing, completed or failed.
     *
     * @param string $batchId Batch ID returned by byIco() or byNames()
     * @return array<string, mixed>
     * @throws ApiException
     */
    public function status(string $batchId): array
    {
        return $this->client->get('/batch/' . rawurlencode($batchId) . '/status');
    }

    /**
     * Results of a completed batch, keyed by IČO (byIco) or by the name query (byNames).
     * Throws ApiException with status code 409 while the batch is not finished or after it failed.
     *
     * @param string $batchId Batch ID returned by byIco() or byNames()
     * @return array<string, mixed>
     * @throws ApiException
     */
    public function results(string $batchId): array
    {
        return $this->client->get('/batch/' . rawurlencode($batchId) . '/results');
    }
}
