# DRMS (Group 1) ↔ Relief Module (Group 2) — REST API Integration

This document describes how **DRMS** connects to the relief & logistics module using HTTP REST APIs only.

There is **no shared codebase**. Databases may be separate or aligned to the joint ERP data dictionary (`users`, `incidents`, `inventory`, `audit_logs`). Integration happens over JSON over HTTPS.

See also [`ERP-INTEGRATION-STRATEGY.md`](ERP-INTEGRATION-STRATEGY.md) for the full two-group platform plan.

---

## Architecture

```
┌─────────────────────────────┐         ┌─────────────────────────────┐
│  Group 1 — DRMS (this repo) │         │  Group 2 — Relief module    │
│  http://drms.web/           │         │  https://relief.example/    │
├─────────────────────────────┤         ├─────────────────────────────┤
│  Incidents, volunteers,     │  HTTP   │  Inventory, distributions,  │
│  evac bridge, users (G1)    │ ◄─────► │  donations, relief audit    │
│                             │  JSON   │                             │
│  ExternalReliefApiClient    │ ──────► │  /api/v1/health             │
│  (outbound to relief)       │         │  /api/v1/inventory          │
│                             │         │  /api/v1/distributions      │
│  /api/v1/incidents          │ ◄────── │  (relief calls DRMS)        │
│  /api/v1/volunteers         │         │                             │
│  /api/v1/evacuation-centers  │         │                             │
└─────────────────────────────┘         └─────────────────────────────┘
```

**DRMS responsibilities:** incident reporting, volunteer management, evacuation centers, user/RBAC, operational reports.

**Relief system responsibilities:** relief goods inventory, stock levels, beneficiary distributions, QR/token claims, relief-specific audit logs.

---

## Quick start checklist

### DRMS team

1. Deploy DRMS with relief tables removed (migration `RemoveReliefAndAiTables` applied).
2. When the relief service is live, set `.env`:

```env
integrations.reliefApiEnabled = true
integrations.reliefApiBaseUrl = https://relief.example.gov.ph/api/v1
integrations.reliefApiKey = your-shared-secret
integrations.reliefApiTimeout = 15
```

3. Test: `GET http://drms.web/api/v1/integrations/relief/status` (logged in as admin).

### Relief system team

1. Expose a REST API with the endpoints in [Relief API contract](#relief-api-contract-what-the-relief-system-must-expose).
2. Protect endpoints with `Authorization: Bearer {shared-secret}` (same value as DRMS `integrations.reliefApiKey`).
3. Return JSON with a top-level `data` key for list endpoints.
4. (Optional) Call DRMS APIs to link distributions to verified incidents or barangays — see [DRMS API reference](#drms-api-reference-what-the-relief-system-can-consume).

---

## Authentication

### DRMS → Relief (outbound)

DRMS sends:

```http
Accept: application/json
Content-Type: application/json
Authorization: Bearer {integrations.reliefApiKey}
```

Configured in `app/Config/Integrations.php` and `app/Services/Integrations/ExternalReliefApiClient.php`.

### Relief → DRMS (inbound)

| Endpoint | Auth required |
|----------|---------------|
| `GET /api/v1/health` | None |
| `POST /api/v1/incidents/report` | None |
| `GET /api/v1/auth/me` | Any authenticated user (session or Bearer token) |
| `GET /api/v1/roles` | `admin.access` |
| `GET /api/v1/users`, `GET /api/v1/users/{id}` | `users.*` or `admin.*` |
| All other `/api/v1/*` routes | **`admin.access`** |

**Recommended for server-to-server:** issue a Shield **access token** for a service account (e.g. `mdrrmo` with `admin.access` and `users.*`). Send:

```http
Authorization: Bearer {shield-access-token}
```

Optional JWT — enable in `.env`:

```env
auth.jwtEnabled = true
```

Full auth, users, and roles reference: [`API-AUTH.md`](API-AUTH.md).

**Shareable one-pager + Postman:** [`ENDPOINTS-SHARE.md`](ENDPOINTS-SHARE.md) · [`postman/DRMS-API-v1.postman_collection.json`](postman/DRMS-API-v1.postman_collection.json)

**Local testing:** log in to DRMS in the browser and call protected endpoints with the session cookie (not suitable for production relief backend).

---

## Relief API contract (what the relief system must expose)

Base URL example: `https://relief.example.gov.ph/api/v1`

All responses should use `Content-Type: application/json`. HTTP status `2xx` = success; `4xx`/`5xx` = error with a `message` field when possible.

### `GET /health`

**Purpose:** Connectivity check. DRMS calls this when an admin hits `/api/v1/integrations/relief/status`.

**Response example:**

```json
{
  "status": "ok",
  "service": "relief-inventory",
  "version": "1.0.0",
  "time": "2026-06-11T10:00:00+08:00"
}
```

Any valid JSON with HTTP `200` is acceptable.

---

### `GET /inventory`

**Purpose:** List relief items / stock.

**Response shape (required by DRMS client):**

```json
{
  "data": [
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
  ],
  "meta": {
    "count": 1
  }
}
```

DRMS reads `response.data` as an array. Extra fields are allowed.

---

### `GET /distributions`

**Purpose:** List distribution records.

**Response example:**

```json
{
  "data": [
    {
      "id": 101,
      "reference_code": "DIST-2026-001",
      "relief_item_id": 1,
      "item_name": "Rice (5kg sack)",
      "quantity": 120,
      "beneficiary_name": "Juan Dela Cruz",
      "beneficiary_contact": "09171234567",
      "barangay_id": 3,
      "barangay_name": "Poblacion",
      "incident_id": 42,
      "incident_reference": "INC-20260611-0042",
      "distributed_at": "2026-06-11T14:30:00+08:00",
      "claim_status": "claimed",
      "notes": "Flood response batch 1"
    }
  ],
  "meta": {
    "count": 1
  }
}
```

---

### `POST /distributions` (optional, future)

**Purpose:** Create a distribution from DRMS or another authorized client.

DRMS includes `ExternalReliefApiClient::createDistribution()` but does not yet expose a public proxy route. The relief team should still implement this for direct relief-app use or future DRMS wiring.

**Request example:**

```json
{
  "relief_item_id": 1,
  "quantity": 10,
  "beneficiary_name": "Maria Santos",
  "beneficiary_contact": "09179876543",
  "barangay_id": 3,
  "incident_id": 42,
  "notes": "Linked to verified flood incident"
}
```

**Success response:**

```json
{
  "data": {
    "id": 102,
    "reference_code": "DIST-2026-002",
    "claim_qr_token": "abc123...",
    "quantity": 10,
    "distributed_at": "2026-06-11T15:00:00+08:00"
  }
}
```

**Validation expectations:**

- Reject if `quantity` exceeds available stock (`400` or `422`).
- Decrement inventory atomically on success.
- Return clear error messages: `{ "message": "Insufficient stock for item 1" }`.

---

### Error responses (relief system)

```json
{
  "status": 400,
  "error": 400,
  "messages": {
    "message": "Insufficient stock"
  }
}
```

DRMS surfaces errors as: `Relief API {status}: {message}`.

---

## DRMS API reference (what the relief system can consume)

Base URL: `{app.baseURL}api/v1` — e.g. `http://drms.web/api/v1` (local) or `https://drms.example.gov.ph/api/v1` (production).

### Public endpoints

#### `GET /health`

```bash
curl -s http://drms.web/api/v1/health
```

```json
{
  "status": "ok",
  "app": "DRMS",
  "version": "1.0.0-dev",
  "time": "2026-06-11T02:00:00+00:00"
}
```

#### `POST /incidents/report`

Public incident report (same rules as web `/report`). Send JSON or form fields.

**Required fields:**

| Field | Type | Values |
|-------|------|--------|
| `title` | string | 3–255 chars |
| `severity` | string | `low`, `moderate`, `high`, `critical` |

**Optional fields:**

| Field | Type | Notes |
|-------|------|-------|
| `description` | string | max 5000 |
| `incident_type` | string | `flood`, `fire`, `landslide`, `earthquake`, `typhoon`, `injured_people`, `rescue_request`, `other` |
| `barangay_id` | int | FK to `barangays` |
| `latitude`, `longitude` | decimal | GPS |

**Example:**

```bash
curl -s -X POST http://drms.web/api/v1/incidents/report \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Flooding on Main Street",
    "severity": "high",
    "incident_type": "flood",
    "barangay_id": 3,
    "description": "Knee-deep water, several households affected"
  }'
```

**Success (`201`):**

```json
{
  "data": {
    "id": 43,
    "reference_code": "INC-20260611-0043",
    "title": "Flooding on Main Street",
    "status": "reported",
    "severity": "high",
    "incident_type": "flood",
    "barangay_id": 3
  }
}
```

---

### Protected endpoints (require `admin.access`)

Use session cookie or JWT `Authorization: Bearer {token}`.

#### `GET /stats`

Dashboard KPIs for widgets / relief planning.

```json
{
  "data": {
    "active_incidents": 5,
    "volunteers_on_duty": 12,
    "evacuation_centers": 4,
    "evacuation_occupancy": "68%",
    "pending_verifications": 2,
    "total_volunteers": 45
  }
}
```

#### `GET /incidents`

Returns up to 50 most recent incidents (newest first).

```json
{
  "data": [
    {
      "id": 42,
      "reference_code": "INC-20260611-0042",
      "title": "Landslide near highway",
      "description": "...",
      "incident_type": "landslide",
      "severity": "critical",
      "priority_tag": "HIGH",
      "status": "verified",
      "latitude": "14.5171",
      "longitude": "121.2672",
      "barangay_id": 5,
      "reported_at": "2026-06-11 08:00:00",
      "verified_at": "2026-06-11 08:15:00"
    }
  ],
  "meta": {
    "count": 1
  }
}
```

**Typical relief use:** filter client-side for `status` in `verified`, `in_progress` when planning distributions.

#### `POST /incidents`

Create incident as authenticated staff (same validation as `POST /incidents/report`).

#### `GET /volunteers`

Returns up to 100 volunteers.

```json
{
  "data": [
    {
      "id": 7,
      "full_name": "Ana Reyes",
      "email": "ana@example.com",
      "phone": "09171234567",
      "barangay_id": 3,
      "availability_status": "available",
      "experience_years": 3,
      "id_verification_status": "approved"
    }
  ],
  "meta": {
    "count": 1
  }
}
```

#### `GET /evacuation-centers`

```json
{
  "data": [
    {
      "id": 1,
      "name": "Municipal gym",
      "barangay_id": 1,
      "address": "Poblacion, Baras, Rizal",
      "capacity": 200,
      "current_occupancy": 130,
      "families_registered": 45,
      "medical_needs_count": 3,
      "status": "open",
      "contact_phone": "09180001111"
    }
  ],
  "meta": {
    "count": 1
  }
}
```

#### `GET /evacuation-centers/{id}`

Single center or `404` if not found.

---

### DRMS relief proxy endpoints (admin only)

These do **not** store relief data locally. They forward to the external relief API configured in `.env`.

| Method | Path | Description |
|--------|------|-------------|
| `GET` | `/integrations/relief/status` | Connection health |
| `GET` | `/integrations/relief/inventory` | Proxy inventory list |
| `GET` | `/integrations/relief/distributions` | Proxy distribution list |

**Status response when not configured:**

```json
{
  "data": {
    "connected": false,
    "message": "Relief API not configured. Set integrations.reliefApiBaseUrl in .env when the external system is ready."
  }
}
```

**Status response when connected:**

```json
{
  "data": {
    "connected": true,
    "message": "External relief API reachable.",
    "data": {
      "status": "ok",
      "service": "relief-inventory"
    }
  }
}
```

---

## Suggested integration flows

### Flow A — Relief linked to verified incident

1. DRMS staff verifies incident `INC-20260611-0042`.
2. Relief system polls `GET /api/v1/incidents` (or receives webhook later if you add one).
3. Staff creates distribution in relief app with `incident_id: 42`.
4. DRMS admin checks stock via `GET /api/v1/integrations/relief/inventory`.

### Flow B — Barangay-based distribution after flood

1. Relief system reads `GET /api/v1/evacuation-centers` for occupancy hotspots.
2. Staff distributes goods in relief app tagged with `barangay_id`.
3. Relief audit log stays in relief database; DRMS audit log stays in DRMS.

### Flow C — Health monitoring

1. Cron on either side calls both `GET /health` endpoints.
2. Alert if `connected: false` from `/api/v1/integrations/relief/status`.

---

## Security notes

- Use **HTTPS** in production for both systems.
- Rotate `integrations.reliefApiKey` periodically; keep secrets out of git.
- Relief API should **not** expose DRMS database credentials.
- DRMS protected routes require `admin.access` — create a dedicated integration user with minimal permissions if Shield groups are extended later.
- Rate-limit public endpoints (`/incidents/report`) on both sides.
- Do not log full Bearer tokens.

---

## Code references (DRMS)

| File | Role |
|------|------|
| `app/Config/Integrations.php` | `.env` settings for relief API |
| `app/Services/Integrations/ExternalReliefApiClient.php` | HTTP client to relief system |
| `app/Controllers/Api/V1/IntegrationsApi.php` | Admin proxy routes |
| `app/Config/Routes.php` | Route definitions under `api/v1` |
| `app/Controllers/Api/V1/IncidentsApi.php` | Incidents REST + public report |
| `app/Controllers/Api/V1/VolunteersApi.php` | Volunteers REST |
| `app/Controllers/Api/V1/EvacuationCentersApi.php` | Evacuation centers REST |
| `app/Controllers/Api/V1/DashboardApi.php` | Stats REST |

---

## Local development example

**1. DRMS `.env` (pointing to local relief stub on port 8081):**

```env
integrations.reliefApiEnabled = true
integrations.reliefApiBaseUrl = http://localhost:8081/api/v1
integrations.reliefApiKey = dev-shared-secret
```

**2. Test relief health from command line (simulating DRMS client):**

```bash
curl -s http://localhost:8081/api/v1/health \
  -H "Authorization: Bearer dev-shared-secret"
```

**3. Test DRMS proxy (after admin login or with JWT):**

```bash
curl -s http://drms.web/api/v1/integrations/relief/status \
  -H "Authorization: Bearer {drms-jwt}"
```

---

## Versioning

- Current DRMS API prefix: `/api/v1`
- Relief system should use its own versioned prefix (e.g. `/api/v1`) at `integrations.reliefApiBaseUrl`
- Breaking changes should bump the version segment (`/api/v2`) on the service that changes

---

## Related docs

- `details/README-DRMS.md` — project overview
- `details/ARCHITECTURE.md` — system architecture
- `details/TEST_ACCOUNTS.md` — demo login accounts for local testing
