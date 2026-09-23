<?php

declare(strict_types=1);

namespace FirmApi\Tests\Resources;

use FirmApi\Tests\TestCase;

class NbsTest extends TestCase
{
    public function test_entities_sends_filters_and_paging(): void
    {
        $client = $this->createClient([
            $this->jsonResponse(['data' => [['entity_id' => '01k5x2m8q7r3v9t6w4y1z0a2b3', 'ico' => '35697270']], 'meta' => ['has_more' => false]]),
        ]);

        $result = $client->nbs->entities([
            'category' => 'samostatný finančný agent',
            'natural_person' => true,
            'country' => '',
        ], 50, 100);

        $uri = urldecode($this->lastRequestUri());
        $this->assertSame('GET', $this->lastRequestMethod());
        $this->assertStringContainsString('/nbs/entities', $uri);
        $this->assertStringContainsString('category=samostatný finančný agent', str_replace('+', ' ', $uri));
        $this->assertStringContainsString('natural_person=1', $uri);
        $this->assertStringContainsString('limit=50', $uri);
        $this->assertStringContainsString('offset=100', $uri);
        $this->assertStringNotContainsString('country=', $uri);
        $this->assertSame('35697270', $result['data'][0]['ico']);
    }

    public function test_entities_caps_limit_at_100(): void
    {
        $client = $this->createClient([$this->jsonResponse(['data' => [], 'meta' => []])]);

        $client->nbs->entities([], 500);

        $this->assertStringContainsString('limit=100', $this->lastRequestUri());
    }

    public function test_entity_fetches_by_id(): void
    {
        $client = $this->createClient([$this->jsonResponse(['data' => ['entity_id' => '01k5x2m8q7r3v9t6w4y1z0a2b3', 'licences' => []]])]);

        $result = $client->nbs->entity('01k5x2m8q7r3v9t6w4y1z0a2b3');

        $this->assertStringContainsString('/nbs/entities/01k5x2m8q7r3v9t6w4y1z0a2b3', $this->lastRequestUri());
        $this->assertSame([], $result['data']['licences']);
    }

    public function test_agents_of_an_institution(): void
    {
        $client = $this->createClient([$this->jsonResponse(['data' => [], 'meta' => ['parent' => ['ico' => '31361358']]])]);

        $client->nbs->agents('31361358', ['status' => 'ended', 'natural_person' => false]);

        $uri = $this->lastRequestUri();
        $this->assertStringContainsString('/company/ico/31361358/nbs-agents', $uri);
        $this->assertStringContainsString('status=ended', $uri);
        $this->assertStringContainsString('natural_person=0', $uri);
    }
}
