# Pages Feature

**Status:** Phase 1

---

## Overview

Static pages are the core content unit of a shop's website. Each company can have multiple pages. Pages are composed of ordered blocks stored as a JSON column. There is no separate blocks table — see `features/blocks/` for the block type definitions.

---

## User Stories

- As a shop owner, I can create a new page with a title, slug, and type.
- As a shop owner, I can add, reorder, and remove blocks on a page using the block editor.
- As a shop owner, I can save a page as draft and preview it before publishing.
- As a shop owner, I can publish a page so it appears on the public site.
- As a shop owner, I can set SEO fields (title, description, OG image) on each page.
- As a visitor, I can navigate to `/{slug}` and see the published page rendered with my shop's theme.

---

## Model: `CmsPage`

```php
final class CmsPage extends Model
{
    use HasUlids, HasCompanyScope, SoftDeletes;

    protected function casts(): array
    {
        return [
            'blocks'       => 'array',
            'published_at' => 'datetime',
        ];
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')
                     ->where('published_at', '<=', now());
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(CmsUser::class, 'created_by');
    }
}
```

### Page Types

| Type | Slug behaviour |
|------|---------------|
| `home` | Served at `/` (no slug in URL) |
| `about` | Served at `/about` (slug locked to `about`) |
| `contact` | Served at `/contact` (slug locked to `contact`) |
| `privacy` | Served at `/privacy-policy` |
| `terms` | Served at `/terms-and-conditions` |
| `custom` | Served at `/{slug}` (fully editable) |

Only one page per type is allowed per company (enforced at the service layer). A company cannot have two `home` pages.

---

## Routes

### Admin (`routes/admin.php`)

```
GET    /admin/pages              → Admin\PageController::index
GET    /admin/pages/create       → Admin\PageController::create
POST   /admin/pages              → Admin\PageController::store
GET    /admin/pages/{page}       → Admin\PageController::show
GET    /admin/pages/{page}/edit  → Admin\PageController::edit
POST   /admin/pages/{page}       → Admin\PageController::update  (POST not PUT — file uploads)
DELETE /admin/pages/{page}       → Admin\PageController::destroy
POST   /admin/pages/{page}/publish   → Admin\PageController::publish
POST   /admin/pages/{page}/unpublish → Admin\PageController::unpublish
```

### Public (`routes/web.php`)

```
GET /             → Public\PageController::home
GET /{slug}       → Public\PageController::show
```

Public routes are matched last (after `/blog`, `/admin`, etc.) to avoid conflicts.

---

## Service: `PageService`

```php
final class PageService
{
    public function create(CreatePageData $data): CmsPage;
    public function update(CmsPage $page, UpdatePageData $data): CmsPage;
    public function publish(CmsPage $page): CmsPage;
    public function unpublish(CmsPage $page): CmsPage;
    public function delete(CmsPage $page): void;
    public function findBySlug(string $companyId, string $slug): ?CmsPage;
    public function findHome(string $companyId): ?CmsPage;
}
```

`publish()` sets `status = 'published'` and `published_at = now()` (if not already set). It also flushes the page cache.

`create()` validates that only one page of each fixed type exists per company.

---

## DTOs

```php
final readonly class CreatePageData
{
    public function __construct(
        public string  $title,
        public string  $slug,
        public string  $type,
        public array   $blocks,
        public string  $status,
        public ?string $seoTitle,
        public ?string $seoDescription,
        public ?string $seoOgImagePath,
        public ?string $seoRobots,
        public ?string $seoSchemaType,
    ) {}
}
```

---

## Admin UI Pages (Inertia)

```
resources/js/Pages/Admin/Pages/
├── Index.tsx      -- table of all pages, status badge, publish toggle
├── Create.tsx     -- form: title, type, slug (auto-generated from title)
└── Edit.tsx       -- block editor + SEO panel side-by-side
```

`Edit.tsx` is the main interface. Layout:
- Left: block editor (add/reorder/remove blocks, edit each block inline)
- Right panel: Page settings (slug, status, SEO fields)
- Top bar: Save Draft / Publish buttons

Block editor interaction: clicking "Add Block" opens a block-type picker. Each block has an inline edit form. Blocks are reordered via drag handles. State is managed locally and submitted as JSON on save.

---

## Caching

Published pages are cached in Redis:

```
Key:   cms:page:{company_id}:{slug}
TTL:   5 minutes
Bust:  on publish, unpublish, update, delete
```

Cache is flushed by `PageCacheObserver` on model events. Public controller reads from cache first; on miss, fetches from DB and warms the cache.

---

## Slug Generation

- Auto-generated from title on creation: `"About Us"` → `about-us`
- Validated: unique within company, lowercase, alphanumeric + hyphens
- For fixed-type pages (`about`, `contact`, etc.), slug is set automatically and the field is hidden in the UI

---

## Authorization (Policy)

```php
final class CmsPagePolicy
{
    public function viewAny(CmsUser $user): bool   → role: any
    public function create(CmsUser $user): bool    → role: admin or editor
    public function update(CmsUser $user): bool    → role: admin or editor
    public function publish(CmsUser $user): bool   → role: admin
    public function delete(CmsUser $user): bool    → role: admin
}
```

---

## Tests

- `PageCrudTest` — create, update, delete, slug uniqueness enforcement
- `PagePublishTest` — publish, unpublish, duplicate type enforcement
- `PagePublicTest` — published pages appear at correct URL, drafts return 404
- `PageSeoTest` — SEO fields render correctly in public `<head>`

All in `tests/Feature/Pages/`.
