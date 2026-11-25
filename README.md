# FirmAPI PHP SDK

Official PHP SDK for [FirmAPI](https://firmapi.sk) - Slovak Company Data API.

## Requirements

- PHP 8.1 or higher
- Guzzle HTTP client

## Installation

```bash
composer require firmapi/phpsdk:^2.7.2
```

> These docs cover **v2.x**. Upgrading? See [Upgrading](#upgrading) and
> [CHANGELOG.md](CHANGELOG.md) for the breaking changes.

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
is queued, the response carries `meta.stale = true` – the data you received is
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

The sandbox only knows ten demo companies, for example `36421928` (Websupport)
or `31333532` (ESET). Any other IČO, including `51636549` used in the examples
below, answers 404 there. `GET /v1/sandbox/companies` lists them.

Transient failures (HTTP 5xx and network errors) are retried automatically with
exponential backoff, up to `maxRetries`. HTTP 429 is **not** retried – it is
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

Companies are always identified by IČO or ORSR ID – the SDK does not expose
internal numeric database identifiers.

#### The `Company` object

`get()` returns a typed, read-only `FirmApi\Objects\Company` value object with
IDE-friendly accessors and `FirmApi\Support\Collection` lists – no Laravel/
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
    echo $s->name . ' – ' . $s->sharePercentage . ' % (' . $s->shareAmount . ' ' . $s->shareCurrency . ')';
}
$company->statutoryBody->first()->role;       // 'predseda predstavenstva'
$company->statutoryBody->first()->function;   // 'chairman' | 'vice_chairman' | 'member' | null
$company->statutoryBody->first()->bodyType;   // 'predstavenstvo'
$company->supervisoryBoard->first()->function;
$company->procurators->first()->actingMethod;
$company->legalPredecessors->first()->ico;
$company->shares->first()->count;             // share issues of joint-stock companies
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
`withSvpsEstablishments()`, `withCrpProjects()`, `withItms21()`,
`withConvictedEntities()`, `withStateAid()`, `withGrants()`,
`withTradeLicenseActivities()`, `withAll()`.

`with(string ...$scopes)` is an escape hatch for passing raw scope tokens
directly, e.g. `->with('tax', 'sanctions')`.

`withTax()` also returns `vat_registration` (VAT payers list of Finančná správa):
`$company->enrichment('vat_registration')` gives `type` (e.g. `§4b`), `is_group_member`
(VAT group, `§4b` or `§4c`), `group_member_count`, `ic_dph`, `registered_at`,
`type_changed_at` and `vat_payer_since`. The key is absent when the company is not listed
or the registration type has not been loaded yet; use `$company->has('vat_registration')`
and never read absence as "not a group member".

The other members of a VAT group are one call away, keyed by that IČ DPH (requires a plan
with tax data):

```php
$company = $client->companies->byIco('34109340')->withTax()->get();
$registration = $company->enrichment('vat_registration');

if ($registration['is_group_member'] ?? false) {
    $page = $client->companies->groupMembers($registration['ic_dph'], limit: 50);

    foreach ($page['data'] as $member) {
        echo "{$member['ico']} {$member['name']} ({$member['type']})\n";
    }

    // meta.total_members counts every member; meta.members_without_ico are listed
    // by the register without an IČO and are never named.
    $matches = $client->companies->groupMembers($registration['ic_dph'], search: 'holding');
}
```

`e_box` (NASES electronic mailbox activated for delivery, with the month of the
published list) is returned on every request and needs no scope:
`$company->enrichment('e_box')` gives `['activated_for_delivery' => bool, 'as_of' => 'YYYY-MM']`,
or `null` until the list has been imported.

### NBS register

Financial market entities supervised by Národná banka Slovenska, their licences and
agent → institution relations with history (requires the `nbs` feature):

```php
// Every independent financial agent in the insurance sector
$page = $client->nbs->entities([
    'category' => 'samostatný finančný agent',
    'sector' => 'sektor poistenia alebo zaistenia',
], limit: 50);

// Agents that used to work for an institution
$former = $client->nbs->agents('31361358', ['status' => 'ended']);

// One entity (also foreign ones without an IČO) with full licence history
$entity = $client->nbs->entity($page['data'][0]['entity_id']);
```

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

Batch operations require the Professional plan or higher. The maximum number of
items per batch depends on your plan (Professional 100, Enterprise 1,000). A batch
costs 1 credit per 2 items (rounded up) and is processed asynchronously within an
hour, when the server is less busy. Status and results are free and kept for 7 days.

```php
// Submit a batch by IČO (or $client->batch->byNames([...]))
$batch = $client->batch->byIco(['51636549', '12345678']);
$batchId = $batch['data']['id'];          // status is 'pending'

// Poll the status (free)
$status = $client->batch->status($batchId);
$status['data']['status'];                // pending | processing | completed | failed

// Once completed, download the results (free); ApiException with code 409 while not finished
$result = $client->batch->results($batchId);
$result['meta'];                          // id, type, total, found, not_found
$result['data']['51636549']['found'];     // bool
$result['data']['51636549']['data'];      // ico, name, address, legal_form, established_date, or null
```

### Account

Responses depend on how your account is billed; `data.billing_mode` is
`credits` or `legacy`.

```php
// Current period usage
$usage = $client->account->usage();
if ($usage['data']['billing_mode'] === 'credits') {
    $usage['data']['credits']['used_this_period'];
    $usage['data']['credits']['balance'];
}

// Remaining quota
$quota = $client->account->quota();
if ($quota['data']['billing_mode'] === 'credits') {
    $quota['data']['credits_remaining'];
}

// Daily usage history (date, requests, credits); the range depends on your plan
$history = $client->account->history();
$history['meta']['history_months'];
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

Usage is metered in credits: one API call costs one credit, a batch 1 credit per
2 items, cached and error responses cost nothing. Each plan grants a monthly credit balance; see
[pricing](https://firmapi.sk/en/pricing) for the current plans. Independently of
the plan, one API key may send at most 120 requests per minute; beyond that the
API answers 429 and the SDK throws `RateLimitException`
(`getRetryAfter()` reads the `Retry-After` header, 60 seconds when absent).

## Upgrading

### v2.7.2

Batches are asynchronous: `byIco()` and `byNames()` return the batch ID
(`data.id`); `status($id)` reports progress and `results($id)` returns the
results once `data.status` is `completed`.

### From v1.x to v2.0

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

## Documentation and support

- Documentation: [https://firmapi.sk/en/docs](https://firmapi.sk/en/docs)
- SDK overview: [https://firmapi.sk/en/docs/sdk](https://firmapi.sk/en/docs/sdk)
- Contact form: [https://firmapi.sk/en/contact](https://firmapi.sk/en/contact)
