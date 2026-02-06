<?php

declare(strict_types=1);

namespace FirmApi\Tests\Resources;

use FirmApi\Tests\TestCase;

class AccountTest extends TestCase
{
    public function test_usage_sends_get_to_correct_endpoint(): void
    {
        $client = $this->createClient([
            $this->jsonResponse(['data' => [
                'requests_month' => 150,
                'requests_today' => 12,
            ]]),
        ]);

        $result = $client->account->usage();

        $this->assertSame('GET', $this->lastRequestMethod());
        $this->assertStringContainsString('/account/usage', $this->lastRequestUri());
        $this->assertSame(150, $result['data']['requests_month']);
    }

    public function test_quota_sends_get_to_correct_endpoint(): void
    {
        $client = $this->createClient([
            $this->jsonResponse(['data' => [
                'remaining_daily' => 88,
                'remaining_monthly' => 850,
            ]]),
        ]);

        $result = $client->account->quota();

        $this->assertSame('GET', $this->lastRequestMethod());
        $this->assertStringContainsString('/account/quota', $this->lastRequestUri());
        $this->assertSame(88, $result['data']['remaining_daily']);
    }

    public function test_history_sends_correct_request(): void
    {
        $client = $this->createClient([
            $this->jsonResponse(['data' => []]),
        ]);

        $client->account->history();

        $this->assertSame('GET', $this->lastRequestMethod());
        $this->assertStringContainsString('/account/history', $this->lastRequestUri());
    }
}
