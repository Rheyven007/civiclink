-- CivicBridge Database Schema
-- Inclusive Urban Governance and Participation System

CREATE DATABASE IF NOT EXISTS civicbridge CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE civicbridge;

-- ========== USERS ==========
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('citizen','sector_rep','lgu_officer','admin') NOT NULL DEFAULT 'citizen',
    contact_number VARCHAR(30),
    address VARCHAR(255),
    status ENUM('active','suspended','pending') NOT NULL DEFAULT 'active',
    department VARCHAR(100) NULL, -- for LGU officers
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ========== SECTORS ==========
CREATE TABLE sectors (
    sector_id INT AUTO_INCREMENT PRIMARY KEY,
    sector_name VARCHAR(100) NOT NULL UNIQUE,
    description VARCHAR(255)
) ENGINE=InnoDB;

INSERT INTO sectors (sector_name, description) VALUES
('PWD','Persons with Disabilities'),
('Senior Citizen','Elderly residents'),
('Urban Poor','Low-income urban residents'),
('Youth','Young residents / students'),
('Indigenous Group','Indigenous peoples / cultural communities'),
('General Public','No specific sector affiliation');

-- ========== CITIZEN PROFILES ==========
CREATE TABLE citizen_profiles (
    profile_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    sector_id INT NULL,
    barangay VARCHAR(100),
    birthdate DATE NULL,
    valid_id_path VARCHAR(255) NULL,
    verified TINYINT(1) DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (sector_id) REFERENCES sectors(sector_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ========== PROPOSALS (Community Project Proposals) ==========
CREATE TABLE proposals (
    proposal_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    justification TEXT,
    expected_benefits TEXT,
    category ENUM('housing','transport','safety','sanitation','infrastructure','health','education','other') DEFAULT 'other',
    status ENUM('pending','under_review','approved','rejected','implemented') DEFAULT 'pending',
    votes_up INT DEFAULT 0,
    votes_down INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE proposal_votes (
    vote_id INT AUTO_INCREMENT PRIMARY KEY,
    proposal_id INT NOT NULL,
    user_id INT NOT NULL,
    vote_type ENUM('up','down') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_vote (proposal_id, user_id),
    FOREIGN KEY (proposal_id) REFERENCES proposals(proposal_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ========== SERVICE REQUESTS ==========
CREATE TABLE service_requests (
    request_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    service_type VARCHAR(150) NOT NULL,
    description TEXT NOT NULL,
    department VARCHAR(100),
    status ENUM('submitted','in_progress','resolved','closed','cancelled') DEFAULT 'submitted',
    priority ENUM('low','normal','high','urgent') DEFAULT 'normal',
    assigned_to INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ========== COMPLAINTS AND DISPUTES ==========
CREATE TABLE complaints (
    complaint_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    subject VARCHAR(200) NOT NULL,
    details TEXT NOT NULL,
    evidence_path VARCHAR(255) NULL,
    category ENUM('service','personnel','facility','dispute','other') DEFAULT 'other',
    status ENUM('filed','investigating','mediation','resolved','dismissed') DEFAULT 'filed',
    assigned_to INT NULL,
    resolution_notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ========== DECISION LOGS (Transparency / Audit of decisions) ==========
CREATE TABLE decision_logs (
    decision_id INT AUTO_INCREMENT PRIMARY KEY,
    reference_type ENUM('proposal','service_request','complaint') NOT NULL,
    reference_id INT NOT NULL,
    decided_by INT NOT NULL,
    decision ENUM('approved','rejected','escalated','closed') NOT NULL,
    justification TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (decided_by) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ========== CONSULTATIONS ==========
CREATE TABLE consultations (
    consultation_id INT AUTO_INCREMENT PRIMARY KEY,
    created_by INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('open','closed') DEFAULT 'open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ========== CONSULTATION RESPONSES (comments + votes/options) ==========
CREATE TABLE consultation_responses (
    response_id INT AUTO_INCREMENT PRIMARY KEY,
    consultation_id INT NOT NULL,
    user_id INT NOT NULL,
    response_text TEXT,
    choice ENUM('support','oppose','neutral') DEFAULT 'neutral',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_response (consultation_id, user_id),
    FOREIGN KEY (consultation_id) REFERENCES consultations(consultation_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ========== FEEDBACK AND RATINGS ==========
CREATE TABLE feedback (
    feedback_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    reference_type ENUM('service_request','complaint','general') DEFAULT 'general',
    reference_id INT NULL,
    rating TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comments TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ========== NOTIFICATIONS ==========
CREATE TABLE notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    message VARCHAR(255) NOT NULL,
    link VARCHAR(255) NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ========== AUDIT LOGS ==========
CREATE TABLE audit_logs (
    audit_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    action VARCHAR(150) NOT NULL,
    details VARCHAR(255) NULL,
    ip_address VARCHAR(45) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ========== SEED ADMIN ACCOUNT ==========
-- password: Admin@123  (hashed with PHP password_hash - bcrypt)
INSERT INTO users (full_name, email, password_hash, role, status)
VALUES ('System Administrator', 'admin@civicbridge.gov', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active');
-- Note: hash above corresponds to password "password" - CHANGE after first login.
