# Media Feature

**Status:** Phase 1

---

## Overview

Each company has their own media library. Images and files are uploaded through the admin panel and stored on disk (local in dev, configurable storage driver in production). Media is used in image blocks, featured images for posts, OG images for SEO, logos, and favicons.

This is a standalone media system — it does not share storage or records with inteteam_crm. The CRM gallery is accessed separately via `CrmApiClient` (see `features/crm_integration/`).

---

## User Stories

- As a shop owner, I can upload images to my media library.
- As a shop owner, I can browse, search, and select images from a media picker modal.
- As a shop owner, I can add alt text and a caption to any media item.
- As a shop owner, I can delete media items I no longer need.
- As a shop owner, trying to delete media used in a published page shows a warning.

---

## Accepted Formats & Limits

| Type | Formats | Max Size |
|------|---------|----------|
| Image | JPEG, PNG, WebP, GIF | 10 MB |
| Vector | SVG | 500 KB |

No video upload in Phase 1 (keeps storage and processing simple). SVG is accepted for logos only; it is sanitised before storage to strip embedded scripts.

---

## Model: `CmsMedia`

```php
final class CmsMedia extends Model
{
    use HasUlids, HasCompanyScope, SoftDeletes;

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(CmsUser::class, 'uploaded_by');
    }

    public function getUrlAttribute(): string
    {
        return Storage::disk($this->disk)->url($this->path);
    }
}
```

---

## Routes (`routes/admin.php`)

```
GET    /admin/media              → Admin\MediaController::index
POST   /admin/media              → Admin\MediaController::store      (multipart upload)
GET    /admin/media/{media}      → Admin\MediaController::show
PATCH  /admin/media/{media}      → Admin\MediaController::update     (alt text, caption)
DELETE /admin/media/{media}      → Admin\MediaController::destroy
```

---

## Service: `MediaService`

```php
final class MediaService
{
    public function upload(CmsUser $uploader, UploadedFile $file): CmsMedia;
    public function update(CmsMedia $media, UpdateMediaData $data): CmsMedia;
    public function delete(CmsMedia $media): void;
    public function paginate(string $companyId, ?string $search, int $perPage = 40): LengthAwarePaginator;
}
```

`upload()` steps:
1. Validate MIME type and file size
2. Generate a storage path: `media/{company_id}/{year}/{month}/{ulid}.{ext}`
3. For SVG: run through `SvgSanitiser::sanitise()` before writing
4. For JPEG/PNG/WebP: generate a thumbnail (`_thumb` suffix, max 400×400) using `Intervention\Image`
5. Write file to `Storage::disk(config('cms.media_disk'))`
6. Create `CmsMedia` record

`delete()` does a soft delete only. A scheduled command (`PruneOrphanedMediaCommand`) hard-deletes soft-deleted records older than 30 days and removes the files from storage.

---

## Storage Configuration

```php
// config/cms.php
'media_disk' => env('CMS_MEDIA_DISK', 'local'),
```

In production: set `CMS_MEDIA_DISK=s3` or `gcs`. Storage driver configuration stays in `config/filesystems.php` as standard Laravel.

Public URLs are generated with `Storage::disk($media->disk)->url($media->path)`. In local dev, files are served from `public/storage` via `php artisan storage:link`.

---

## Thumbnails

`_thumb` variants are generated on upload for JPEG, PNG, and WebP. GIF thumbnails are skipped to avoid frame extraction complexity. Thumbnails are served in the admin media picker grid. Full-size is served when used in a block.

```
original:  media/01abc.../2026/03/01jq4x2k.jpg
thumbnail: media/01abc.../2026/03/01jq4x2k_thumb.jpg
```

---

## Admin UI (Inertia)

### Media Library Page

```
resources/js/Pages/Admin/Media/
└── Index.tsx   -- grid of thumbnails, search input, upload button, delete button
```

Grid view with infinite scroll (or simple pagination). Each item shows:
- Thumbnail
- Filename
- Dimensions
- Upload date
- Alt text (if set)

Clicking an item opens a detail sidebar: full preview, alt text / caption edit form, delete button with usage warning.

### Media Picker Modal

```
resources/js/Components/MediaPicker/
├── MediaPicker.tsx      -- modal wrapper (trigger + overlay)
├── MediaPickerGrid.tsx  -- scrollable thumbnail grid with selection state
└── MediaPickerUpload.tsx -- drag-drop upload zone inside the picker
```

Used in: image blocks, featured image on posts, OG image in SEO panel, logo/favicon in settings.

API: `<MediaPicker value={mediaId} onChange={setMediaId} />`

---

## Usage Tracking

Before soft-deleting, `MediaService::delete()` checks if the media is referenced:
- Any `cms_pages.blocks` contains `"media_id": "{id}"`
- Any `cms_posts.blocks` contains `"media_id": "{id}"`
- Any `cms_posts.featured_image_path = path`
- Any `companies.logo_path = path`

If references are found, the controller returns a warning response (not an error): "This image is used in X pages. Are you sure?" A second confirmation deletes it. The pages/posts that referenced it will show a missing-image placeholder.

---

## Authorization (Policy)

```php
final class CmsMediaPolicy
{
    public function viewAny(CmsUser $user): bool  → role: any
    public function create(CmsUser $user): bool   → role: admin or editor
    public function update(CmsUser $user): bool   → role: admin or editor
    public function delete(CmsUser $user): bool   → role: admin
}
```

---

## Tests

- `MediaUploadTest` — JPEG, PNG, WebP, SVG upload; oversized file rejected; wrong MIME rejected
- `MediaUpdateTest` — alt text and caption update
- `MediaDeleteTest` — soft delete, usage warning when referenced, prune command
- `MediaPickerTest` — pagination, search by filename

All in `tests/Feature/Media/`.
