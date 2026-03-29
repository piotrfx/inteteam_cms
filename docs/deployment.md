# Deployment Guide

How to deploy inteteam_cms on a VM (Proxmox or bare metal). For general deployment patterns see `inte-playbook/deployment/README.md`.

---

## Prerequisites

- Ubuntu 22.04+ or Debian 12+
- Docker Engine + Docker Compose plugin
- Git access to `git@github.com:piotrfx/inteteam_cms.git`
- Wildcard DNS: `*.cms.inte.team` A record pointing to the server IP
- SSL handled by Nginx Proxy Manager (NPM) externally

## Current Production

| Key | Value |
|-----|-------|
| Server | Dell R550 (Proxmox VM at 192.168.0.42) |
| Domain | cms.inte.team |
| Port | 8092 (Nginx inside container on :80, mapped to host :8092) |
| SSL | Nginx Proxy Manager (external) → forwards to :8092 |
| Profile | `prod` |

---

## First-Time Setup

```bash
# 1. Clone
cd /home/deploy
git clone git@github.com:piotrfx/inteteam_cms.git
cd inteteam_cms

# 2. Run setup script (interactive — creates .env, DB, admin user)
bash setup.sh --prod
```

The setup script handles:
- `.env` creation with generated DB passwords
- Docker services (profile: prod — no phpMyAdmin, no Mailpit, no Vite)
- Composer install (no-dev)
- APP_KEY generation
- Database migrations
- Storage symlink
- First admin company + user (interactive prompts)
- Frontend asset build
- Storage permissions
- Production cache (config, routes, views)

### Post-Setup Manual Steps

1. **Set APP_URL and TRUSTED_PROXIES** in `.env`:
   ```
   APP_URL=https://cms.inte.team
   APP_PORT=8092
   TRUSTED_PROXIES=*
   ```
   `TRUSTED_PROXIES=*` is required because the app sits behind Nginx Proxy Manager. Without it, Laravel won't trust the forwarded HTTPS headers, causing mixed-content errors (assets loaded over HTTP on an HTTPS page).

2. **Set APP_PORT** to avoid conflicts with other apps on the server (default 8090, production uses 8092).

3. **Configure mail** in `.env` (SMTP for password resets):
   ```
   MAIL_HOST=mail.inte.team
   MAIL_PORT=587
   MAIL_USERNAME=...
   MAIL_PASSWORD=...
   ```

4. **Configure NPM** — add proxy host:
   - Domain: `cms.inte.team`
   - Forward: `192.168.0.42:8092`
   - SSL: Let's Encrypt
   - Force SSL: yes
   - Wildcard: `*.cms.inte.team` (for tenant subdomains)

5. **Recreate containers and rebuild caches** after `.env` changes:
   ```bash
   # Recreate — docker-compose.yml uses env_file: .env, so containers
   # must be recreated (not just restarted) to pick up .env changes.
   # A plain "docker compose restart" does NOT re-read env_file.
   docker compose up -d --force-recreate php-fpm queue
   docker compose restart nginx

   # Rebuild Laravel caches inside the new containers
   docker compose exec php-fpm php artisan config:clear
   docker compose exec php-fpm php artisan config:cache
   docker compose exec php-fpm php artisan route:cache
   ```
   The setup script caches config before you edit `.env`. If you skip these steps, Laravel keeps using old (or empty) values — including `APP_KEY`, which causes a 500 "No application encryption key" error. Nginx must also be restarted because it caches the upstream php-fpm IP, which changes after container recreation (causes 502 Bad Gateway).

---

## Routine Deployment (Code Updates)

After pushing changes to `main`:

```bash
cd /home/deploy/inteteam_cms

# 1. Pull latest code
git pull

# 2. PHP dependencies (only if composer.lock changed)
docker compose exec -u www php-fpm composer install --no-dev --optimize-autoloader

# 3. Run migrations
docker compose exec -u www php-fpm php artisan migrate --force

# 4. Build frontend assets
docker compose run --rm --entrypoint npm npm run build

# 5. Clear and rebuild caches
docker compose exec -u www php-fpm php artisan optimize:clear
docker compose exec -u www php-fpm php artisan config:cache
docker compose exec -u www php-fpm php artisan route:cache
docker compose exec -u www php-fpm php artisan view:cache

# 6. Restart PHP-FPM (picks up new code)
docker compose restart php-fpm queue
```

**Note:** The `npm` service in docker-compose is missing `entrypoint: npm`, so you must pass `--entrypoint npm` when running builds. This will be fixed in a future update.

### Quick Deploy (no migration, no composer)

For frontend-only or minor PHP changes:

```bash
git pull
docker compose run --rm --entrypoint npm npm run build
docker compose exec -u www php-fpm php artisan optimize:clear
docker compose restart php-fpm
```

---

## Docker Services (Production Profile)

| Service | Container | Purpose |
|---------|-----------|---------|
| php-fpm | cms_php | Laravel application |
| queue | cms_queue | Redis queue worker |
| nginx | cms_nginx | Reverse proxy to PHP-FPM |
| mariadb | cms_mariadb | Database (MariaDB 11.8) |
| redis | cms_redis | Cache, queue, sessions |

Dev-only services (not started in `--profile prod`): phpMyAdmin, Mailpit, Node/Vite HMR.

---

## Environment Variables (Production)

Key differences from `.env.example` defaults:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://cms.inte.team
APP_PORT=8092
TRUSTED_PROXIES=*

LOG_LEVEL=warning

SESSION_SECURE_COOKIE=true

# DB passwords — generated by setup.sh, do not change after initial setup
DB_PASSWORD=<generated>
DB_ROOT_PASSWORD=<generated>

# Mail — real SMTP, not Mailpit
MAIL_HOST=mail.inte.team
MAIL_PORT=587

# Platform domain — used to extract tenant slug from subdomain
CMS_DOMAIN=cms.inte.team
```

---

## Troubleshooting

### Container won't start
```bash
docker compose --profile prod logs -f php-fpm
docker compose --profile prod logs -f nginx
```

### Database connection refused
```bash
# Check MariaDB is healthy
docker compose exec mariadb healthcheck.sh --connect --innodb_initialized

# Check credentials match
docker compose exec php-fpm php artisan tinker --execute="DB::connection()->getPdo(); echo 'OK';"
```

### Stale cache after deploy
```bash
docker compose exec -u www php-fpm php artisan optimize:clear
docker compose exec -u www php-fpm php artisan config:cache
docker compose exec -u www php-fpm php artisan route:cache
docker compose restart php-fpm
```

### Frontend assets not updating
```bash
# Verify build ran successfully
docker compose run --rm --entrypoint npm npm run build

# Check manifest exists
ls -la public/build/manifest.json
```

### Permission errors on storage
```bash
docker compose exec -u root php-fpm sh -c \
  "chown -R www:www /var/www/storage /var/www/bootstrap/cache && \
   chmod -R 775 /var/www/storage /var/www/bootstrap/cache"
```

---

## Backup

### Database
```bash
docker compose exec mariadb sh -c \
  'mariadb-dump -u root -p"$MARIADB_ROOT_PASSWORD" inteteam_cms' > backup_$(date +%Y%m%d).sql
```

### Media files
```bash
tar czf media_$(date +%Y%m%d).tar.gz storage/app/public/
```

### Restore
```bash
# Database
docker compose exec -T mariadb sh -c \
  'mariadb -u root -p"$MARIADB_ROOT_PASSWORD" inteteam_cms' < backup_20260329.sql

# Media
tar xzf media_20260329.tar.gz
```
