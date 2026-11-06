# DRMS – Implementation Notes

This project is a **CodeIgniter 4** + **AdminLTE 3** base for a Disaster Relief Coordination & Volunteer Matching System. Use this file together with the in-code comments when onboarding new developers.

**Full architecture guide (same format as Student Portal doc):** [ARCHITECTURE.md](ARCHITECTURE.md)

## Which URL is the main landing page?

| URL | What you see |
|-----|----------------|
| **`http://drms.web/`** | **DRMS public homepage** (carousel, modules, map, weather) — **use this** |
| `http://drms.web/login` | Staff login (burgundy split layout) |
| `http://drms.web/admin/dashboard` | Operations dashboard (after login) |
| `http://localhost/` | WAMP server homepage (list of all projects — **not DRMS**) |
| `http://localhost/drms/` | DRMS homepage (after `index.php` at project root) |

Apache **DocumentRoot** for DRMS must be the **`public/`** folder (virtual host `drms.web`). Do not open only the project folder in the browser without `public/` unless the root `index.php` redirect is in place.

The **index / landing page** is `app/Views/home/index.php` → route **`GET /`** → `Home::index`. It is not a separate “computer studies” or WAMP demo page.

## Stack vs. specification

- **Bootstrap**: AdminLTE **3.x** ships with **Bootstrap 4**. The UI spec mentioned Bootstrap 5; migrating would require AdminLTE 4+ and a full regression pass — **not done here** so hosting stays simple and InfinityFree-friendly.
- **Icons**: Public site can use **Lucide**; AdminLTE continues to use **Font Awesome** bundled with the template.

## Database

**Quick setup (Windows):** double-click `setup-database.bat` in the project root.

**Manual setup:**

1. **Start WAMP** (Apache + MySQL).
2. Create database: import `database/01_create_database.sql` in phpMyAdmin, or:

   ```bash
   mysql -u root < database/01_create_database.sql
   ```

3. Configure `.env` → `database.default.database = drms_db`.
4. Create all tables via migrations:

   ```bash
   php spark migrate --all
   php spark db:seed DrmsDemoSeeder
   ```

**Alternative (phpMyAdmin only):** import `database/drms_db_schema.sql` (full table DDL). See `database/README.md`.

## What works now (modules)

| Module | Status |
|--------|--------|
| **Public** `/report` | Citizen reporting + Leaflet map picker, media upload |
| **Public** `/evac-centers` | Shelter map, nearest center, family registration |
| **Dashboard** | KPIs, Leaflet incident map (disaster icons), heatmap, live poll |
| **Incidents** | CRUD, verify, media, geo resolution via barangay |
| **Volunteers** | CRUD, skills, ID verify/reject |
| **Volunteer Dispatch** | Incident queue, map, staffing bars, bulk/auto deploy |
| **Evacuation Centers** | Admin map + charts; public page with pins |
| **Users** | Approval workflow; register as **resident** first |
| **Reports & Analytics** | Charts, live refresh, PDF/Excel export |
| **Audit Trail** | Filters, timeline, detail modal, CSV export |
| **Relief** | **External only** — proxy via `/api/v1/integrations/relief/*` (see `API-INTEGRATION.md`) |
| **API** | Health, incidents, volunteers, evac centers, stats, public report POST |

## Test login accounts (all roles)

Run once after migrations:

```bash
php spark db:seed DrmsTestUsersSeeder
```

Then sign in at **`/login`** using any account in **`TEST_ACCOUNTS.md`** (password **`Drms@2026`** for all test users).

Public self-registration is **enabled** at `/register` (email + password). New accounts start as **resident**; admin approves and assigns roles in `app/Config/AuthGroups.php`.

## Security

- **Admin + JSON API** (except `GET /api/v1/health`) use the Shield filter **` permission:admin.access `**, which requires a logged-in user with the right permission matrix.
- **CSRF** is set to **session** mode (Shield setup). A CSRF meta tag is output in `app/Views/layouts/admin.php` for future AJAX `POST`/`PUT`/`DELETE` forms.
- Harden **CORS**, **rate limiting**, and **API tokens** (`TokenAuth`) before exposing the API to third parties.

## REST API (v1)

Admin pages and external systems use **`/api/v1/*`** (session cookie or JWT).

- **Endpoint list (share with partners):** [ENDPOINTS-SHARE.md](ENDPOINTS-SHARE.md)
- **Postman collection:** [postman/DRMS-API-v1.postman_collection.json](postman/DRMS-API-v1.postman_collection.json)
- **Auth:** [API-AUTH.md](API-AUTH.md)
- **Relief bridge:** [API-INTEGRATION.md](API-INTEGRATION.md)

## Folder layout (high level)

- `app/Controllers/Admin/*` — AdminLTE pages + server-side DataTables endpoints.
- `app/Controllers/Api/V1/*` — REST-style JSON controllers.
- `app/Models/*` — Database models (validation rules added per entity).
- `app/Services/*` — Reusable domain logic (stats, audit, matching).
- `app/Database/Migrations/*` — Schema (FKs to Shield `users`).

## Related docs

| Doc | Purpose |
|-----|---------|
| [README.md](README.md) | Documentation index |
| [ARCHITECTURE.md](ARCHITECTURE.md) | Full system architecture |
| [ENDPOINTS-SHARE.md](ENDPOINTS-SHARE.md) | REST API reference for integrations |
| [API-INTEGRATION.md](API-INTEGRATION.md) | External relief system bridge |
| [API-AUTH.md](API-AUTH.md) | Session and JWT authentication |
| [TEST_ACCOUNTS.md](TEST_ACCOUNTS.md) | Demo logins |
| [SETUP-WAMP.md](SETUP-WAMP.md) | Local WAMP setup |
| [DEMO_MODE.md](DEMO_MODE.md) | Presentation / thesis demo mode |

---

*For CodeIgniter 4 and Shield docs, see [CI4 User Guide](https://codeigniter.com/user_guide/) and [Shield](https://codeigniter4.github.io/shield/).*
