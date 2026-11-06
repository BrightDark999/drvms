# DRMS — Integration Strategy

Summary of the **ERP Integration Strategy** document: two independent development groups build one unified disaster response platform for LGU / MDRRMO operations.

**DRMS = Group 1** — this repository is the response & volunteer core. **Group 2** is a separate codebase (relief & logistics).

---

## Platform vision

| Item | Description |
|------|-------------|
| **System name** | DRMS (Disaster Response & Volunteer Matching System) |
| **Group 1** | Volunteer registration, incident reporting, volunteer matching, task assignment, notifications, operations dashboard |
| **Group 2** | Relief monitoring, donation monitoring, evacuation management, shared inventory, relief dashboard |
| **Integration** | Shared standards, shared master data design, REST APIs, SSO/RBAC, phased integration testing |

---

## Group 1 — Response & volunteer core (this repo)

| Module | API (target) |
|--------|----------------|
| Incident reporting | `GET/POST /api/v1/incidents` |
| Volunteer registration | `GET /api/v1/volunteers` |
| Automated matching / dispatch | Admin `/admin/matching` + future `/api/v1/matching` |
| Task assignment | `/api/v1/tasks` (planned) |
| Operations dashboard | `/api/v1/stats` |
| Notifications | Shared service with Group 2 |

---

## Group 2 — Relief & logistics (partner repo)

| Module | API (target) |
|--------|----------------|
| Relief monitoring | `/api/relief` — proxied via `GET /api/v1/integrations/relief/*` in DRMS |
| Donation monitoring | `/api/donations` |
| Evacuation management | `/api/evacuation` — **bridge**: DRMS hosts `/api/v1/evacuation-centers` until cutover |
| Shared inventory | `/api/inventory` |
| Relief dashboard | Partner admin UI |

See [`API-INTEGRATION.md`](API-INTEGRATION.md) for the relief proxy contract.

---

## Shared master data

Both groups read/write (or sync) these entities:

| Table | Primary owner |
|-------|----------------|
| `users`, `roles` | Both (SSO) |
| `notifications` | Both |
| `inventory` | Both (single source of truth) |
| `incidents` | Group 1 writes; Group 2 consumes for relief/evac |
| `audit_logs` | Both |
| `volunteers`, `tasks` | Group 1 |
| `donations`, `evacuation_centers` | Group 2 (evac bridge in DRMS transitional) |

Configuration mirror: `app/Config/Drms.php` → `erpSharedMasterData`, `erpModuleGroup1`, `erpModuleGroup2`.

---

## Integrated workflow (Phase 6)

1. Incident reported (G1)  
2. Volunteer matching & deploy (G1)  
3. Task assignment (G1)  
4. Inventory availability check (G2)  
5. Donation allocation (G2)  
6. Relief distribution (G2)  
7. Evacuation monitoring (G2)  
8. Notifications sent (shared)  
9. Dashboard & audit logs (shared)  

Integrated workflow steps are defined in `app/Config/Drms.php` (`erpIntegratedWorkflow`) and summarized on the About page.

---

## Role access matrix (SSO target)

| Role | Group 1 | Group 2 |
|------|---------|---------|
| Admin | ✓ | ✓ |
| DRRM Officer | ✓ | ✓ |
| Volunteer | ✓ | — |
| Donor | — | ✓ |
| Warehouse Staff | ✓ | ✓ |
| Evacuation Manager | — | ✓ |

---

## Phased integration (from strategy doc)

1. **Standards** — coding, API versioning, error format, auth headers  
2. **Data dictionary** — align table names, FKs, incident ID as join key  
3. **Group 1 APIs** — incidents, volunteers, tasks stable for G2 consumers  
4. **Group 2 APIs** — relief, donations, inventory, evacuation  
5. **SSO / RBAC** — single login, role → module group mapping  
6. **End-to-end testing** — incident → relief → evac → audit  
7. **Production cutover** — evac bridge off DRMS when G2 live  

---

## Website & config

| Location | Purpose |
|----------|---------|
| `app/Config/Drms.php` | module groups, workflow, role matrix |
| `app/Views/home/about.php` | Group 1 vs Group 2 explanation |
| `details/ARCHITECTURE.md` | Technical architecture + ERP section |
| `details/API-INTEGRATION.md` | Group 2 relief proxy details |

---

## Transitional note: evacuation

The ERP strategy assigns **evacuation** to Group 2. DRMS still hosts evacuation admin and public pages as a **bridge** until the partner module is deployed and data is migrated. UI labels this as `G2 bridge`.
