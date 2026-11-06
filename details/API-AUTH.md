# DRMS — Shared authentication & REST API

How **users**, **roles**, and **API access** work — for the admin UI, relief/logistics integration, Postman, and other external services.

---

## Two layers (important)

| Layer | Base path | Used by | Auth |
|-------|-----------|---------|------|
| **Admin JSON (AJAX)** | `/admin/.../data`, `/admin/.../summary` | AdminLTE pages (jQuery, DataTables) | Session cookie + CSRF on POST |
| **Public REST API** | `/api/v1/...` | Relief system, scripts, mobile, Postman | **JWT (recommended)** or session / access token |

The admin UI **does not** call `/api/v1/*` for tables. It uses **module-specific JSON routes** under `/admin/` (e.g. `GET /admin/incidents/data`). Those are still JSON over HTTP, but they are **not** the same as the external REST API.

Mutations in admin (save, approve, deploy) use **POST** to `/admin/...` via `DRMS.postData()` / `DRMS.postJson()` with CSRF — not REST `PUT`/`DELETE` on `/api/v1`.

---

## Shared authentication (Shield)

One user table and one role matrix power **both** the web admin and `/api/v1`:

- **Users** — `users` + `auth_identities` (Shield)
- **Roles** — `auth_groups_users` (e.g. `mdrrmo`, `volunteer`, `resident`)
- **Permissions** — `app/Config/AuthGroups.php` matrix (`incidents.*`, `users.*`, `admin.access`, …)

### Ways to authenticate API requests

1. **JWT (recommended for Postman & integrations)** — log in once, use Bearer token on all requests:
   ```http
   POST /api/v1/auth/login
   Authorization: Bearer {access_token}
   ```
2. **Browser session** — log in at `/login`, then call API with the same cookie (local testing only).
3. **Access token (legacy CLI)** — Shield personal access token via `php spark drms:api-token mdrrmo`.

Protected API routes use the **`chain`** filter: tries session → JWT → access token → HMAC (see `app/Config/Auth.php` → `authenticationChain`).

JWT config (`app/Config/AuthJWT.php`):

```env
auth.jwtSecret = your-32-byte-secret-here
auth.jwtTTL = 3600
```

Requires `firebase/php-jwt` (already in `composer.json`).

---

## JWT login

### `POST /api/v1/auth/login` — no auth required

**Body → raw → JSON:**

```json
{
  "email": "mdrrmo@drms.local",
  "password": "Drms@2026"
}
```

**Response (200):**

```json
{
  "data": {
    "token_type": "Bearer",
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "expires_in": 3600,
    "user": {
      "id": 2,
      "username": "mdrrmo",
      "email": "mdrrmo@drms.local",
      "groups": ["mdrrmo"]
    }
  }
}
```

Copy `access_token` into Postman **Authorization → Bearer Token**.

---

## Auth & roles endpoints

### `GET /api/v1/auth/me` — current user (any logged-in account)

No `admin.access` required — only valid authentication.

```bash
curl -s http://drms.web/api/v1/auth/me \
  -H "Authorization: Bearer YOUR_JWT"
```

```json
{
  "data": {
    "authenticated": true,
    "user": {
      "id": 2,
      "username": "mdrrmo",
      "email": "mdrrmo@drms.local",
      "groups": ["mdrrmo"],
      "primary_role": "mdrrmo",
      "account_status": "approved",
      "permissions": ["admin.access", "incidents.*", "users.*"],
      "full_name": "MDRRMO Duty Officer",
      "barangay_id": null
    }
  }
}
```

---

### `GET /api/v1/roles` — role catalog

Requires **`admin.access`**.

Returns every Shield group with its permission list plus the full permission dictionary.

---

## Users API — full CRUD

Requires **`users.*` or `admin.*`** (checked in controller).

| Method | Path | Action |
|--------|------|--------|
| GET | `/api/v1/users` | List (query: `limit`, `offset`, `search`, `status`, `role`) |
| GET | `/api/v1/users/{id}` | Detail |
| POST | `/api/v1/users` | Create |
| PUT/PATCH | `/api/v1/users/{id}` | Update |
| DELETE | `/api/v1/users/{id}` | Soft delete (deactivate) |

**Create user (POST body):**

```json
{
  "username": "new_resident",
  "email": "new@drms.local",
  "password": "Drms@2026",
  "group": "resident",
  "full_name": "Juan Dela Cruz",
  "phone": "09171234567",
  "account_status": "approved"
}
```

**Update user (PUT/PATCH body — all fields optional):**

```json
{
  "full_name": "Updated Name",
  "phone": "09180001111",
  "group": "volunteer",
  "account_status": "approved"
}
```

---

## Incidents API — full CRUD

List/read/create require **`admin.access`** (route filter). Write also checks **`incidents.*` or `admin.*`** in controller.

| Method | Path | Action |
|--------|------|--------|
| GET | `/api/v1/incidents` | List (`?limit=50`) |
| GET | `/api/v1/incidents/{id}` | Detail |
| POST | `/api/v1/incidents` | Create (staff) |
| PUT/PATCH | `/api/v1/incidents/{id}` | Update |
| DELETE | `/api/v1/incidents/{id}` | Delete |
| POST | `/api/v1/incidents/report` | Public citizen report (no auth) |

**Create incident (POST body):**

```json
{
  "title": "Flooding on Main Street",
  "severity": "moderate",
  "description": "Water level rising",
  "incident_type": "flood",
  "latitude": 14.5171,
  "longitude": 121.0509
}
```

**Update incident (PUT/PATCH body):**

```json
{
  "title": "Flooding — verified",
  "severity": "high",
  "description": "Evacuation advised",
  "status": "verified"
}
```

Valid `status`: `reported`, `verified`, `in_progress`, `resolved`, `closed`.

---

## Operations API (read-only extras)

All require **`admin.access`**:

| Method | Path |
|--------|------|
| GET | `/api/v1/stats` |
| GET | `/api/v1/volunteers` |
| GET | `/api/v1/evacuation-centers` |
| GET | `/api/v1/integrations/relief/*` |

See [`API-INTEGRATION.md`](API-INTEGRATION.md) for relief proxy contract.

---

## Test with Postman (local WAMP)

Base URL: **`http://drms.web`**

Test account: **`mdrrmo@drms.local`** / **`Drms@2026`** — see [`TEST_ACCOUNTS.md`](TEST_ACCOUNTS.md).

### Step 1 — Get JWT

| Method | URL |
|--------|-----|
| POST | `http://drms.web/api/v1/auth/login` |

Body (JSON):

```json
{
  "email": "mdrrmo@drms.local",
  "password": "Drms@2026"
}
```

Copy `data.access_token` from the response.

### Step 2 — Set Bearer token

1. Postman → **Authorization** → Type: **Bearer Token**
2. Paste the JWT

Or header manually:

| Key | Value |
|-----|--------|
| `Authorization` | `Bearer eyJ0eXAi...` |
| `Accept` | `application/json` |
| `Content-Type` | `application/json` |

### Step 3 — Try protected requests

| Method | URL |
|--------|-----|
| GET | `/api/v1/auth/me` |
| GET | `/api/v1/users` |
| POST | `/api/v1/users` |
| PUT | `/api/v1/users/{id}` |
| DELETE | `/api/v1/users/{id}` |
| GET | `/api/v1/incidents` |
| POST | `/api/v1/incidents` |
| PUT | `/api/v1/incidents/{id}` |
| DELETE | `/api/v1/incidents/{id}` |

### Step 4 — Collection variables (optional)

| Variable | Value |
|----------|--------|
| `baseUrl` | `http://drms.web` |
| `token` | (paste JWT from login) |

Use `{{baseUrl}}/api/v1/incidents` and Bearer `{{token}}`.

**Tip:** Add a Postman **Tests** script on the login request to auto-save the token:

```javascript
const json = pm.response.json();
if (json.data && json.data.access_token) {
  pm.collectionVariables.set('token', json.data.access_token);
}
```

---

## Error codes

| Code | Meaning |
|------|---------|
| **401** | Missing/invalid/expired JWT |
| **403** | Authenticated but missing permission (e.g. volunteer cannot call `/api/v1/users`) |
| **404** | Resource not found |
| **422** | Validation errors (check response body) |

---

## Admin UI — AJAX pattern (not `/api/v1`)

Examples:

| Page | List (GET JSON) | Mutations (POST) |
|------|-----------------|------------------|
| Incidents | `/admin/incidents/data` | `/admin/incidents/store`, `verify`, … |
| Users | `/admin/users/data` | `/admin/users/approve`, `update`, … |

Implemented in `public/assets/js/drms-admin.js`.

---

## Security notes

- Do not log full Bearer tokens.
- Use HTTPS in production.
- Set `auth.jwtSecret` in production (never use the dev fallback).
- Prefer JWT from a dedicated staff account (`mdrrmo`) for integrations.
- `/api/v1/*` routes are excluded from CSRF — auth is via JWT only.
