# Where DRMS stores login passwords

## Why there is no `password` column on `users`

CodeIgniter **Shield** splits account data:

| Table | Purpose |
|-------|---------|
| `users` | Username, active flag, timestamps — **no password** |
| `auth_identities` | Login credentials (email + password hash) |
| `auth_groups_users` | Roles (`admin`, `mdrrmo`, `lgu`, …) |

This is normal for Shield and is **more secure** than putting passwords on `users`.

## Where the hash lives

```sql
SELECT
    u.id,
    u.username,
    i.type,
    i.secret AS login_email,
    LEFT(i.secret2, 60) AS password_hash_preview
FROM users u
JOIN auth_identities i ON i.user_id = u.id
WHERE i.type = 'email_password';
```

| Column | Meaning |
|--------|---------|
| `secret` | Email used to log in |
| `secret2` | **Hashed password** (starts with `$2y$` = bcrypt via PHP `password_hash()`) |

Plain text passwords are **never** stored. MD5 is **not** used (MD5 is weak and unsuitable for passwords). Shield hashes on save when you set `$user->password = '...'` in seeds or registration.

## Test accounts

See `TEST_ACCOUNTS.md`. All test users use password **`Drms@2026`** (stored as bcrypt in `auth_identities.secret2`).

Re-apply hashes:

```bash
php spark db:seed DrmsTestUsersSeeder
```

## phpMyAdmin tip

Open table **`auth_identities`**, not `users`, to inspect login hashes.
