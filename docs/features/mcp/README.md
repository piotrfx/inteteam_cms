# MCP Server — AI Page Editing Feature

**Status:** Phase 2

---

## Overview

The MCP (Model Context Protocol) server exposes CMS tools over HTTP so an AI assistant (Claude, GPT, or any MCP-compatible client) can read and edit a shop's website on behalf of the owner. The shop owner chats with an AI assistant; the AI calls CMS tools to make changes, generates a preview URL for the owner to review, and only publishes when the owner explicitly confirms.

**Safety guarantee:** AI write tools always create a staged revision. The AI cannot push content directly to live visitors. Publishing requires human confirmation.

---

## User Stories

- As a shop owner, I can chat with an AI assistant and say "rewrite my homepage to focus on fast turnarounds" and the AI updates my page and sends me a preview link.
- As a shop owner, I review the AI's changes at the preview URL and click "Publish" when I'm happy.
- As a shop owner, I can give the AI a read-only token so it can answer questions about my site without being able to change it.
- As a shop owner, I can revoke an AI token at any time from the admin panel.
- As an AI client, I receive clear tool descriptions so I understand what each tool does and its safety implications.

---

## Protocol

The MCP server implements [Model Context Protocol](https://modelcontextprotocol.io) over **HTTP (Streamable HTTP transport)** — a single POST endpoint that accepts JSON-RPC 2.0.

```
POST /mcp/v1
Authorization: Bearer {mcp_token}
Content-Type: application/json

{
  "jsonrpc": "2.0",
  "id": 1,
  "method": "tools/call",
  "params": {
    "name": "get_page",
    "arguments": { "slug": "home" }
  }
}
```

Methods supported:
- `initialize` — handshake, returns server info + capabilities
- `tools/list` — returns all tools available to this token (filtered by permissions)
- `tools/call` — executes a tool

---

## Authentication

Each company creates one or more MCP tokens in Settings → AI Integration. The token is generated once (shown as plaintext), then stored as a SHA-256 hash. Lost tokens must be revoked and regenerated.

Permissions per token:
- `read` — `list_pages`, `get_page`, `get_post`, `list_posts`, `list_media`, `get_navigation`, `get_site_settings`
- `write` — all of the above + all `update_*` and `create_*` tools (always stages, never live)
- `publish` — all of the above + `publish_staged`, `discard_staged`

Default when creating a token: `["read", "write"]`. The `publish` permission is intentionally not the default — publishing should remain a deliberate human action via the preview banner in most integrations.

Middleware: `auth:cms_mcp` resolves the company from the token hash. Rate limit: 120 requests/minute per token.

---

## Tools Reference

### Read Tools (permission: `read`)

#### `initialize`
Handshake. Returns server name, version, and a description the AI client can show to the user.

---

#### `list_pages`
Returns all pages for the site with their status and whether a staged revision is pending.

```json
{
  "name": "list_pages",
  "description": "List all pages on this website. Shows slug, title, type (home/about/contact/custom), publish status, and whether there are staged (unpublished) AI changes pending.",
  "inputSchema": { "type": "object", "properties": {}, "required": [] }
}
```

Response example:
```json
[
  { "slug": "home",  "title": "Home",  "type": "home",   "status": "published", "has_staged": false },
  { "slug": "about", "title": "About", "type": "about",  "status": "published", "has_staged": true  }
]
```

---

#### `get_page`
Returns a page's current **live** blocks and SEO fields. Always returns live content, never staged.

```json
{
  "name": "get_page",
  "description": "Read a page's current live content. Returns the blocks array and SEO fields. Use this before making edits so you understand the existing structure.",
  "inputSchema": {
    "type": "object",
    "properties": {
      "slug": { "type": "string", "description": "Page slug (e.g. 'home', 'about')" }
    },
    "required": ["slug"]
  }
}
```

---

#### `list_posts`
Returns all blog posts with status and excerpt.

---

#### `get_post`
Returns a post's current live blocks, excerpt, and SEO fields.

---

#### `list_media`
Returns the company's media library (id, filename, url, alt_text, dimensions). Use this to find existing images before suggesting a new image block.

---

#### `get_navigation`
Returns the header and footer navigation items tree.

---

#### `get_site_settings`
Returns company name, primary colour, SEO defaults, business info. Read-only — helps the AI understand the brand before writing content.

---

### Write Tools (permission: `write`)

All write tools create a **staged revision**. They never modify live content. After calling any write tool, call `create_preview` and show the URL to the user before suggesting they publish.

---

#### `update_page_blocks`
Replaces all blocks on a page with a new set. Creates a staged revision.

```json
{
  "name": "update_page_blocks",
  "description": "Update a page's content blocks. IMPORTANT: This creates a staged (draft) version — it does NOT go live immediately. After calling this, always call create_preview and show the preview URL to the user before they decide to publish. Never call publish_staged without the user explicitly asking you to.",
  "inputSchema": {
    "type": "object",
    "properties": {
      "slug":    { "type": "string", "description": "Page slug" },
      "blocks":  { "type": "array",  "description": "Complete blocks array replacing current content. Use get_page first to understand existing structure." },
      "summary": { "type": "string", "description": "One-sentence description of what changed, shown to the user in the preview banner and revision history. E.g. 'Rewrote hero section to emphasise fast turnaround time.'" }
    },
    "required": ["slug", "blocks", "summary"]
  }
}
```

---

#### `update_page_seo`
Updates the SEO fields on a page (title, description, OG image path). Creates a staged revision. Does not change blocks.

---

#### `update_post_blocks`
Same as `update_page_blocks` but for blog posts.

---

#### `create_page`
Creates a new page as a draft (not staged — new pages start as drafts, not replacing anything live).

```json
{
  "name": "create_page",
  "description": "Create a new page as a draft. The page will not appear on the live site until published by the user from the admin panel.",
  "inputSchema": {
    "type": "object",
    "properties": {
      "title":  { "type": "string" },
      "slug":   { "type": "string", "description": "URL-safe slug. If omitted, generated from title." },
      "type":   { "type": "string", "enum": ["custom"], "description": "Only 'custom' type allowed via MCP. Home/about/contact are managed by the user." },
      "blocks": { "type": "array" },
      "summary":{ "type": "string" }
    },
    "required": ["title", "blocks", "summary"]
  }
}
```

---

#### `create_post`
Creates a new blog post as a draft.

---

#### `update_navigation`
Replaces the header or footer navigation items. Creates a staged revision on a navigation record (same `staged_revision_id` pattern as pages).

---

### Preview & Publish Tools (permission: `write` for preview, `publish` for publishing)

---

#### `create_preview`

Generates a shareable preview URL for the staged revision of a page or post. **Always call this after `update_page_blocks` and show the URL to the user.**

```json
{
  "name": "create_preview",
  "description": "Generate a preview URL for staged changes so the user can review them before publishing. Show this URL to the user and ask them to confirm before publishing. The URL is valid for 48 hours.",
  "inputSchema": {
    "type": "object",
    "properties": {
      "content_type": { "type": "string", "enum": ["page", "post"] },
      "slug":         { "type": "string" }
    },
    "required": ["content_type", "slug"]
  }
}
```

Response:
```json
{
  "preview_url": "https://acme.cms.inte.team/preview/a9f3b2...",
  "expires_at": "2026-03-22T14:00:00Z",
  "summary": "Rewrote hero section to emphasise fast turnaround time."
}
```

---

#### `publish_staged`

Promotes the staged revision to live. **Only call this when the user has explicitly said they are happy with the preview and want to publish.**

```json
{
  "name": "publish_staged",
  "description": "Publish staged changes to the live site. IMPORTANT: Only call this after the user has explicitly confirmed they reviewed the preview and want to go live. Do not call this proactively.",
  "inputSchema": {
    "type": "object",
    "properties": {
      "content_type": { "type": "string", "enum": ["page", "post"] },
      "slug":         { "type": "string" }
    },
    "required": ["content_type", "slug"]
  }
}
```

---

#### `discard_staged`
Discards the staged revision without publishing. Live content is unchanged.

---

## Laravel Implementation

### Route

```php
// routes/api.php
Route::post('/mcp/v1', McpController::class)
    ->middleware(['auth:cms_mcp', 'throttle:120,1']);
```

### Controller

```php
final class McpController
{
    public function __invoke(McpRequest $request): JsonResponse
    {
        return match ($request->input('method')) {
            'initialize'  => $this->initialize(),
            'tools/list'  => $this->listTools($request->mcpToken()),
            'tools/call'  => $this->callTool(
                                $request->input('params.name'),
                                $request->input('params.arguments', []),
                                $request->mcpToken(),
                            ),
            default       => $this->methodNotFound($request->input('method')),
        };
    }
}
```

### Tool Registry

Tools self-register in `AppServiceProvider::boot()`. Same pattern as `BlockTypeRegistry`:

```php
McpToolRegistry::register('list_pages',        ListPagesTool::class,       permission: 'read');
McpToolRegistry::register('get_page',          GetPageTool::class,         permission: 'read');
McpToolRegistry::register('update_page_blocks',UpdatePageBlocksTool::class,permission: 'write');
McpToolRegistry::register('create_preview',    CreatePreviewTool::class,   permission: 'write');
McpToolRegistry::register('publish_staged',    PublishStagedTool::class,   permission: 'publish');
McpToolRegistry::register('discard_staged',    DiscardStagedTool::class,   permission: 'publish');
// ... etc
```

### Tool Interface

```php
interface McpTool
{
    public function schema(): array;                              // JSON Schema for inputSchema
    public function execute(array $arguments, Company $company): array;  // returns MCP content array
}
```

Each tool is a `final` class. Write tools use `RevisionService::stagePageRevision()` internally. They never touch `cms_pages.blocks` or `cms_pages.live_revision_id` directly.

### File Structure

```
app/
├── Http/
│   ├── Controllers/Api/McpController.php
│   └── Middleware/AuthenticateMcpToken.php
├── Mcp/
│   ├── McpToolRegistry.php
│   ├── Tools/
│   │   ├── ListPagesTool.php
│   │   ├── GetPageTool.php
│   │   ├── UpdatePageBlocksTool.php
│   │   ├── UpdatePageSeoTool.php
│   │   ├── CreatePageTool.php
│   │   ├── ListPostsTool.php
│   │   ├── GetPostTool.php
│   │   ├── UpdatePostBlocksTool.php
│   │   ├── CreatePostTool.php
│   │   ├── ListMediaTool.php
│   │   ├── GetNavigationTool.php
│   │   ├── UpdateNavigationTool.php
│   │   ├── GetSiteSettingsTool.php
│   │   ├── CreatePreviewTool.php
│   │   ├── PublishStagedTool.php
│   │   └── DiscardStagedTool.php
│   └── McpTokenGuard.php    -- Laravel auth guard for cms_mcp
```

---

## Admin UI: AI Integration Settings

```
resources/js/Pages/Admin/Settings/AiIntegration.tsx
```

Displays:
- List of active MCP tokens (name, permissions, last used, created)
- "Create Token" button → form: name, permissions checkboxes (read / write / publish)
- On create: shows raw token **once** with a "Copy" button and a warning ("Store this token securely — it won't be shown again")
- Revoke button per token

Admin route:
```
GET    /admin/settings/ai                    → Admin\AiSettingsController::index
POST   /admin/settings/ai/tokens             → Admin\AiSettingsController::createToken
DELETE /admin/settings/ai/tokens/{token}     → Admin\AiSettingsController::revokeToken
```

---

## Intended AI Workflow

The expected conversation flow when a shop owner uses an AI assistant with `write` permissions:

```
User:  "My homepage feels too generic. Can you make it more focused
        on fast phone screen repairs?"

AI:    [calls get_page("home")]
       [reads current blocks]
       [calls update_page_blocks("home", [...improved blocks...],
         "Rewrote hero and CTA to emphasise same-day screen repair")]

AI:    "I've updated your homepage. Here's a preview so you can check
        it before it goes live:
        https://acme.cms.inte.team/preview/a9f3b2...
        (Link valid for 48 hours. Let me know if you'd like any tweaks,
        or say 'publish' when you're happy.)"

User:  "Looks great! Publish it."

AI:    [calls publish_staged("page", "home")]

AI:    "Done — your homepage is now live."
```

---

## Error Handling

All tool errors return a structured MCP error response (never a Laravel exception page):

```json
{
  "jsonrpc": "2.0",
  "id": 1,
  "error": {
    "code": -32001,
    "message": "Page not found: no published page with slug 'services'",
    "data": { "slug": "services" }
  }
}
```

Error codes:
| Code | Meaning |
|------|---------|
| -32700 | Parse error (invalid JSON) |
| -32600 | Invalid request |
| -32601 | Method not found |
| -32602 | Invalid params (validation failure) |
| -32001 | Resource not found |
| -32002 | Permission denied (token lacks required permission) |
| -32003 | No staged revision exists (can't create preview or publish) |
| -32004 | CRM not connected (CRM block tool called with no CRM configured) |

---

## Security Notes

- MCP tokens are hashed (SHA-256) — a leaked DB does not expose raw tokens
- The `publish` permission is not granted by default — publishing remains intentional
- All write tools go through `RevisionService` which enforces the staged-only rule
- Rate limited to prevent abuse
- Token `revoked_at` is checked on every request (no caching of revoked tokens)
- MCP endpoint is completely separate from admin session auth — no session cookie accepted

---

## Tests

- `McpAuthTest` — valid token, invalid token, revoked token, missing header
- `McpPermissionsTest` — read token cannot call write tools, write token cannot publish
- `McpListPagesTest` — returns correct pages, excludes other companies
- `McpGetPageTest` — returns live blocks, not staged
- `McpUpdatePageBlocksTest` — creates staged revision, does NOT modify live blocks
- `McpCreatePreviewTest` — returns preview URL, token valid 48h, expired token 404
- `McpPublishStagedTest` — requires publish permission, promotes staged, busts cache
- `McpDiscardStagedTest` — clears staged, live unchanged
- `McpRateLimitTest` — 121st request in a minute returns 429

All in `tests/Feature/Mcp/`.
