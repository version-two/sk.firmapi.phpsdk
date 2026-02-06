<?php

declare(strict_types=1);

namespace FirmApi\Resources;

use FirmApi\Client;
use FirmApi\Exceptions\ApiException;

class Search
{
    private Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Autocomplete search for companies.
     * Returns Select2-compatible format.
     *
     * @param string $query Search query (min 2 characters)
     * @param int $limit Maximum results (default: 10, max: 20)
     * @return array<string, mixed>
     * @throws ApiException
     */
    public function autocomplete(string $query, int $limit = 10): array
    {
        return $this->client->get('/search/autocomplete', [
            'q' => $query,
            'limit' => min($limit, 20),
        ]);
    }

    /**
     * Search companies by name.
     *
     * @param string $name Company name
     * @param bool $exact Exact match (default: false)
     * @param int $limit Maximum results
     * @param int $offset Pagination offset
     * @return array<string, mixed>
     * @throws ApiException
     */
    public function byName(
        string $name,
        bool $exact = false,
        int $limit = 10,
        int $offset = 0
    ): array {
        return $this->client->get('/search/name', [
            'q' => $name,
            'exact' => $exact ? 1 : 0,
            'limit' => $limit,
            'offset' => $offset,
        ]);
    }

    /**
     * Search companies by partial IČO.
     *
     * @param string $ico Partial IČO
     * @param int $limit Maximum results
     * @param int $offset Pagination offset
     * @return array<string, mixed>
     * @throws ApiException
     */
    public function byIco(string $ico, int $limit = 10, int $offset = 0): array
    {
        return $this->client->get('/search/ico', [
            'q' => $ico,
            'limit' => $limit,
            'offset' => $offset,
        ]);
    }

    /**
     * Advanced multi-field search.
     *
     * @param array<string, mixed> $params Search parameters (name, city, legal_form, etc.)
     * @return array<string, mixed>
     * @throws ApiException
     */
    public function advanced(array $params): array
    {
        return $this->client->get('/search/advanced', $params);
    }
}
