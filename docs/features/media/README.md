# Feature: Media Uploads

**Phase:** 1
**Status:** ✅ Done
**Tests:** `tests/Feature/Media/MediaUploadTest.php` (8), `MediaDeleteTest.php` (3)

---

## What It Does

Admins and editors upload images and SVGs to a per-company media library. Files are stored on the local disk (configurable to S3/GCS via `CMS_MEDIA_DISK`). Thumbnails are generated automatically at 400×400. SVGs are sanitised before storage. Media is scoped strictly to the company.

---

## Routes

| Method | URI | Name | Auth |
|--------|-----|------|------|
| GET | `/admin/media` | `admin.media.index` | admin, editor, viewer |
| POST | `/admin/media` | `admin.media.store` | admin, editor |
| PATCH | `/admin/media/{media}` | `admin.media.update` | admin, editor |
| DELETE | `/admin/media/{media}` | `admin.media.destroy` | admin |

`store` returns JSON (201/422) — required for the MediaPicker component.

---

## Key Files

| File | Purpose |
|------|---------|
| `app/Services/MediaService.php` | Upload, thumbnail, paginate, update, delete |
| `app/Services/SvgSanitiser.php` | Strips `<script>`, `on*` attrs, `javascript:` from SVGs |
| `app/Models/CmsMedia.php` | `HasUlids`, `HasCompanyScope`, `SoftDeletes` |
| `app/Policies/CmsMediaPolicy.php` | viewAny/create: admin+editor; delete: admin only |
| `app/Http/Controllers/Admin/MediaController.php` | Thin controller |
| `app/Http/Requests/Admin/StoreMediaRequest.php` | Validates `file` field |
| `app/Http/Requests/Admin/UpdateMediaRequest.php` | Validates `alt_text`, `caption` |
| `app/DTO/UpdateMediaData.php` | DTO for update |
| `resources/js/Pages/Admin/Media/Index.tsx` | Library grid + detail panel |
| `resources/js/Components/MediaPicker/` | Reusable modal picker for other features |

---

## Accepted MIME Types

| Type | Max size |
|------|---------|
| image/jpeg | 10 MB |
| image/png | 10 MB |
| image/webp | 10 MB |
| image/gif | 10 MB |
| image/svg+xml | 500 KB |

---

## Storage

- Path: `media/{company_id}/{year}/{month}/{ulid}.{ext}`
- Thumbnail: same path with `_thumb` before extension
- Disk: `local` by default; change `CMS_MEDIA_DISK` for S3/GCS
- `php artisan storage:link` required for local disk public access

---

## Multi-tenancy

`CmsMedia` uses `HasCompanyScope`. Cross-company access returns 404 (scope filters before policy — no information disclosure).
