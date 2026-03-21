# Feature: Page Builder

**Phase:** 1
**Status:** ✅ Done
**Tests:** `tests/Feature/Pages/PageCrudTest.php` (15), `PagePublishTest.php` (6)

---

## What It Does

Admins and editors create, edit, and delete pages. Each page has a type (home, about, contact, privacy, terms, custom), a slug, a status (draft/published), a blocks JSON array, and SEO fields. Fixed-type pages have locked slugs. Only one page per fixed type is allowed per company.

---

## Routes

| Method | URI | Name | Policy |
|--------|-----|------|--------|
| GET | `/admin/pages` | `admin.pages.index` | viewAny |
| GET | `/admin/pages/create` | `admin.pages.create` | create |
| POST | `/admin/pages` | `admin.pages.store` | create |
| GET | `/admin/pages/{page}/edit` | `admin.pages.edit` | update |
| PUT/POST | `/admin/pages/{page}` | `admin.pages.update` | update |
| DELETE | `/admin/pages/{page}` | `admin.pages.destroy` | delete |
| POST | `/admin/pages/{page}/publish` | `admin.pages.publish` | publish |
| POST | `/admin/pages/{page}/unpublish` | `admin.pages.unpublish` | publish |

---

## Key Files

| File | Purpose |
|------|---------|
| `app/Services/PageService.php` | CRUD, publish, cache bust, findBySlug, findHome |
| `app/Models/CmsPage.php` | `HasUlids`, `HasCompanyScope`, `SoftDeletes` |
| `app/Policies/CmsPagePolicy.php` | publish/delete: admin only; create/update: admin+editor |
| `app/Http/Controllers/Admin/PageController.php` | Thin controller |
| `app/Http/Requests/Admin/StorePageRequest.php` | slug regex, type enum, status |
| `app/Http/Requests/Admin/UpdatePageRequest.php` | Same minus type |
| `app/DTO/CreatePageData.php` / `UpdatePageData.php` | `fromRequest()` DTOs |
| `resources/js/Pages/Admin/Pages/Index.tsx` | Table with badges + actions |
| `resources/js/Pages/Admin/Pages/Create.tsx` | Type selector, slug auto-fill, block editor, SEO |
| `resources/js/Pages/Admin/Pages/Edit.tsx` | Pre-populated form + danger zone |
| `resources/js/Components/BlockEditor/BlockEditor.tsx` | Block picker + reorder |
| `resources/js/Components/BlockEditor/BlockItem.tsx` | Per-block field editors |
| `resources/js/Components/SeoFields.tsx` | Collapsible SEO panel (reused on posts) |

---

## Page Types & Slug Rules

| Type | Forced slug | One per company |
|------|------------|-----------------|
| `home` | `home` | ✅ |
| `about` | `about` | ✅ |
| `contact` | `contact` | ✅ |
| `privacy` | `privacy-policy` | ✅ |
| `terms` | `terms-and-conditions` | ✅ |
| `custom` | User-supplied (slugified) | ❌ |

---

## Block Data Shapes (Phase 1 local blocks)

```json
{ "id": "uuid", "type": "heading",   "data": { "text": "...", "level": 2 } }
{ "id": "uuid", "type": "rich_text", "data": { "html": "<p>...</p>" } }
{ "id": "uuid", "type": "image",     "data": { "src": "/...", "alt": "...", "caption": "" } }
{ "id": "uuid", "type": "cta",       "data": { "text": "...", "url": "...", "style": "primary" } }
{ "id": "uuid", "type": "divider",   "data": {} }
```

Block IDs are generated with `crypto.randomUUID()` on the frontend.

---

## Cache

`PageService::bustCache()` → `Cache::forget("cms:page:{company_id}:{slug}")` on every write. Read-side caching (Redis, TTL TBD) will be added in the public site feature.

---

## Multi-tenancy

`CmsPage` uses `HasCompanyScope`. Cross-company access returns 404.
