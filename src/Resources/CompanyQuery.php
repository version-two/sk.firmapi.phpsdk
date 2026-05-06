<?php

declare(strict_types=1);

namespace FirmApi\Resources;

use FirmApi\Client;

class CompanyQuery
{
    private array $scopes = [];

    public function __construct(
        private Client $client,
        private string $path,
        private bool $waitForFreshData = true,
        private int $maxStaleRetries = 3,
    ) {}

    public function withTax(): static
    {
        $this->scopes[] = 'tax';
        return $this;
    }

    public function withBankAccounts(): static
    {
        $this->scopes[] = 'bank_accounts';
        return $this;
    }

    public function withContacts(): static
    {
        $this->scopes[] = 'contacts';
        return $this;
    }

    public function withFinancials(): static
    {
        $this->scopes[] = 'financials';
        return $this;
    }

    public function withDebtorStatus(): static
    {
        $this->scopes[] = 'debtor_status';
        return $this;
    }

    public function withFinancialStatements(): static
    {
        $this->scopes[] = 'financial_statements';
        return $this;
    }

    public function withInsolvency(): static
    {
        $this->scopes[] = 'insolvency';
        return $this;
    }

    public function withCommercialBulletin(): static
    {
        $this->scopes[] = 'commercial_bulletin';
        return $this;
    }

    public function withPublicContracts(): static
    {
        $this->scopes[] = 'public_contracts';
        return $this;
    }

    public function withProcurement(): static
    {
        $this->scopes[] = 'procurement';
        return $this;
    }

    public function withExecutionAuthorizations(): static
    {
        $this->scopes[] = 'execution_authorizations';
        return $this;
    }

    public function withRpvs(): static
    {
        $this->scopes[] = 'rpvs';
        return $this;
    }

    public function withNbs(): static
    {
        $this->scopes[] = 'nbs';
        return $this;
    }

    public function withTaxReliability(): static
    {
        $this->scopes[] = 'tax_reliability';
        return $this;
    }

    public function withErasedVat(): static
    {
        $this->scopes[] = 'erased_vat';
        return $this;
    }

    public function withReges(): static
    {
        $this->scopes[] = 'reges';
        return $this;
    }

    public function withSocialEnterprise(): static
    {
        $this->scopes[] = 'social_enterprise';
        return $this;
    }

    public function withGleif(): static
    {
        $this->scopes[] = 'gleif';
        return $this;
    }

    public function withSanctions(): static
    {
        $this->scopes[] = 'sanctions';
        return $this;
    }

    public function withTedTenders(): static
    {
        $this->scopes[] = 'ted_tenders';
        return $this;
    }

    public function withReplikAdministrator(): static
    {
        $this->scopes[] = 'replik_administrator';
        return $this;
    }

    public function withSbs(): static
    {
        $this->scopes[] = 'sbs';
        return $this;
    }

    public function withTransportLicence(): static
    {
        $this->scopes[] = 'transport_licence';
        return $this;
    }

    public function withUtilityLicence(): static
    {
        $this->scopes[] = 'utility_licence';
        return $this;
    }

    public function withAll(): static
    {
        $this->scopes = ['all'];
        return $this;
    }

    /**
     * Execute the query and return the company data.
     *
     * @return array<string, mixed>
     */
    public function get(): array
    {
        $fullPath = $this->path;
        if (!empty($this->scopes)) {
            $fullPath .= '?scope=' . implode(',', array_unique($this->scopes));
        }

        $response = $this->client->get($fullPath);

        return $this->resolveStaleResponse($response, $fullPath);
    }

    private function resolveStaleResponse(array $response, string $path): array
    {
        if (!$this->waitForFreshData || empty($response['meta']['stale'])) {
            return $response;
        }

        for ($attempt = 0; $attempt < $this->maxStaleRetries; $attempt++) {
            $waitSeconds = $this->calculateWaitSeconds($response['meta']['retry_at'] ?? null);

            if ($waitSeconds > 0) {
                sleep($waitSeconds);
            }

            $response = $this->client->get($path);

            if (empty($response['meta']['stale'])) {
                return $response;
            }
        }

        return $response;
    }

    private function calculateWaitSeconds(?string $retryAt): int
    {
        if ($retryAt === null) {
            return 5;
        }

        $retryTime = strtotime($retryAt);
        if ($retryTime === false) {
            return 5;
        }

        $wait = $retryTime - time();

        return max(1, min($wait, 60));
    }
}
