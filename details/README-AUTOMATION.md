# DRMS automation & AI assist

## What is included

### 1. Email notification queue
Incidents (create/verify), volunteer deploy, and daily digest queue rows in `notification_queue`.

- **Local WAMP:** immediate send when `drms.notifications.autoSend = true`
- **Cron / Task Scheduler:**
  ```bash
  php spark notifications:process
  ```
- **Web cron** (set `drms.cronSecret`):
  ```
  GET http://drms.web/cron/notifications?key=YOUR_SECRET
  ```

### 2. Webhooks (Slack / Teams / generic)
Critical incidents POST to `drms.webhookUrl` (configure format with `drms.webhookFormat=slack|teams|generic`).

### 3. SMS channel (adapter)
When `drms.smsEnabled=true`, critical alerts queue SMS rows. With `drms.smsDemoMode=true` (default), messages are logged as sent. Set `drms.smsApiUrl` for a real HTTP gateway.

### 4. Dashboard automation
- **Rule-based alerts** (verification backlog, critical incidents, stale reports, evacuation capacity, low stock)
- **Leaflet map** of active incidents (`admin/dashboard/incidents-map`)
- **AI briefing** card (Reports or dashboard)

### 5. Volunteer matching + AI explain
Scores by availability, barangay, distance. **Robot** button calls AI (or rules) to explain the match.

### 6. Relief distributions
`admin/relief-distributions` — records beneficiary payouts and **decrements** `relief_items.quantity`.

### 7. Reports export
`admin/reports` — PDF/Excel via Dompdf + PhpSpreadsheet:
- Incidents PDF / Excel
- Operations overview PDF (KPIs + alerts + briefing)

### 8. Daily operations digest
```bash
php spark drms:digest
```
Or **Automation & AI** → Send digest now.

## `.env` reference

```ini
drms.alertEmails = mdrrmo@drms.local
drms.notifications.autoSend = true
drms.cronSecret = change-me-in-production
drms.webhookUrl =
drms.webhookFormat = slack
drms.smsEnabled = false
drms.smsDemoMode = true
drms.smsAlertNumbers =
drms.smsApiUrl =
drms.digestEnabled = true
ai.enabled = false
# ai.apiKey =
# ai.apiUrl = https://api.openai.com/v1/chat/completions
# ai.model = gpt-4o-mini
```

## Admin pages

| Page | URL |
|------|-----|
| Automation status | `/admin/automation` |
| Reports & exports | `/admin/reports` |
| Distributions | `/admin/relief-distributions` |

## PhpSpreadsheet note (WAMP)

Composer may require `ext-gd` for full PhpSpreadsheet features. Enable `extension=gd` in `php.ini` if Excel export fails. PDF export uses Dompdf only.

## Test flow

1. Login as `mdrrmo` / `Drms@2026`
2. Report a **critical** incident → check Notifications, webhook logs (if URL set)
3. Dashboard → map pins + Generate briefing
4. Matching → AI explain → Deploy
5. Relief Distributions → record payout → verify stock decreased
6. Reports → download PDF/Excel
7. `php spark drms:digest`

See `TEST_ACCOUNTS.md` for users.
