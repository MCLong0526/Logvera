# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Git identity

Commit all changes as **MCLong0526** `<chia5040@gmail.com>`. This is set as the repo-local git config (`git config user.name` / `user.email`); keep it that way and do not commit under any other identity.

## What this is

Logvera is a Project & Daily Log Management System: a Laravel 13 REST API (`backend/`) + a Vue 3 SPA (`frontend/`). Projects have members and tasks; tasks accumulate an "update-log" timeline; files attach to tasks/updates; a dashboard aggregates charts. Full feature list and API table live in [README.md](README.md); DDL in [docs/schema.sql](docs/schema.sql).

## Commands

**Backend** (`cd backend`, PHP 8.2+, MySQL):
```bash
composer install
php artisan migrate --seed      # schema + demo data (admin@logvera.test / password)
php artisan storage:link        # required once — serves uploads at /storage
php artisan serve               # :8000
php artisan test                # full suite
php artisan test --filter=Name  # single test / class
composer dev                    # serve + queue + pail logs + vite, all at once
```

**Frontend** (`cd frontend`, Node 18+):
```bash
npm install
npm run dev                     # :5173, proxies /api and /storage to :8000 (see vite.config.js)
npm run build
```
No lint/test tooling is configured on the frontend.

**MySQL runs on port 3307**, not the default 3306 (`.env.example` is preset for it — Homebrew MySQL, not Docker). Notifications use the `database` channel, so run a queue worker (or `composer dev`) if testing notification delivery.

## Backend architecture

Standard Laravel API layering — controllers stay thin, logic lives in the pieces around them:

- **Auth:** Sanctum bearer tokens. All routes except `POST /login` sit behind `auth:sanctum` ([routes/api.php](backend/routes/api.php)).
- **Authorization:** `ProjectPolicy` / `TaskPolicy` ([app/Policies/](backend/app/Policies/)) enforce owner/member/admin rules. Three roles: **admin** (`users.is_admin`) manages users; **owner** has full project access; **member** views the project and edits tasks they created or are assigned. When touching any project/task endpoint, check the policy — that's where access is decided.
- **Validation:** Form Request classes ([app/Http/Requests/](backend/app/Http/Requests/)), one per write action.
- **Serialization:** API Resource classes ([app/Http/Resources/](backend/app/Http/Resources/)) shape every JSON response. Change output shape here, not in controllers.
- **File uploads:** the `HandlesFileUploads` trait ([app/Http/Controllers/Concerns/](backend/app/Http/Controllers/Concerns/HandlesFileUploads.php)) is the single place files get stored. Attachments are **polymorphic** (`attachable_type/id` → Task or TaskUpdate) but also carry **denormalized** `project_id`/`task_id`/`user_id` so the Files page can filter cheaply without joins. Preserve both when adding attachment sources.
- **Notifications:** `ProjectActivity` ([app/Notifications/](backend/app/Notifications/)) fires to project members on task create/update/complete via the database channel.

Data model (see README ERD): `users` ↔ `projects` (M:N via `project_members` with role) and `projects` 1:N `tasks` 1:N `task_updates`. A task update can sync the parent task's progress/status.

## Frontend architecture

Vue 3 Composition API + Pinia + Vue Router, no component library — views compose primitives from `src/components/`.

- **API layer:** every call goes through `src/api/http.js` — an Axios instance that injects the bearer token from `localStorage` and, on any 401, clears the token and redirects to `/login`. Don't create ad-hoc axios instances.
- **Auth:** the Pinia `auth` store ([src/stores/auth.js](frontend/src/stores/auth.js)) holds user + token; `router.beforeEach` gates every non-`public` route on `isAuthenticated`.
- **Icons — Heroicons v1, centralized.** All icons route through `src/components/Icon.vue`, which maps semantic names (`dashboard`, `edit`, `trash`, …) to `@heroicons/vue/outline` v1 components. Add icons by extending the map there; never import Heroicons directly in a view, and note the icon set is pinned to **v1** (different export names than v2).

### Design system (frontend-design)

A deliberate "engineering journal / spec-sheet" aesthetic — treat these as house style, defined in [tailwind.config.js](frontend/tailwind.config.js) and [src/style.css](frontend/src/style.css):

- **Warm paper/ink palette**, not default Tailwind grays: `paper`, `surface`, `ink`, `line`, plus a monochrome `brand` ramp. The UI is intentionally monochrome — `accent` (burnt amber) is used sparingly; color otherwise signals status only.
- **Fonts:** Hanken Grotesk (sans) + JetBrains Mono (mono). Numbers, dates, metadata, and form labels render in mono (`.tabular`, `.label`) — that's the "log" detailing.
- **Component classes** in `style.css`: `.btn`/`.btn-primary`(ink)/`.btn-secondary`/`.btn-danger`, `.input`, `.label`, `.card`. Reuse these instead of ad-hoc utility clusters.
- **Motion:** `.stagger` for staggered page-load reveals, `page-enter/leave` route transitions. Keep new UI consistent with this restrained, monochrome, mono-metadata language.
