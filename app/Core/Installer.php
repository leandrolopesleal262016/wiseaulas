<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use RuntimeException;

final class Installer
{
    public static function run(PDO $pdo): void
    {
        if (!self::schemaExists($pdo)) {
            self::runSchema($pdo);
        }

        self::upgradeSchema($pdo);
        self::upgradeDefaultCredentials($pdo);
        self::seedDefaults($pdo);
    }

    private static function schemaExists(PDO $pdo): bool
    {
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        if ($driver === 'mysql') {
            $statement = $pdo->query("SHOW TABLES LIKE 'users'");

            return (bool) $statement->fetchColumn();
        }

        $statement = $pdo->query("SELECT name FROM sqlite_master WHERE type = 'table' AND name = 'users'");

        return (bool) $statement->fetchColumn();
    }

    private static function runSchema(PDO $pdo): void
    {
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $file = $driver === 'mysql'
            ? base_path('database/schema.mysql.sql')
            : base_path('database/schema.sqlite.sql');

        if (!is_file($file)) {
            throw new RuntimeException('Arquivo de schema nao encontrado: ' . $file);
        }

        $pdo->exec((string) file_get_contents($file));
    }

    private static function upgradeSchema(PDO $pdo): void
    {
        self::addColumnIfMissing(
            $pdo,
            'branding',
            'theme_key',
            "ALTER TABLE branding ADD COLUMN theme_key VARCHAR(60) NOT NULL DEFAULT 'classic-slate'",
            "ALTER TABLE branding ADD COLUMN theme_key TEXT NOT NULL DEFAULT 'classic-slate'"
        );
        self::addColumnIfMissing(
            $pdo,
            'branding',
            'background_image_path',
            'ALTER TABLE branding ADD COLUMN background_image_path VARCHAR(255) NULL',
            'ALTER TABLE branding ADD COLUMN background_image_path TEXT NULL'
        );
        self::addColumnIfMissing(
            $pdo,
            'branding',
            'hero_image_path',
            'ALTER TABLE branding ADD COLUMN hero_image_path VARCHAR(255) NULL',
            'ALTER TABLE branding ADD COLUMN hero_image_path TEXT NULL'
        );
        self::addColumnIfMissing(
            $pdo,
            'students',
            'notes',
            'ALTER TABLE students ADD COLUMN notes TEXT NULL',
            'ALTER TABLE students ADD COLUMN notes TEXT NULL'
        );
        self::addColumnIfMissing(
            $pdo,
            'lessons',
            'category_name',
            'ALTER TABLE lessons ADD COLUMN category_name VARCHAR(120) NULL',
            'ALTER TABLE lessons ADD COLUMN category_name TEXT NULL'
        );
        self::addColumnIfMissing(
            $pdo,
            'lessons',
            'is_featured',
            'ALTER TABLE lessons ADD COLUMN is_featured TINYINT(1) NOT NULL DEFAULT 0',
            'ALTER TABLE lessons ADD COLUMN is_featured INTEGER NOT NULL DEFAULT 0'
        );
        self::addColumnIfMissing(
            $pdo,
            'lessons',
            'content_type',
            "ALTER TABLE lessons ADD COLUMN content_type VARCHAR(20) NOT NULL DEFAULT 'youtube'",
            "ALTER TABLE lessons ADD COLUMN content_type TEXT NOT NULL DEFAULT 'youtube'"
        );
        self::addColumnIfMissing(
            $pdo,
            'lessons',
            'content_file_path',
            'ALTER TABLE lessons ADD COLUMN content_file_path VARCHAR(255) NULL',
            'ALTER TABLE lessons ADD COLUMN content_file_path TEXT NULL'
        );
        self::addColumnIfMissing(
            $pdo,
            'lessons',
            'content_original_name',
            'ALTER TABLE lessons ADD COLUMN content_original_name VARCHAR(255) NULL',
            'ALTER TABLE lessons ADD COLUMN content_original_name TEXT NULL'
        );
        self::addColumnIfMissing(
            $pdo,
            'lessons',
            'plan_file_path',
            'ALTER TABLE lessons ADD COLUMN plan_file_path VARCHAR(255) NULL',
            'ALTER TABLE lessons ADD COLUMN plan_file_path TEXT NULL'
        );
        self::addColumnIfMissing(
            $pdo,
            'lessons',
            'plan_original_name',
            'ALTER TABLE lessons ADD COLUMN plan_original_name VARCHAR(255) NULL',
            'ALTER TABLE lessons ADD COLUMN plan_original_name TEXT NULL'
        );
        self::addColumnIfMissing(
            $pdo,
            'users',
            'login_name',
            'ALTER TABLE users ADD COLUMN login_name VARCHAR(120) NULL',
            'ALTER TABLE users ADD COLUMN login_name TEXT NULL'
        );
        self::addColumnIfMissing(
            $pdo,
            'users',
            'teacher_terms_version',
            'ALTER TABLE users ADD COLUMN teacher_terms_version VARCHAR(80) NULL',
            'ALTER TABLE users ADD COLUMN teacher_terms_version TEXT NULL'
        );
        self::addColumnIfMissing(
            $pdo,
            'users',
            'teacher_terms_content',
            'ALTER TABLE users ADD COLUMN teacher_terms_content TEXT NULL',
            'ALTER TABLE users ADD COLUMN teacher_terms_content TEXT NULL'
        );
        self::addColumnIfMissing(
            $pdo,
            'users',
            'teacher_terms_accepted_at',
            'ALTER TABLE users ADD COLUMN teacher_terms_accepted_at DATETIME NULL',
            'ALTER TABLE users ADD COLUMN teacher_terms_accepted_at TEXT NULL'
        );

        if (!self::tableExists($pdo, 'teacher_access_logs')) {
            $pdo->exec($driver === 'mysql'
                ? 'CREATE TABLE teacher_access_logs (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    ip_address VARCHAR(64) NULL,
                    user_agent TEXT NULL,
                    accessed_at DATETIME NOT NULL,
                    CONSTRAINT fk_teacher_access_logs_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                )'
                : 'CREATE TABLE teacher_access_logs (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    user_id INTEGER NOT NULL,
                    ip_address TEXT NULL,
                    user_agent TEXT NULL,
                    accessed_at TEXT NOT NULL,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                )');
        }

        if (!self::tableExists($pdo, 'lesson_photos')) {
            $pdo->exec($driver === 'mysql'
                ? 'CREATE TABLE lesson_photos (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    lesson_id INT NOT NULL,
                    file_path VARCHAR(255) NOT NULL,
                    original_name VARCHAR(255) NOT NULL,
                    created_at DATETIME NOT NULL,
                    CONSTRAINT fk_lesson_photos_lesson FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE
                )'
                : 'CREATE TABLE lesson_photos (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    lesson_id INTEGER NOT NULL,
                    file_path TEXT NOT NULL,
                    original_name TEXT NOT NULL,
                    created_at TEXT NOT NULL,
                    FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE
                )');
        }

        if (!self::tableExists($pdo, 'student_notes')) {
            $pdo->exec($driver === 'mysql'
                ? 'CREATE TABLE student_notes (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    student_id INT NOT NULL,
                    teacher_id INT NOT NULL,
                    note TEXT NOT NULL,
                    created_at DATETIME NOT NULL,
                    updated_at DATETIME NOT NULL,
                    is_legacy_import TINYINT(1) NOT NULL DEFAULT 0,
                    CONSTRAINT fk_student_notes_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
                    CONSTRAINT fk_student_notes_teacher FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE
                )'
                : 'CREATE TABLE student_notes (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    student_id INTEGER NOT NULL,
                    teacher_id INTEGER NOT NULL,
                    note TEXT NOT NULL,
                    created_at TEXT NOT NULL,
                    updated_at TEXT NOT NULL,
                    is_legacy_import INTEGER NOT NULL DEFAULT 0,
                    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
                    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE
                )');
        }

        $pdo->exec("UPDATE branding SET theme_key = 'classic-slate' WHERE theme_key IS NULL OR theme_key = ''");
        $pdo->exec("UPDATE users SET login_name = name WHERE login_name IS NULL OR login_name = ''");
        $pdo->exec("UPDATE lessons SET is_featured = 0 WHERE is_featured IS NULL");
        $pdo->exec("UPDATE lessons SET content_type = 'youtube' WHERE content_type IS NULL OR content_type = ''");
        self::migrateLegacyStudentNotes($pdo);
    }

    private static function tableExists(PDO $pdo, string $table): bool
    {
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        if ($driver === 'mysql') {
            $statement = $pdo->query("SHOW TABLES LIKE '{$table}'");

            return (bool) $statement->fetchColumn();
        }

        $statement = $pdo->query("SELECT name FROM sqlite_master WHERE type = 'table' AND name = '{$table}'");

        return (bool) $statement->fetchColumn();
    }

    private static function columnExists(PDO $pdo, string $table, string $column): bool
    {
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        if ($driver === 'mysql') {
            $statement = $pdo->prepare("SHOW COLUMNS FROM {$table} LIKE :column");
            $statement->execute(['column' => $column]);

            return (bool) $statement->fetchColumn();
        }

        $statement = $pdo->query("PRAGMA table_info({$table})");

        foreach ($statement->fetchAll() as $row) {
            if (($row['name'] ?? null) === $column) {
                return true;
            }
        }

        return false;
    }

    private static function addColumnIfMissing(
        PDO $pdo,
        string $table,
        string $column,
        string $mysqlSql,
        string $sqliteSql
    ): void {
        if (self::columnExists($pdo, $table, $column)) {
            return;
        }

        try {
            $pdo->exec($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql' ? $mysqlSql : $sqliteSql);
        } catch (\Throwable $exception) {
            if (!self::columnExists($pdo, $table, $column)) {
                throw $exception;
            }
        }
    }

    private static function seedDefaults(PDO $pdo): void
    {
        $usersCount = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();

        if ($usersCount > 0) {
            return;
        }

        try {
            $pdo->beginTransaction();

            $insertBranding = $pdo->prepare(
                'INSERT INTO branding (site_name, theme_key, primary_color, secondary_color, accent_color, logo_path, background_image_path, hero_image_path)
                 VALUES (:site_name, :theme_key, :primary_color, :secondary_color, :accent_color, :logo_path, :background_image_path, :hero_image_path)'
            );
            $insertBranding->execute([
                'site_name' => env('APP_NAME', 'Sistema de Aulas Online'),
                'theme_key' => 'classic-slate',
                'primary_color' => '#12355b',
                'secondary_color' => '#f7efe5',
                'accent_color' => '#ef476f',
                'logo_path' => null,
                'background_image_path' => null,
                'hero_image_path' => null,
            ]);

            $insertUser = $pdo->prepare(
                'INSERT INTO users (name, login_name, email, password_hash, role)
                 VALUES (:name, :login_name, :email, :password_hash, :role)'
            );

            $insertUser->execute([
                'name' => 'Leandro',
                'login_name' => 'Leandro',
                'email' => 'admin@escola.local',
                'password_hash' => password_hash('5510', PASSWORD_DEFAULT),
                'role' => 'admin',
            ]);

            $insertUser->execute([
                'name' => 'Professor Demo',
                'login_name' => 'Professor Demo',
                'email' => 'professor@escola.local',
                'password_hash' => password_hash('prof123', PASSWORD_DEFAULT),
                'role' => 'teacher',
            ]);

            $teacherId = (int) $pdo->lastInsertId();

            $insertCourse = $pdo->prepare('INSERT INTO courses (name) VALUES (:name)');
            $insertCourse->execute(['name' => 'Matematica - 1A']);
            $courseOneId = (int) $pdo->lastInsertId();
            $insertCourse->execute(['name' => 'Fisica - 2B']);
            $courseTwoId = (int) $pdo->lastInsertId();

            $insertStudent = $pdo->prepare('INSERT INTO students (course_id, name) VALUES (:course_id, :name)');
            foreach ([
                [$courseOneId, 'Ana Beatriz'],
                [$courseOneId, 'Carlos Henrique'],
                [$courseOneId, 'Julia Nunes'],
                [$courseTwoId, 'Marcos Paulo'],
                [$courseTwoId, 'Renata Souza'],
                [$courseTwoId, 'Tiago Almeida'],
            ] as [$courseId, $studentName]) {
                $insertStudent->execute([
                    'course_id' => $courseId,
                    'name' => $studentName,
                ]);
            }

            $insertLesson = $pdo->prepare(
                'INSERT INTO lessons (course_id, teacher_id, title, youtube_url, youtube_video_id, form_url, created_at)
                 VALUES (:course_id, :teacher_id, :title, :youtube_url, :youtube_video_id, :form_url, :created_at)'
            );
            $insertLesson->execute([
                'course_id' => $courseOneId,
                'teacher_id' => $teacherId,
                'title' => 'Introducao a Equacoes',
                'youtube_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                'youtube_video_id' => 'dQw4w9WgXcQ',
                'form_url' => 'https://docs.google.com/forms/d/e/1FAIpQLScm_lf4R9YQw6i4T0x1hCsHc9v3d-embed-example/viewform?embedded=true',
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            $lessonId = (int) $pdo->lastInsertId();
            $students = $pdo->prepare('SELECT id FROM students WHERE course_id = :course_id');
            $students->execute(['course_id' => $courseOneId]);
            $attendance = $pdo->prepare(
                'INSERT INTO attendance (lesson_id, student_id, status, recorded_by, updated_at)
                 VALUES (:lesson_id, :student_id, :status, :recorded_by, :updated_at)'
            );
            foreach ($students->fetchAll() as $student) {
                $attendance->execute([
                    'lesson_id' => $lessonId,
                    'student_id' => (int) $student['id'],
                    'status' => 'present',
                    'recorded_by' => $teacherId,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            }

            $pdo->commit();
        } catch (\Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $usersCount = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();

            if ($usersCount > 0) {
                return;
            }

            throw $exception;
        }
    }

    private static function upgradeDefaultCredentials(PDO $pdo): void
    {
        $statement = $pdo->prepare(
            "SELECT id, name, login_name
             FROM users
             WHERE role = 'admin' AND email = :email
             LIMIT 1"
        );
        $statement->execute(['email' => 'admin@escola.local']);
        $admin = $statement->fetch();

        if (!$admin) {
            return;
        }

        $currentName = (string) ($admin['name'] ?? '');
        $currentLogin = (string) ($admin['login_name'] ?? '');

        if ($currentName === 'Leandro' && $currentLogin === 'Leandro') {
            return;
        }

        if (
            !in_array($currentName, ['Administrador', 'Leandro'], true) ||
            !in_array($currentLogin, ['Administrador', 'Leandro'], true)
        ) {
            return;
        }

        $update = $pdo->prepare(
            'UPDATE users
             SET name = :name,
                 login_name = :login_name,
                 password_hash = :password_hash
             WHERE id = :id'
        );
        $update->execute([
            'id' => (int) $admin['id'],
            'name' => 'Leandro',
            'login_name' => 'Leandro',
            'password_hash' => password_hash('5510', PASSWORD_DEFAULT),
        ]);
    }

    private static function migrateLegacyStudentNotes(PDO $pdo): void
    {
        if (!self::columnExists($pdo, 'students', 'notes') || !self::tableExists($pdo, 'student_notes')) {
            return;
        }

        $students = $pdo->query(
            "SELECT id, course_id, notes
             FROM students
             WHERE notes IS NOT NULL AND TRIM(notes) <> ''"
        )->fetchAll();

        if ($students === []) {
            return;
        }

        $teacherStatement = $pdo->prepare(
            "SELECT l.teacher_id
             FROM lessons l
             WHERE l.course_id = :course_id
             ORDER BY l.id ASC
             LIMIT 1"
        );
        $fallbackTeacherStatement = $pdo->query(
            "SELECT id
             FROM users
             WHERE role = 'teacher'
             ORDER BY id ASC
             LIMIT 1"
        );
        $fallbackTeacherId = (int) ($fallbackTeacherStatement->fetchColumn() ?: 0);
        $existingLegacyStatement = $pdo->prepare(
            'SELECT COUNT(*) FROM student_notes WHERE student_id = :student_id AND is_legacy_import = 1'
        );
        $insertLegacyStatement = $pdo->prepare(
            'INSERT INTO student_notes (student_id, teacher_id, note, created_at, updated_at, is_legacy_import)
             VALUES (:student_id, :teacher_id, :note, :created_at, :updated_at, :is_legacy_import)'
        );

        foreach ($students as $student) {
            $existingLegacyStatement->execute(['student_id' => (int) $student['id']]);

            if ((int) $existingLegacyStatement->fetchColumn() > 0) {
                continue;
            }

            $teacherStatement->execute(['course_id' => (int) $student['course_id']]);
            $teacherId = (int) ($teacherStatement->fetchColumn() ?: $fallbackTeacherId);

            if ($teacherId <= 0) {
                continue;
            }

            $insertLegacyStatement->execute([
                'student_id' => (int) $student['id'],
                'teacher_id' => $teacherId,
                'note' => trim((string) $student['notes']),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
                'is_legacy_import' => 1,
            ]);
        }
    }
}
