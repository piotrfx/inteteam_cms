# Theming Feature

**Status:** Phase 1 (default theme) → Phase 4 (extractable to Next.js)

---

## Overview

The public site is rendered by Laravel Blade templates. In Phase 1 there is one built-in default theme. The theme reads branding settings (primary colour, logo) from the company record and applies them via CSS custom properties. No theme installation, no marketplace — one clean, professional default that works well for any repair shop.

Phase 4 (optional) extracts the public site to a Next.js app that consumes a headless API. Until then, Blade is the right choice: no extra infrastructure, full SSR out of the box, and excellent SEO.

---

## User Stories

- As a visitor, the public site looks professional and loads fast.
- As a shop owner, the site automatically uses my brand's primary colour and logo.
- As a shop owner, the site is responsive and works on mobile.

---

## Blade Layout Structure

```
resources/views/
├── layouts/
│   └── public.blade.php     -- outer shell: <html>, <head>, <body>
├── partials/
│   ├── seo-head.blade.php   -- all meta, OG, Twitter, JSON-LD tags
│   ├── header.blade.php     -- logo + primary nav + mobile hamburger
│   └── footer.blade.php     -- footer nav + copyright + optional CRM branding
├── pages/
│   ├── show.blade.php       -- static page: title + block loop
│   └── home.blade.php       -- home page: inherits show.blade.php
├── posts/
│   ├── index.blade.php      -- post list with pagination
│   └── show.blade.php       -- single post: author, date, block loop
└── blocks/
    ├── rich_text.blade.php
    ├── heading.blade.php
    ├── image.blade.php
    ├── cta.blade.php
    ├── divider.blade.php
    ├── raw_html.blade.php
    ├── gallery.blade.php          (Phase 2)
    ├── crm_form.blade.php         (Phase 2)
    ├── storefront.blade.php       (Phase 2)
    └── business_updates.blade.php (Phase 2)
```

---

## CSS & Branding

The public site uses a minimal CSS file (no Tailwind — Tailwind is admin-only). A single `public.css` file handles layout, typography, and colour tokens.

Primary colour is injected as a CSS custom property in `<head>`:

```html
<style>
  :root {
    --color-primary: {{ $company->primary_colour ?? '#2563eb' }};
    --color-primary-dark: {{ $company->primaryColourDarken(15) }};
  }
</style>
```

The `primaryColourDarken()` accessor on `Company` computes a darkened shade for hover states (no runtime JS needed).

Typography: system font stack. Clean, fast, no web font requests unless the shop owner adds a Google Fonts link in a `raw_html` block.

---

## Block Rendering in Blade

The `public.blade.php` layout passes `$blocks` to `show.blade.php`. The template loops and includes the relevant partial:

```blade
@foreach ($blocks as $block)
  @include('blocks.' . $block['type'], ['data' => $block['data']])
@endforeach
```

Unknown block types: `@includeIf` with a fallback of empty string. A missing partial renders nothing silently.

---

## Performance Targets

- First Contentful Paint: < 1.2s (no render-blocking JS on public site)
- LCP: < 2.5s
- CLS: 0 (no layout shifts — images have explicit dimensions from `cms_media` width/height)
- Zero external JS dependencies on public pages (admin JS bundle is separate)
- No inline styles beyond the colour token block above

Images use `loading="lazy"` on all `<img>` tags below the fold. The hero image (first image block on home page) gets `loading="eager"` and `fetchpriority="high"`.

---

## Phase 4 — Next.js Extraction (Future)

When introduced, the public site becomes a Next.js app at a separate origin that calls a new headless API layer in inteteam_cms:

```
GET /api/headless/{company}/pages/{slug}
GET /api/headless/{company}/posts
GET /api/headless/{company}/posts/{slug}
GET /api/headless/{company}/navigation
GET /api/headless/{company}/settings
```

Each endpoint returns structured JSON. The Blade layer would be kept as a fallback or retired.

This is not planned for Phase 1–3. Document it here so the headless API endpoints can be designed consistently with the Blade rendering layer from the start (same data shape).

---

## Tests

- `ThemeRenderTest` — primary colour rendered in `<head>`, logo URL correct
- `PublicLayoutTest` — header nav, footer nav, block loop renders all block types
- `ResponsiveTest` — not automated, manual QA checklist

All in `tests/Feature/Theming/`.
