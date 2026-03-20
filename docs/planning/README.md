# inteteam_cms — Master Plan

**Repository:** `inteteam_cms` (standalone — does not share code with inteteam_crm)
**Status:** Planning
**Last Updated:** 2026-03-20

---

## What It Is

A SaaS CMS where each UK repair shop gets their own managed website. Shop owners log in, build pages and posts using a block editor, and embed live data (gallery, booking forms, storefront products) from their inteteam_crm instance. The public site is served at a unique subdomain per shop.

Think WordPress as a mental model: admin panel to create content, public-facing site that renders it, plugins (blocks) that pull external data.

---

## Domain & URL Structure

| URL | Purpose |
|-----|---------|
| `cms.inte.team` | Platform landing / login redirect |
| `{shop-slug}.cms.inte.team` | Shop's public website |
| `{shop-slug}.cms.inte.team/admin` | Shop's admin panel |
| `{custom-domain}.com` | Optional custom domain (mapped via `companies.domain`) |

Tenant is resolved from subdomain in `ResolveTenant` middleware on every request.

---

## Tech Stack

| Layer | Technology | Reason |
|-------|-----------|--------|
| Backend | Laravel 12 / PHP 8.4 | Same team conventions as inteteam_crm |
| Admin UI | React 19 + TypeScript + Inertia.js | Same component patterns as inteteam_crm |
| Public site | Laravel Blade (Phase 1) | Simple, SSR, SEO-friendly for MVP |
| Public site | Extractable to Next.js (Phase 3+) | When theming demands it |
| Database | MariaDB 11.8 | Consistent across platform |
| Cache / Queue | Redis 7 | Session, cache, job queue |
| Dev | Docker Compose | Same workflow as inteteam_crm |

**This is a fully standalone application.** It shares no code, models, or database with inteteam_crm. Integration with the CRM happens exclusively via HTTP API calls through `CrmApiClient`.

---

## Architecture

Three layers, cleanly separated:

```
┌─────────────────────────────────────┐
│           ADMIN PANEL               │
│   React + Inertia.js                │
│   Create/edit pages, posts, nav,    │
│   settings, media, SEO fields       │
└──────────────────┬──────────────────┘
                   │
         ┌─────────▼──────────┐
         │   Laravel Backend  │
         │   Controllers       │
         │   Services          │
         │   Models (CMS DB)   │
         └────────┬────────────┘
        /         │          \
       /          │           \
┌─────▼───┐  ┌───▼────┐  ┌───▼──────────────┐
│ Public  │  │ CMS DB │  │  CrmApiClient    │
│  Site   │  │MariaDB │  │  (HTTP, read-only)│
│ (Blade) │  │        │  │  gallery, forms,  │
└─────────┘  └────────┘  │  storefront, etc  │
                          └──────────────────┘
```

---

## Database Schema

### `companies`
```sql
id              ULID PK
name            VARCHAR
slug            VARCHAR UNIQUE          -- used in subdomain
domain          VARCHAR NULL UNIQUE     -- custom domain e.g. acmerepairs.co.uk
logo_path       VARCHAR NULL
favicon_path    VARCHAR NULL

-- CRM connection
crm_base_url    VARCHAR NULL            -- e.g. https://crm.inte.team
crm_company_id  VARCHAR NULL            -- CRM's company ULID
crm_api_key     VARCHAR NULL ENCRYPTED  -- API key to call CRM

-- SEO (site-level defaults)
seo_site_name               VARCHAR NULL
seo_title_suffix            VARCHAR NULL     -- appended to page titles
seo_meta_description        VARCHAR(160) NULL
seo_og_image_path           VARCHAR NULL
seo_twitter_handle          VARCHAR NULL
seo_google_verification     VARCHAR NULL
seo_robots                  ENUM('index,follow','noindex,nofollow') DEFAULT 'index,follow'

-- Local business (JSON-LD)
seo_address_street          VARCHAR NULL
seo_address_city            VARCHAR NULL
seo_address_postcode        VARCHAR NULL
seo_phone                   VARCHAR NULL
seo_opening_hours           JSON NULL        -- [{days, open, close}]
seo_price_range             ENUM('£','££','£££') NULL

-- Branding
primary_colour  VARCHAR NULL            -- hex
settings        JSON NULL               -- catch-all for future site options

is_active       BOOLEAN DEFAULT true
created_at, updated_at, deleted_at
```

### `cms_users`
```sql
id              ULID PK
company_id      FK → companies
name            VARCHAR
email           VARCHAR
password        VARCHAR                 -- bcrypt, nullable in Phase 2 (SSO)
role            ENUM('admin','editor','viewer')
sso_user_id     VARCHAR NULL            -- populated when SSO is enabled
remember_token
email_verified_at, created_at, updated_at, deleted_at
```

### `cms_pages`
```sql
id              ULID PK
company_id      FK → companies
title           VARCHAR
slug            VARCHAR
type            ENUM('home','about','contact','privacy','terms','custom')
blocks          JSON NOT NULL DEFAULT '[]'    -- ordered array of block objects
status          ENUM('draft','published')
published_at    TIMESTAMP NULL

-- SEO overrides (null = inherit site default)
seo_title           VARCHAR NULL
seo_description     VARCHAR(160) NULL
seo_og_image_path   VARCHAR NULL
seo_canonical_url   VARCHAR NULL
seo_robots          ENUM('index,follow','noindex,nofollow') NULL
seo_schema_type     ENUM('WebPage','FAQPage','ContactPage') DEFAULT 'WebPage'

created_by      FK → cms_users
created_at, updated_at, deleted_at

INDEX idx_pages_company_slug (company_id, slug)
INDEX idx_pages_company_status (company_id, status)
```

### `cms_posts`
```sql
id              ULID PK
company_id      FK → companies
author_id       FK → cms_users
title           VARCHAR
slug            VARCHAR
excerpt         TEXT NULL
blocks          JSON NOT NULL DEFAULT '[]'
status          ENUM('draft','published','scheduled')
published_at    TIMESTAMP NULL
featured_image_path VARCHAR NULL

-- SEO overrides
seo_title           VARCHAR NULL
seo_description     VARCHAR(160) NULL
seo_og_image_path   VARCHAR NULL
seo_canonical_url   VARCHAR NULL
seo_robots          ENUM('index,follow','noindex,nofollow') NULL

created_at, updated_at, deleted_at

INDEX idx_posts_company_status_published (company_id, status, published_at)
INDEX idx_posts_company_slug (company_id, slug)
```

### `cms_navigation`
```sql
id          ULID PK
company_id  FK → companies
location    ENUM('header','footer')
items       JSON NOT NULL DEFAULT '[]'   -- [{label, url, target, children:[...]}]
created_at, updated_at

UNIQUE idx_nav_company_location (company_id, location)
```

### `cms_media`
```sql
id              ULID PK
company_id      FK → companies
uploaded_by     FK → cms_users
filename        VARCHAR
path            VARCHAR
disk            ENUM('local','s3','gcs')
mime_type       VARCHAR
size_bytes      BIGINT
width           INT NULL
height          INT NULL
alt_text        VARCHAR NULL
caption         VARCHAR NULL
created_at, updated_at, deleted_at

INDEX idx_media_company_created (company_id, created_at)
```

---

## Block Types

Blocks are stored as a JSON array on `cms_pages.blocks` and `cms_posts.blocks`. There is no separate blocks table and no CmsBlock model. A block is `{ "id": "uuid", "type": "...", "data": {...} }`.

| Type | Source | `data` shape |
|------|--------|-------------|
| `rich_text` | Local | `{ content: string }` (HTML from editor) |
| `image` | Local media | `{ media_id, alt, caption, size }` |
| `heading` | Local | `{ text, level }` (h2–h4) |
| `cta` | Local | `{ heading, body, button_text, button_url, style }` |
| `divider` | Local | `{}` |
| `raw_html` | Local | `{ html }` (admin-only, not shown to editors) |
| `gallery` | CRM API | `{ gallery_slug, layout, columns }` |
| `crm_form` | CRM API | `{ form_slug, title, submit_label }` |
| `storefront` | CRM API | `{ category_slug, limit, show_prices, layout }` |
| `business_updates` | CRM API | `{ limit, show_date }` |

CRM blocks are rendered server-side at page request time: `CrmApiClient` fetches data, result is cached in Redis (5 min TTL), Blade renders the block. No client-side CORS calls from public visitors.

---

## Feature List

| Feature | Phase | Docs |
|---------|-------|------|
| Multi-tenancy (subdomain routing) | 1 | `features/tenancy/` |
| Authentication — local | 1 | `features/auth/` |
| Site settings & branding | 1 | `features/settings/` |
| Media uploads | 1 | `features/media/` |
| Page builder (block editor) | 1 | `features/pages/` |
| Blog posts | 1 | `features/posts/` |
| Navigation menus | 1 | `features/navigation/` |
| Public site (Blade themes) | 1 | `features/theming/` |
| SEO metadata, sitemap, robots.txt | 1 | `features/seo/` |
| CRM integration (gallery, forms, storefront) | 2 | `features/crm_integration/` |
| Authentication — SSO | 3 | `features/auth/` |

---

## Build Phases

### Phase 1 — Core CMS (No CRM, No SSO)

A shop owner can log in, manage their site, build pages/posts with local blocks, and publish a working public website with correct SEO output.

- Docker Compose dev environment (PHP-FPM, Nginx, MariaDB, Redis)
- `ResolveTenant` middleware (subdomain → company)
- Local email/password auth
- Admin panel: company settings, media, pages, posts, navigation
- Block editor (rich_text, image, heading, cta, divider)
- Blade public site with a default theme
- SEO: per-page meta, OG, Twitter Card, JSON-LD, sitemap.xml, robots.txt
- PHPUnit feature test suite

### Phase 2 — CRM Integration

Shop owners can embed live CRM data into their pages.

- `CrmApiClient` HTTP service
- Per-company CRM connection settings (base URL + API key)
- Block types: `gallery`, `crm_form`, `storefront`, `business_updates`
- Redis caching of CRM responses (5 min TTL)
- Admin: block picker shows CRM blocks only when CRM is connected

### Phase 3 — SSO

One login for CRM + CMS.

- `SsoService` (OAuth2 Authorization Code + PKCE)
- `SsoController` (redirect → callback)
- `HandleSsoToken` middleware (auto-refresh)
- Register inteteam_cms as OAuth client in inteteam_sso admin
- `sso_user_id` column on `cms_users` — matched on first SSO login by email
- Subscription gating from token `subscriptions.cms` claim

### Phase 4 — Next.js Public Site (Optional)

Extract Blade public rendering to a standalone Next.js site consuming a new headless API layer. Adds SSG/ISR for performance and enables community themes.

---

## CRM Integration Points

All reads. inteteam_cms never writes to the CRM database.

| CRM API Endpoint | Used For |
|-----------------|----------|
| `GET /api/v1/galleries/{slug}` | `gallery` block |
| `GET /api/v1/{company}/pages/{slug}` | Import CRM static pages |
| `POST /api/v1/forms/{slug}/submit` | `crm_form` block submissions |
| `GET /api/v1/storefront/{company}/products` | `storefront` block |
| `GET /api/v1/storefront/{company}/config` | Store colours/branding |
| `GET /api/v1/embed/{company}/updates` | `business_updates` block |

The `crm_form` block submit endpoint (`POST /api/v1/forms/{slug}/submit`) is **planned but not yet implemented in inteteam_crm** — needs to land there before Phase 2 form embeds work.

---

## SSO Integration Path

inteteam_sso already has everything needed. No changes required on the SSO side.

Steps when Phase 3 begins:
1. Register inteteam_cms as OAuth client in SSO admin panel (`/admin/clients`)
   - Redirect URI: `https://cms.inte.team/auth/sso/callback`
   - Logout URL: `https://cms.inte.team/auth/sso/logout`
2. Set in `.env`:
   ```
   SSO_ENABLED=true
   SSO_URL=https://sso.inte.team
   SSO_CLIENT_ID=<from step 1>
   SSO_CLIENT_SECRET=<from step 1>
   SSO_REDIRECT_URI=https://cms.inte.team/auth/sso/callback
   ```
3. Add `sso_user_id` (nullable) migration to `cms_users`
4. On first SSO login: match user by email, set `sso_user_id`, retire local password

Token claims to read:
- `company_id` — tenant context
- `subscriptions.cms` — tier gating (e.g. `"standard"` or `"pro"`)
- `role` — user role within their company

---

## Code Conventions

Same as inteteam_crm:
- `declare(strict_types=1);` on all PHP files
- PHP 8 constructor property promotion
- Explicit return types on all methods
- ULIDs for all primary keys (`HasUlids` trait)
- `HasCompanyScope` trait on all tenant-scoped models
- Controllers: thin orchestrators, delegate to services
- Services: business logic, `final` classes
- Form Requests: array syntax for validation rules
- PHPUnit for all tests (no Pest)
- PHPStan Level 9
- Laravel Pint formatting
- Tailwind v4 (CSS-first `@theme`)
- Dark mode via `dark:` classes

### Multi-Tenancy Pattern

```php
use App\Models\Concerns\HasCompanyScope;

class CmsPage extends Model
{
    use HasUlids, HasCompanyScope, SoftDeletes;
}
```

`HasCompanyScope` auto-filters all queries by `company_id` and auto-sets `company_id` on creation. Same implementation as inteteam_crm — but copied into this repo, not imported from it.

---

## Dev URLs

| Service | URL |
|---------|-----|
| Application | http://localhost:8090 (avoid conflict with CRM on :80) |
| Vite HMR | http://localhost:5190 |
| phpMyAdmin | http://localhost:8091 |
| Mailpit | http://localhost:8029 |
