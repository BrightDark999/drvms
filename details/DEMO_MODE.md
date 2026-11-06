# DRMS client demo mode

Use this for **thesis defense, client walkthroughs, or screenshots** without wiring each controller to fake data.

## Quick start

1. Open **`http://drms.web/demo`** (or your local base URL + `/demo`).
2. Click **Enter as …** for any role (MDRRMO, volunteer, barangay official, etc.).
3. Browse admin modules — tables, dashboard chart, and map load **sample data** automatically.
4. Click **Exit demo** in the yellow banner when finished.

## How it works

| Piece | Purpose |
|--------|---------|
| `/demo` | Role picker with one-click login |
| `drms_presentation` session | Turns on sample data + simulated saves |
| `PresentationModeFilter` | Blocks POST before controllers; GET uses **database** sample rows |
| `PresentationService` | Fixture JSON only for **offline** demo (no MySQL) |
| `DemoAuthService` | Logs in via database **or** offline Shield session |
| `DemoSessionFilter` | Keeps offline demo logged in without a `users` row |

## Read-only while demo is on

After you enter from `/demo`, the session flag `drms_presentation` is set:

- **Tables, charts, maps** → loaded from your **database** (including rows from `DrmsDemoSeeder`).
- **Create / edit / delete / verify / deploy** → UI shows success, but **nothing new is inserted or updated**.
- **Exit demo** (banner link) to use normal login and real saves again.

Run seeders once so tables have sample rows to show:

```bash
php spark db:seed DrmsTestUsersSeeder
php spark db:seed DrmsDemoSeeder
```

If MySQL is unavailable, demo falls back to built-in fixture JSON (offline preview only).

Optional seed (richer real data if you also want DB-backed screens):

```bash
php spark db:seed DrmsTestUsersSeeder
php spark db:seed DrmsDemoSeeder
```

## Environment (`.env`)

```ini
drms.demoMode = true
drms.presentationData = true
drms.demoPreferDatabaseLogin = true
drms.demoPassword = Drms@2026
```

Set `drms.demoMode = false` on production hosting to hide `/demo`.

If you omit `drms.demoMode` from `.env`, demo stays **enabled** by default (local/thesis). Only set it when you want to turn demo off explicitly.

## Security

Demo mode is for **local / thesis** environments only. Do not enable on a public production server.
