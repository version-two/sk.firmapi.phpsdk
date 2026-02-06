<?php

declare(strict_types=1);

namespace FirmApi\Tests\Resources;

use FirmApi\Tests\TestCase;

class BatchTest extends TestCase
{
    public function test_by_ico_sends_post_with_icos(): void
    {
        $client = $this->createClient([
            $this->jsonResponse(['data' => []]),
        ]);

        $client->batch->byIco(['51636549', '12345678']);

        $this->assertSame('POST', $this->lastRequestMethod());
        $this->assertStringContainsString('/batch/ico', $this->lastRequestUri());
        $this->assertSame(['51636549', '12345678'], $this->lastRequestBody()['icos']);
    }

    public function test_by_names_sends_post_with_names(): void
    {
        $client = $this->createClient([
            $this->jsonResponse(['data' => []]),
        ]);

        $client->batch->byNames(['Version Two s. r. o.', 'Example Company']);

        $this->assertSame('POST', $this->lastRequestMethod());
        $this->assertStringContainsString('/batch/names', $this->lastRequestUri());
        $this->assertSame(['Version Two s. r. o.', 'Example Company'], $this->lastRequestBody()['names']);
    }

    public function test_status_sends_get_to_correct_endpoint(): void
    {
        $client = $this->createClient([
            $this->jsonResponse(['data' => ['status' => 'completed', 'total' => 5, 'processed' => 5]]),
        ]);

        $result = $client->batch->status('batch-abc-123');

        $this->assertSame('GET', $this->lastRequestMethod());
        $this->assertStringContainsString('/batch/batch-abc-123/status', $this->lastRequestUri());
        $this->assertSame('completed', $result['data']['status']);
    }

    public function test_results_sends_get_to_correct_endpoint(): void
    {
        $client = $this->createClient([
            $this->jsonResponse(['data' => [
                ['ico' => '51636549', 'name' => 'Version Two s. r. o.'],
                ['ico' => '12345678', 'name' => 'Another Company'],
            ]]),
        ]);

        $result = $client->batch->results('batch-abc-123');

        $this->assertSame('GET', $this->lastRequestMethod());
        $this->assertStringContainsString('/batch/batch-abc-123/results', $this->lastRequestUri());
        $this->assertCount(2, $result['data']);
    }
}
