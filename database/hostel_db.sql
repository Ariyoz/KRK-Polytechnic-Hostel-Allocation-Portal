-- ============================================================
-- KRK POLYTECHNIC HOSTEL ALLOCATION PORTAL
-- Full Database Setup — Paste this into phpMyAdmin SQL tab
-- ============================================================



-- Drop tables in correct FK order
DROP TABLE IF EXISTS email_logs;
DROP TABLE IF EXISTS hostel_applications;
DROP TABLE IF EXISTS hostel_rooms;
DROP TABLE IF EXISTS students;
DROP TABLE IF EXISTS admins;
DROP TABLE IF EXISTS departments;
DROP TABLE IF EXISTS faculties;
DROP TABLE IF EXISTS settings;

-- -------------------------------------------------------
-- SETTINGS (key/value store for portal configuration)
-- -------------------------------------------------------
CREATE TABLE settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO settings (setting_key, setting_value) VALUES
('academic_session', '2025/2026'),
('hostel_fee_amount', '25000'),
('registration_open', '1'),
('portal_name', 'KRK Polytechnic Hostel Portal'),
('max_bed_spaces', '200');

-- -------------------------------------------------------
-- FACULTIES
-- -------------------------------------------------------
CREATE TABLE faculties (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO faculties (id, name) VALUES
(1, 'Faculty of ICT'),
(2, 'Faculty of Sciences'),
(3, 'Faculty of Engineering'),
(4, 'Faculty of Law');

-- -------------------------------------------------------
-- DEPARTMENTS
-- -------------------------------------------------------
CREATE TABLE departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    faculty_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    FOREIGN KEY (faculty_id) REFERENCES faculties(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO departments (faculty_id, name) VALUES
(1, 'Computer Science'),
(1, 'Information Technology'),
(1, 'Cyber Security'),
(2, 'Mathematics'),
(2, 'Physics'),
(2, 'Chemistry'),
(3, 'Mechanical Engineering'),
(3, 'Electrical Engineering'),
(3, 'Civil Engineering'),
(4, 'Business Law'),
(4, 'International Law'),
(4, 'Criminal Law');

-- -------------------------------------------------------
-- ADMINS
-- -------------------------------------------------------
CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('dsa','registrar','ict') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Default password for all admins: Admin@2025
-- Hash generated with: password_hash('Admin@2025', PASSWORD_DEFAULT)
INSERT INTO admins (full_name, email, password, role) VALUES
('Dean of Student Affairs', 'dsa@krkpoly.edu.ng',       '$2y$10$.wL0w4LtmHqV95bvizHr6udtEfwoLpZnUxRHFK32lgokFqyQtvRvy', 'dsa'),
('Registrar Office',        'registrar@krkpoly.edu.ng', '$2y$10$.wL0w4LtmHqV95bvizHr6udtEfwoLpZnUxRHFK32lgokFqyQtvRvy', 'registrar'),
('ICT Department',          'ict@krkpoly.edu.ng',       '$2y$10$.wL0w4LtmHqV95bvizHr6udtEfwoLpZnUxRHFK32lgokFqyQtvRvy', 'ict');

-- -------------------------------------------------------
-- STUDENTS
-- -------------------------------------------------------
CREATE TABLE students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    matric_number VARCHAR(50) UNIQUE NOT NULL,
    surname VARCHAR(100) NOT NULL,
    firstname VARCHAR(100) NOT NULL,
    othername VARCHAR(100),
    gender ENUM('male','female') NOT NULL,
    faculty_id INT NOT NULL,
    department_id INT NOT NULL,
    level VARCHAR(10) NOT NULL,
    academic_session VARCHAR(20) DEFAULT '2025/2026',
    semester ENUM('first','second') NOT NULL,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    school_fees_paid TINYINT(1) DEFAULT 0,
    is_activated TINYINT(1) DEFAULT 0,
    activation_token VARCHAR(512) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (faculty_id) REFERENCES faculties(id),
    FOREIGN KEY (department_id) REFERENCES departments(id),
    INDEX idx_email (email),
    INDEX idx_matric (matric_number),
    INDEX idx_activated (is_activated),
    INDEX idx_fees (school_fees_paid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------
-- HOSTEL ROOMS
-- -------------------------------------------------------
CREATE TABLE hostel_rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_number VARCHAR(20) UNIQUE NOT NULL,
    block VARCHAR(10) NOT NULL,
    bed_space INT DEFAULT 1,
    gender ENUM('male','female') NOT NULL,
    status ENUM('available','occupied','maintenance') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_gender (gender)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------
-- HOSTEL APPLICATIONS
-- -------------------------------------------------------
CREATE TABLE hostel_applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    room_id INT DEFAULT NULL,
    status ENUM('pending','granted','revoked') DEFAULT 'pending',
    hostel_fee_paid TINYINT(1) DEFAULT 0,
    txn_ref VARCHAR(50) DEFAULT NULL,
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (room_id) REFERENCES hostel_rooms(id) ON DELETE SET NULL,
    INDEX idx_student (student_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------
-- EMAIL LOGS
-- -------------------------------------------------------
CREATE TABLE email_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    recipient_email VARCHAR(150) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_recipient (recipient_email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
