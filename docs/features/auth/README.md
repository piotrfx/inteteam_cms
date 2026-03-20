# Auth Feature

**Status:** Phase 1 (local) → Phase 3 (SSO)

---

## Overview

Authentication is intentionally simple in Phase 1 — email/password with a standard Laravel session. Phase 3 swaps the login flow for OAuth2 via inteteam_sso. The backend scaffolding for SSO is added in Phase 1 (config + disabled flag) so Phase 3 is a drop-in.

---

## Phase 1 — Local Auth

### User Stories

- As a shop owner, I can log in with my email and password to access the admin panel.
- As a shop owner, I can request a password reset via email.
- As a shop owner, I can log out and my session is terminated.

### Routes (`routes/admin.php`)

```
GET  /login          → Auth\LoginController::showForm
POST /login          → Auth\LoginController::login
POST /logout         → Auth\LoginController::logout
GET  /password/forgot → Auth\PasswordController::showForgotForm
POST /password/forgot → Auth\PasswordController::sendResetLink
GET  /password/reset/{token} → Auth\PasswordController::showResetForm
POST /password/reset  → Auth\PasswordController::reset
```

All admin routes are behind `auth` middleware. Unauthenticated requests redirect to `/login`.

### Model: `CmsUser`

```php
final class CmsUser extends Authenticatable
{
    use HasUlids, HasCompanyScope, SoftDeletes;

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'email_verified_at' => 'datetime',
        ];
    }
}
```

`CmsUser` is the authenticatable model, not `User`. The auth guard in `config/auth.php` uses the `cms_users` table.

### Middleware

`auth` — Laravel's built-in auth middleware, guards the entire `/admin` prefix.

`ResolveTenant` — runs before auth, sets company context from subdomain. If subdomain does not match a company, returns 404. See `features/tenancy/`.

### Policy

Only users belonging to the current company can access that company's admin. This is enforced by `HasCompanyScope` — all queries are already scoped. There is no cross-tenant login.

### Controllers

```
app/Http/Controllers/Auth/
├── LoginController.php
└── PasswordController.php
```

`LoginController::login()`:
1. Validate credentials
2. Attempt `Auth::guard('cms')->attempt()`
3. On success: `session()->regenerate()`, redirect to `/admin`
4. On failure: redirect back with validation error

### Views

Login and password reset pages are Blade (not Inertia) — they sit outside the admin panel and must render without JS hydration.

```
resources/views/auth/
├── login.blade.php
└── passwords/
    ├── forgot.blade.php
    └── reset.blade.php
```

---

## Phase 3 — SSO

Replaces the login flow. Local password auth is retired; `cms_users.password` becomes nullable.

### What Changes

| Component | Change |
|-----------|--------|
| `LoginController` | Add SSO redirect button. Local login remains as fallback. |
| `SsoController` | New controller (copied from inteteam_crm pattern) |
| `SsoService` | New service (copied from inteteam_crm pattern) |
| `HandleSsoToken` | New middleware: auto-refreshes expired access tokens |
| `cms_users` | Add `sso_user_id VARCHAR NULL` migration |
| `.env` | `SSO_ENABLED=true` + 3 SSO vars |

### SSO Flow

```
User clicks "Log in with inteteam account"
  → SsoController::redirect()
      → Generate PKCE (code_verifier + code_challenge)
      → Store verifier + state in session
      → Redirect to SSO: GET /oauth/authorize?client_id=...&code_challenge=...

SSO authenticates user, redirects back:
  → GET /auth/sso/callback?code=...&state=...
      → SsoController::callback()
          → Validate state matches session
          → POST /oauth/token (exchange code + verifier for tokens)
          → GET /oauth/userinfo (fetch claims)
          → Find CmsUser by sso_user_id OR email
          → If not found: create CmsUser (role from token claims)
          → Set sso_user_id if not already set
          → Auth::guard('cms')->login($user)
          → Redirect to /admin
```

### Token Storage

Stored in session (not DB):
- `sso_access_token`
- `sso_refresh_token`
- `sso_token_expires_at`
- `sso_claims` (full decoded claims array)

### HandleSsoToken Middleware

Runs on every authenticated admin request:
1. Check `session('sso_token_expires_at')` vs `now()`
2. If expired and refresh token present → call `SsoService::refreshToken()`
3. Update session with new token set
4. If refresh fails → destroy session, redirect to login

### Subscription Gating

```php
$claims = session('sso_claims');
$tier = $claims['subscriptions']['cms'] ?? null; // 'standard', 'pro', or null

if ($tier === null) {
    abort(403, 'No CMS subscription.');
}
```

---

## Tests

Phase 1:
- `LoginTest` — valid login, invalid password, locked account, logout
- `PasswordResetTest` — request link, use token, expired token

Phase 3:
- `SsoCallbackTest` — valid callback, invalid state, token exchange failure, new user creation, existing user match

All in `tests/Feature/Auth/`.
