-- ============================================================
-- Σύστημα Υποστήριξης Διπλωματικών Εργασιών
-- Database schema (MySQL / MariaDB)
-- ============================================================
DROP DATABASE IF EXISTS thesis_system;
CREATE DATABASE thesis_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE thesis_system;

-- ------------------------------------------------------------
-- USERS (base table for all 3 roles)
-- ------------------------------------------------------------
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('student','professor','secretary') NOT NULL,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_users_role (role)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- STUDENTS (extends users)
-- ------------------------------------------------------------
CREATE TABLE students (
    user_id INT PRIMARY KEY,
    am VARCHAR(20) NOT NULL UNIQUE,           -- Αριθμός Μητρώου
    address VARCHAR(255),
    mobile_phone VARCHAR(30),
    home_phone VARCHAR(30),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_students_am (am)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- PROFESSORS (extends users)
-- ------------------------------------------------------------
CREATE TABLE professors (
    user_id INT PRIMARY KEY,
    department VARCHAR(150) DEFAULT 'ΤΜΗΥΠ',
    topic_count INT DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- TOPICS (θέματα προς ανάθεση, δημιουργούνται από Διδάσκοντα)
-- ------------------------------------------------------------
CREATE TABLE topics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    professor_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    short_description VARCHAR(1000) NOT NULL,
    pdf_path VARCHAR(255) DEFAULT NULL,
    is_taken TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (professor_id) REFERENCES professors(user_id) ON DELETE CASCADE,
    INDEX idx_topics_professor (professor_id),
    INDEX idx_topics_taken (is_taken)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- THESES (Διπλωματικές Εργασίες - ΔΕ)
-- ------------------------------------------------------------
CREATE TABLE theses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    topic_id INT NOT NULL,
    student_id INT NOT NULL,
    supervisor_id INT NOT NULL,
    status ENUM('under_assignment','active','under_examination','completed','cancelled')
           NOT NULL DEFAULT 'under_assignment',
    assigned_at DATETIME DEFAULT NULL,          -- επίσημη (οριστική) ανάθεση
    ga_number VARCHAR(50) DEFAULT NULL,         -- Αριθμός Πρωτοκόλλου/ΓΣ ανάθεσης
    ga_year INT DEFAULT NULL,
    cancel_ga_number VARCHAR(50) DEFAULT NULL,
    cancel_ga_year INT DEFAULT NULL,
    cancel_reason VARCHAR(500) DEFAULT NULL,
    draft_file_path VARCHAR(255) DEFAULT NULL,
    exam_datetime DATETIME DEFAULT NULL,
    exam_mode ENUM('in_person','online') DEFAULT NULL,
    exam_location VARCHAR(255) DEFAULT NULL,    -- αίθουσα
    exam_link VARCHAR(255) DEFAULT NULL,        -- σύνδεσμος διαδικτυακής παρακολούθησης
    nemertes_link VARCHAR(255) DEFAULT NULL,
    final_grade DECIMAL(4,2) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (topic_id) REFERENCES topics(id),
    FOREIGN KEY (student_id) REFERENCES students(user_id),
    FOREIGN KEY (supervisor_id) REFERENCES professors(user_id),
    INDEX idx_theses_status (status),
    INDEX idx_theses_student (student_id),
    INDEX idx_theses_supervisor (supervisor_id),
    INDEX idx_theses_exam_datetime (exam_datetime)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- COMMITTEE MEMBERS (Τριμελής επιτροπή, incl. supervisor row)
-- ------------------------------------------------------------
CREATE TABLE committee_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    thesis_id INT NOT NULL,
    professor_id INT NOT NULL,
    member_role ENUM('supervisor','member') NOT NULL DEFAULT 'member',
    invitation_status ENUM('pending','accepted','rejected','cancelled') NOT NULL DEFAULT 'pending',
    invited_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    responded_at DATETIME DEFAULT NULL,
    grade DECIMAL(4,2) DEFAULT NULL,
    grade_criteria_json TEXT DEFAULT NULL,
    graded_at DATETIME DEFAULT NULL,
    FOREIGN KEY (thesis_id) REFERENCES theses(id) ON DELETE CASCADE,
    FOREIGN KEY (professor_id) REFERENCES professors(user_id),
    UNIQUE KEY uq_thesis_professor (thesis_id, professor_id),
    INDEX idx_committee_status (invitation_status),
    INDEX idx_committee_professor (professor_id)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- STATUS HISTORY (χρονολόγιο ενεργειών)
-- ------------------------------------------------------------
CREATE TABLE thesis_status_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    thesis_id INT NOT NULL,
    old_status VARCHAR(50),
    new_status VARCHAR(50) NOT NULL,
    changed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    note VARCHAR(500) DEFAULT NULL,
    FOREIGN KEY (thesis_id) REFERENCES theses(id) ON DELETE CASCADE,
    INDEX idx_history_thesis (thesis_id)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- NOTES (ιδιωτικές σημειώσεις διδασκόντων, <=300 chars)
-- ------------------------------------------------------------
CREATE TABLE notes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    thesis_id INT NOT NULL,
    professor_id INT NOT NULL,
    note_text VARCHAR(300) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (thesis_id) REFERENCES theses(id) ON DELETE CASCADE,
    FOREIGN KEY (professor_id) REFERENCES professors(user_id),
    INDEX idx_notes_thesis_prof (thesis_id, professor_id)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- MATERIALS (υποστηρικτικό υλικό / links κατά την "Υπό Εξέταση")
-- ------------------------------------------------------------
CREATE TABLE materials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    thesis_id INT NOT NULL,
    material_type ENUM('file','link') NOT NULL,
    label VARCHAR(150) DEFAULT NULL,
    path_or_url VARCHAR(500) NOT NULL,
    uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (thesis_id) REFERENCES theses(id) ON DELETE CASCADE,
    INDEX idx_materials_thesis (thesis_id)
) ENGINE=InnoDB;
