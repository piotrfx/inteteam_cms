# SEO Feature

**Status:** Phase 1

---

## Overview

Every public page the CMS renders must produce correct, complete SEO output out of the box. Shop owners should not need to understand SEO to benefit from it — the defaults are sensible, and overrides are available when needed.

The system has two tiers: **site-level defaults** (configured once in Settings) and **per-content overrides** (set on each page or post). If an override is blank, the site default is used. If the site default is also blank, a safe fallback is computed.

---

## What Gets Rendered

Every public page emits:

```html
<!-- Title -->
<title>{{ seo.title }}</title>

<!-- Core meta -->
<meta name="description" content="{{ seo.description }}">
<link rel="canonical" href="{{ seo.canonical }}">
<meta name="robots" content="{{ seo.robots }}">

<!-- Open Graph — controls previews in Facebook, LinkedIn, WhatsApp, iMessage -->
<meta property="og:title"       content="{{ seo.title }}">
<meta property="og:description" content="{{ seo.description }}">
<meta property="og:image"       content="{{ seo.ogImage }}">
<meta property="og:url"         content="{{ seo.canonical }}">
<meta property="og:type"        content="{{ seo.ogType }}">
<meta property="og:site_name"   content="{{ seo.siteName }}">

<!-- Twitter Card — controls previews when shared on X/Twitter -->
<meta name="twitter:card"        content="summary_large_image">
<meta name="twitter:title"       content="{{ seo.title }}">
<meta name="twitter:description" content="{{ seo.description }}">
<meta name="twitter:image"       content="{{ seo.ogImage }}">
<meta name="twitter:site"        content="{{ seo.twitterHandle }}">  <!-- if set -->

<!-- JSON-LD structured data (always present) -->
<script type="application/ld+json">{{ seo.jsonLd | raw }}</script>
```

---

## Title Resolution

The title displayed in `<title>` and `og:title` is resolved in this order:

1. `cms_pages.seo_title` or `cms_posts.seo_title` (if set)
2. `{page/post title} {companies.seo_title_suffix}` (e.g. `"About Us | Acme Repairs"`)
3. `{page/post title}` (if no suffix configured)

Title suffix examples: `"| Acme Phone Repairs"`, `"— Acme Repairs"`.

---

## Description Resolution

1. `cms_pages.seo_description` or `cms_posts.seo_description` (if set)
2. `companies.seo_meta_description` (site default)
3. First 155 characters of the first `rich_text` block's plain text (auto-extracted)
4. `cms_posts.excerpt` for posts
5. Empty string (meta tag omitted if nothing found — better than a bad description)

Max 160 characters. Truncated with `…` if over.

---

## OG Image Resolution

1. `cms_pages.seo_og_image_path` or `cms_posts.seo_og_image_path` (if set)
2. `cms_posts.featured_image_path` (for posts)
3. `companies.seo_og_image_path` (site default)
4. Omit the tag (no placeholder image — broken images look bad in social previews)

Recommended OG image size: 1200×630px. A note in the settings UI tells shop owners this.

---

## Canonical URL

1. `cms_pages.seo_canonical_url` or `cms_posts.seo_canonical_url` (if set explicitly — for cross-posted content)
2. Current full URL (scheme + host + path, no query string)

Always set. Never omit canonical.

---

## Robots

1. `cms_pages.seo_robots` or `cms_posts.seo_robots` (if set)
2. `companies.seo_robots` (site default)
3. `index,follow`

Applies to individual pages. The `/robots.txt` file is separate (see below).

---

## Auto-Generated Files

### `/robots.txt`

Dynamically served by a controller (no static file). Rules:

```
User-agent: *
Allow: /
Disallow: /admin
Disallow: /admin/*
Sitemap: https://{slug}.cms.inte.team/sitemap.xml
```

If `companies.seo_robots = 'noindex,nofollow'`, adds:
```
Disallow: /
```

### `/sitemap.xml`

Dynamically served by a controller. Includes:
- All published pages (`<changefreq>monthly</changefreq>`)
- All published posts (`<changefreq>weekly</changefreq>`)

```xml
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <url>
    <loc>https://acme.cms.inte.team/</loc>
    <lastmod>2026-03-20</lastmod>
    <changefreq>monthly</changefreq>
    <priority>1.0</priority>
  </url>
  <url>
    <loc>https://acme.cms.inte.team/about</loc>
    ...
  </url>
</urlset>
```

Priority: home = 1.0, pages = 0.8, posts = 0.6.

The sitemap is cached (1 hour TTL). Flushed when any page/post is published or deleted.

On publish: optionally ping Google's indexing API (`https://searchconsole.googleapis.com/v1/urlNotifications:publish`). This is a best-effort ping — failure is logged and ignored.

### `/favicon.ico` and `/favicon.png`

Served from `companies.favicon_path`. If not set, serve a default inteteam favicon.

---

## JSON-LD Structured Data

### `LocalBusiness` (every page, from company settings)

```json
{
  "@context": "https://schema.org",
  "@type": "LocalBusiness",
  "name": "Acme Phone Repairs",
  "url": "https://acme.cms.inte.team",
  "@id": "https://acme.cms.inte.team/#business",
  "telephone": "+44 1234 567890",
  "address": {
    "@type": "PostalAddress",
    "streetAddress": "123 High Street",
    "addressLocality": "London",
    "postalCode": "W1A 1AA",
    "addressCountry": "GB"
  },
  "openingHoursSpecification": [
    {
      "@type": "OpeningHoursSpecification",
      "dayOfWeek": ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday"],
      "opens": "09:00",
      "closes": "18:00"
    }
  ],
  "priceRange": "££",
  "logo": "https://...",
  "image": "https://..."
}
```

Emitted on every page if `companies.seo_address_city` is set (minimum required field).

### `WebSite` (site root only)

Emitted on the home page. Enables the Google Sitelinks Searchbox.

```json
{
  "@context": "https://schema.org",
  "@type": "WebSite",
  "name": "Acme Phone Repairs",
  "url": "https://acme.cms.inte.team"
}
```

### `Article` (posts only)

See `features/posts/README.md`.

### `WebPage` or `ContactPage` (static pages)

For `contact` type pages, `@type` is `ContactPage`. All others use `WebPage`.

---

## Service: `SeoMetaService`

```php
final class SeoMetaService
{
    public function forPage(CmsPage $page, Company $company): SeoMeta;
    public function forPost(CmsPost $post, Company $company): SeoMeta;
    public function sitemapEntries(Company $company): Collection;
}
```

Returns a `SeoMeta` readonly DTO:

```php
final readonly class SeoMeta
{
    public string  $title;
    public string  $description;
    public string  $canonical;
    public string  $robots;
    public string  $ogImage;
    public string  $ogType;       // 'website' or 'article'
    public string  $siteName;
    public ?string $twitterHandle;
    public string  $jsonLd;       // JSON-encoded string, pre-escaped for safe raw output
}
```

The Blade layout receives `$seo` and renders the full `<head>` block from a shared partial:

```
resources/views/partials/seo-head.blade.php
```

---

## Admin SEO Panel

Appears as a collapsible sidebar panel in the page/post editor.

Fields:
- **SEO title** — text input, 60 char counter, preview of final title (with suffix)
- **Meta description** — textarea, 160 char counter, turns red over limit
- **OG image** — media picker (opens media library)
- **Canonical URL** — text input, defaults to current URL (shown as placeholder)
- **Robots** — select (inherit from site / index,follow / noindex,nofollow)
- **Schema type** — select (WebPage / FAQPage / ContactPage) (pages only)

Live Google SERP preview snippet: shows how title + description will appear in Google results using the resolved values (not just the override fields).

---

## Site-Level SEO Settings

In Settings → SEO tab:

- Site name
- Title suffix
- Default meta description
- Default OG image
- Twitter handle (with `@` prefix)
- Google Search Console verification code
- Default robots
- LocalBusiness data (address, phone, opening hours, price range)

---

## Tests

- `SeoMetaServiceTest` — title resolution chain, description truncation, OG image fallbacks
- `SitemapTest` — sitemap includes published content, excludes drafts, correct XML structure
- `RobotsTxtTest` — correct disallow rules, noindex site returns `Disallow: /`
- `JsonLdTest` — LocalBusiness schema emitted when address is set, Article schema on posts

All in `tests/Feature/Seo/`.
