# InfinityFree deployment

The application is prepared to run from the account's `/htdocs` directory at
`https://drvms.freedev.app/`.

## Required account values

Before the application can connect to MySQL, copy the following exact values
from **InfinityFree Control Panel > MySQL Databases**:

- MySQL hostname (normally starts with `sql`)
- Full database name: `if0_42211259_drms_db`
- Database username: `if0_42211259`
- Database password

The website hostname is not the MySQL hostname. Port `3306` is already set.

## Production environment

1. Copy `.env.infinityfree.example` to a temporary file.
2. Replace the placeholder hostname, database name, password, and encryption key.
3. Upload it to `/htdocs/.env` through the InfinityFree file manager or FTP.
4. Do not commit the completed `.env` file. It is intentionally ignored by Git
   and by the VS Code upload configuration.
5. Import `drms_db.sql` using the account's phpMyAdmin page. The SQL dump is
   intentionally excluded from automatic uploads.

## VS Code uploads

InfinityFree free hosting does not support SSH/SFTP. The included
`.vscode/sftp.json` uses FTP on port 21 with `ftpupload.net`, which is the upload
service provided for the account.

1. Install the VS Code extension named **SFTP**.
2. Set the local environment variable `INFINITYFREE_FTP_PASSWORD` to the FTP
   password, then restart VS Code. The tracked configuration reads the password
   from that variable so it is never committed.
3. Run **SFTP: Sync Local -> Remote** once to upload the project to `/htdocs`.
4. With `uploadOnSave` enabled, later saved file changes are uploaded
   automatically.

If the extension offers FTP over TLS, prefer explicit TLS. The upload account
password and the database password may be different; use the FTP password in
`.vscode/sftp.json`.

## Hosting checks

- Select PHP 8.1 or newer in the InfinityFree control panel.
- Ensure `/htdocs/writable` and its subdirectories are writable.
- Confirm the SSL certificate is active before using the HTTPS URL.
- Visit `/api/v1/health` after deployment, then test the homepage and login.
