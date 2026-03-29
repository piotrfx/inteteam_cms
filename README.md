# InteTeam CMS

Standalone SaaS CMS for UK repair shops. Each tenant gets a website at `{slug}.cms.inte.team` with a WordPress-style admin panel, block editor, public Blade-rendered site, and AI editing via MCP.

## Stack

- **Backend:** Laravel 12, PHP 8.3, MariaDB, Redis
- **Frontend (admin):** React 19, TypeScript, Tailwind v4, Inertia v2
- **Public site:** Blade templates with CSS custom properties for theming
- **Infrastructure:** Docker (multi-profile dev/prod), Nginx, Proxmox

## Architecture

- **Multi-tenancy** via subdomain — `ResolveTenant` middleware resolves `{slug}` to a company
- **Auth guard:** `cms` (authenticatable: `CmsUser`, not `User`)
- **Block editor** — blocks stored as JSON on pages/posts, rendered via `BlockRendererService`
- **Revisions & staging** — edits go to `staged_revision_id`, publishing copies to live
- **MCP server** (`POST /mcp/v1`) — JSON-RPC 2.0 for AI-driven page editing
- **CRM integration** — read-only HTTP client (`CrmApiClient`) to inteteam_crm

## Quick Start

```bash
# Clone and start
git clone git@github.com:piotrfx/inteteam_cms.git
cd inteteam_cms
cp .env.example .env
docker compose --profile dev up -d

# Install dependencies and setup
docker compose exec php-fpm composer install
docker compose exec php-fpm php artisan key:generate
docker compose exec php-fpm php artisan migrate --seed
docker compose run --rm npm install && docker compose run --rm npm run build
```

Add local subdomain entries to `/etc/hosts`:

```
127.0.0.1  cms.inte.team
127.0.0.1  acme.cms.inte.team
```

## Dev URLs

| Service | URL |
|---------|-----|
| Application | http://localhost:8090 |
| Vite HMR | http://localhost:5190 |
| phpMyAdmin | http://localhost:8091 |
| Mailpit | http://localhost:8029 |

## Production

Deployed at **cms.inte.team** (192.168.0.42:8092) via Nginx Proxy Manager + SSL.

## Commands

```bash
# Tests
docker compose exec php-fpm php artisan test
docker compose exec php-fpm php artisan test --filter=FeatureName

# Code quality
docker compose exec php-fpm ./vendor/bin/pint --dirty
docker compose exec php-fpm ./vendor/bin/phpstan analyse --memory-limit=512M

# Frontend
docker compose run --rm npm run build
docker compose run --rm npm run dev
```

## Documentation

| Doc | Purpose |
|-----|---------|
| `CLAUDE.md` | AI/developer quickstart and conventions |
| `.sop.md` | Feature planning (9-step), debugging, architecture analysis |
| `docs/planning/README.md` | Master plan — DB schema, phases, block types |
| `docs/architecture/controllers.md` | Controller/Service/DTO pattern with examples |
| `docs/features/` | Per-feature READMEs (pages, posts, blocks, media, auth, navigation, tenancy, theming, SEO, settings, CRM integration, revisions, MCP) |

## Build Phases

| Phase | Status | Scope |
|-------|--------|-------|
| 1 | Done | Pages, posts, blocks, media, auth, navigation, tenancy, theming, SEO, settings |
| 2 | Done | CRM integration, revisions & staging, MCP server |
| 3 | Planned | SSO integration (PKCE flow, token refresh, subscription gating) |
| 4 | Planned | Next.js extraction for public site |

## Related Repos

- [inteteam_crm](https://github.com/piotrfx/inteteam_crm) — backend API (products, orders, customers)
- [inteteam_sso](https://github.com/piotrfx/inteteam_sso) — single sign-on (Phase 3 dependency)
- [store_front](https://github.com/piotrfx/store_front) — universal customer-facing storefront
- [inte-playbook](https://github.com/piotrfx/inte-playbook) — dev conventions and patterns
