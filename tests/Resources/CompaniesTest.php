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

        $result = $client->companies->byIco('51636549');

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

        $client->companies->byOrsrId('427482');

        $this->assertStringContainsString('/company/id/427482', $this->lastRequestUri());
        $this->assertSame('GET', $this->lastRequestMethod());
    }

    public function test_by_id_sends_get_to_correct_endpoint(): void
    {
        $client = $this->createClient([
            $this->jsonResponse(['data' => ['id' => 12345]]),
        ]);

        $client->companies->byId(12345);

        $this->assertStringContainsString('/company/12345', $this->lastRequestUri());
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

        $result = $client->companies->byIco('51636549');

        $this->assertSame($companyData, $result);
        $this->assertSame('2120776680', $result['data']['tax']['dic']);
        $this->assertCount(1, $result['data']['shareholders']);
    }
}
