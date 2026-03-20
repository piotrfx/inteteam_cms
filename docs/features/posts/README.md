# Posts Feature

**Status:** Phase 1

---

## Overview

Blog posts are time-ordered content entries. They share the same block-editor pattern as pages but add authorship, scheduling, excerpts, and a public post index at `/blog`. Posts are intended for news, repair tips, product launches, or any recurring content a shop owner wants to publish.

---

## User Stories

- As a shop owner, I can create a blog post with a title, excerpt, and blocks.
- As a shop owner, I can schedule a post to publish at a future date and time.
- As a shop owner, I can set a featured image that appears in the post index and social share previews.
- As a shop owner, I can assign myself or another editor as the post author.
- As a visitor, I can see a list of published posts at `/blog`, newest first.
- As a visitor, I can read a post at `/blog/{slug}`.

---

## Model: `CmsPost`

```php
final class CmsPost extends Model
{
    use HasUlids, HasCompanyScope, SoftDeletes;

    protected function casts(): array
    {
        return [
            'blocks'       => 'array',
            'published_at' => 'immutable_datetime',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(CmsUser::class, 'author_id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('status', 'published')
            ->where('published_at', '<=', now());
    }
}
```

### Status Lifecycle

```
draft → published   (manual publish or auto when scheduled_at is reached)
draft → scheduled   (set future published_at, job fires when time comes)
published → draft   (unpublish — takes post offline immediately)
```

Scheduling is handled by a queued job (`PublishScheduledPostsJob`) dispatched by the scheduler (`php artisan schedule:run`) every minute. It queries `WHERE status = 'scheduled' AND published_at <= NOW()` and calls `PostService::publish()`.

---

## Routes

### Admin (`routes/admin.php`)

```
GET    /admin/posts              → Admin\PostController::index
GET    /admin/posts/create       → Admin\PostController::create
POST   /admin/posts              → Admin\PostController::store
GET    /admin/posts/{post}/edit  → Admin\PostController::edit
POST   /admin/posts/{post}       → Admin\PostController::update
DELETE /admin/posts/{post}       → Admin\PostController::destroy
POST   /admin/posts/{post}/publish   → Admin\PostController::publish
POST   /admin/posts/{post}/unpublish → Admin\PostController::unpublish
```

### Public (`routes/web.php`)

```
GET /blog          → Public\PostController::index
GET /blog/{slug}   → Public\PostController::show
```

---

## Service: `PostService`

```php
final class PostService
{
    public function create(CmsUser $author, CreatePostData $data): CmsPost;
    public function update(CmsPost $post, UpdatePostData $data): CmsPost;
    public function publish(CmsPost $post): CmsPost;
    public function unpublish(CmsPost $post): CmsPost;
    public function delete(CmsPost $post): void;
    public function paginatePublished(string $companyId, int $perPage = 10): LengthAwarePaginator;
}
```

---

## Admin UI Pages (Inertia)

```
resources/js/Pages/Admin/Posts/
├── Index.tsx    -- list with status, author, published date, actions
├── Create.tsx   -- title, excerpt, author picker, scheduled_at (datetime picker)
└── Edit.tsx     -- block editor + right panel (slug, status, SEO, author, featured image)
```

`Edit.tsx` mirrors the pages editor. Additional fields specific to posts:
- Author selector (dropdown of company's CmsUsers with editor+ role)
- Featured image picker (opens media library)
- Published at datetime (empty = publish immediately, future date = scheduled)
- Excerpt textarea (max 300 chars, used in post index cards and meta description fallback)

---

## Public Post Index

`/blog` renders a paginated grid/list of published posts. Each card shows:
- Featured image (or placeholder)
- Title
- Excerpt
- Author name
- Published date (formatted: "15 March 2026")
- Read more link

Pagination: 10 posts per page, URL-based (`/blog?page=2`).

---

## Featured Image

Stored as `featured_image_path` on `cms_posts`. Set via the media picker in the editor. Rendered in:
- Post index card
- Post detail page header
- `og:image` (if no dedicated SEO OG image is set)

---

## Caching

```
Key:   cms:post:{company_id}:{slug}
TTL:   5 minutes
Bust:  on publish, unpublish, update, delete

Key:   cms:posts:index:{company_id}:{page}
TTL:   2 minutes
Bust:  on any post publish, unpublish, or delete
```

---

## Authorization (Policy)

```php
final class CmsPostPolicy
{
    public function viewAny(CmsUser $user): bool  → role: any
    public function create(CmsUser $user): bool   → role: admin or editor
    public function update(CmsUser $user, CmsPost $post): bool
        → admin: any post; editor: own posts only
    public function publish(CmsUser $user): bool  → role: admin
    public function delete(CmsUser $user): bool   → role: admin
}
```

---

## JSON-LD for Posts

When rendering a single post, `SeoMetaService` emits an `Article` schema alongside the site-level `LocalBusiness` schema:

```json
{
  "@context": "https://schema.org",
  "@type": "Article",
  "headline": "...",
  "description": "...",
  "image": "...",
  "author": {
    "@type": "Person",
    "name": "..."
  },
  "publisher": {
    "@type": "Organization",
    "name": "Acme Repairs"
  },
  "datePublished": "2026-03-20T09:00:00Z",
  "dateModified": "2026-03-20T09:00:00Z"
}
```

---

## Tests

- `PostCrudTest` — create, update, delete, author assignment
- `PostPublishTest` — publish, unpublish, schedule, auto-publish via job
- `PostPublicTest` — index paginates correctly, drafts/scheduled not shown, published post renders
- `PostPolicyTest` — editor can only edit own posts, admin can edit any

All in `tests/Feature/Posts/`.
