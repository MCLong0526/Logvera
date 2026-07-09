-- ============================================================
-- Logvera — MySQL schema (reference DDL)
-- Mirrors the Laravel migrations in backend/database/migrations.
-- Running `php artisan migrate` creates these tables for you;
-- this file is provided as a standalone reference / manual setup.
-- ============================================================

CREATE DATABASE IF NOT EXISTS logvera
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE logvera;

-- ---------- Users ----------
CREATE TABLE users (
  id                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name              VARCHAR(255) NOT NULL,
  email             VARCHAR(255) NOT NULL,
  job_title         VARCHAR(255) NULL,
  avatar            VARCHAR(255) NULL,
  is_admin          TINYINT(1) NOT NULL DEFAULT 0,
  email_verified_at TIMESTAMP NULL,
  password          VARCHAR(255) NOT NULL,
  remember_token    VARCHAR(100) NULL,
  created_at        TIMESTAMP NULL,
  updated_at        TIMESTAMP NULL,
  PRIMARY KEY (id),
  UNIQUE KEY users_email_unique (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- Sanctum personal access tokens ----------
CREATE TABLE personal_access_tokens (
  id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  tokenable_type VARCHAR(255) NOT NULL,
  tokenable_id   BIGINT UNSIGNED NOT NULL,
  name           VARCHAR(255) NOT NULL,
  token          VARCHAR(64) NOT NULL,
  abilities      TEXT NULL,
  last_used_at   TIMESTAMP NULL,
  expires_at     TIMESTAMP NULL,
  created_at     TIMESTAMP NULL,
  updated_at     TIMESTAMP NULL,
  PRIMARY KEY (id),
  UNIQUE KEY personal_access_tokens_token_unique (token),
  KEY pat_tokenable_index (tokenable_type, tokenable_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- Projects ----------
CREATE TABLE projects (
  id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name        VARCHAR(255) NOT NULL,
  description TEXT NULL,
  owner_id    BIGINT UNSIGNED NOT NULL,
  status      ENUM('active','archived') NOT NULL DEFAULT 'active',
  created_at  TIMESTAMP NULL,
  updated_at  TIMESTAMP NULL,
  deleted_at  TIMESTAMP NULL,
  PRIMARY KEY (id),
  KEY projects_status_index (status),
  CONSTRAINT projects_owner_id_foreign FOREIGN KEY (owner_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- Project members (owner / member) ----------
CREATE TABLE project_members (
  id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  project_id BIGINT UNSIGNED NOT NULL,
  user_id    BIGINT UNSIGNED NOT NULL,
  role       ENUM('owner','member') NOT NULL DEFAULT 'member',
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  PRIMARY KEY (id),
  UNIQUE KEY project_members_project_id_user_id_unique (project_id, user_id),
  CONSTRAINT project_members_project_id_foreign FOREIGN KEY (project_id) REFERENCES projects (id) ON DELETE CASCADE,
  CONSTRAINT project_members_user_id_foreign FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- Tasks ----------
CREATE TABLE tasks (
  id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  project_id       BIGINT UNSIGNED NOT NULL,
  title            VARCHAR(255) NOT NULL,
  description      TEXT NULL,
  type             ENUM('bug','change_request','development','enhancement','idea','maintenance','others','quality_assurance','release','research_and_do','unit_testing','update','website_migration') NOT NULL DEFAULT 'development',
  priority         ENUM('low','medium','high','critical') NOT NULL DEFAULT 'medium',
  status           ENUM('not_started','in_progress','on_hold','completed','cancelled') NOT NULL DEFAULT 'not_started',
  progress         TINYINT UNSIGNED NOT NULL DEFAULT 0,
  target_date      DATE NULL,
  target_time      TIME NULL,
  created_by       BIGINT UNSIGNED NOT NULL,
  created_at       TIMESTAMP NULL,
  updated_at       TIMESTAMP NULL,
  deleted_at       TIMESTAMP NULL,
  PRIMARY KEY (id),
  KEY tasks_project_id_status_index (project_id, status),
  KEY tasks_target_date_index (target_date),
  CONSTRAINT tasks_project_id_foreign FOREIGN KEY (project_id) REFERENCES projects (id) ON DELETE CASCADE,
  CONSTRAINT tasks_created_by_foreign FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- Task assignees (M:N — a task can be assigned to many users) ----------
CREATE TABLE task_assignees (
  id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  task_id    BIGINT UNSIGNED NOT NULL,
  user_id    BIGINT UNSIGNED NOT NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  PRIMARY KEY (id),
  UNIQUE KEY task_assignees_task_id_user_id_unique (task_id, user_id),
  CONSTRAINT task_assignees_task_id_foreign FOREIGN KEY (task_id) REFERENCES tasks (id) ON DELETE CASCADE,
  CONSTRAINT task_assignees_user_id_foreign FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- Task update logs ----------
CREATE TABLE task_updates (
  id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  task_id        BIGINT UNSIGNED NOT NULL,
  user_id        BIGINT UNSIGNED NOT NULL,
  description    TEXT NOT NULL,
  progress_after TINYINT UNSIGNED NULL,
  status_after   ENUM('not_started','in_progress','on_hold','completed','cancelled') NULL,
  created_at     TIMESTAMP NULL,
  updated_at     TIMESTAMP NULL,
  PRIMARY KEY (id),
  KEY task_updates_task_id_index (task_id),
  CONSTRAINT task_updates_task_id_foreign FOREIGN KEY (task_id) REFERENCES tasks (id) ON DELETE CASCADE,
  CONSTRAINT task_updates_user_id_foreign FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- Attachments (polymorphic: Task | TaskUpdate) ----------
CREATE TABLE attachments (
  id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  attachable_type  VARCHAR(255) NOT NULL,
  attachable_id    BIGINT UNSIGNED NOT NULL,
  project_id       BIGINT UNSIGNED NOT NULL,
  task_id          BIGINT UNSIGNED NULL,
  user_id          BIGINT UNSIGNED NOT NULL,
  original_name    VARCHAR(255) NOT NULL,
  path             VARCHAR(255) NOT NULL,
  mime_type        VARCHAR(255) NULL,
  size             BIGINT UNSIGNED NOT NULL DEFAULT 0,
  created_at       TIMESTAMP NULL,
  updated_at       TIMESTAMP NULL,
  PRIMARY KEY (id),
  KEY attachments_attachable_type_attachable_id_index (attachable_type, attachable_id),
  KEY attachments_project_id_index (project_id),
  KEY attachments_task_id_index (task_id),
  KEY attachments_user_id_index (user_id),
  CONSTRAINT attachments_project_id_foreign FOREIGN KEY (project_id) REFERENCES projects (id) ON DELETE CASCADE,
  CONSTRAINT attachments_task_id_foreign FOREIGN KEY (task_id) REFERENCES tasks (id) ON DELETE CASCADE,
  CONSTRAINT attachments_user_id_foreign FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- Notifications (Laravel database channel) ----------
CREATE TABLE notifications (
  id              CHAR(36) NOT NULL,
  type            VARCHAR(255) NOT NULL,
  notifiable_type VARCHAR(255) NOT NULL,
  notifiable_id   BIGINT UNSIGNED NOT NULL,
  data            TEXT NOT NULL,
  read_at         TIMESTAMP NULL,
  created_at      TIMESTAMP NULL,
  updated_at      TIMESTAMP NULL,
  PRIMARY KEY (id),
  KEY notifications_notifiable_type_notifiable_id_index (notifiable_type, notifiable_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
