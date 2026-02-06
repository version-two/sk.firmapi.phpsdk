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

// Get company by IČO
$company = $client->companies->byIco('51636549');
echo $company['data']['name']; // "Version Two s. r. o."

// Search companies
$results = $client->search->autocomplete('version');
foreach ($results['results'] as $result) {
    echo "{$result['text']} ({$result['ico']})\n";
}
```

## Usage

### Plain PHP

```php
use FirmApi\Client;

$client = new Client('your-api-key');

// With custom options
$client = new Client(
    apiKey: 'your-api-key',
    baseUrl: 'https://api.firmapi.sk/v1', // optional
    timeout: 30 // optional, in seconds
);
```

### Laravel

The SDK includes a Laravel service provider with auto-discovery. Add your API key to `.env`:

```env
FIRMAPI_API_KEY=your-api-key
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
        return $client->companies->byIco($ico);
    }
}
```

### Companies

```php
// Get company by IČO (8-digit registration number)
$company = $client->companies->byIco('51636549');

// Get company by ORSR ID
$company = $client->companies->byOrsrId('427482');

// Get company by internal ID
$company = $client->companies->byId(12345);
```

### Search

```php
// Autocomplete (Select2-compatible format)
$results = $client->search->autocomplete('version', limit: 10);

// Search by name
$results = $client->search->byName('Version Two');

// Search by name (exact match)
$results = $client->search->byName('Version Two s. r. o.', exact: true);

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
    $company = $client->companies->byIco('51636549');
} catch (AuthenticationException $e) {
    // Invalid API key (401)
} catch (RateLimitException $e) {
    // Too many requests (429)
    $e->getRetryAfter(); // seconds to wait
} catch (ValidationException $e) {
    // Invalid parameters (422)
    $e->getErrors(); // ['field' => ['error message']]
} catch (ApiException $e) {
    // Other API errors (403, 404, 500, etc.)
}
```

## Rate Limits

API rate limits depend on your subscription tier. See [pricing](https://firmapi.sk/en/pricing) for details.

Rate limit headers are included in all responses:
- `X-RateLimit-Limit-Minute` / `X-RateLimit-Remaining-Minute`
- `X-RateLimit-Limit-Daily` / `X-RateLimit-Remaining-Daily`

## License

MIT License. See [LICENSE](LICENSE) for details.

## Documentation

[https://firmapi.sk/en/docs](https://firmapi.sk/en/docs)
