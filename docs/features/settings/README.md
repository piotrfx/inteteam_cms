# Settings Feature

**Status:** Phase 1

---

## Overview

Settings is the admin section where a shop owner configures everything about their site that is not content: branding, domain, SEO defaults, and (in Phase 2) the CRM connection.

All settings live on the `companies` table. There is no separate `settings` table — fields are explicit columns, not a JSON blob, so they are type-safe and indexable where needed.

---

## User Stories

- As a shop owner, I can upload my logo and favicon.
- As a shop owner, I can set my brand's primary colour, used in the public site theme.
- As a shop owner, I can configure my site's SEO defaults (site name, title suffix, default description, OG image).
- As a shop owner, I can enter my business address, phone, and opening hours for use in structured data.
- As a shop owner, I can configure a custom domain for my site.

---

## Settings Sections

Settings are grouped into tabs in the admin UI. Each tab maps to a dedicated form and a dedicated update endpoint.

### 1. General

- Site name
- Primary colour (hex, colour picker)
- Logo (media picker)
- Favicon (media picker, 512×512 px recommended)

### 2. SEO

- Title suffix (e.g. `| Acme Repairs`)
- Default meta description (max 160 chars)
- Default OG image (media picker)
- Twitter handle (optional)
- Google Search Console verification code (optional — outputs a `<meta name="google-site-verification">` tag)
- Default robots (select: index,follow / noindex,nofollow)

### 3. Business Info

Used to populate `LocalBusiness` JSON-LD on the public site.

- Street address
- City
- Postcode
- Phone number
- Opening hours (repeating row: days checkboxes + open/close time inputs)
- Price range (select: £ / ££ / £££)

### 4. Domain

- Current subdomain (read-only, shows `{slug}.cms.inte.team`)
- Custom domain (text input: e.g. `www.acmerepairs.co.uk`)
- Status indicator: Not configured / Pending DNS / Active

DNS setup instructions shown when custom domain is entered (expandable):
> Point your domain's CNAME record to `cms.inte.team`. Changes may take up to 48 hours to propagate.

### 5. CRM Integration

See `features/crm_integration/README.md`.

---

## Routes (`routes/admin.php`)

```
GET   /admin/settings                   → Admin\SettingsController::index
POST  /admin/settings/general           → Admin\SettingsController::updateGeneral
POST  /admin/settings/seo               → Admin\SettingsController::updateSeo
POST  /admin/settings/business          → Admin\SettingsController::updateBusiness
POST  /admin/settings/domain            → Admin\SettingsController::updateDomain
```

Each tab POSTs to its own endpoint. This keeps validation isolated and avoids one giant update form.

---

## Service: `CompanySettingsService`

```php
final class CompanySettingsService
{
    public function updateGeneral(Company $company, UpdateGeneralData $data): Company;
    public function updateSeo(Company $company, UpdateSeoData $data): Company;
    public function updateBusiness(Company $company, UpdateBusinessData $data): Company;
    public function updateDomain(Company $company, UpdateDomainData $data): Company;
}
```

`updateDomain()` validates:
- The domain is a valid hostname
- The domain is not already claimed by another company
- Sets `domain = $data->domain` (DNS verification is passive — `ResolveTenant` will start resolving it once DNS propagates)

---

## Admin UI (Inertia)

```
resources/js/Pages/Admin/Settings/
├── Index.tsx          -- tab shell, renders active tab
├── General.tsx        -- general settings form
├── Seo.tsx            -- SEO defaults form
├── Business.tsx       -- business info form with opening hours builder
├── Domain.tsx         -- domain settings
└── CrmIntegration.tsx -- Phase 2
```

Opening hours builder in `Business.tsx`: each row is a day-range selector + time picker. Days use a multi-select checkbox group (Mon, Tue, Wed...). Rows can be added/removed. Output is a JSON array:

```json
[
  { "days": ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday"], "open": "09:00", "close": "18:00" },
  { "days": ["Saturday"], "open": "10:00", "close": "16:00" }
]
```

---

## Caching

Company settings are read on every page render (for nav, SEO head, theme). They are cached:

```
Key:   cms:company:{company_id}:settings
TTL:   30 minutes
Bust:  on any settings update
```

---

## Tests

- `GeneralSettingsTest` — logo upload, favicon upload, primary colour validation
- `SeoSettingsTest` — title suffix saved, description max length
- `BusinessInfoTest` — opening hours JSON structure, address fields
- `DomainSettingsTest` — domain uniqueness, invalid hostname rejected

All in `tests/Feature/Settings/`.
