# Revisions & Staging Preview Feature

**Status:** Phase 2

---

## Overview

Every change to a page or post — whether made by a human editor or an AI agent — can be staged before going live. A staged revision is a full snapshot of the block content. It can be previewed at a shareable URL. The shop owner approves or discards the staged version from the preview banner or from the admin panel.

This is the safety layer that makes AI editing trustworthy. The AI can make changes freely, but nothing reaches live visitors until the shop owner explicitly approves it.

---

## User Stories

- As a shop owner, I can see a history of all changes made to a page, including who or what made them.
- As a shop owner, when an AI assistant suggests changes, I receive a preview link to review them before they go live.
- As a shop owner, I can approve staged changes with one click from the preview or from the admin panel.
- As a shop owner, I can discard staged changes without affecting the live page.
- As a shop owner, I can roll back the live page to any previous revision.
- As an AI agent, my edits always create a staged revision — I cannot push directly to live.

---

## Revision Model: `CmsPageRevision`

```php
final class CmsPageRevision extends Model
{
    use HasUlids, HasCompanyScope;

    protected function casts(): array
    {
        return ['blocks' => 'array'];
    }
}
```

No `SoftDeletes` — revisions are permanent history records. They are only pruned by a scheduled command after 90 days (configurable).

---

## How Revisions Integrate with Pages and Posts

`cms_pages` and `cms_posts` each carry two nullable FK columns:

```sql
live_revision_id    FK → cms_page_revisions NULL
staged_revision_id  FK → cms_page_revisions NULL
```

`blocks` on the parent table remains the **canonical live content**. It is kept in sync with `live_revision_id.blocks` whenever a revision is published. This means existing queries that read `$page->blocks` continue to work without change — the revision system is additive.

### State Diagram

```
[No revision]
      │
      ▼  (user saves or AI calls update_page_blocks)
[staged_revision_id set, live unchanged]
      │
      ├─ User approves → live_revision_id = staged, blocks = staged.blocks, staged = null
      │
      └─ User discards → staged_revision_id = null, live unchanged
```

---

## Service: `RevisionService`

```php
final class RevisionService
{
    // Create a staged revision for a page
    public function stagePageRevision(
        CmsPage $page,
        array   $blocks,
        string  $summary,
        string  $createdByType,    // 'user' or 'ai_agent'
        ?string $createdById,      // cms_users.id or agent name
        ?string $aiSessionId = null,
    ): CmsPageRevision;

    // Same for posts
    public function stagePostRevision(CmsPost $post, array $blocks, ...): CmsPageRevision;

    // Promote staged to live
    public function publishStaged(CmsPage|CmsPost $content): void;

    // Discard staged without touching live
    public function discardStaged(CmsPage|CmsPost $content): void;

    // Roll back to a specific revision (creates a new staged revision from it)
    public function rollBackTo(CmsPage|CmsPost $content, CmsPageRevision $revision): CmsPageRevision;

    // History
    public function history(CmsPage|CmsPost $content, int $limit = 20): Collection;
}
```

`publishStaged()` steps:
1. Load `staged_revision_id`
2. Set `live_revision_id = staged_revision_id`
3. Copy `staged.blocks` → `content.blocks` (keeps existing queries working)
4. Set `staged_revision_id = null`
5. Expire all preview tokens for this content
6. Flush page/post Redis cache
7. Log: `"Published staged revision {id} for page {slug}"`

---

## Preview Tokens

A preview token grants time-limited, unauthenticated access to the staged version of a specific page or post.

### Creation

Created whenever a staged revision exists:

```php
final class PreviewTokenService
{
    public function createForPage(CmsPage $page, string $createdByType): CmsPreviewToken;
    public function createForPost(CmsPost $post, string $createdByType): CmsPreviewToken;
    public function validate(string $token): CmsPreviewToken; // throws if expired/not found
    public function expire(string $contentType, string $contentId): void; // on publish/discard
}
```

Token is a `Str::random(64)` stored hashed in the DB. The raw token is returned once and included in the URL.

TTL: **48 hours**. After expiry, the preview URL returns 404 with a friendly "This preview has expired" page.

### Preview URL

```
https://{slug}.cms.inte.team/preview/{raw_token}
```

No auth required — the token is the secret. Safe to share with a client for approval.

---

## Preview Route & Rendering

```php
// routes/web.php (public, no auth middleware)
Route::get('/preview/{token}', Public\PreviewController::class);
Route::post('/preview/{token}/publish', Public\PreviewPublishController::class);
Route::post('/preview/{token}/discard', Public\PreviewDiscardController::class);
```

`PreviewController`:
1. Validate token (`PreviewTokenService::validate($token)`)
2. Load content (page or post) + `staged_revision_id` blocks
3. Record `viewed_at` on the token
4. Render exactly as the live page would render, but using staged blocks
5. Inject preview banner (see below)

`PreviewPublishController` and `PreviewDiscardController` validate the token then call `RevisionService::publishStaged()` or `discardStaged()`. Both redirect to the live page with a flash message.

### Preview Banner

A floating bar injected at the top of the page via a Blade `@push('head')` stack:

```html
<div id="cms-preview-banner" style="position:fixed;top:0;width:100%;z-index:9999;...">
  <span>⚡ Preview — staged changes by {{ $revision->created_by_type === 'ai_agent' ? 'AI Assistant' : 'Editor' }}</span>
  <span style="opacity:.7">{{ $revision->summary }}</span>
  <form method="POST" action="/preview/{{ $token }}/publish">
    @csrf <button type="submit">✓ Publish Live</button>
  </form>
  <form method="POST" action="/preview/{{ $token }}/discard">
    @csrf <button type="submit">✕ Discard</button>
  </form>
</div>
```

No JavaScript required. Pure HTML form POSTs. Works even if the page's JS has errors.

---

## Admin UI: Revision History Panel

In the page/post editor (`Edit.tsx`), a collapsible sidebar panel shows:

```
Revision History
─────────────────────────────────
● LIVE  Today 14:32 — You — "Updated hero section"
        [Restore as staged]

  ○     Today 11:15 — AI Assistant — "Added repair process section"
        [Restore as staged]

  ○     Yesterday 09:00 — You — "Initial content"
        [Restore as staged]
```

"Staged" badge appears when `staged_revision_id` is set, with a "View Preview" link and "Publish" / "Discard" buttons.

Admin routes:
```
GET  /admin/pages/{page}/revisions           → Admin\RevisionController::index
POST /admin/pages/{page}/revisions/{rev}/restore → Admin\RevisionController::restore
POST /admin/pages/{page}/staged/publish      → Admin\StagedController::publish
POST /admin/pages/{page}/staged/discard      → Admin\StagedController::discard
GET  /admin/pages/{page}/staged/preview      → Admin\StagedController::preview  (creates token, redirects)
```

(Same routes for `/admin/posts/{post}/...`)

---

## Caching

Staging does not pollute the live cache. The live Redis cache (`cms:page:{company_id}:{slug}`) continues to serve live blocks. Preview URLs bypass the cache entirely (always DB read).

Cache bust happens only on `publishStaged()`, not on `stagePageRevision()`.

---

## Scheduled Maintenance

`PruneOldRevisionsCommand` runs weekly:
- Hard-deletes `cms_page_revisions` older than 90 days
- Skips any revision currently referenced as `live_revision_id` or `staged_revision_id`
- Deletes expired `cms_preview_tokens`

---

## Authorization (Policy)

```php
final class RevisionPolicy
{
    public function viewHistory(CmsUser $user): bool   → role: any
    public function stage(CmsUser $user): bool         → role: admin or editor
    public function publish(CmsUser $user): bool       → role: admin
    public function discard(CmsUser $user): bool       → role: admin or editor
    public function restore(CmsUser $user): bool       → role: admin
}
```

AI agents (MCP tokens) can `stage` only. They cannot `publish`, `discard`, or `restore`. Those actions require a human.

---

## Tests

- `RevisionStageTest` — staging creates revision, sets staged_revision_id, does not touch live
- `RevisionPublishTest` — publish copies blocks to live, clears staged, expires preview tokens, busts cache
- `RevisionDiscardTest` — discard clears staged, live unchanged
- `RevisionHistoryTest` — history returns ordered revisions, shows correct created_by_type
- `RevisionRollbackTest` — roll back creates a new staged revision from historical content
- `PreviewTokenTest` — valid token renders staged content, expired token returns 404
- `PreviewBannerTest` — banner renders with publish/discard forms
- `PreviewPublishViaUrlTest` — POST to /preview/{token}/publish promotes staged, redirects live

All in `tests/Feature/Revisions/`.
