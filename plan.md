# Multi-Tenancy Plan — CADEBECK HRMS

**Status:** Draft for approval
**Date:** 2026-10-05
**Scope:** Convert the single-tenant HRMS into a multi-tenant system where each paying customer is a **Client**, with its own isolated workforce data.

---

## 1. Objectives

1. Introduce a first-class **Client** entity (the tenant).
2. Every user belongs to exactly one Client.
3. A **System Admin** role (platform staff) manages clients; a **Developer** role has full cross-client access.
4. Existing demo/HR data is preserved under a single demo Client.
5. All business data is automatically scoped to the current Client — developers cannot read each other's data by accident.

---

## 2. Confirmed Decisions

| Decision | Choice |
|---|---|
| Tenancy strategy | **Shared database, `client_id` column** (Spatie-style) |
| Packages | **`spatie/laravel-multitenancy` ^4.0** (current client lifecycle) + **`spatie/laravel-permission` ^6.21** with teams enabled (per-client permissions). The `client_id` global scope is written by us as a `BelongsToClient` trait — no package provides it. |
| Tenant model | `App\Models\Client` (custom tenant model, table `clients`) — keeps "client" terminology rather than the package default "tenant" |
| Client identification | **Derived from the logged-in user** (`auth()->user()->client_id`). No subdomain/URL/session tenant state. |
| Email uniqueness | **Globally unique** (no change — one account per email system-wide) |
| Client model | First-class `Client` model; **1 client per user**; platform staff have `client_id = NULL` |
| Roles | Shared default roles per client; **clients may create custom roles from the existing 150-permission set** |
| Existing data | Backfill to a single **Demo Client** |
| Tables | **New tables are created where necessary** (e.g. `clients`), but **no separate `add_column_to_table` migrations** — `client_id` is added by **editing the existing create-table migrations**. Databases are rebuilt with `migrate:fresh --seed` (pre-launch, demo data is expendable). |
| Orphan tables | Per-table audit (below) |
| Registration | **Disabled** (comment out links + routes — to be done manually by the developer) |
| System Admin access | List all clients + cross-client reporting; **no direct edits** to client HR data |
| Developer access | **Full read/edit across all clients** |
| Authorization model | **Permission-based.** Roles are only named bundles of the 150 permissions. Access is checked with permissions (`permission:` middleware, `@can`, policies) — **not** with role names |

### 2.1 Packages

| Package | Version | Role in this build |
|---|---|---|
| `spatie/laravel-multitenancy` | ^4.0 | Current-client lifecycle: `Client::current()`, `makeCurrent()`, `forgetCurrent()`, tenant-aware queues/artisan. Requires PHP 8.2+ / Laravel 11+ (we are Laravel 12). Does **not** auto-scope models. |
| `spatie/laravel-permission` | ^6.21 (already installed) | Permission checks. `teams` enabled so assignments are stored per client. |
| *(none — written in-repo)* | — | `BelongsToClient` trait providing the `client_id` global scope. Laravel's documented shared-DB tenancy pattern. |

---

## 3. Current State (audit)

- **Laravel 12**, `bootstrap/app.php` skeleton (no `app/Http/Kernel.php`).
- **Spatie `laravel-permission` ^6.21** installed. `config/permission.php` → `'teams' => false`.
- **Authorization is already permission-based:** 49 route-level `permission:` middleware guards, **131** `hasPermissionTo`/`@can`/`authorize()` call sites, and **33** `hasRole()` call sites (the exception — mostly in `LeaveRequestPolicy` and `WellBeingResponse::scopeViewableBy`). This plan keeps and reinforces that model.
- **5 roles** seeded (`RolesAndPermissionsSeeder`): Developer, Executive, Manager N-1, Manager N-2, Employee — plus **150 permissions**. Roles act as permission bundles.
- **`users.email` is globally `unique()`** — kept as-is (per decision).
- **36 tables**: 13 infrastructure/auth, **21 business tables** that are client-owned, 2 non-client tables.
- **Registration already closed** (`routes/auth.php:11` notes this); users are provisioned by an admin Volt component.
- **No tenancy foundation exists**: no `Client` model, no `client_id` on any table, no global scopes.

---

## 4. Target Architecture

### 4.1 The `clients` table (new)

```
clients
  id
  name                    (string)        e.g. "Acme Ltd"
  slug                    (string, unique)
  status                  (enum: active, suspended)
  contact_email           (string, nullable)
  contact_phone           (string, nullable)
  plan                    (string, nullable)   e.g. starter, professional, enterprise
  max_users               (int, nullable)      optional seat cap
  timezone                (string, nullable)   default UTC
  created_at, updated_at, deleted_at (softDeletes)
```

### 4.2 `client_id` — which tables get it

**Client-owned (21 tables — get `client_id`, indexed, with FK to `clients.id`):**

`users` (nullable — NULL = platform staff), `job_adverts`, `locations`, `branches`, `departments`, `designations`, `contract_types`, `employees`, `attendances`, `leave_requests`, `well_being_responses`, `payrolls`, `payroll_allowances`, `payroll_deductions`, `employee_loans`, `payslips`, `loan_repayments`, `notifications`, `applications`, `audits`, `launch_subscribers`.

**Not client-scoped (reasons):**

| Table | Reason |
|---|---|
| `languages` | Global reference/config for the whole system |
| `permissions`, `roles`, `model_has_*`, `permission_roles`, `role_has_permissions` | Spatie system tables — handled by the teams feature, not `client_id` |
| `migrations`, `jobs`, `sessions`, `cache*`, `failed_jobs`, `password_reset_tokens`, `personal_access_tokens`, `job_batches` | Framework infrastructure |

> **Note on hierarchy:** `locations → branches → departments → designations` already form a chain. Each is stored per-client (so one client can have a "Head Office" and another a "Warehouse") and the existing global-`unique` `code` columns should be reviewed to become unique **per client** instead of globally.

### 4.3 Authorization model (permission-based)

**This system is permission-based, not role-based.** Every resource is gated by a permission check:

- Route level: `->middleware(['auth', 'permission:manage_employee'])` (49 such guards today)
- View level: `@can('manage_employee')`
- Policy level: `$this->authorize(...)` / `hasPermissionTo(...)`

**Roles are only convenience bundles** — a named group of the 150 permissions assigned to users in bulk. A role is *never* used as an access check. This matters for two reasons:

1. **Custom client roles must not change how access is enforced.** A client inventing a role called "Payroll Wizard" gets exactly the permissions selected for it — access checks are unchanged.
2. **Adding a new role is just a new permission bundle**, not a code change.

Therefore the design rule for this project: **never introduce a new `hasRole()` check.** Every new capability = a new permission.

### 4.4 New platform permissions (namespace for platform-level capability)

System-level capabilities get their own permission namespace so they can be granted independently of client-scoped HR permissions:

| Permission | Grants |
|---|---|
| `clients.view` | List and view all clients |
| `clients.create` | Add a new paying client (the client's onboarding action) |
| `clients.edit` | Edit client profile (name, contact, plan, seat cap) |
| `clients.suspend` | Suspend/reactivate a client |
| `reports.cross_client` | View aggregate/cross-client reporting |
| `access_all_clients` | **Scope bypass** — see data across all clients (Developer) |
| `impersonate_client` | Enter a client context to support them (Developer) |

`access_all_clients` is deliberately a **permission, not a role check** — the global scope in Phase 2 asks `$user->can('access_all_clients')` rather than `$user->hasRole('Developer')`.

### 4.5 Client-context permissions

New client-scoped permissions for managing a client:

| Permission | Grants |
|---|---|
| `manage_client_settings` | Edit own client profile/settings |
| `manage_client_roles` | Create/edit custom roles for own client from the 150-permission catalogue |
| `manage_client_users` | Create/edit/deactivate users within own client |
| `view_client_reports` | View own client's reports |

### 4.6 Permission bundles (roles) after conversion

Roles remain as bundles; the permission checks do the enforcing.

| Bundle | Permissions | Notes |
|---|---|---|
| **Developer** | all 150 existing + all new platform permissions incl. `access_all_clients`, `impersonate_client` | `client_id = NULL`. Full read/edit everywhere. |
| **System Admin** (new) | `clients.view`, `clients.create`, `clients.edit`, `clients.suspend`, `reports.cross_client` **only** | `client_id = NULL`. Gets **no** `*_employee`, `process_payroll`, `edit_employee` etc. permissions — which is precisely how "cannot edit client HR data" is enforced. **No special-case code required.** |
| **Client Admin** (new) | all 150 existing + `manage_client_settings`, `manage_client_roles`, `manage_client_users`, `view_client_reports` | `client_id` set. Full access **within their own client only**. |
| Executive / Manager N-1 / Manager N-2 / Employee | unchanged existing bundles | `client_id` set. Applied within their client. |
| **Custom (per client)** | any subset of the catalogue | Created by a Client Admin holding `manage_client_roles`. |

> Because the Developer/System Admin distinction is expressed purely as *which permissions are granted*, there is no code that needs to know "this user is a Developer". A client could theoretically be granted `clients.view` — so `clients.*` and `reports.cross_client` should be treated as platform-only in practice (never exposed in the per-client role builder's pickable list).

---

## 5. Implementation Phases

### Phase 1 — Foundation (delivered as small, reviewable commits)

- **1a.** Install `spatie/laravel-multitenancy`; publish and customise `config/multitenancy.php`; create the `Client` model (custom tenant model) and the `clients` table migration.
- **1b.** Add nullable, indexed `client_id` (FK → `clients`) to all 21 client-owned tables **by editing their existing create-table migrations** (no separate `add_..._to_...` migration files). `users.client_id` stays **nullable** (platform staff have NULL).
- **1c.** Update seeders to create the **Demo Client** and assign `client_id` to existing demo data; rebuild with `migrate:fresh --seed`.

### Phase 2 — Automatic scoping (the critical correctness step)
- Add a `BelongsToClient` trait to all 21 client-owned models: `bootBelongsToClient()` applying a **global scope** filtering on the authenticated user's client (or the "current client" during CLI/system contexts).
- Ensure System Admin / Developer contexts deliberately **bypass** the scope when cross-client access is intended.
- Audit and fix existing unscoped queries, especially:
  - `TempFileCleanup` middleware (global cache key leaks across clients) — key it per client.
  - `SetLanguage` middleware — decide global vs per-client language.
  - Any raw `DB::table(...)` usage.

### Phase 3 — Permissions & role bundles
- Enable Spatie teams (`'teams' => true`); run the migration adding `client_id` to `model_has_roles` / `model_has_permissions` so assignments are stored per client.
- Set `client_id` on role assignments using the teams foreign key.
- Seed the new platform permissions (`clients.*`, `reports.cross_client`, `access_all_clients`, `impersonate_client`) and the new client-context permissions (`manage_client_*`, `view_client_reports`).
- Create the **System Admin** and **Client Admin** permission bundles (see 4.6).
- **Remove permission-system coupling to role names:** replace the existing `hasRole()` checks with permission equivalents (see Risk below).
- Add the per-client **custom role builder** (Client Admin picks from the pickable permission catalogue; `clients.*` and `access_all_clients` are excluded from the pickable list).

### Phase 4 — User provisioning (registration stays closed)
- All users are created **inside a Client context**:
  - Platform staff (Developer/System Admin) created by a Developer.
  - Client users (Client Admin/Executive/Managers/Employee) created by that client's **Client Admin**.
- Ensure the email-globally-unique rule is still enforced at creation.
- **Registration:** developer comments out the `register` routes + navigation/footer links (manual step — flagged).

### Phase 5 — Cross-client reporting & admin
- System Admin dashboard: list clients (status, plan, seat count, created date) + aggregate reporting (total clients, total employees, payroll totals across clients) — **read-only**.
- Developer tools: cross-client data access and impersonation ("view as client") for support.
- System Admin must be **blocked** from editing client HR records (enforced in policies, not just hidden in UI).

### Phase 6 — Testing & hardening
- Add feature tests:
  - Two clients with interleaved data → user A never sees client B's data (employees, payrolls, leave, etc.).
  - Global email uniqueness still enforced.
  - Developer sees all; System Admin sees aggregate but cannot edit client data; Client Admin scoped to own client.
  - Backfill moved existing data to the Demo Client.
- Fix the 2 stale registration tests (since `/register` is disabled).

---

## 6. Risks & Considerations

- **⚠️ Hardcoded `hasRole()` checks break under custom client roles (highest priority).** There are 33 `hasRole()` call sites, concentrated in `app/Policies/LeaveRequestPolicy.php`, `app/Models/WellBeingResponse.php:60-80`, and others. They compare against fixed names like `'Manager N-1'`, `'Manager N-2'`, `'Executive'`. Once clients can create their own roles, these checks are unreliable — a client role named "Manager N-1" would inherit those rules, and a custom role with equivalent permissions would not. **These must be converted to permission checks** (e.g. `hasPermissionTo('approve_all_leaves')`) as part of Phase 3, not left as-is.
- **Row-level hierarchy vs permissions.** `LeaveRequestPolicy` and `WellBeingResponse::scopeViewableBy` implement an N-1/N-2 approval hierarchy. This is the one place a genuine *role/level* notion is needed — but it must still be expressed via permissions (e.g. `approve_team_leaves` vs `approve_all_leaves`) rather than role names, since custom roles have no position in a hierarchy.
- **⚠️ Global-scope leakage** is the biggest correctness risk. Any query bypassing the `BelongsToClient` scope could expose another client's payroll. Mitigation: trait applied consistently + tests asserting cross-client isolation per model.
- **Permission caching under teams.** Spatie caches permission→role mappings; verify the cache is keyed per client and is reset when a client's roles change (otherwise a revoked permission may linger).
- **Existing unique `code` columns** (locations, branches, departments, designations, contract_types) are globally unique today; likely need to become per-client unique.
- **Backfill** must run before enforcing `NOT NULL`.
- **Framework tables** (`jobs`, `sessions`, `cache`) stay global — fine for shared-DB tenancy.
- **Client Admin ≡ full permissions within own client.** Since Client Admin gets all 150 permissions, "which client user can delete another user's record" is governed by the tenant scope, not by permission granularity. Confirm that is acceptable, or narrow the Client Admin bundle.

---

## 7. Open Items for Developer

1. **Approve the new platform permission list** in §4.4 (`clients.*`, `reports.cross_client`, `access_all_clients`, `impersonate_client`) — confirm names match your conventions.
2. **Approve the new client-context permission list** in §4.5.
3. **Client Admin permission breadth** — the proposal grants all 150 permissions within their own client. Confirm, or specify a narrower set.
4. **Confirm the pickable catalogue for the custom role builder** — proposal: the 150 existing permissions + §4.5 client-context ones, with §4.4 platform permissions excluded.
5. **Confirm the register links/routes to comment out** (Phase 4 manual step).
6. **Confirm scope of the custom role builder** — in this phase or a follow-up.
7. **Row-level approval hierarchy** (§6) — confirm the permission names that should replace the N-1/N-2 role checks, since this changes existing leave-approval behaviour.

---

## 8. Rollout Order (summary)

1. Phase 1 (schema + backfill) → deploy
2. Phase 2 (global scopes) → deploy + isolation tests
3. Phase 3 (roles/teams + new roles + role builder) → deploy
4. Phase 5 (System Admin dashboard/reporting) → deploy
5. Phase 6 (hardening/tests) → continuous
6. Phase 4 (provisioning) — coordinate with the manual registration-disable step

> The registration-disable step (developer) must land before/with Phase 4 so no stray self-registration creates users outside a Client context.
