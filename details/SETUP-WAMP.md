# DRMS – WAMP Setup Guide

## Project location
`C:\wamp64\www\drms`

## URLs
| Page | URL |
|------|-----|
| **Public homepage** | http://localhost/drms/public/ |
| **About** | http://localhost/drms/public/about |
| **Admin dashboard** | http://localhost/drms/public/admin/dashboard |
| **API health** | http://localhost/drms/public/api/v1/health |

## Steps
1. Start **WAMP** (Apache + MySQL green).
2. Open phpMyAdmin → create database `drms_db`.
3. Copy `.env` is already configured for root with no password (default WAMP).
4. Visit the homepage URL above.

## AdminLTE assets
Bundled under `public/assets/adminlte/` (no CDN required for admin panel except Google Fonts).

## Next development phases
1. CodeIgniter Shield (login + RBAC)
2. Database migrations per module
3. REST API controllers under `app/Controllers/Api/V1/`
