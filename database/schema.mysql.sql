CREATE TABLE branding (
    id INT AUTO_INCREMENT PRIMARY KEY,
    site_name VARCHAR(120) NOT NULL,
    theme_key VARCHAR(60) NOT NULL DEFAULT 'classic-slate',
    primary_color VARCHAR(20) NOT NULL,
    secondary_color VARCHAR(20) NOT NULL,
    accent_color VARCHAR(20) NOT NULL,
    logo_path VARCHAR(255) NULL,
    background_image_path VARCHAR(255) NULL,
    hero_image_path VARCHAR(255) NULL
);

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    login_name VARCHAR(120) NOT NULL UNIQUE,
    email VARCHAR(120) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('teacher', 'admin') NOT NULL
);

CREATE TABLE courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL UNIQUE
);

CREATE TABLE students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    name VARCHAR(120) NOT NULL,
    notes TEXT NULL,
    attendance_start_lesson_id INT NULL,
    CONSTRAINT fk_students_course FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
);

CREATE TABLE lessons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    teacher_id INT NOT NULL,
    title VARCHAR(180) NOT NULL,
    category_name VARCHAR(120) NULL,
    is_featured TINYINT(1) NOT NULL DEFAULT 0,
    sort_order INT NOT NULL DEFAULT 0,
    content_type ENUM('youtube', 'file') NOT NULL DEFAULT 'youtube',
    content_file_path VARCHAR(255) NULL,
    content_original_name VARCHAR(255) NULL,
    youtube_url VARCHAR(255) NOT NULL,
    youtube_video_id VARCHAR(32) NOT NULL,
    form_url VARCHAR(255) NULL,
    plan_file_path VARCHAR(255) NULL,
    plan_original_name VARCHAR(255) NULL,
    created_at DATETIME NOT NULL,
    CONSTRAINT fk_lessons_course FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    CONSTRAINT fk_lessons_teacher FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lesson_id INT NOT NULL,
    student_id INT NOT NULL,
    status ENUM('present', 'absent') NOT NULL,
    recorded_by INT NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY uniq_attendance_lesson_student (lesson_id, student_id),
    CONSTRAINT fk_attendance_lesson FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE,
    CONSTRAINT fk_attendance_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    CONSTRAINT fk_attendance_user FOREIGN KEY (recorded_by) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE teacher_access_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    ip_address VARCHAR(64) NULL,
    user_agent TEXT NULL,
    accessed_at DATETIME NOT NULL,
    CONSTRAINT fk_teacher_access_logs_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE lesson_photos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lesson_id INT NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL,
    CONSTRAINT fk_lesson_photos_lesson FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE
);

CREATE TABLE lesson_materials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lesson_id INT NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL,
    CONSTRAINT fk_lesson_materials_lesson FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE
);

CREATE TABLE student_notes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    teacher_id INT NOT NULL,
    note TEXT NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    is_legacy_import TINYINT(1) NOT NULL DEFAULT 0,
    CONSTRAINT fk_student_notes_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    CONSTRAINT fk_student_notes_teacher FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE
);
