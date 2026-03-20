# Blocks Feature

**Status:** Phase 1 (local blocks) → Phase 2 (CRM integration blocks)

---

## Overview

Blocks are the content units that make up a page or post. They are stored as an ordered JSON array in `cms_pages.blocks` and `cms_posts.blocks`. There is no separate database table and no `CmsBlock` model. A block has no independent existence — it belongs entirely to its parent.

This is the same approach used by Gutenberg (WordPress), Notion, and Sanity. It is the right call because:
- Blocks are never queried independently
- Ordering is trivially managed via array index
- No join is needed to load a page with its content
- Adding new block types requires no schema changes

---

## Block Structure (JSON)

Every block is an object with three guaranteed keys:

```json
{
  "id": "01jq4x2kpf0000000000000000",
  "type": "rich_text",
  "data": { ... }
}
```

`id` is a client-generated ULID assigned when the block is created in the editor. It is used as the React `key` prop and for identifying blocks during drag-reorder operations.

---

## Phase 1 — Local Block Types

### `rich_text`

Arbitrary HTML from a rich text editor (e.g. TipTap or Quill).

```json
{
  "type": "rich_text",
  "data": {
    "content": "<p>We repair all major brands...</p>"
  }
}
```

Rendered: raw HTML, sanitised server-side with an allow-list (strip `<script>`, `<iframe>`, on* attributes) before output.

---

### `heading`

Standalone headings outside rich text blocks. Useful for clear section breaks.

```json
{
  "type": "heading",
  "data": {
    "text": "Why Choose Us",
    "level": 2
  }
}
```

`level` is 2–4 (h2–h4). h1 is reserved for the page title.

---

### `image`

A single image from the media library.

```json
{
  "type": "image",
  "data": {
    "media_id": "01jq4x...",
    "alt": "Technician repairing a screen",
    "caption": "Our workshop",
    "size": "full"
  }
}
```

`size`: `full` (100%), `wide` (breakout), `medium` (60%), `small` (40%).

Rendered using the `cms_media` record to get the URL. If the media record is deleted, the block renders a placeholder.

---

### `cta`

Call-to-action section with heading, body text, and a button.

```json
{
  "type": "cta",
  "data": {
    "heading": "Book a Repair Today",
    "body": "Fast turnaround. No fix, no fee.",
    "button_text": "Book Now",
    "button_url": "/contact",
    "style": "primary"
  }
}
```

`style`: `primary` (filled), `outline`, `minimal`.

---

### `divider`

A horizontal rule for visual separation.

```json
{
  "type": "divider",
  "data": {}
}
```

---

### `raw_html`

Arbitrary HTML for admin users only. Not available to editors. Useful for embedding third-party widgets (chat, booking scripts, etc.).

```json
{
  "type": "raw_html",
  "data": {
    "html": "<script>...</script>"
  }
}
```

This block type is only shown in the block picker for users with `admin` role. The HTML is output as-is with no sanitisation (admin trusts themselves).

---

## Phase 2 — CRM Integration Block Types

These blocks pull live data from the connected inteteam_crm instance via `CrmApiClient`. They are only shown in the block picker when the company has a CRM connection configured.

### `gallery`

Renders a photo gallery from the CRM.

```json
{
  "type": "gallery",
  "data": {
    "gallery_slug": "workshop-photos",
    "layout": "grid",
    "columns": 3
  }
}
```

`layout`: `grid`, `masonry`, `carousel`.

Rendered server-side: `CrmApiClient::gallery($slug)` → cached 5 min → Blade partial.

---

### `crm_form`

Renders a form from the CRM's form builder. The form is displayed in the public page; submission POSTs to the CRM API.

```json
{
  "type": "crm_form",
  "data": {
    "form_slug": "contact-us",
    "title": "Get in Touch",
    "submit_label": "Send Message"
  }
}
```

Rendered server-side: `CrmApiClient::formSchema($slug)` fetches the field definitions. The form HTML is rendered by a Blade partial. Submission is handled via a small JS fetch POST to `CrmApiClient::submitForm()` (CMS proxies the request to avoid exposing the CRM API key to the browser).

**Note:** The CRM's `POST /api/v1/forms/{slug}/submit` endpoint is planned but not yet implemented in inteteam_crm. This block will be disabled until that endpoint exists.

---

### `storefront`

Embeds a product grid from the CRM storefront.

```json
{
  "type": "storefront",
  "data": {
    "category_slug": "phone-screens",
    "limit": 6,
    "show_prices": true,
    "layout": "grid"
  }
}
```

Rendered server-side: `CrmApiClient::products($category, $limit)` → cached 5 min → Blade grid.

---

### `business_updates`

A list of recent business updates from the CRM (news, notices, promotions).

```json
{
  "type": "business_updates",
  "data": {
    "limit": 3,
    "show_date": true
  }
}
```

---

## Backend: Block Rendering

Block rendering lives in `BlockRendererService`. It receives the blocks array from a page/post and returns rendered HTML (as a collection of Blade partials).

```php
final class BlockRendererService
{
    public function renderAll(array $blocks, Company $company): string;
    public function renderBlock(array $block, Company $company): string;
}
```

Each block type maps to a Blade partial:

```
resources/views/blocks/
├── rich_text.blade.php
├── heading.blade.php
├── image.blade.php
├── cta.blade.php
├── divider.blade.php
├── raw_html.blade.php
├── gallery.blade.php        (Phase 2)
├── crm_form.blade.php       (Phase 2)
├── storefront.blade.php     (Phase 2)
└── business_updates.blade.php (Phase 2)
```

Unknown block types are silently skipped (renders nothing) — this prevents errors if a block type is removed in a future version.

---

## Frontend: Block Editor (React)

```
resources/js/Components/BlockEditor/
├── BlockEditor.tsx          -- root component, receives blocks[], emits onChange
├── BlockList.tsx            -- renders blocks with drag handles
├── BlockPicker.tsx          -- modal: choose block type to add
├── blocks/
│   ├── RichTextBlock.tsx
│   ├── HeadingBlock.tsx
│   ├── ImageBlock.tsx
│   ├── CtaBlock.tsx
│   ├── DividerBlock.tsx
│   ├── RawHtmlBlock.tsx
│   ├── GalleryBlock.tsx          (Phase 2)
│   ├── CrmFormBlock.tsx          (Phase 2)
│   ├── StorefrontBlock.tsx       (Phase 2)
│   └── BusinessUpdatesBlock.tsx  (Phase 2)
```

`BlockEditor` is a controlled component: it receives `blocks: Block[]` and fires `onChange(blocks: Block[])`. The parent page (Edit.tsx) holds state and includes blocks in the form submission.

Reordering: drag-and-drop via `@dnd-kit/core` (lightweight, accessible). Each block has an up/down arrow fallback for keyboard users.

---

## Block Type Registry

Block types are never defined in a hardcoded enum. They register themselves via `BlockTypeRegistry` at boot time. This means adding a new block type never requires editing existing files — only adding new ones.

```php
final class BlockTypeRegistry
{
    private static array $types = [];

    public static function register(
        string $type,
        string $label,
        string $rendererClass,
        string $validatorClass,
        bool   $requiresCrm = false,
        string $plan = 'starter',       -- minimum plan required to use this block
    ): void {
        static::$types[$type] = compact(
            'label', 'rendererClass', 'validatorClass', 'requiresCrm', 'plan'
        );
    }

    public static function all(): array              { return array_keys(static::$types); }
    public static function forPlan(string $plan): array  { /* filter by plan tier */ }
    public static function renderer(string $type): ?string { return static::$types[$type]['rendererClass'] ?? null; }
    public static function validator(string $type): ?string { return static::$types[$type]['validatorClass'] ?? null; }
    public static function requiresCrm(string $type): bool  { return static::$types[$type]['requiresCrm'] ?? false; }
}
```

Registration happens in `AppServiceProvider::boot()`:

```php
BlockTypeRegistry::register('rich_text',  'Text',     RichTextRenderer::class,  RichTextValidator::class);
BlockTypeRegistry::register('image',      'Image',    ImageRenderer::class,     ImageValidator::class);
BlockTypeRegistry::register('heading',    'Heading',  HeadingRenderer::class,   HeadingValidator::class);
BlockTypeRegistry::register('cta',        'CTA',      CtaRenderer::class,       CtaValidator::class);
BlockTypeRegistry::register('divider',    'Divider',  DividerRenderer::class,   DividerValidator::class);
BlockTypeRegistry::register('raw_html',   'HTML',     RawHtmlRenderer::class,   RawHtmlValidator::class);
// Phase 2 — CRM blocks:
BlockTypeRegistry::register('gallery',    'Gallery',  GalleryRenderer::class,   GalleryValidator::class,   requiresCrm: true);
BlockTypeRegistry::register('crm_form',   'Form',     CrmFormRenderer::class,   CrmFormValidator::class,   requiresCrm: true);
BlockTypeRegistry::register('storefront', 'Shop',     StorefrontRenderer::class,StorefrontValidator::class,requiresCrm: true);
BlockTypeRegistry::register('business_updates', 'Updates', UpdatesRenderer::class, UpdatesValidator::class, requiresCrm: true);
```

## Block Validation (PHP)

Form requests for page/post save validate the blocks array using the registry:

```php
'blocks'             => ['present', 'array'],
'blocks.*.id'        => ['required', 'string'],
'blocks.*.type'      => ['required', 'string', Rule::in(BlockTypeRegistry::all())],
'blocks.*.data'      => ['required', 'array'],
```

Each block type has its own validator class (registered above). `BlockRendererService` resolves the renderer class from the registry. Unknown block types are skipped silently (backward compat) during rendering, rejected with a validation error during save.
