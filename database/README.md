# DRMS database setup

Two ways to create all tables.

## Method A — Migrations (recommended)

Keeps schema in sync with CodeIgniter migrations (Shield + DRMS).

1. Start **WAMP MySQL**.
2. Create the database (once):

   ```bash
   mysql -u root < database/01_create_database.sql
   ```

   Or in **phpMyAdmin**: import `01_create_database.sql`.

3. From project root:

   ```bash
   php spark migrate --all
   php spark db:seed DrmsDemoSeeder
   ```

**Windows shortcut:** double-click `setup-database.bat` in the project root.

## Method B — Import SQL schema (phpMyAdmin)

Use when you cannot run `php spark` on the server (e.g. some shared hosts).

1. Import `01_create_database.sql` (if `drms_db` does not exist).
2. Import `drms_db_schema.sql` (all tables + foreign keys).

Then create an admin user on a machine with CLI:

```bash
php spark shield:user create -n admin -e admin@drms.local -g admin
php spark shield:user activate -n admin
```

## Tables created

| Group | Tables |
|-------|--------|
| Shield (auth) | `users`, `auth_identities`, `auth_logins`, `auth_token_logins`, `auth_remember_tokens`, `auth_groups_users`, `auth_permissions_users` |
| Settings | `settings` |
| Framework | `migrations` |
| DRMS | `barangays`, `incidents`, `incident_media`, `volunteers`, `volunteer_skills`, `volunteer_deployments`, `relief_items`, `relief_distributions`, `evacuation_centers`, `donors`, `donations`, `notification_queue`, `audit_logs` |

## Check status

```bash
php spark migrate:status
```

## Reset (development only — deletes all data)

```bash
php spark migrate:rollback --all
php spark migrate --all
php spark db:seed DrmsDemoSeeder
```
