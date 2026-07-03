<?php

declare(strict_types=1);

namespace FirmApi\Tests\Resources;

use FirmApi\Tests\TestCase;

class CompaniesTest extends TestCase
{
    public function test_by_ico_sends_get_to_correct_endpoint(): void
    {
        $client = $this->createClient([
            $this->jsonResponse(['data' => ['ico' => '51636549', 'name' => 'Version Two s. r. o.']]),
        ]);

        $result = $client->companies->byIco('51636549')->get();

        $this->assertStringContainsString('/company/ico/51636549', $this->lastRequestUri());
        $this->assertSame('GET', $this->lastRequestMethod());
        $this->assertSame('51636549', $result['data']['ico']);
        $this->assertSame('Version Two s. r. o.', $result['data']['name']);
    }

    public function test_by_orsr_id_sends_get_to_correct_endpoint(): void
    {
        $client = $this->createClient([
            $this->jsonResponse(['data' => ['orsr_id' => '427482']]),
        ]);

        $client->companies->byOrsrId('427482')->get();

        $this->assertStringContainsString('/company/id/427482', $this->lastRequestUri());
        $this->assertSame('GET', $this->lastRequestMethod());
    }

    public function test_stale_response_is_returned_immediately_by_default(): void
    {
        // Fast-by-default: a stale flag must NOT trigger blocking re-polls.
        $client = $this->createClient([
            $this->jsonResponse([
                'data' => ['ico' => '51636549'],
                'meta' => ['stale' => true, 'retry_at' => '2999-01-01T00:00:00Z'],
            ]),
        ]);

        $result = $client->companies->byIco('51636549')->get();

        $this->assertTrue($result['meta']['stale']);
        $this->assertCount(1, $this->requestHistory, 'default get() must not re-poll on stale');
    }

    public function test_fresh_repolls_until_non_stale(): void
    {
        // retry_at in the past keeps the between-poll wait to the 1s floor.
        $client = $this->createClient([
            $this->jsonResponse([
                'data' => ['ico' => '51636549'],
                'meta' => ['stale' => true, 'retry_at' => '2000-01-01T00:00:00Z'],
            ]),
            $this->jsonResponse([
                'data' => ['ico' => '51636549', 'name' => 'Version Two s. r. o.'],
                'meta' => ['stale' => false],
            ]),
        ]);

        $result = $client->companies->byIco('51636549')->fresh()->get();

        $this->assertFalse($result['meta']['stale']);
        $this->assertSame('Version Two s. r. o.', $result['data']['name']);
        $this->assertCount(2, $this->requestHistory, 'fresh() must re-poll until non-stale');
    }

    public function test_with_adds_raw_scopes(): void
    {
        $client = $this->createClient([
            $this->jsonResponse(['data' => ['ico' => '51636549']]),
        ]);

        $client->companies->byIco('51636549')->with('tax', 'sanctions')->get();

        $this->assertStringContainsString('scope=tax,sanctions', urldecode($this->lastRequestUri()));
    }

    public function test_with_crp_projects_adds_scope_to_request(): void
    {
        $client = $this->createClient([
            $this->jsonResponse(['data' => ['ico' => '51636549', 'crp_projects' => ['count' => 0, 'latest' => []]]]),
        ]);

        $client->companies->byIco('51636549')->withCrpProjects()->get();

        $this->assertStringContainsString('scope=crp_projects', $this->lastRequestUri());
        $this->assertSame('GET', $this->lastRequestMethod());
    }

    public function test_by_ico_returns_full_company_data(): void
    {
        $companyData = [
            'data' => [
                'id' => 1,
                'ico' => '51636549',
                'name' => 'Version Two s. r. o.',
                'address' => 'Bratislava',
                'legal_form' => 'Spoločnosť s ručením obmedzeným',
                'shareholders' => [
                    ['name' => 'John Doe', 'share_amount' => '5000 EUR'],
                ],
                'statutory_body' => [
                    ['name' => 'John Doe', 'role' => 'konateľ'],
                ],
                'tax' => [
                    'dic' => '2120776680',
                    'ic_dph' => 'SK2120776680',
                ],
            ],
            'meta' => [
                'synced_at' => '2026-02-05T10:00:00Z',
                'source' => 'database',
            ],
        ];

        $client = $this->createClient([
            $this->jsonResponse($companyData),
        ]);

        $result = $client->companies->byIco('51636549')->get();

        // Backward-compatible array access still yields the raw response.
        $this->assertSame($companyData, $result->toArray());
        $this->assertSame('2120776680', $result['data']['tax']['dic']);
        $this->assertCount(1, $result['data']['shareholders']);
    }

    public function test_get_returns_typed_company_object(): void
    {
        $client = $this->createClient([
            $this->jsonResponse([
                'data' => [
                    'ico' => '51636549',
                    'name' => 'Version Two s. r. o.',
                    'legal_form' => 'Spoločnosť s ručením obmedzeným',
                    'address' => [
                        'street' => 'Hlavná 1',
                        'city' => 'Bratislava',
                        'postal_code' => '81101',
                        'country' => 'SK',
                    ],
                    'orsr_id' => '427482',
                    'shareholders' => [
                        ['name' => 'Ján Kováč', 'share_amount' => '5000', 'is_company' => false],
                    ],
                    'statutory_body' => [
                        ['name' => 'Ján Kováč', 'role' => 'konateľ'],
                    ],
                    'business_activities' => [
                        ['activity' => 'Kúpa tovaru', 'since' => '2018-01-01'],
                    ],
                    'tax' => ['dic' => '2120776680'],
                ],
                'meta' => ['stale' => false, 'source' => 'database'],
            ]),
        ]);

        $company = $client->companies->byIco('51636549')->get();

        // Typed core accessors
        $this->assertSame('51636549', $company->ico);
        $this->assertSame('Version Two s. r. o.', $company->name);
        $this->assertSame('Bratislava', $company->address->city);
        $this->assertSame('427482', $company->orsrId);

        // Collections of typed nested objects
        $this->assertCount(1, $company->shareholders);
        $this->assertSame('Ján Kováč', $company->shareholders->first()->name);
        $this->assertFalse($company->shareholders->first()->isCompany);
        $this->assertSame('konateľ', $company->statutoryBody->first()->role);
        $this->assertSame('Kúpa tovaru', $company->businessActivities->first()->activity);

        // Meta + generic enrichment access
        $this->assertFalse($company->meta->stale);
        $this->assertSame('database', $company->meta->source);
        $this->assertTrue($company->has('tax'));
        $this->assertSame('2120776680', $company->enrichment('tax')['dic']);
        $this->assertNull($company->enrichment('sanctions'));
    }
}
