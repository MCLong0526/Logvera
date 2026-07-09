# Logvera

A complete **Project & Daily Log Management System** — projects, members, daily task logs, an update-log timeline, file uploads, a dashboard with charts, and search/filter.

- **Backend:** Laravel 13 · REST API · Sanctum auth · Eloquent · MySQL · public file storage
- **Frontend:** Vue 3 (Composition API) · Vite · Pinia · Vue Router · Tailwind CSS · Axios · Chart.js

> Requested as "Laravel 12"; the toolchain resolved the current LTS skeleton (Laravel 13). The code (Eloquent, API Resources, Sanctum, policies) is identical across 12/13.

---

## Features

| Area | What it does |
|------|--------------|
| **Auth** | Token login/logout (Sanctum), profile edit |
| **Admin** | Admins (`is_admin`) can add and edit users from the Users page |
| **Projects** | Create · edit · archive · delete (soft delete), owner + members |
| **Members** | Owner adds/removes members; roles: `owner`, `member` |
| **Tasks** | Type, priority, status, progress %, target date/time, multiple assignees |
| **Timeline** | Tasks auto-grouped by target date, newest first |
| **Update logs** | Unlimited timeline updates; syncs task progress/status |
| **Files** | Upload on task create or update; dedicated Files page with filters |
| **Dashboard** | Cards + charts (by status / progress / project) |
| **Search & filter** | By project, task, user, status, type, date |
| **Task It** | Kanban boards (name + users) with Assigned / In Progress / Close stages, drag & drop cards |
| **Notifications** | Members notified on task create/update/complete (database channel) |

---

## Entity Relationship (ERD)

```
users ──1:N──> projects (owner_id)
users <──M:N──> projects            (project_members: role owner|member)
projects ──1:N──> tasks
users ──1:N──> tasks (created_by)
users <──M:N──> tasks               (task_assignees)
tasks ──1:N──> task_updates ──N:1──> users
tasks ──1:N──> attachments (polymorphic)         \
task_updates ──1:N──> attachments (polymorphic)   } attachable_type/id + denormalised project_id/task_id/user_id
users ──1:N──> notifications (notifiable)
```

Full DDL: [`docs/schema.sql`](docs/schema.sql). Migrations: `backend/database/migrations/`.

---

## Installation

### Prerequisites
PHP 8.2+, Composer, Node 18+, MySQL 8+.

### 1. Database
```sql
CREATE DATABASE logvera CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 2. Backend
```bash
cd backend
composer install
cp .env.example .env          # already set for DB logvera / root / no password — adjust as needed
php artisan key:generate
php artisan migrate --seed     # creates schema + sample data
php artisan storage:link       # exposes uploaded files at /storage
php artisan serve              # http://127.0.0.1:8000
```

### 3. Frontend
```bash
cd frontend
npm install
npm run dev                    # http://localhost:5173 (proxies /api and /storage to :8000)
```

### Demo login
```
admin@logvera.test  /  password
```
Members `priya@`, `diego@`, `mei@logvera.test` share the same password.

---

## API

Base URL `http://127.0.0.1:8000/api`. All routes except `POST /login` require `Authorization: Bearer <token>`.

| Method | Endpoint | Purpose |
|--------|----------|---------|
| POST | `/login` | Authenticate → `{ user, token }` |
| POST | `/logout` | Revoke current token |
| GET | `/me` | Current user |
| PUT | `/profile` | Update name/email/job_title/password |
| GET | `/users` | List users (`?search=`) |
| POST | `/users` | Create user *(admin only)* |
| PUT | `/users/{id}` | Update user *(admin only)* |
| GET | `/dashboard` | Cards + recent tasks + chart data |
| GET | `/projects` | List (`?search=&status=&per_page=`) |
| POST | `/projects` | Create (optional `member_ids[]` to add members) |
| GET | `/projects/{id}` | Show |
| PUT | `/projects/{id}` | Update |
| POST | `/projects/{id}/archive` | Toggle archive/active |
| DELETE | `/projects/{id}` | Delete |
| GET/POST/DELETE | `/projects/{id}/members[/{user}]` | Manage members |
| GET | `/tasks` | List/search/filter (`?project_id=&status=&type=&priority=&assigned_user_id=&search=&date=&date_from=&date_to=`) |
| POST | `/projects/{id}/tasks` | Create task (multipart: `attachments[]`) |
| GET/PUT/DELETE | `/tasks/{id}` | Show / update / delete |
| GET/POST | `/tasks/{id}/updates` | Update logs (POST is multipart) |
| GET | `/files` | All files (`?project_id=&task_id=&user_id=&date=`) |
| GET | `/files/{id}/download` | Download |
| DELETE | `/files/{id}` | Delete |
| GET/POST | `/boards` | Task It kanban boards (POST: `name`, `member_ids[]`) |
| GET/DELETE | `/boards/{id}` | Show (members + cards) / delete *(owner)* |
| POST | `/boards/{id}/cards` | Add card to the Assigned stage |
| PUT | `/boards/{id}/cards/reorder` | Persist drag & drop (`columns: {stage: [ids…]}`) |
| DELETE | `/boards/{id}/cards/{card}` | Delete card *(creator or owner)* |
| GET | `/notifications` | List + unread count |
| POST | `/notifications/read` | Mark all read |

### Permissions
- **Admin (`is_admin`):** manage users (add/edit) in addition to normal access.
- **Owner:** full access to their project, members, and all tasks.
- **Member:** view project, create tasks, update tasks they created or are assigned.

Enforced by `ProjectPolicy` / `TaskPolicy` (`backend/app/Policies/`).

---

## Project structure
```
Logvera/
├── backend/     Laravel 13 API
│   ├── app/Models · Http/Controllers · Http/Requests · Http/Resources · Policies · Notifications
│   ├── database/migrations · seeders
│   └── routes/api.php
├── frontend/    Vue 3 SPA
│   └── src/ api · stores · router · layouts · components · views · utils
└── docs/schema.sql
```
