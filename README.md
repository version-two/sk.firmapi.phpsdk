# FirmAPI PHP SDK

Official PHP SDK for [FirmAPI](https://firmapi.sk) - Slovak Company Data API.

## Requirements

- PHP 8.1 or higher
- Guzzle HTTP client

## Installation

```bash
composer require firmapi/phpsdk
```

## Quick Start

```php
<?php

use FirmApi\Client;

$client = new Client('your-api-key');

// Look up a company by IČO. byIco() returns a query; call get() to execute it.
$company = $client->companies->byIco('51636549')->get();
echo $company['data']['name']; // "Version Two s. r. o."

// Search companies
$results = $client->search->autocomplete('version');
foreach ($results['results'] as $result) {
    echo "{$result['text']} ({$result['ico']})\n";
}
```

## Fresh vs. cached data (important)

FirmAPI serves precomputed company data immediately. When a background refresh
is queued, the response carries `meta.stale = true` — the data you received is
still valid; the flag only tells you a newer version is being prepared.

By default (since v2.0) the SDK returns that immediately-available data without
blocking. If you specifically need the post-refresh values and can tolerate the
extra latency and billed re-poll requests, opt in per query with `fresh()`:

```php
// Fast (default): returns immediately, even if meta.stale is true
$company = $client->companies->byIco('51636549')->get();

// Wait for a completed refresh (blocks and re-polls, bounded)
$company = $client->companies->byIco('51636549')->fresh()->get();
```

You can also make waiting the default for every lookup on a client (rarely
needed) via the constructor flag / Laravel config below.

## Usage

### Plain PHP

```php
use FirmApi\Client;

$client = new Client('your-api-key');

// All constructor options (defaults shown)
$client = new Client(
    apiKey: 'your-api-key',
    baseUrl: 'https://api.firmapi.sk/v1', // optional override
    timeout: 30,                          // per-request HTTP timeout (seconds)
    httpClient: null,                     // inject a preconfigured Guzzle client
    waitForFreshData: false,              // block/re-poll until non-stale (opt-in)
    maxStaleRetries: 3,                   // re-polls when waiting for fresh data
    maxRetries: 2,                        // retries for transient 5xx/network errors
    sandbox: null,                        // null = auto (FIRMAPI_SANDBOX constant); true/false to force
);
```

#### Sandbox mode

The sandbox needs no API key, returns demo data, and has no rate limits. Enable
it any of three ways:

```php
// 1. Explicit factory (or constructor flag)
$client = Client::sandbox();
$client = new Client('ignored', sandbox: true);

// 2. Plain-PHP constant - define it before constructing the client
define('FIRMAPI_SANDBOX', true);
$client = new Client('ignored');   // auto-detected -> sandbox

// 3. Laravel: set FIRMAPI_SANDBOX=true in .env (see below)
```

When sandbox is active the base URL and key are overridden with the built-in
sandbox endpoint automatically, so any key you pass is ignored. An explicit
`sandbox: true|false` argument always wins over the constant.

Transient failures (HTTP 5xx and network errors) are retried automatically with
exponential backoff, up to `maxRetries`. HTTP 429 is **not** retried — it is
raised as a `RateLimitException` so you control pacing.

### Laravel

The SDK includes a Laravel service provider with auto-discovery. Add your API
key to `.env`:

```env
FIRMAPI_API_KEY=your-api-key

# Optional (defaults shown)
FIRMAPI_BASE_URL=https://api.firmapi.sk/v1
FIRMAPI_TIMEOUT=30
FIRMAPI_WAIT_FOR_FRESH_DATA=false
FIRMAPI_MAX_STALE_RETRIES=3
FIRMAPI_MAX_RETRIES=2

# Set true to use the sandbox (no key needed, demo data, no rate limits)
FIRMAPI_SANDBOX=false
```

Optionally publish the config file:

```bash
php artisan vendor:publish --tag=firmapi-config
```

Then use dependency injection:

```php
use FirmApi\Client;

class CompanyController extends Controller
{
    public function show(Client $client, string $ico)
    {
        return $client->companies->byIco($ico)->get();
    }
}
```

### Companies

```php
// Get company by IČO (8-digit registration number)
$company = $client->companies->byIco('51636549')->get();

// Get company by ORSR ID
$company = $client->companies->byOrsrId('427482')->get();
```

Companies are always identified by IČO or ORSR ID — the SDK does not expose
internal numeric database identifiers.

#### The `Company` object

`get()` returns a typed, read-only `FirmApi\Objects\Company` value object with
IDE-friendly accessors and `FirmApi\Support\Collection` lists — no Laravel/
Illuminate dependency required. It also implements `ArrayAccess`, so existing
`$company['data']['ico']` code keeps working.

```php
$company = $client->companies->byIco('51636549')->get();

$company->ico;                 // '51636549'
$company->name;                // 'Version Two s. r. o.'
$company->address->city;       // nested Address value object
$company->address->formatted();

// Collections of typed nested objects
foreach ($company->shareholders as $s) {
    echo $s->name . ' — ' . $s->sharePercentage;
}
$company->statutoryBody->first()->role;   // 'konateľ'
$company->businessActivities->pluck('activity');

// Metadata
$company->meta->stale;         // bool (true = a refresh is queued)
$company->meta->source;

// Enrichment scopes (tax, sanctions, financials, ...) are plan- and
// scope-dependent, so they are reached generically rather than mistyped:
if ($company->has('sanctions')) {
    $hits = $company->enrichment('sanctions');
}

// Backward-compatible raw access
$company['data']['ico'];
$company->toArray();           // the full { data, meta } array
```

#### Enrichment scopes

Base lookups return core registry data. Request additional datasets with the
fluent `withX()` helpers (each maps to an API `scope`, subject to your plan's
features). Chain as many as you need, then call `get()`:

```php
$company = $client->companies->byIco('51636549')
    ->withTax()
    ->withFinancials()
    ->withSanctions()
    ->get();

// Everything your plan is entitled to
$company = $client->companies->byIco('51636549')->withAll()->get();
```

Available scope helpers:

`withTax()`, `withBankAccounts()`, `withContacts()`, `withFinancials()`,
`withDebtorStatus()`, `withFinancialStatements()`, `withInsolvency()`,
`withCommercialBulletin()`, `withPublicContracts()`, `withProcurement()`,
`withExecutionAuthorizations()`, `withRpvs()`, `withNbs()`,
`withTaxReliability()`, `withErasedVat()`, `withReges()`,
`withSocialEnterprise()`, `withGleif()`, `withSanctions()`, `withTedTenders()`,
`withReplikAdministrator()`, `withSbs()`, `withTransportLicence()`,
`withUtilityLicence()`, `withContractingAuthority()`, `withDebarred()`,
`withUvoReferences()`, `withFsImports()`, `withIllegalEmployment()`,
`withCourtDecisions()`, `withEmployerHeadcount()`, `withSoiTravelAgency()`,
`withSvpsEstablishments()`, `withCrpProjects()`,
`withTradeLicenseActivities()`, `withAll()`.

`with(string ...$scopes)` is an escape hatch for passing raw scope tokens
directly, e.g. `->with('tax', 'sanctions')`.

### Search

```php
// Autocomplete (Select2-compatible format)
$results = $client->search->autocomplete('version', limit: 10);

// Search by name
$results = $client->search->byName('Version Two');

// Search by name (exact match, with pagination)
$results = $client->search->byName('Version Two s. r. o.', exact: true, limit: 10, offset: 0);

// Search by partial IČO
$results = $client->search->byIco('5163');

// Advanced search
$results = $client->search->advanced([
    'name' => 'version',
    'city' => 'Bratislava',
    'limit' => 20,
]);
```

### Batch Operations

Batch operations require Starter plan or higher.

```php
// Batch lookup by IČO
$results = $client->batch->byIco(['51636549', '12345678']);

// Batch lookup by names
$results = $client->batch->byNames(['Version Two s. r. o.', 'Example Company']);

// Check batch job status
$status = $client->batch->status('batch-id-123');

// Get batch results
$results = $client->batch->results('batch-id-123');
```

### Account

```php
// Get current usage
$usage = $client->account->usage();

// Get remaining quota
$quota = $client->account->quota();

// Get usage history (range depends on your subscription tier)
$history = $client->account->history();
```

## Error Handling

```php
use FirmApi\Exceptions\ApiException;
use FirmApi\Exceptions\AuthenticationException;
use FirmApi\Exceptions\RateLimitException;
use FirmApi\Exceptions\ValidationException;

try {
    $company = $client->companies->byIco('51636549')->get();
} catch (AuthenticationException $e) {
    // Invalid API key (401)
} catch (RateLimitException $e) {
    // Too many requests (429)
    $e->getRetryAfter(); // seconds to wait
} catch (ValidationException $e) {
    // Invalid parameters (422)
    $e->getErrors(); // ['field' => ['error message']]
} catch (ApiException $e) {
    // Other API errors (403, 404, 500, malformed response, network, etc.)
}
```

## Rate Limits

API rate limits depend on your subscription tier. See [pricing](https://firmapi.sk/en/pricing) for details.

Rate limit headers are included in all responses:
- `X-RateLimit-Limit-Minute` / `X-RateLimit-Remaining-Minute`
- `X-RateLimit-Limit-Daily` / `X-RateLimit-Remaining-Daily`

## Upgrading from v1.x

v2.0 is a behavior/API change:

- **Fast by default.** Lookups no longer block waiting for a background refresh.
  If you relied on the old auto-wait, call `->fresh()` per query, or set
  `waitForFreshData: true` (constructor) / `FIRMAPI_WAIT_FOR_FRESH_DATA=true`.
- **`Companies::byId(int $id)` was removed.** Use `byIco()` or `byOrsrId()`.
- **Transient retries added.** 5xx/network errors are retried (`maxRetries`,
  default 2); 429 still raises `RateLimitException`.
- **Malformed responses now throw** `ApiException` instead of returning `[]`.

## License

MIT License. See [LICENSE](LICENSE) for details.

## Documentation

[https://firmapi.sk/en/docs](https://firmapi.sk/en/docs)
