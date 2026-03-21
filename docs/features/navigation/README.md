# Navigation Feature

**Status:** Phase 1 — ✅ Implemented

---

## Overview

Each company can configure a header menu and a footer menu. Menus are stored as a JSON tree on the `cms_navigation` table. The public site reads them on every page render (from cache). The admin panel provides a visual menu editor.

---

## User Stories

- As a shop owner, I can add links to my header navigation (internal pages or external URLs).
- As a shop owner, I can add a second level of links (dropdown) under a top-level item.
- As a shop owner, I can reorder navigation items by dragging.
- As a shop owner, I can configure a separate footer navigation with grouped link columns.

---

## Model: `CmsNavigation`

```php
final class CmsNavigation extends Model
{
    use HasUlids, HasCompanyScope;

    protected function casts(): array
    {
        return ['items' => 'array'];
    }

    public function scopeHeader(Builder $query): Builder
    {
        return $query->where('location', 'header');
    }

    public function scopeFooter(Builder $query): Builder
    {
        return $query->where('location', 'footer');
    }
}
```

One row per location per company. If no row exists, the nav renders empty (no error).

---

## Items JSON Structure

Each item in the `items` array:

```json
{
  "id": "01jq...",
  "label": "About Us",
  "url": "/about",
  "target": "_self",
  "children": [
    {
      "id": "01jr...",
      "label": "Our Team",
      "url": "/about/team",
      "target": "_self",
      "children": []
    }
  ]
}
```

- `url`: internal (`/about`) or external (`https://...`)
- `target`: `_self` or `_blank`
- `children`: only one level deep (no deeply nested menus)
- Max 8 top-level items, max 6 children per item

---

## Routes (`routes/admin.php`)

```
GET   /admin/navigation   → Admin\NavigationController::index   (name: admin.navigation.index)
POST  /admin/navigation   → Admin\NavigationController::update  (name: admin.navigation.update)
```

The `update` action receives `{location: 'header'|'footer', items: NavItem[]}` and upserts that location. One POST per menu; no per-item CRUD.

---

## Service: `NavigationService`

```php
final class NavigationService
{
    public function get(string $companyId, string $location): array;
    public function save(string $companyId, string $location, array $items): CmsNavigation;
}
```

`save()` upserts the row by `(company_id, location)`. Phase 1 — no depth/count validation yet.

---

## Admin UI (Inertia)

```
resources/js/Pages/Admin/Navigation/
└── Index.tsx   -- two NavEditor panels (header + footer), each independently saveable
```

`NavEditor` component:
- Flat item list (no nested children in Phase 1)
- Each item: Label input, URL input, Same/New tab select, ✕ remove
- ▲/▼ buttons to reorder
- "+ Add link" appends a blank item
- "Save" button in each panel header calls `router.post(route('admin.navigation.update'), {location, items})`

---

## Public Site Rendering

Header and footer navigation are fetched at the controller level via `NavigationService` and passed to the Blade layout. All public pages share the same layout, so this is fetched once per request.

Cached:
```
Key:   cms:nav:header:{company_id}
Key:   cms:nav:footer:{company_id}
TTL:   10 minutes
Bust:  on save
```

---

## Tests

- `NavigationCrudTest` — save header, save footer, validate max depth, validate max items
- `NavigationPublicTest` — nav items render in public layout, external links have `target="_blank"`

All in `tests/Feature/Navigation/`.
