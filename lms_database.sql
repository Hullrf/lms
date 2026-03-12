-- =====================================================
-- LMS Database Schema
-- Motor: MySQL 8.0+
-- Charset: utf8mb4
-- =====================================================

CREATE DATABASE IF NOT EXISTS lms_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE lms_db;

-- =====================================================
-- TABLA: users
-- =====================================================
CREATE TABLE users (
    id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(100) NOT NULL,
    email         VARCHAR(150) NOT NULL UNIQUE,
    password      VARCHAR(255) NOT NULL,
    role          ENUM('admin', 'student') NOT NULL DEFAULT 'student',
    avatar        VARCHAR(255) NULL,
    bio           TEXT NULL,
    email_verified_at TIMESTAMP NULL,
    remember_token    VARCHAR(100) NULL,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_role (role),
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: courses
-- =====================================================
CREATE TABLE courses (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    created_by          BIGINT UNSIGNED NOT NULL,
    title               VARCHAR(200) NOT NULL,
    slug                VARCHAR(200) NOT NULL UNIQUE,
    description         TEXT NULL,
    short_description   VARCHAR(500) NULL,
    thumbnail           VARCHAR(255) NULL,
    price               DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    is_free             BOOLEAN NOT NULL DEFAULT FALSE,
    is_published        BOOLEAN NOT NULL DEFAULT FALSE,
    level               ENUM('beginner', 'intermediate', 'advanced') NOT NULL DEFAULT 'beginner',
    language            VARCHAR(50) NOT NULL DEFAULT 'Español',
    duration_hours      DECIMAL(4,1) NULL COMMENT 'Duración estimada en horas',
    lessons_count       INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Desnormalizado para performance',
    enrollments_count   INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Desnormalizado para performance',
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_slug (slug),
    INDEX idx_published (is_published),
    INDEX idx_free (is_free)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: modules
-- =====================================================
CREATE TABLE modules (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    course_id   BIGINT UNSIGNED NOT NULL,
    title       VARCHAR(200) NOT NULL,
    description TEXT NULL,
    `order`     INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Orden dentro del curso',
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    INDEX idx_course_order (course_id, `order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: lessons
-- =====================================================
CREATE TABLE lessons (
    id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    module_id        BIGINT UNSIGNED NOT NULL,
    title            VARCHAR(200) NOT NULL,
    content          LONGTEXT NULL COMMENT 'HTML/Markdown del contenido',
    video_url        VARCHAR(500) NULL COMMENT 'YouTube/Vimeo embed URL',
    video_duration   INT UNSIGNED NULL COMMENT 'Duración en segundos',
    type             ENUM('video', 'text', 'mixed') NOT NULL DEFAULT 'video',
    is_free_preview  BOOLEAN NOT NULL DEFAULT FALSE COMMENT 'Visible sin inscripción',
    `order`          INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Orden dentro del módulo',
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE CASCADE,
    INDEX idx_module_order (module_id, `order`),
    INDEX idx_free_preview (is_free_preview)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: enrollments
-- =====================================================
CREATE TABLE enrollments (
    id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id          BIGINT UNSIGNED NOT NULL,
    course_id        BIGINT UNSIGNED NOT NULL,
    progress_percent DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    enrolled_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at     TIMESTAMP NULL,
    UNIQUE KEY uq_user_course (user_id, course_id),
    FOREIGN KEY (user_id)   REFERENCES users(id)   ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_course (course_id),
    INDEX idx_completed (completed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: lesson_progress
-- =====================================================
CREATE TABLE lesson_progress (
    id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id      BIGINT UNSIGNED NOT NULL,
    lesson_id    BIGINT UNSIGNED NOT NULL,
    completed    BOOLEAN NOT NULL DEFAULT FALSE,
    watch_time   INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Segundos vistos (para video)',
    completed_at TIMESTAMP NULL,
    UNIQUE KEY uq_user_lesson (user_id, lesson_id),
    FOREIGN KEY (user_id)   REFERENCES users(id)   ON DELETE CASCADE,
    FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE,
    INDEX idx_user_completed (user_id, completed)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: payments
-- =====================================================
CREATE TABLE payments (
    id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id          BIGINT UNSIGNED NOT NULL,
    course_id        BIGINT UNSIGNED NOT NULL,
    mp_payment_id    VARCHAR(100) NULL COMMENT 'ID retornado por MercadoPago',
    mp_preference_id VARCHAR(100) NULL COMMENT 'Preference ID generado',
    amount           DECIMAL(10,2) NOT NULL,
    currency         VARCHAR(10) NOT NULL DEFAULT 'COP',
    status           ENUM('pending', 'approved', 'rejected', 'refunded') NOT NULL DEFAULT 'pending',
    mp_raw_response  JSON NULL COMMENT 'Respuesta completa de MP para auditoría',
    paid_at          TIMESTAMP NULL,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)   REFERENCES users(id)   ON DELETE RESTRICT,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE RESTRICT,
    INDEX idx_user (user_id),
    INDEX idx_course (course_id),
    INDEX idx_status (status),
    INDEX idx_mp_payment (mp_payment_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: certificates
-- =====================================================
CREATE TABLE certificates (
    id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id    BIGINT UNSIGNED NOT NULL,
    course_id  BIGINT UNSIGNED NOT NULL,
    uuid       CHAR(36) NOT NULL UNIQUE COMMENT 'Para URL pública de verificación',
    pdf_path   VARCHAR(255) NULL COMMENT 'Ruta storage del PDF',
    issued_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user_course_cert (user_id, course_id),
    FOREIGN KEY (user_id)   REFERENCES users(id)   ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    INDEX idx_uuid (uuid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: password_reset_tokens (Laravel estándar)
-- =====================================================
CREATE TABLE password_reset_tokens (
    email      VARCHAR(150) NOT NULL PRIMARY KEY,
    token      VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: sessions (para Laravel session driver=database)
-- =====================================================
CREATE TABLE sessions (
    id            VARCHAR(255) NOT NULL PRIMARY KEY,
    user_id       BIGINT UNSIGNED NULL,
    ip_address    VARCHAR(45) NULL,
    user_agent    TEXT NULL,
    payload       LONGTEXT NOT NULL,
    last_activity INT NOT NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_last_activity (last_activity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- DATOS INICIALES: Admin por defecto
-- Contraseña: Admin@LMS2025 (cambiar en producción)
-- Hash bcrypt generado con Laravel
-- =====================================================
INSERT INTO users (name, email, password, role, email_verified_at) VALUES
('Administrador', 'admin@lms.com', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', NOW());
-- NOTA: El hash anterior es bcrypt de 'password' — CAMBIAR INMEDIATAMENTE en producción
