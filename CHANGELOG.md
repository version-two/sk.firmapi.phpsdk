# Changelog

All notable changes to the FirmAPI PHP SDK are documented here.

## v2.3.0

- Registry people split by body (API v1.20.0): new `Company` collections `supervisoryBoard`,
  `procurators`, `liquidators`, `administrators`, `founders`, `branchHeads`, `legalPredecessors`
  and `shares`; `otherStakeholders` keeps only persons outside those bodies.
- New `RegisteredPerson` object (with `function` and, for procurators, `actingMethod`) replaces
  `OtherStakeholder`, which stays as a deprecated alias.
- `Shareholder` gained `sharePaid`, `shareCurrency`, `contributionKind`, `depositLien` and `function`.
- `StatutoryBody` gained `function`, `bodyType`, `appointedAt`, `effectiveFrom`, `effectiveTo` and `current`.
- New `ShareIssue` object for the `shares` collection.

## v2.2.0

- `Shareholder` gained `stakeholderType`, `effectiveFrom`, `effectiveTo` and `current`
  (API v1.19.0). `shareholders` now holds equity holders only.
- New `Company::$otherStakeholders` collection of `OtherStakeholder` objects – supervisory
  board members, procurators, liquidators, administrators and other registered persons
  without a stake (API `other_stakeholders`).

## v2.1.0

- `CompanyQuery::withOrsr()` scope helper.

## v2.0.0

Breaking changes to defaults and surface, plus reliability fixes.

### Breaking
- **Typed responses.** `companies->byIco(...)->get()` now returns a read-only
  `FirmApi\Objects\Company` value object (typed core accessors: `->ico`,
  `->name`, `->address`, `->shareholders`, `->statutoryBody`,
  `->businessActivities`, `->meta`, plus `->enrichment($scope)` / `->has()`)
  with `FirmApi\Support\Collection` lists — no Laravel dependency. It still
  implements `ArrayAccess`, so `$company['data']['ico']` and `->toArray()`
  keep working; code that did `=== $rawArray` must use `->toArray()`.
- **Fast by default.** Company lookups no longer block waiting for a completed
  background refresh. A `meta.stale = true` response (valid, precomputed data
  with a refresh queued) is now returned immediately. Opt into waiting per
  query with `CompanyQuery::fresh()`, or globally via the `waitForFreshData`
  constructor argument / `FIRMAPI_WAIT_FOR_FRESH_DATA` config. Previously the
  SDK could block the calling thread for minutes by default.
- **Removed `Companies::byId(int $id)`** and the `/company/{id}` numeric-ID
  lookup. Use `byIco()` or `byOrsrId()`.

### Added
- `CompanyQuery::fresh(?int $maxRetries = null)` — opt a single query into the
  bounded fresh-data wait.
- `CompanyQuery::with(string ...$scopes)` — raw-scope escape hatch.
- `CompanyQuery::withTradeLicenseActivities()` — ZRSR trade-licence activities.
- Automatic retry of transient failures (HTTP 5xx and network errors) with
  exponential backoff, controlled by the new `maxRetries` argument /
  `FIRMAPI_MAX_RETRIES` config (default 2). HTTP 429 is never silently retried;
  it raises `RateLimitException`.
- Laravel config keys `wait_for_fresh_data`, `max_stale_retries`, `max_retries`
  (with matching `FIRMAPI_*` env vars), now passed through to the client.
- Sandbox can now be enabled three ways: `Client::sandbox()`, the
  `sandbox: true` constructor argument, the `FIRMAPI_SANDBOX` env var (Laravel
  config `sandbox`), or by defining the `FIRMAPI_SANDBOX` constant in plain PHP.
  A read-only `$client->sandbox` flag exposes the resolved mode.

### Fixed
- Malformed / non-JSON responses now raise `ApiException` instead of being
  silently coerced into an empty array.
- The fresh-data wait is bounded by a total wall-clock budget so it can never
  stack into minutes.

## v1.x

Initial releases: fluent company lookups with enrichment scopes, search, batch,
account resources, typed exceptions, and Laravel integration.
