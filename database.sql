CREATE DATABASE IF NOT EXISTS college_system
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;
USE college_system;

CREATE TABLE IF NOT EXISTS admin (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    username VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_admin_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS students (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    student_id VARCHAR(32) DEFAULT NULL,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(190) NOT NULL,
    phone VARCHAR(40) NOT NULL,
    dob DATE NOT NULL,
    gender VARCHAR(30) NOT NULL,
    address TEXT NOT NULL,
    course VARCHAR(100) NOT NULL,
    semester TINYINT UNSIGNED NOT NULL DEFAULT 1,
    section VARCHAR(50) DEFAULT NULL,
    photo VARCHAR(255) DEFAULT NULL,
    marksheet_10th VARCHAR(255) DEFAULT NULL,
    marksheet_12th VARCHAR(255) DEFAULT NULL,
    status ENUM('Pending', 'Approved', 'Rejected') NOT NULL DEFAULT 'Pending',
    password VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_students_student_id (student_id),
    KEY idx_students_academic (course, semester, section, status),
    KEY idx_students_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS subjects (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    subject_name VARCHAR(150) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_subjects_name (subject_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS teachers (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    teacher_name VARCHAR(150) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_teachers_name (teacher_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS time_slots (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    slot_name VARCHAR(100) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_time_slots_name (slot_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS timetable (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    day ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday') NOT NULL,
    time_slot VARCHAR(100) NOT NULL,
    subject VARCHAR(150) NOT NULL,
    teacher VARCHAR(150) NOT NULL DEFAULT '',
    PRIMARY KEY (id),
    UNIQUE KEY uq_timetable_day_slot (day, time_slot)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS attendance (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    student_id VARCHAR(32) NOT NULL,
    subject VARCHAR(150) NOT NULL,
    `date` DATE NOT NULL,
    status ENUM('Present', 'Absent') NOT NULL DEFAULT 'Present',
    PRIMARY KEY (id),
    UNIQUE KEY uq_attendance_student_subject_date (student_id, subject, `date`),
    KEY idx_attendance_student (student_id),
    CONSTRAINT fk_attendance_student
        FOREIGN KEY (student_id) REFERENCES students (student_id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS marks (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    student_id VARCHAR(32) NOT NULL,
    subject VARCHAR(150) NOT NULL,
    ia_marks DECIMAL(6,2) NOT NULL DEFAULT 0.00,
    lab_marks DECIMAL(6,2) NOT NULL DEFAULT 0.00,
    sessional_marks DECIMAL(6,2) NOT NULL DEFAULT 0.00,
    PRIMARY KEY (id),
    UNIQUE KEY uq_marks_student_subject (student_id, subject),
    KEY idx_marks_student (student_id),
    CONSTRAINT fk_marks_student
        FOREIGN KEY (student_id) REFERENCES students (student_id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS study_materials (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL,
    subject VARCHAR(150) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    uploaded_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_study_materials_uploaded (uploaded_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO admin (username, password)
VALUES ('admin', '$2y$10$w.61DTatHspyZIG5J1QsGurii.io3EzRPpB7SAJRB3punOlaZ.P5q')
ON DUPLICATE KEY UPDATE username = VALUES(username);
