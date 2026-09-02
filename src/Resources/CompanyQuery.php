<?php

declare(strict_types=1);

namespace FirmApi\Resources;

use FirmApi\Client;
use FirmApi\Objects\Company;

class CompanyQuery
{
    /** @var list<string> */
    private array $scopes = [];

    public function __construct(
        private Client $client,
        private string $path,
        private bool $waitForFreshData = false,
        private int $maxStaleRetries = 3,
    ) {}

    /**
     * Opt THIS query into waiting for a completed background refresh.
     *
     * By default the SDK returns the API's immediately-available (precomputed)
     * data even when `meta.stale` is set -- that data is valid; the flag only
     * means a refresh is queued. Call fresh() when you specifically need the
     * post-refresh values and can tolerate the added latency (and the extra
     * billed re-poll requests).
     *
     * @param int|null $maxRetries Override the number of re-polls for this query.
     */
    public function fresh(?int $maxRetries = null): static
    {
        $this->waitForFreshData = true;
        if ($maxRetries !== null) {
            $this->maxStaleRetries = max(0, $maxRetries);
        }
        return $this;
    }

    /**
     * Add one or more raw scope tokens (e.g. with('tax', 'sanctions')).
     * Prefer the typed withX() helpers; this is an escape hatch for scopes
     * added to the API before a matching helper ships.
     */
    public function with(string ...$scopes): static
    {
        foreach ($scopes as $scope) {
            $this->scopes[] = $scope;
        }
        return $this;
    }

    public function withOrsr(): static
    {
        $this->scopes[] = 'orsr';
        return $this;
    }

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

    public function withContractingAuthority(): static
    {
        $this->scopes[] = 'contracting_authority';
        return $this;
    }

    public function withDebarred(): static
    {
        $this->scopes[] = 'debarred';
        return $this;
    }

    public function withUvoReferences(): static
    {
        $this->scopes[] = 'uvo_references';
        return $this;
    }

    public function withFsImports(): static
    {
        $this->scopes[] = 'fs_imports';
        return $this;
    }

    public function withIllegalEmployment(): static
    {
        $this->scopes[] = 'illegal_employment';
        return $this;
    }

    public function withCourtDecisions(): static
    {
        $this->scopes[] = 'court_decisions';
        return $this;
    }

    public function withEmployerHeadcount(): static
    {
        $this->scopes[] = 'employer_headcount';
        return $this;
    }

    public function withSoiTravelAgency(): static
    {
        $this->scopes[] = 'soi_travel_agency';
        return $this;
    }

    public function withSvpsEstablishments(): static
    {
        $this->scopes[] = 'svps_establishments';
        return $this;
    }

    public function withCrpProjects(): static
    {
        $this->scopes[] = 'crp_projects';
        return $this;
    }

    public function withTradeLicenseActivities(): static
    {
        $this->scopes[] = 'trade_license_activities';
        return $this;
    }

    public function withAll(): static
    {
        $this->scopes = ['all'];
        return $this;
    }

    /**
     * Execute the query and return the company as a typed value object.
     *
     * The result is also array-accessible for backward compatibility, so
     * $company['data']['ico'] keeps working alongside $company->ico.
     */
    public function get(): Company
    {
        $fullPath = $this->path;
        if (!empty($this->scopes)) {
            $fullPath .= '?scope=' . implode(',', array_unique($this->scopes));
        }

        $response = $this->client->get($fullPath);

        return Company::fromResponse($this->resolveStaleResponse($response, $fullPath));
    }

    /**
     * Total wall-clock budget (seconds) for the opt-in fresh-data wait, across
     * all re-polls, so fresh() can never block a caller for minutes.
     */
    private const MAX_TOTAL_WAIT_SECONDS = 120;

    private function resolveStaleResponse(array $response, string $path): array
    {
        if (!$this->waitForFreshData || empty($response['meta']['stale'])) {
            return $response;
        }

        $spent = 0;

        for ($attempt = 0; $attempt < $this->maxStaleRetries; $attempt++) {
            $waitSeconds = $this->calculateWaitSeconds($response['meta']['retry_at'] ?? null);

            if ($spent + $waitSeconds > self::MAX_TOTAL_WAIT_SECONDS) {
                break;
            }

            if ($waitSeconds > 0) {
                sleep($waitSeconds);
                $spent += $waitSeconds;
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
