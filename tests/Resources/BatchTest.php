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

    public function test_by_ico_returns_the_batch_id(): void
    {
        $client = $this->createClient([
            $this->jsonResponse([
                'data' => ['id' => '01jfz3k8q7x2m5n9p4r6t8v0wa', 'type' => 'ico', 'status' => 'pending', 'total' => 2],
                'meta' => ['status_url' => 'https://api.firmapi.sk/v1/batch/01jfz3k8q7x2m5n9p4r6t8v0wa/status'],
            ], 202),
        ]);

        $result = $client->batch->byIco(['51636549', '12345678']);

        $this->assertSame('01jfz3k8q7x2m5n9p4r6t8v0wa', $result['data']['id']);
        $this->assertSame('pending', $result['data']['status']);
    }

    public function test_status_sends_get_to_correct_endpoint(): void
    {
        $client = $this->createClient([
            $this->jsonResponse(['data' => ['id' => '01jfz3k8q7x2m5n9p4r6t8v0wa', 'status' => 'completed']]),
        ]);

        $result = $client->batch->status('01jfz3k8q7x2m5n9p4r6t8v0wa');

        $this->assertSame('GET', $this->lastRequestMethod());
        $this->assertStringContainsString('/batch/01jfz3k8q7x2m5n9p4r6t8v0wa/status', $this->lastRequestUri());
        $this->assertSame('completed', $result['data']['status']);
    }

    public function test_results_sends_get_to_correct_endpoint(): void
    {
        $client = $this->createClient([
            $this->jsonResponse([
                'data' => [
                    '51636549' => ['found' => true, 'data' => ['ico' => '51636549']],
                    '12345678' => ['found' => false, 'data' => null],
                ],
                'meta' => ['id' => '01jfz3k8q7x2m5n9p4r6t8v0wa', 'type' => 'ico', 'total' => 2, 'found' => 1, 'not_found' => 1],
            ]),
        ]);

        $result = $client->batch->results('01jfz3k8q7x2m5n9p4r6t8v0wa');

        $this->assertSame('GET', $this->lastRequestMethod());
        $this->assertStringContainsString('/batch/01jfz3k8q7x2m5n9p4r6t8v0wa/results', $this->lastRequestUri());
        $this->assertTrue($result['data']['51636549']['found']);
        $this->assertSame(1, $result['meta']['not_found']);
    }

    public function test_results_of_an_unfinished_batch_throw_409(): void
    {
        $client = $this->createClient([
            $this->jsonResponse(['error' => 'batch_not_ready', 'message' => 'Batch is still being processed.'], 409),
        ]);

        try {
            $client->batch->results('01jfz3k8q7x2m5n9p4r6t8v0wa');
            $this->fail('Expected ApiException');
        } catch (\FirmApi\Exceptions\ApiException $e) {
            $this->assertSame(409, $e->getCode());
        }
    }
}
