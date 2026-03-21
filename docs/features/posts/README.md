# Feature: Blog Posts

**Phase:** 1
**Status:** ✅ Done
**Tests:** `tests/Feature/Posts/PostCrudTest.php` (13), `PostPublishTest.php` (6)

---

## What It Does

Admins and editors create, edit, publish, and delete blog posts. Each post has an author (`author_id` = the authenticated `CmsUser`), title, slug, excerpt, blocks, featured image path, status (draft/published/scheduled), and SEO fields. `published_at` is set once on first publish and preserved across unpublish/re-publish cycles.

---

## Routes

| Method | URI | Name | Policy |
|--------|-----|------|--------|
| GET | `/admin/posts` | `admin.posts.index` | viewAny |
| GET | `/admin/posts/create` | `admin.posts.create` | create |
| POST | `/admin/posts` | `admin.posts.store` | create |
| GET | `/admin/posts/{post}/edit` | `admin.posts.edit` | update |
| PUT/POST | `/admin/posts/{post}` | `admin.posts.update` | update |
| DELETE | `/admin/posts/{post}` | `admin.posts.destroy` | delete |
| POST | `/admin/posts/{post}/publish` | `admin.posts.publish` | publish |
| POST | `/admin/posts/{post}/unpublish` | `admin.posts.unpublish` | publish |

---

## Key Files

| File | Purpose |
|------|---------|
| `app/Services/PostService.php` | CRUD, publish, unpublish, cache bust |
| `app/Models/CmsPost.php` | `HasUlids`, `HasCompanyScope`, `SoftDeletes`; `author()` belongsTo |
| `app/Policies/CmsPostPolicy.php` | publish/delete: admin only; create/update: admin+editor |
| `app/Http/Controllers/Admin/PostController.php` | Thin controller |
| `app/Http/Requests/Admin/StorePostRequest.php` | title, slug regex, status, excerpt |
| `app/Http/Requests/Admin/UpdatePostRequest.php` | Same as store |
| `app/DTO/CreatePostData.php` / `UpdatePostData.php` | `fromRequest()` DTOs |
| `resources/js/Pages/Admin/Posts/Index.tsx` | Table with status + author |
| `resources/js/Pages/Admin/Posts/Create.tsx` | Form with excerpt, blocks, featured image, SEO |
| `resources/js/Pages/Admin/Posts/Edit.tsx` | Pre-populated form |

---

## Statuses

| Status | Meaning |
|--------|---------|
| `draft` | Not public |
| `published` | Live on public site |
| `scheduled` | Future publish — Phase 2 (queue job not yet implemented) |

---

## Block Data Shapes

Same as pages — see `docs/features/pages/README.md`.

---

## Cache

`PostService::bustCache()` → `Cache::forget("cms:post:{company_id}:{slug}")` on every write.

---

## Multi-tenancy

`CmsPost` uses `HasCompanyScope`. Cross-company access returns 404.
