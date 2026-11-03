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

    /**
     * Members of a VAT group (§ 4b / § 4c), paged. Take the IČ DPH from
     * `vat_registration.ic_dph` of any member (`withTax()`). Members the register
     * lists without an IČO are counted in `meta.members_without_ico`, never named.
     * Requires a plan with tax data (Starter+).
     *
     * @param string $icDph IČ DPH of the group, e.g. `SK2020999001`
     * @param string|null $search Member name fragment, or IČO prefix when numeric
     * @return array<string, mixed> `data` (members: ico, name, type, registration dates) and
     *     `meta` (ic_dph, total_members, members_without_ico, limit, offset, has_more)
     * @throws \FirmApi\Exceptions\ApiException
     */
    public function groupMembers(string $icDph, ?string $search = null, int $limit = 25, int $offset = 0): array
    {
        $query = ['limit' => max(1, min($limit, 100)), 'offset' => max(0, $offset)];
        if ($search !== null && $search !== '') {
            $query['q'] = $search;
        }

        return $this->client->get('/vat-groups/' . rawurlencode($icDph) . '/members', $query);
    }
}
