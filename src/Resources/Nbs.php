<?php

declare(strict_types=1);

namespace FirmApi\Resources;

use FirmApi\Client;
use FirmApi\Exceptions\ApiException;

/**
 * NBS register of financial market entities: licences, agent → institution
 * relations and their history. Requires the `nbs` feature on your plan.
 */
class Nbs
{
    private Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * List register entities (Slovak and foreign, with or without an IČO).
     *
     * @param array{
     *     q?: string, category?: string, sector?: string, parent_ico?: string, parent_id?: string,
     *     country?: string, natural_person?: bool, status?: 'current'|'ended'|'all'
     * } $filters
     * @return array<string, mixed> `data` (entity summaries) and `meta` (limit, offset, has_more)
     * @throws ApiException
     */
    public function entities(array $filters = [], int $limit = 25, int $offset = 0): array
    {
        return $this->client->get('/nbs/entities', $this->query($filters) + [
            'limit' => min($limit, 100),
            'offset' => $offset,
        ]);
    }

    /**
     * One entity with its full licence history.
     *
     * @param string $entityId `entity_id` from any NBS response
     * @return array<string, mixed>
     * @throws ApiException
     */
    public function entity(string $entityId): array
    {
        return $this->client->get('/nbs/entities/' . rawurlencode($entityId));
    }

    /**
     * Financial agents working for an institution, each with the licences tying it there.
     *
     * @param array{q?: string, sector?: string, natural_person?: bool, status?: 'current'|'ended'|'all'} $filters
     * @return array<string, mixed> `data` (agents) and `meta` (parent, limit, offset, has_more)
     * @throws ApiException
     */
    public function agents(string $ico, array $filters = [], int $limit = 25, int $offset = 0): array
    {
        return $this->client->get('/company/ico/' . rawurlencode($ico) . '/nbs-agents', $this->query($filters) + [
            'limit' => min($limit, 100),
            'offset' => $offset,
        ]);
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<string, string>
     */
    private function query(array $filters): array
    {
        $query = [];
        foreach ($filters as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            $query[$key] = is_bool($value) ? ($value ? '1' : '0') : (string) $value;
        }

        return $query;
    }
}
