# Settings Feature

**Status:** Phase 1 — ✅ Implemented

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

## Routes (`routes/admin.php`) — As Built

```
GET   /admin/settings   → Admin\SettingsController::index   (name: admin.settings.index)
POST  /admin/settings   → Admin\SettingsController::update  (name: admin.settings.update)
```

Phase 1 uses a single form/endpoint for all fields. The multi-tab split is deferred to Phase 2 when the CRM integration tab is added. Logo/favicon uploads are handled via `MediaService` in the `update` action.

---

## Controller: `Admin\SettingsController` — As Built

No separate service — the controller delegates directly to `$company->update()` and `MediaService::upload()`. The admin-only gate uses `abort_unless(auth('cms')->user()?->role === 'admin', 403)`.

---

## Admin UI (Inertia) — As Built

```
resources/js/Pages/Admin/Settings/
└── Index.tsx   -- single-page form with three card sections
```

Sections in `Index.tsx`:
1. **Branding** — Site name, brand colour (colour picker + hex input), theme select
2. **SEO Defaults** — Site name (OG), title suffix, meta description (160 char max), robots, Twitter handle, Google verification code
3. **Business Details** — street, city, postcode, phone, price range (used in LocalBusiness JSON-LD)

Logo/favicon upload fields are planned but not wired in Phase 1 (`logo_path` / `favicon_path` columns exist on the model).

Form uses `useForm` from `@inertiajs/react` and `post(route('admin.settings.update'))`.

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
