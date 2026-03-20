#!/usr/bin/env bash
set -euo pipefail

# ============================================================
# Inte.Team CMS — One-command setup script
# Usage:
#   bash setup.sh              (local dev)
#   bash setup.sh --prod       (production, Caddy handles SSL)
# ============================================================

PROFILE="dev"
IS_PROD=false

for arg in "$@"; do
    case $arg in
        --prod) PROFILE="prod"; IS_PROD=true ;;
    esac
done

# ─── Colours ───────────────────────────────────────────────
RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'
CYAN='\033[0;36m'; BOLD='\033[1m'; RESET='\033[0m'
ok()   { echo -e "${GREEN}✓${RESET} $1"; }
warn() { echo -e "${YELLOW}⚠${RESET}  $1"; }
info() { echo -e "${CYAN}→${RESET} $1"; }
fail() { echo -e "${RED}✗${RESET} $1" >&2; exit 1; }

echo ""
echo -e "${BOLD}Inte.Team CMS — Setup${RESET}"
echo "Profile: ${PROFILE}"
echo ""

# ─── Prerequisites ─────────────────────────────────────────
command -v docker >/dev/null 2>&1 || fail "Docker is not installed."
docker compose version >/dev/null 2>&1 || fail "Docker Compose plugin is not installed."
ok "Prerequisites OK"

# ─── .env ──────────────────────────────────────────────────
if [[ ! -f .env ]]; then
    cp .env.example .env
    ok "Created .env from .env.example"

    if $IS_PROD; then
        DB_PASS=$(openssl rand -base64 32 | tr -dc 'a-zA-Z0-9' | head -c 40)
        DB_ROOT_PASS=$(openssl rand -base64 32 | tr -dc 'a-zA-Z0-9' | head -c 40)

        sed -i "s|DB_PASSWORD=.*|DB_PASSWORD=${DB_PASS}|"             .env
        sed -i "s|DB_ROOT_PASSWORD=.*|DB_ROOT_PASSWORD=${DB_ROOT_PASS}|" .env
        sed -i "s|^APP_ENV=.*|APP_ENV=production|"                    .env
        sed -i "s|^APP_DEBUG=.*|APP_DEBUG=false|"                     .env
        sed -i "s|^SESSION_SECURE_COOKIE=.*|SESSION_SECURE_COOKIE=true|" .env
        sed -i "s|^LOG_LEVEL=.*|LOG_LEVEL=warning|"                   .env
        sed -i "s|^CMS_MEDIA_DISK=.*|CMS_MEDIA_DISK=local|"          .env

        echo ""
        echo -e "${BOLD}Generated secrets (saved to .env):${RESET}"
        echo -e "  DB_PASSWORD      = ${CYAN}${DB_PASS}${RESET}"
        echo -e "  DB_ROOT_PASSWORD = ${CYAN}${DB_ROOT_PASS}${RESET}"
        echo ""
        warn "Save these somewhere safe — they won't be shown again."
        echo ""
        warn "Still needs manual configuration in .env:"
        echo "  APP_URL       — platform root domain (e.g. https://cms.inte.team)"
        echo "  MAIL_*        — SMTP credentials for password resets"
        echo ""
        echo -e "  ${BOLD}Wildcard DNS required:${RESET}"
        echo "  An A record for *.cms.inte.team pointing to this server's IP."
        echo "  Caddy will obtain the wildcard TLS cert automatically."
        echo ""
        read -rp "Press Enter when ready, or Ctrl+C to abort…"
    fi
else
    ok ".env already exists — skipping"
fi

# ─── proxy-tier network (production) ───────────────────────
if $IS_PROD; then
    docker network create proxy-tier 2>/dev/null || true
    ok "proxy-tier network ready"
fi

# ─── Check for stale volumes (production) ──────────────────
if $IS_PROD; then
    PROJECT=$(docker compose config --format json 2>/dev/null | python3 -c "import sys,json; print(json.load(sys.stdin).get('name',''))" 2>/dev/null || basename "$PWD")
    VOLUME="${PROJECT}_mariadb_data"
    if docker volume inspect "$VOLUME" >/dev/null 2>&1; then
        echo ""
        warn "Existing MariaDB volume detected: ${VOLUME}"
        warn "If this is a fresh install, the old volume may cause an auth mismatch."
        read -rp "Remove it and start clean? [y/N] " CONFIRM
        if [[ "$CONFIRM" =~ ^[Yy]$ ]]; then
            docker compose --profile "$PROFILE" down -v 2>/dev/null || true
            ok "Old volumes removed"
        fi
    fi
fi

# ─── Start services ────────────────────────────────────────
info "Starting Docker services (profile: ${PROFILE})…"
docker compose --profile "$PROFILE" up -d --build
ok "Services started"

# ─── Wait for MariaDB ──────────────────────────────────────
info "Waiting for MariaDB to be ready…"
MAX_WAIT=60; WAITED=0
until docker compose exec -T mariadb sh -c 'mariadb-admin ping -h127.0.0.1 --silent 2>/dev/null || mysqladmin ping -h127.0.0.1 --silent 2>/dev/null'; do
    WAITED=$((WAITED+2))
    if (( WAITED >= MAX_WAIT )); then
        fail "MariaDB did not become ready within ${MAX_WAIT}s."
    fi
    sleep 2
done
ok "MariaDB is ready"

# ─── Composer install ──────────────────────────────────────
info "Installing PHP dependencies…"
if $IS_PROD; then
    docker compose exec -u root php-fpm composer install --no-dev --optimize-autoloader --no-interaction
else
    docker compose exec -u root php-fpm composer install --no-interaction
fi
ok "Composer dependencies installed"

# ─── App key ───────────────────────────────────────────────
if ! grep -qE "^APP_KEY=base64:" .env; then
    docker compose exec -u root php-fpm php artisan key:generate
    ok "APP_KEY generated"
else
    ok "APP_KEY already set"
fi

# ─── Migrations ────────────────────────────────────────────
info "Running migrations…"
if $IS_PROD; then
    docker compose exec -u root php-fpm php artisan migrate --force
else
    docker compose exec -u root php-fpm php artisan migrate --seed
fi
ok "Migrations complete"

# ─── Storage link ──────────────────────────────────────────
docker compose exec -u root php-fpm php artisan storage:link 2>/dev/null || true
ok "Storage symlink created"

# ─── First admin company + user ────────────────────────────
ADMIN_EMAIL=""
HAS_ADMIN=$(docker compose exec -u root -T php-fpm php artisan tinker \
    --execute="echo App\Models\CmsUser::where('role','admin')->exists() ? 'yes' : 'no';" \
    2>/dev/null | tr -d '[:space:]' || echo "no")

if [[ "$HAS_ADMIN" != "yes" ]]; then
    echo ""
    echo -e "${BOLD}Create first admin account${RESET}"
    read -rp "  Company name (e.g. Acme Repairs): " COMPANY_NAME
    read -rp "  Company slug (e.g. acme-repairs):  " COMPANY_SLUG
    read -rp "  Your name: "  ADMIN_NAME
    read -rp "  Your email: " ADMIN_EMAIL
    while true; do
        read -rsp "  Password: "         ADMIN_PASSWORD; echo ""
        read -rsp "  Confirm password: " ADMIN_PASSWORD_CONFIRM; echo ""
        [[ "$ADMIN_PASSWORD" == "$ADMIN_PASSWORD_CONFIRM" ]] && break
        warn "Passwords do not match. Please try again."
    done

    docker compose exec -u root -T php-fpm php artisan tinker --execute="
        \$company = App\Models\Company::create([
            'name'  => '${COMPANY_NAME}',
            'slug'  => '${COMPANY_SLUG}',
            'plan'  => 'starter',
            'theme' => 'default',
        ]);

        App\Models\CmsUser::create([
            'company_id' => \$company->id,
            'name'       => '${ADMIN_NAME}',
            'email'      => '${ADMIN_EMAIL}',
            'password'   => Hash::make('${ADMIN_PASSWORD}'),
            'role'       => 'admin',
        ]);

        echo 'created';
    " 2>/dev/null
    ok "Company and admin user created"
else
    ok "Admin user already exists — skipping"
fi

# ─── Frontend assets ───────────────────────────────────────
info "Building frontend assets…"
docker compose run --rm npm sh -c "npm ci && npm run build"
ok "Frontend assets built"

# ─── Fix storage permissions ───────────────────────────────
docker compose exec -u root php-fpm sh -c \
    "chown -R www:www /var/www/storage /var/www/bootstrap/cache && \
     chmod -R 775 /var/www/storage /var/www/bootstrap/cache"
ok "Storage permissions fixed"

# ─── Production caches ─────────────────────────────────────
if $IS_PROD; then
    docker compose exec -u root php-fpm php artisan config:cache
    docker compose exec -u root php-fpm php artisan route:cache
    docker compose exec -u root php-fpm php artisan view:cache
    docker compose exec -u root php-fpm sh -c \
        "chown -R www:www /var/www/storage /var/www/bootstrap/cache && \
         chmod -R 775 /var/www/storage /var/www/bootstrap/cache"
    ok "Production caches built"
fi

# ─── Summary ───────────────────────────────────────────────
APP_URL=$(grep "^APP_URL=" .env | cut -d= -f2-)

echo ""
echo -e "${GREEN}${BOLD}✓ Inte.Team CMS is ready.${RESET}"
echo ""
echo -e "  ${BOLD}Platform URL:${RESET}     ${APP_URL}"
echo -e "  ${BOLD}Admin panel:${RESET}      ${APP_URL}/admin"
if [[ -n "$ADMIN_EMAIL" ]]; then
    echo -e "  ${BOLD}Admin email:${RESET}      ${ADMIN_EMAIL}"
fi
echo ""
if $IS_PROD; then
    echo -e "  ${BOLD}Each shop site:${RESET}   https://{slug}.cms.inte.team"
    echo -e "  ${BOLD}Wildcard SSL:${RESET}     Caddy will provision *.cms.inte.team automatically."
    echo ""
    warn "Ensure your DNS has a wildcard A record:"
    echo "    *.cms.inte.team  →  $(curl -s ifconfig.me 2>/dev/null || echo '<this server IP>')"
    echo ""
fi
echo -e "  Docs: docs/architecture/controllers.md"
echo -e "  SOP:  .sop.md"
echo ""
echo -e "  I haven't nuked the server — probably. 🙂"
echo ""
