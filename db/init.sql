-- db/init.sql
-- This file runs automatically the first time the MySQL container starts.

CREATE DATABASE IF NOT EXISTS `studentportal`;
USE `studentportal`;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `leave`;
DROP TABLE IF EXISTS `enrolment`;
DROP TABLE IF EXISTS `course`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `student`;
DROP TABLE IF EXISTS `teacher`;
DROP TABLE IF EXISTS `program`;
DROP TABLE IF EXISTS `faculty`;
DROP TABLE IF EXISTS `settings`;
DROP TABLE IF EXISTS `admin`;
SET FOREIGN_KEY_CHECKS = 1;


CREATE TABLE `admin` (
    `admin_id`    INT AUTO_INCREMENT PRIMARY KEY,
    `admin_name`  VARCHAR(100) NOT NULL,
    `admin_email` VARCHAR(100) UNIQUE NOT NULL,
    `admin_phone` VARCHAR(20),
    `role`        ENUM('admin') NOT NULL DEFAULT 'admin'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `faculty` (
    `faculty_id`   INT AUTO_INCREMENT PRIMARY KEY,
    `faculty_name` VARCHAR(120) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `program` (
    `program_id`             INT AUTO_INCREMENT PRIMARY KEY,
    `faculty_id`             INT NOT NULL,
    `program_name`           VARCHAR(150) NOT NULL,
    `program_code`           VARCHAR(20) NOT NULL UNIQUE,
    `level`                  ENUM('Foundation', 'Diploma', 'Bachelor', 'Master', 'PhD') NOT NULL,
    `total_credits_required` INT NOT NULL DEFAULT 0,
    `status`                 ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    FOREIGN KEY (`faculty_id`) REFERENCES `faculty`(`faculty_id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `teacher` (
    `teacher_id`   INT AUTO_INCREMENT PRIMARY KEY,
    `teacher_name` VARCHAR(100) NOT NULL,
    `email`        VARCHAR(100) UNIQUE NOT NULL,
    `phone`        VARCHAR(20),
    `faculty_id`   INT NULL,
    `joining_date` DATE NOT NULL,
    `status`       ENUM('active', 'resigned') NOT NULL DEFAULT 'active',
    FOREIGN KEY (`faculty_id`) REFERENCES `faculty`(`faculty_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `student` (
    `student_id`             INT AUTO_INCREMENT PRIMARY KEY,
    `program_id`             INT NOT NULL,
    `full_name`              VARCHAR(100) NOT NULL,
    `dob`                    DATE NOT NULL,
    `gender`                 ENUM('Male', 'Female', 'Other') NOT NULL,
    `address`                TEXT,
    `contact_no`             VARCHAR(20),
    `email`                  VARCHAR(100) UNIQUE NOT NULL,
    `admission_date`         DATE NOT NULL,
    `status`                 ENUM('active', 'dropout', 'graduated') NOT NULL DEFAULT 'active',
    `current_semester`       INT NOT NULL DEFAULT 1,
    `current_academic_year`  INT NOT NULL DEFAULT 1,
    `parent_name`            VARCHAR(100),
    `parent_contact`         VARCHAR(20),
    `parent_email`           VARCHAR(100),
    FOREIGN KEY (`program_id`) REFERENCES `program`(`program_id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `users` (
    `id`            INT AUTO_INCREMENT PRIMARY KEY,
    `email`         VARCHAR(150) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `role`          ENUM('admin', 'student') NOT NULL DEFAULT 'student',
    `student_id`    INT NULL,
    `admin_id`      INT NULL,
    `created_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`student_id`) REFERENCES `student`(`student_id`) ON DELETE SET NULL ON UPDATE CASCADE,
    FOREIGN KEY (`admin_id`) REFERENCES `admin`(`admin_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `course` (
    `course_id`    INT AUTO_INCREMENT PRIMARY KEY,
    `program_id`   INT NOT NULL,
    `course_name`  VARCHAR(100) NOT NULL,
    `course_code`  VARCHAR(20) NOT NULL,
    `credit_hours` INT NOT NULL,
    `duration`     VARCHAR(50) NULL,
    `status`       ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    FOREIGN KEY (`program_id`) REFERENCES `program`(`program_id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `enrolment` (
    `enroll_id`     INT AUTO_INCREMENT PRIMARY KEY,
    `student_id`    INT NOT NULL,
    `course_id`     INT NOT NULL,
    `program_id`    INT NOT NULL,
    `semester`      INT NOT NULL,
    `academic_year` INT NOT NULL,
    `status`        ENUM('registered', 'dropped') NOT NULL DEFAULT 'registered',
    FOREIGN KEY (`student_id`) REFERENCES `student`(`student_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (`course_id`) REFERENCES `course`(`course_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (`program_id`) REFERENCES `program`(`program_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    UNIQUE KEY `unique_student_course_year` (`student_id`, `course_id`, `academic_year`, `semester`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `leave` (
    `leave_id`    INT AUTO_INCREMENT PRIMARY KEY,
    `student_id`  INT NOT NULL,
    `start_date`  DATE NOT NULL,
    `end_date`    DATE NOT NULL,
    `reason`      TEXT NOT NULL,
    `evidence`    VARCHAR(255) NULL,
    `status`      ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    `approved_by` INT,
    FOREIGN KEY (`student_id`) REFERENCES `student`(`student_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (`approved_by`) REFERENCES `admin`(`admin_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================== SAMPLE TEST DATA =====================

INSERT INTO `admin` (`admin_name`, `admin_email`, `admin_phone`, `role`) VALUES
('Kelvin Tan', 'admin@portal.com', '0123456789', 'admin');

INSERT INTO `faculty` (`faculty_id`, `faculty_name`) VALUES
(1, 'Faculty of Computing'),
(2, 'Faculty of Business'),
(3, 'Faculty of Architecture'),
(4, 'Faculty of Arts'),
(5, 'Faculty of Engineering');

INSERT INTO `program` (`faculty_id`, `program_name`, `program_code`, `level`, `total_credits_required`, `status`) VALUES
(1, 'Bachelor of Information Technology', 'BIT', 'Bachelor', 120, 'active'),
(2, 'Bachelor of Business Administration', 'BBA', 'Bachelor', 120, 'active'),
(2, 'Bachelor of Human Resource', 'BHR', 'Bachelor', 120, 'active');

INSERT INTO `teacher` (`teacher_name`, `email`, `phone`, `faculty_id`, `joining_date`, `status`) VALUES
('Wong Mei', 'wong@school.edu', '0129998888', 1, '2022-03-01', 'active'),
('Ng Li', 'ng@school.edu', '0127776666', 2, '2021-11-15', 'active');

INSERT INTO `student` (`program_id`, `full_name`, `dob`, `gender`, `address`, `contact_no`, `email`, `admission_date`, `status`, `current_semester`, `current_academic_year`, `parent_name`, `parent_contact`, `parent_email`) VALUES
(1, 'Alice Tan', '2008-01-15', 'Female', '2, Ross Residence, Skudai, Johor Bahru, Johor', '0101112222', 'alice@school.edu', '2024-01-10', 'active', 1, 2026, 'John Parent', '0112233445', 'johnparent@mail.com');

INSERT INTO `users` (`email`, `password_hash`, `role`, `student_id`, `admin_id`) VALUES
('admin@portal.com', '$2y$10$3RDBONZqqvc.EQMHN6y7pOJ77hKz4S.KtltN3jPiIqZUfKFu7IHcy', 'admin', NULL, 1),
('alice@school.edu', '$2y$10$3RDBONZqqvc.EQMHN6y7pOJ77hKz4S.KtltN3jPiIqZUfKFu7IHcy', 'student', 1, NULL);

INSERT INTO `course` (`program_id`, `course_name`, `course_code`, `credit_hours`, `duration`, `status`) VALUES
(1, 'Introduction to Computer Science', 'CS101', 3, '14 Weeks', 'active'),
(1, 'IT Essentials', 'BIT101', 4, '14 Weeks', 'active'),
(2, 'Human Resource', 'BBA102', 3, '14 Weeks', 'active');

INSERT INTO `enrolment` (`student_id`, `course_id`, `program_id`, `semester`, `academic_year`, `status`) VALUES
(1, 1, 1, 1, 2026, 'registered');

INSERT INTO `leave` (`student_id`, `start_date`, `end_date`, `reason`, `status`, `approved_by`) VALUES
(1, '2026-04-01', '2026-04-03', 'Family trip', 'approved', 1);
