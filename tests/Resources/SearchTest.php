<?php

declare(strict_types=1);

namespace FirmApi\Tests\Resources;

use FirmApi\Tests\TestCase;

class SearchTest extends TestCase
{
    public function test_autocomplete_sends_correct_request(): void
    {
        $client = $this->createClient([
            $this->jsonResponse([
                'results' => [
                    ['id' => '51636549', 'text' => 'Version Two s. r. o.', 'ico' => '51636549', 'city' => 'Bratislava'],
                ],
                'pagination' => ['more' => false],
            ]),
        ]);

        $result = $client->search->autocomplete('version');

        $this->assertSame('GET', $this->lastRequestMethod());
        $this->assertStringContainsString('/search/autocomplete', $this->lastRequestUri());
        $this->assertStringContainsString('q=version', $this->lastRequestUri());
        $this->assertCount(1, $result['results']);
    }

    public function test_autocomplete_respects_limit(): void
    {
        $client = $this->createClient([
            $this->jsonResponse(['results' => [], 'pagination' => ['more' => false]]),
        ]);

        $client->search->autocomplete('test', 5);

        $this->assertStringContainsString('limit=5', $this->lastRequestUri());
    }

    public function test_autocomplete_caps_limit_at_20(): void
    {
        $client = $this->createClient([
            $this->jsonResponse(['results' => [], 'pagination' => ['more' => false]]),
        ]);

        $client->search->autocomplete('test', 50);

        $this->assertStringContainsString('limit=20', $this->lastRequestUri());
    }

    public function test_by_name_sends_correct_request(): void
    {
        $client = $this->createClient([
            $this->jsonResponse(['data' => []]),
        ]);

        $client->search->byName('Version Two');

        $this->assertStringContainsString('/search/name', $this->lastRequestUri());
        $this->assertStringContainsString('q=Version', $this->lastRequestUri());
        $this->assertStringContainsString('exact=0', $this->lastRequestUri());
    }

    public function test_by_name_exact_match(): void
    {
        $client = $this->createClient([
            $this->jsonResponse(['data' => []]),
        ]);

        $client->search->byName('Version Two s. r. o.', exact: true);

        $this->assertStringContainsString('exact=1', $this->lastRequestUri());
    }

    public function test_by_name_with_pagination(): void
    {
        $client = $this->createClient([
            $this->jsonResponse(['data' => []]),
        ]);

        $client->search->byName('test', limit: 25, offset: 50);

        $this->assertStringContainsString('limit=25', $this->lastRequestUri());
        $this->assertStringContainsString('offset=50', $this->lastRequestUri());
    }

    public function test_by_ico_sends_correct_request(): void
    {
        $client = $this->createClient([
            $this->jsonResponse(['data' => []]),
        ]);

        $client->search->byIco('5163');

        $this->assertStringContainsString('/search/ico', $this->lastRequestUri());
        $this->assertStringContainsString('q=5163', $this->lastRequestUri());
    }

    public function test_by_ico_with_pagination(): void
    {
        $client = $this->createClient([
            $this->jsonResponse(['data' => []]),
        ]);

        $client->search->byIco('5163', limit: 15, offset: 30);

        $this->assertStringContainsString('limit=15', $this->lastRequestUri());
        $this->assertStringContainsString('offset=30', $this->lastRequestUri());
    }

    public function test_advanced_sends_correct_request(): void
    {
        $client = $this->createClient([
            $this->jsonResponse(['data' => []]),
        ]);

        $client->search->advanced([
            'name' => 'version',
            'city' => 'Bratislava',
            'limit' => 20,
        ]);

        $this->assertStringContainsString('/search/advanced', $this->lastRequestUri());
        $this->assertStringContainsString('name=version', $this->lastRequestUri());
        $this->assertStringContainsString('city=Bratislava', $this->lastRequestUri());
        $this->assertStringContainsString('limit=20', $this->lastRequestUri());
    }
}
