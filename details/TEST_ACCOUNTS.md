# DRMS test login accounts

**Fastest for client demos:** open **`http://drms.web/demo`** for one-click logins and sample data (see [DEMO_MODE.md](DEMO_MODE.md)).

Use these accounts on **`http://drms.web/login`** (or your local login URL) to sample each role manually.

**Shared password (all test users):** `Drms@2026`

Passwords are **not** in the `users` table. Shield stores a **bcrypt hash** in `auth_identities.secret2` (not plain text, not MD5). See [database/AUTH_PASSWORDS.md](database/AUTH_PASSWORDS.md).

| Role | Username | Email | Shield group | Dashboard access |
|------|----------|-------|--------------|------------------|
| Admin | `admin` | admin@drms.local | admin | Full |
| MDRRMO / LGU Staff | `mdrrmo` | mdrrmo@drms.local | mdrrmo | Operations (incidents, volunteers, evacuation, audit, …) |
| Volunteer | `volunteer` | volunteer@drms.local | volunteer | Dashboard (limited permissions) |
| Donor | `donor` | donor@drms.local | donor | Donations module |
| Evacuation Center Manager | `evac_manager` | evac@drms.local | evac_manager | Evacuation + reports |

## Recreate / reset test users

```bash
php spark db:seed DrmsTestUsersSeeder
```

This resets passwords to `Drms@2026`, activates accounts, and assigns one group per user.

## Security note

These accounts are for **local development only**. Do not deploy these passwords to production or InfinityFree.
