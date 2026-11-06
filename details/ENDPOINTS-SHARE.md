# DRMS API v1 — Shareable Endpoint Reference

**For:** Relief module, inventory system, mobile apps, and other connected systems  
**DRMS base URL (local):** `http://drms.web/api/v1`  
**DRMS base URL (production):** `https://{your-host}/api/v1`

Related docs: [`API-INTEGRATION.md`](API-INTEGRATION.md) · [`API-AUTH.md`](API-AUTH.md)  
Postman collection: [`postman/DRMS-API-v1.postman_collection.json`](postman/DRMS-API-v1.postman_collection.json)

---

## Quick start

### 1. Check DRMS is up

```http
GET /api/v1/health
```

No authentication required.

### 2. Get an access token (server-to-server)

```http
POST /api/v1/auth/login
Content-Type: application/json

{
  "email": "mdrrmo@drms.local",
  "password": "YourPassword"
}
```

Response:

```json
{
  "data": {
    "token_type": "Bearer",
    "access_token": "eyJ...",
    "expires_in": 3600,
    "user": { "id": 1, "username": "mdrrmo", "email": "...", "groups": ["mdrrmo"] }
  }
}
```

### 3. Call protected endpoints

```http
GET /api/v1/incidents
Authorization: Bearer {access_token}
Accept: application/json
```

---

## Authentication summary

| Endpoint group | Required auth |
|----------------|---------------|
| `GET /health`, `POST /incidents/report`, `POST /auth/login` | **None** |
| `GET /auth/me` | Any valid auth (JWT, Shield access token, or session cookie) |
| `GET /users/*`, `POST /users/*`, etc. | JWT/token + permission `users.*` or `admin.*` |
| Most other `/api/v1/*` routes | JWT/token + permission **`admin.access`** |

**Headers for JSON APIs:**

```http
Accept: application/json
Content-Type: application/json
Authorization: Bearer {token}
```

**CORS:** Enabled for `http://drms.web`, `http://localhost`, `http://127.0.0.1` (configure more in `app/Config/Cors.php`).

---

## Public endpoints (no auth)

| Method | Path | Description |
|--------|------|-------------|
| `GET` | `/health` | API health + relief bridge status |
| `POST` | `/incidents/report` | Public disaster report (JSON or form) |
| `POST` | `/auth/login` | Email/password → JWT |

### `GET /health`

**Response example:**

```json
{
  "status": "ok",
  "app": "DRMS",
  "app_full_name": "Disaster Risk Management System",
  "relief_api": "connected",
  "version": "1.0.0-dev",
  "time": "2026-06-18T12:00:00+08:00"
}
```

### `POST /incidents/report`

**Required:** `title`, `severity` (`low` \| `moderate` \| `high` \| `critical`)

**Optional:** `description`, `incident_type`, `barangay_id`, `latitude`, `longitude`

**Success:** `201` with `data.reference_code`, `data.id`

---

## Auth

| Method | Path | Auth | Description |
|--------|------|------|-------------|
| `POST` | `/auth/login` | None | Returns JWT `access_token` |
| `GET` | `/auth/me` | Bearer / session | Current user + groups |

---

## Dashboard & stats

**Auth:** `admin.access`

| Method | Path | Description |
|--------|------|-------------|
| `GET` | `/stats` | KPI overview |
| `GET` | `/dashboard/live` | Live stats + alerts |
| `GET` | `/dashboard/incidents-map` | Incidents map (GeoJSON) |

---

## Incidents

**Auth:** `admin.access` (except public report above)

| Method | Path | Description |
|--------|------|-------------|
| `GET` | `/incidents` | List (`?limit=50`, default 50, max 100) |
| `GET` | `/incidents/{id}` | Single incident |
| `POST` | `/incidents` | Create (staff) |
| `PUT` | `/incidents/{id}` | Update |
| `PATCH` | `/incidents/{id}` | Partial update |
| `DELETE` | `/incidents/{id}` | Soft delete |
| `POST` | `/incidents/{id}/verify` | Mark verified |

**Relief use:** Poll `GET /incidents`, filter `status` = `verified` or `in_progress`, link `incident_id` / `reference_code` to distributions.

---

## Volunteers

**Auth:** `admin.access`

| Method | Path | Description |
|--------|------|-------------|
| `GET` | `/volunteers` | List (`?limit=100`, `?search=`) |
| `GET` | `/volunteers/{id}` | Detail + skills |
| `POST` | `/volunteers` | Create |
| `PUT` | `/volunteers/{id}` | Update |
| `PATCH` | `/volunteers/{id}` | Partial update |
| `DELETE` | `/volunteers/{id}` | Delete |
| `POST` | `/volunteers/{id}/verify-id` | Approve ID document |
| `POST` | `/volunteers/{id}/reject-id` | Reject ID document |
| `POST` | `/volunteers/{id}/id-document` | Upload ID (`multipart/form-data`, field `id_document`) |

---

## Evacuation centers

**Auth:** `admin.access`

| Method | Path | Description |
|--------|------|-------------|
| `GET` | `/evacuation-centers/summary` | Occupancy summary |
| `GET` | `/evacuation-centers` | List all centers |
| `GET` | `/evacuation-centers/{id}` | Single center |
| `POST` | `/evacuation-centers` | Create |
| `PUT` | `/evacuation-centers/{id}` | Update |
| `PATCH` | `/evacuation-centers/{id}` | Partial update |
| `DELETE` | `/evacuation-centers/{id}` | Delete |
| `GET` | `/evacuation-centers/{id}/families` | Families at center |
| `POST` | `/evacuation-centers/{id}/families` | Register family |
| `POST` | `/evacuation-centers/{id}/families/{familyId}/checkout` | Check out family |

**Relief use:** `GET /evacuation-centers` for occupancy hotspots and `barangay_id` planning.

---

## Reports & audit

**Auth:** `admin.access`

| Method | Path | Description |
|--------|------|-------------|
| `GET` | `/reports/analytics` | Analytics aggregates |
| `GET` | `/audit-trail` | Audit log list |
| `GET` | `/audit-trail/summary` | Summary counts |
| `GET` | `/audit-trail/timeline` | Timeline view |
| `GET` | `/audit-trail/{id}` | Single entry |

---

## Volunteer dispatch (matching)

**Auth:** `admin.access`

| Method | Path | Description |
|--------|------|-------------|
| `GET` | `/matching/queue` | Deployment queue |
| `GET` | `/matching/incidents/{id}` | Recommended volunteers |
| `POST` | `/matching/deploy` | Deploy one volunteer |
| `POST` | `/matching/deploy-bulk` | Bulk deploy |
| `POST` | `/matching/auto-assign` | Auto-assign |

---

## Users & roles

| Method | Path | Auth | Description |
|--------|------|------|-------------|
| `GET` | `/roles` | `admin.access` | Role catalog |
| `GET` | `/users` | `users.*` or `admin.*` | List users |
| `GET` | `/users/{id}` | `users.*` or `admin.*` | User detail |
| `POST` | `/users` | `users.*` or `admin.*` | Create user |
| `PUT` | `/users/{id}` | `users.*` or `admin.*` | Update user |
| `PATCH` | `/users/{id}` | `users.*` or `admin.*` | Partial update |
| `DELETE` | `/users/{id}` | `users.*` or `admin.*` | Soft delete |
| `POST` | `/users/{id}/approve` | `users.*` or `admin.*` | Approve registration |
| `POST` | `/users/{id}/reject` | `users.*` or `admin.*` | Reject registration |
| `POST` | `/users/{id}/verify-id` | `users.*` or `admin.*` | Verify ID document |

---

## DRMS → Relief / inventory proxy

DRMS does **not** store relief inventory locally. These routes proxy your external relief API (configured in `.env`).

**Auth:** `admin.access`

| Method | Path | Description |
|--------|------|-------------|
| `GET` | `/integrations/relief/status` | Relief API connectivity |
| `GET` | `/integrations/relief/inventory` | Proxy → your `GET /inventory` |
| `GET` | `/integrations/relief/distributions` | Proxy → your `GET /distributions` |

### DRMS `.env` (operations team)

```env
integrations.reliefApiEnabled = true
integrations.reliefApiBaseUrl = https://relief.example.gov.ph/api/v1
integrations.reliefApiKey = your-shared-secret
integrations.reliefApiTimeout = 15
```

---

## What your relief / inventory system must expose

**Base URL example:** `https://relief.example.gov.ph/api/v1`

| Method | Path | Description |
|--------|------|-------------|
| `GET` | `/health` | Connectivity check |
| `GET` | `/inventory` | Stock list → `{ "data": [ ... ], "meta": { "count": N } }` |
| `GET` | `/distributions` | Distribution list → `{ "data": [ ... ] }` |
| `POST` | `/distributions` | (Recommended) Create distribution |

**Auth from DRMS:**

```http
Authorization: Bearer {integrations.reliefApiKey}
Accept: application/json
```

### Inventory item shape (recommended)

```json
{
  "id": 1,
  "sku": "RICE-5KG",
  "name": "Rice (5kg sack)",
  "category": "food",
  "quantity": 450,
  "unit": "sacks",
  "low_stock_threshold": 50,
  "warehouse_location": "Municipal warehouse",
  "expires_at": "2026-12-31"
}
```

### Distribution record shape (recommended)

```json
{
  "id": 101,
  "reference_code": "DIST-2026-001",
  "relief_item_id": 1,
  "item_name": "Rice (5kg sack)",
  "quantity": 120,
  "beneficiary_name": "Juan Dela Cruz",
  "beneficiary_contact": "09171234567",
  "barangay_id": 3,
  "incident_id": 42,
  "incident_reference": "INC-20260611-0042",
  "distributed_at": "2026-06-11T14:30:00+08:00",
  "claim_status": "claimed"
}
```

---

## Other public DRMS routes (not under `/api/v1`)

| Method | Path | Description |
|--------|------|-------------|
| `GET` | `/api/evac/nearest` | Nearest evac centers (query: lat, lng) |
| `POST` | `/report/submit` | Web form incident report |
| `GET` | `/cron/notifications?key={secret}` | Process email queue (ops cron) |

---

## Integration flows

### Flow A — Distribution linked to incident

1. Relief system: `GET /api/v1/incidents` (filter `status=verified`)
2. Create distribution in relief DB with `incident_id` + `incident_reference`
3. DRMS admin: `GET /api/v1/integrations/relief/distributions` to view

### Flow B — Barangay relief after flood

1. `GET /api/v1/evacuation-centers` — occupancy by barangay
2. Distribute goods in relief app with `barangay_id`
3. `GET /api/v1/integrations/relief/inventory` — stock check

### Flow C — Health monitoring

1. Cron: `GET /api/v1/health` (DRMS)
2. Cron: `GET {reliefBaseUrl}/health` (relief)
3. Cron: `GET /api/v1/integrations/relief/status` (bridge)

---

## Error format

Validation errors (`400`):

```json
{
  "status": 400,
  "error": 400,
  "messages": {
    "email": "The email field must contain a valid email address."
  }
}
```

Auth errors (`401`):

```json
{
  "status": 401,
  "message": "Authentication required. Please log in again."
}
```

---

## Versioning

- Current prefix: **`/api/v1`**
- Breaking changes should use `/api/v2` on the service that changes
- Relief system should version its own base URL independently

---

## Local test accounts

See [`TEST_ACCOUNTS.md`](TEST_ACCOUNTS.md). Example integration account:

- Email: `mdrrmo@drms.local`
- Password: `Drms@2026` (change in production)

---

*Generated for DRMS ERP integration — share this file + Postman collection with your relief/inventory team.*
