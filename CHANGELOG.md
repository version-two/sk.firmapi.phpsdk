# Changelog

All notable changes to the FirmAPI PHP SDK are documented here.

## v2.7.0

- New `$client->companies->groupMembers($icDph, $search, $limit, $offset)` (API v1.26.0): the
  members of a VAT group, paged, keyed by the group IČ DPH from `vat_registration.ic_dph`. Each
  member has `ico`, `name`, `type` and the registration dates. `meta.total_members` counts every
  member; `meta.members_without_ico` are those the register lists without an IČO, which are
  never named.

## v2.6.1

- No code change needed for API v1.26.0: the `tax` scope (`withTax()`) now also returns a
  `vat_registration` block, read with `$company->enrichment('vat_registration')`. It holds the
  registration type from the VAT payers list (`type`, e.g. `§4b`), `is_group_member` (VAT group,
  `§4b` or `§4c`), `group_member_count`, `ic_dph` and the registration dates. The key is absent
  when the company is not listed or the registration type has not been loaded yet, so check
  `$company->has('vat_registration')` before reading it; absence never means "not a member".

## v2.6.0

- New scope helpers `withConvictedEntities()` (register of convicted legal entities, General
  Prosecutor's Office), `withStateAid()` (PPA state aid and de minimis aid, from 2023) and
  `withGrants()` (grant applications and approved amounts from the Ministry of Economy, the
  Ministry of Environment and the Government Office, from 2023). Each result is read with
  `$company->enrichment('<scope>')`; `listed` is always present.
- `$company->enrichment('e_box')` returns the NASES electronic mailbox flag
  (`activated_for_delivery`, `as_of`); it comes with every response and needs no scope.
- No code change needed for API v1.23.0: NBS entity arrays returned by `$client->nbs` now carry
  `person_match` (`address`, `name_postcode` – a probable match – or `null`).

## v2.5.0

- New `$client->nbs` resource (API v1.22.0) for the NBS register of financial market entities:
  `entities()` (filter by category, sector, parent institution, country, natural person, status),
  `entity()` (one entity with full licence history, including foreign entities without an IČO) and
  `agents()` (financial agents working for an institution).
- The `nbs` scope now also carries `licences` (with parent institution, current/ended status),
  `agents_count`, `is_natural_person` and `entity_id`; `withNbs()` is unchanged.

## v2.4.0

- New `withItms21()` scope helper (API v1.21.0): EU funds 2021 – 2027 from ITMS21+ – projects,
  grant applications, irregularities/receivables and a procurement summary under the `itms21` key.

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
