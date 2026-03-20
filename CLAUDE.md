# CLAUDE.md

This file provides guidance to Claude Code when working in this repository.
**This file overrides all other documentation.**

## Session Setup

```bash
git config user.name "piotrfx" && git config user.email "shopscot@gmail.com"
```

## Project Overview

inteteam_cms is a standalone SaaS CMS for UK repair shops. Each shop gets a website at `{slug}.cms.inte.team`. WordPress-like admin panel, block editor, public Blade-rendered site, AI editing via MCP. **Shares no code with inteteam_crm** — CRM data is read-only via HTTP (`CrmApiClient`).

Read `.sop.md` at the start of every session.

---

## Dev Commands

```bash
# Start / stop
docker compose --profile dev up -d
docker compose --profile dev down

# Tests (always before committing)
docker compose exec php-fpm php artisan test
docker compose exec php-fpm php artisan test --filter=FeatureName

# Code quality
docker compose exec php-fpm ./vendor/bin/pint --dirty
docker compose exec php-fpm ./vendor/bin/phpstan analyse --memory-limit=512M

# Frontend
docker compose run --rm npm run build
docker compose run --rm npm run dev

# Database
docker compose exec php-fpm php artisan migrate
docker compose exec php-fpm php artisan tinker
```

### Dev URLs

| Service | URL |
|---------|-----|
| Application | http://localhost:8090 |
| Vite HMR | http://localhost:5190 |
| phpMyAdmin | http://localhost:8091 |
| Mailpit | http://localhost:8029 |

### Local subdomain dev

`{slug}.cms.inte.team` won't resolve to localhost automatically. Add entries to `/etc/hosts` for any slugs you're testing:

```
127.0.0.1  acme.cms.inte.team
127.0.0.1  cms.inte.team
```

---

## Architecture

### Multi-Tenancy

All tenant-scoped models use `HasCompanyScope` trait:
- Applies `CompanyScope` global scope (auto `WHERE company_id = ?`)
- Auto-sets `company_id` on creation from `app('current_company')`
- `ResolveTenant` middleware sets `app('current_company')` from subdomain on every request

**`app('current_company')` is only available during HTTP requests.** In artisan commands, bind the company manually before using tenant-scoped models.

```php
use App\Models\Concerns\HasCompanyScope;

class CmsPage extends Model
{
    use HasUlids, HasCompanyScope, SoftDeletes;
}
```

### Auth Guard

The authenticatable model is `CmsUser`, not `User`. The guard is `cms`, not `web`.

```php
auth('cms')->user()          // correct
auth()->user()               // wrong — uses web guard, returns null
Auth::guard('cms')->check()  // correct
```

### Layers

- **Controllers** (`app/Http/Controllers/`) — thin: authorize, delegate, return. No business logic.
- **Services** (`app/Services/`) — all business logic. `final` class. No `auth()` or `request()` calls.
- **DTOs** (`app/DTO/`) — `readonly` classes with `fromRequest()` named constructor.
- **Policies** (`app/Policies/`) — registered via `#[UsePolicy]` on the model.
- **Form Requests** (`app/Http/Requests/`) — `authorize(): true`, array-syntax rules only.

Full pattern with examples: `docs/architecture/controllers.md`

### Authorization Pattern

```php
// Correct
abort_unless(auth('cms')->user()->can('update', $page), 403);

// Wrong — uses web guard
$this->authorize('update', $page);
```

### Block Types

Blocks are JSON on `cms_pages.blocks` and `cms_posts.blocks`. No separate table.
New block types register via `BlockTypeRegistry::register()` in `AppServiceProvider::boot()`. Never a hardcoded enum.

### Themes

Public site templates live in `resources/views/themes/{theme}/`. Active theme from `companies.theme`. `BlockRendererService` falls back to `default` theme if a partial doesn't exist in the active theme.

### Revisions & Staging

AI (MCP) and human edits go to `staged_revision_id` — never directly to live. Publishing calls `RevisionService::publishStaged()` which copies blocks to `cms_pages.blocks` and sets `live_revision_id`. Preview URLs: `/preview/{token}` (48h TTL, no auth).

### MCP Server

`POST /mcp/v1` — JSON-RPC 2.0. Auth via `cms_mcp` guard (token hash). Write tools always stage. `publish_staged` requires `publish` permission (not granted by default).

---

## Code Conventions

### PHP

- `declare(strict_types=1);` on all files
- PHP 8 constructor property promotion
- Explicit return types on all methods
- `HasUlids` on all models (ULID primary keys)
- `HasCompanyScope` on all tenant-scoped models
- `casts()` method — not `$casts` property
- Form request rules: array syntax — **never** pipe strings
- Services: `final` class, <250 lines
- Controllers: <150 lines

### Authorization

```php
// viewAny / create — no model instance
abort_unless(auth('cms')->user()->can('viewAny', CmsPage::class), 403);

// update / delete / publish — model instance
abort_unless(auth('cms')->user()->can('update', $page), 403);
```

### Flash Messages

```php
->with(['alert' => 'Page saved.', 'type' => 'success'])
->with(['alert' => 'Something went wrong.', 'type' => 'error'])
```

### File Upload Routes

```php
// Backend — accept POST for multipart
Route::match(['PUT', 'POST'], '/admin/pages/{page}', [PageController::class, 'update']);

// Frontend — always use post(), never put()
router.post(route('admin.pages.update', page.id), form)
```

### Testing

- PHPUnit only — `final class XTest extends TestCase`
- `use RefreshDatabase`
- Never Pest
- Run by feature: `php artisan test --filter=PageCrudTest`
- Test multi-tenant isolation: company A cannot read company B's records

### Frontend

- TypeScript strict mode
- All props interfaces defined
- Tailwind v4 (CSS-first `@theme`) — admin only, not public site
- Dark mode via `dark:` classes
- Inertia v2: `router.post()` for forms, `router.get()` for navigation

---

## Key Services

| Service | Purpose |
|---------|---------|
| `PageService` | Page CRUD, publish, cache bust |
| `PostService` | Post CRUD, scheduling |
| `RevisionService` | Stage, publish, discard, rollback, history |
| `PreviewTokenService` | Generate + validate preview tokens |
| `BlockRendererService` | Render blocks array → HTML via Blade partials |
| `BlockTypeRegistry` | Register + resolve block types |
| `SeoMetaService` | Resolve SEO fields, emit JSON-LD, sitemap entries |
| `CrmApiClient` | HTTP client for inteteam_crm API (read-only, cached) |
| `NavigationService` | Header/footer menu CRUD |
| `MediaService` | Upload, thumbnail, SVG sanitise, delete |
| `TenantResolverService` | Subdomain + domain → Company lookup |
| `McpToolRegistry` | Register + resolve MCP tools |

---

## Documentation

- **SOP**: `.sop.md` — read at the start of every session
- **Master plan**: `docs/planning/README.md` — DB schema, phases, block types
- **Controller pattern**: `docs/architecture/controllers.md` — full examples
- **Features**: `docs/features/` — one folder per feature
