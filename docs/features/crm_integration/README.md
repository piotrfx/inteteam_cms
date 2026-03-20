# CRM Integration Feature

**Status:** Phase 2

---

## Overview

Phase 2 connects inteteam_cms to a shop's inteteam_crm instance. The CMS reads data from the CRM (gallery, storefront products, forms, business updates) to power embedded content blocks. The connection is one-way — the CMS never writes to the CRM.

Each company configures their CRM connection once in Settings → CRM Integration. Once connected, CRM block types become available in the block editor.

---

## User Stories

- As a shop owner, I can connect my CMS site to my inteteam_crm account by entering my CRM URL and API key.
- As a shop owner, I can test the connection before saving it.
- As a shop owner, I can add a Gallery block to a page and choose a gallery from my CRM.
- As a shop owner, I can embed a contact/booking form from my CRM.
- As a shop owner, I can display a storefront product grid on any page.
- As a shop owner, I can display my latest business updates on any page.

---

## Connection Setup

Stored on `companies`:

```sql
crm_base_url   VARCHAR NULL  -- e.g. 'https://acme.crm.inte.team'
crm_company_id VARCHAR NULL  -- CRM's company ULID
crm_api_key    VARCHAR NULL  -- encrypted at rest
```

The API key is encrypted using Laravel's `Crypt::encryptString()` on save and decrypted on read inside `CrmApiClient`. It is never exposed in API responses or logs.

A company is considered "connected" when all three fields are set and the last connection test was successful. Connection status is cached for 1 hour.

---

## Service: `CrmApiClient`

```php
final class CrmApiClient
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly string $apiKey,
        private readonly string $companyId,
    ) {}

    public function testConnection(): bool;
    public function galleries(): array;
    public function gallery(string $slug): array;
    public function formSchema(string $slug): array;
    public function submitForm(string $slug, array $data): array;
    public function products(?string $categorySlug = null, int $limit = 12): array;
    public function productCategories(): array;
    public function businessUpdates(int $limit = 5): array;
    public function storefront Config(): array;
}
```

### HTTP Client Setup

Built on Laravel's `Http` facade (wraps Guzzle):

```php
Http::withToken(base64_encode($this->apiKey . ':'))
    ->timeout(5)
    ->retry(2, 500)
    ->get($this->baseUrl . '/api/v1/...');
```

Timeout: 5 seconds. Retry: 2 attempts, 500ms delay. On failure: throw `CrmConnectionException` which renders the block as a graceful error state (does not break the page).

### Factory: `CrmApiClientFactory`

Resolves the client from a `Company` model:

```php
final class CrmApiClientFactory
{
    public function forCompany(Company $company): CrmApiClient
    {
        if (! $company->hasCrmConnection()) {
            throw new NoCrmConnectionException();
        }

        return new CrmApiClient(
            baseUrl:   $company->crm_base_url,
            apiKey:    Crypt::decryptString($company->crm_api_key),
            companyId: $company->crm_company_id,
        );
    }
}
```

---

## Caching

All CRM responses are cached in Redis. The CMS does not call the CRM on every page view.

| Data | Cache key | TTL |
|------|-----------|-----|
| Gallery | `crm:{company_id}:gallery:{slug}` | 5 min |
| Products | `crm:{company_id}:products:{category}:{limit}` | 5 min |
| Product categories | `crm:{company_id}:product-categories` | 30 min |
| Form schema | `crm:{company_id}:form:{slug}` | 15 min |
| Business updates | `crm:{company_id}:updates:{limit}` | 5 min |
| Storefront config | `crm:{company_id}:storefront-config` | 30 min |

Cache is not automatically busted when CRM data changes (the CMS has no webhook receiver for CRM events in Phase 2). The TTL is the only expiry mechanism. A shop owner can manually clear the cache from Settings → CRM Integration → "Clear cached data".

---

## Block Rendering

CRM blocks are rendered server-side by `BlockRendererService`. The service fetches data via `CrmApiClient` (from cache), then renders a Blade partial.

### Error States

If `CrmApiClient` throws (network error, CRM offline, 4xx/5xx):
- Log the error
- Render the block's error partial instead of the content partial
- Do not propagate the exception to the page — the rest of the page renders normally

```
resources/views/blocks/
├── gallery.blade.php
├── gallery_error.blade.php      -- "Gallery unavailable"
├── crm_form.blade.php
├── crm_form_error.blade.php     -- "Form unavailable at this time"
├── storefront.blade.php
├── storefront_error.blade.php   -- "Products unavailable"
├── business_updates.blade.php
└── business_updates_error.blade.php
```

---

## Form Submission Proxy

When a visitor submits a `crm_form` block, the form POSTs to the CMS (not directly to the CRM). This prevents exposing the CRM API key to the browser.

```
POST /forms/crm/{company_slug}/{form_slug}
```

Controller:
1. Validate the submitted data against the form schema (fetched from cache)
2. Forward via `CrmApiClient::submitForm($slug, $data)`
3. Return JSON `{ success: true }` or `{ success: false, errors: {...} }`

The form renders a success message or inline field errors without a page reload (small JS fetch on the Blade form partial).

**Dependency:** The CRM endpoint `POST /api/v1/forms/{slug}/submit` must be implemented in inteteam_crm. Until it is, `crm_form` blocks render a "Form not available" message.

---

## Admin UI: CRM Integration Settings

```
resources/js/Pages/Admin/Settings/CrmIntegration.tsx
```

Fields:
- CRM URL (text input: `https://acme.crm.inte.team`)
- CRM Company ID (text input)
- API Key (password input — masked, reveal button)
- "Test Connection" button — calls `POST /admin/settings/crm/test` and shows success/error inline
- "Clear Cached Data" button — flushes all `crm:{company_id}:*` cache keys
- Last connection test result + timestamp

---

## Routes

```
GET   /admin/settings/crm         → Admin\CrmSettingsController::show
POST  /admin/settings/crm         → Admin\CrmSettingsController::update
POST  /admin/settings/crm/test    → Admin\CrmSettingsController::test
DELETE /admin/settings/crm/cache  → Admin\CrmSettingsController::clearCache

POST /forms/crm/{company}/{slug}  → Public\CrmFormController::submit
```

---

## CRM APIs Used

| CRM Endpoint | Method | Used For |
|-------------|--------|----------|
| `/api/v1/galleries/{slug}` | GET | `gallery` block |
| `/api/v1/storefront/{id}/config` | GET | Theme colours from CRM |
| `/api/v1/storefront/{id}/categories` | GET | Category picker in `storefront` block settings |
| `/api/v1/storefront/{id}/products` | GET | `storefront` block |
| `/api/v1/{id}/pages/{slug}` | GET | Optional: import CRM-managed static pages |
| `/api/v1/embed/{id}/updates` | GET | `business_updates` block |
| `/api/v1/forms/{slug}/submit` | POST | `crm_form` block submissions (**not yet in CRM**) |

---

## Tests

- `CrmApiClientTest` — HTTP calls, timeout handling, retry behaviour, cache hit/miss
- `CrmConnectionTest` — test connection endpoint, valid key, invalid key, unreachable host
- `CrmBlockRenderTest` — gallery/storefront/form blocks render correctly, error partials shown on failure
- `CrmFormSubmitTest` — proxy validates data, forwards to CRM, returns correct JSON response

All in `tests/Feature/CrmIntegration/`.
