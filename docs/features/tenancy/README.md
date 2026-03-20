# Tenancy Feature

**Status:** Phase 1

---

## Overview

Each repair shop is a **company** (tenant). A company has a unique slug that becomes their subdomain: `{slug}.cms.inte.team`. Every database table scoped to a company uses `company_id` and the `HasCompanyScope` trait to automatically filter queries.

This is not a package-based tenancy solution. It is the same hand-rolled pattern used in inteteam_crm, copied into this repo.

---

## User Stories

- As a visitor, navigating to `acme.cms.inte.team` shows Acme's public site, not another shop's.
- As a shop owner, data I create in the admin panel is invisible to users from other companies.
- As a shop owner, I can configure a custom domain and my site will be served at that domain.

---

## Tenant Resolution

`ResolveTenant` middleware runs on **every request** (both admin and public site).

Resolution order:
1. Check `Host` header for a matching `companies.domain` (custom domain)
2. Extract subdomain from `Host` header, match against `companies.slug`
3. If no match: return 404

On match:
- Set `app()->instance('current_company', $company)` (bound into the service container)
- Store `$company->id` in the request (available as `request()->company_id` or via helper)

```php
// Anywhere in the app after middleware runs:
$company = app('current_company');
```

The middleware is registered globally and runs before auth.

---

## HasCompanyScope Trait

Copy from inteteam_crm. Applied to every tenant-scoped model.

```php
trait HasCompanyScope
{
    protected static function bootHasCompanyScope(): void
    {
        static::addGlobalScope(new CompanyScope());

        static::creating(function (Model $model): void {
            if (! $model->company_id) {
                $model->company_id = app('current_company')->id;
            }
        });
    }

    public function scopeForCompany(Builder $query, string $companyId): Builder
    {
        return $query->withoutGlobalScope(CompanyScope::class)
                     ->where('company_id', $companyId);
    }
}
```

`CompanyScope` adds `WHERE company_id = ?` to all queries on models using the trait.

---

## Database

### `companies` table

See full schema in `docs/planning/README.md`.

Key columns for tenancy:

```sql
slug    VARCHAR UNIQUE   -- 'acme-repairs' → acme-repairs.cms.inte.team
domain  VARCHAR NULL UNIQUE  -- 'www.acmerepairs.co.uk'
```

### Slug Rules

- Lowercase alphanumeric + hyphens
- No consecutive hyphens
- No leading/trailing hyphens
- Min 3 chars, max 40 chars
- Reserved slugs blocked: `www`, `api`, `admin`, `static`, `cms`, `mail`, `help`

---

## Custom Domains

Custom domains are stored on `companies.domain`. When set:
- DNS must point the domain to the CMS server (shop owner's responsibility, with setup guide)
- SSL via Let's Encrypt wildcard cert for `*.cms.inte.team` + per-domain certs for custom domains
- Nginx config auto-generated or uses `server_name _` catch-all that delegates to `ResolveTenant`

No separate domain verification step in Phase 1 — trust the DNS lookup. Phase 2+ can add a TXT record verification step.

---

## Routes

Tenant context is assumed on all routes. There is no route prefix for the company — the subdomain handles it.

```
# Public site — resolved by subdomain
GET /           → public home page
GET /blog       → post index
GET /blog/{slug} → single post
GET /{slug}     → static page

# Admin — also resolved by subdomain
GET /admin      → admin dashboard
GET /admin/pages → pages index
...
```

---

## Tests

- `TenantResolutionTest` — slug match, domain match, unknown subdomain returns 404
- `CompanyScopeTest` — company A cannot read company B's pages, posts, or media
- `CustomDomainTest` — request with custom domain host header resolves correct company

All in `tests/Feature/Tenancy/`.
