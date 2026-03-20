# Navigation Feature

**Status:** Phase 1

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
GET   /admin/navigation          → Admin\NavigationController::index
POST  /admin/navigation/header   → Admin\NavigationController::updateHeader
POST  /admin/navigation/footer   → Admin\NavigationController::updateFooter
```

Both `updateHeader` and `updateFooter` receive the full `items` array and replace the existing record (upsert). Single-operation save — no per-item CRUD.

---

## Service: `NavigationService`

```php
final class NavigationService
{
    public function getHeader(string $companyId): array;
    public function getFooter(string $companyId): array;
    public function saveHeader(string $companyId, array $items): CmsNavigation;
    public function saveFooter(string $companyId, array $items): CmsNavigation;
}
```

`save*` methods validate the items array (depth, count, URL format) then upsert.

---

## Admin UI (Inertia)

```
resources/js/Pages/Admin/Navigation/
└── Index.tsx   -- two tabs: Header / Footer, each with a drag-drop menu builder
```

Menu builder:
- List of current items with drag handles and edit/delete icons
- "Add item" button opens an inline form: label, URL, target (checkbox for new tab)
- Drag to reorder and drag into a top-level item to nest (one level only)
- Save button submits the full items array

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
