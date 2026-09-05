-- ==========================================
-- CIVICBRIDGE DATABASE
-- Safe schema import
-- ==========================================

CREATE DATABASE IF NOT EXISTS civicbridge
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE civicbridge;


-- ==========================================
-- USERS
-- ==========================================

CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('citizen','sector_rep','lgu_officer','admin')
        NOT NULL DEFAULT 'citizen',
    contact_number VARCHAR(30),
    address VARCHAR(255),
    status ENUM('active','suspended','pending')
        NOT NULL DEFAULT 'active',
    department VARCHAR(100) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ==========================================
-- SECTORS
-- ==========================================

CREATE TABLE IF NOT EXISTS sectors (
    sector_id INT AUTO_INCREMENT PRIMARY KEY,
    sector_name VARCHAR(100) NOT NULL UNIQUE,
    description VARCHAR(255)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


INSERT IGNORE INTO sectors (sector_name, description) VALUES
('PWD','Persons with Disabilities'),
('Senior Citizen','Elderly residents'),
('Urban Poor','Low-income urban residents'),
('Youth','Young residents / students'),
('Indigenous Group','Indigenous peoples / cultural communities'),
('General Public','No specific sector affiliation');


-- ==========================================
-- CITIZEN PROFILES
-- ==========================================

CREATE TABLE IF NOT EXISTS citizen_profiles (
    profile_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    sector_id INT NULL,
    barangay VARCHAR(100),
    birthdate DATE NULL,
    valid_id_path VARCHAR(255) NULL,
    verified TINYINT(1) DEFAULT 0,

    CONSTRAINT fk_profile_user
        FOREIGN KEY (user_id)
        REFERENCES users(user_id)
        ON DELETE CASCADE,

    CONSTRAINT fk_profile_sector
        FOREIGN KEY (sector_id)
        REFERENCES sectors(sector_id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ==========================================
-- PROPOSALS
-- ==========================================

CREATE TABLE IF NOT EXISTS proposals (
    proposal_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    justification TEXT,
    expected_benefits TEXT,

    category ENUM(
        'housing',
        'transport',
        'safety',
        'sanitation',
        'infrastructure',
        'health',
        'education',
        'other'
    ) DEFAULT 'other',

    status ENUM(
        'pending',
        'under_review',
        'approved',
        'rejected',
        'implemented'
    ) DEFAULT 'pending',

    votes_up INT NOT NULL DEFAULT 0,
    votes_down INT NOT NULL DEFAULT 0,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_proposal_user
        FOREIGN KEY (user_id)
        REFERENCES users(user_id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ==========================================
-- PROPOSAL VOTES
-- ==========================================

CREATE TABLE IF NOT EXISTS proposal_votes (
    vote_id INT AUTO_INCREMENT PRIMARY KEY,
    proposal_id INT NOT NULL,
    user_id INT NOT NULL,

    vote_type ENUM('up','down') NOT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY unique_vote (
        proposal_id,
        user_id
    ),

    CONSTRAINT fk_vote_proposal
        FOREIGN KEY (proposal_id)
        REFERENCES proposals(proposal_id)
        ON DELETE CASCADE,

    CONSTRAINT fk_vote_user
        FOREIGN KEY (user_id)
        REFERENCES users(user_id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ==========================================
-- SERVICE REQUESTS
-- ==========================================

CREATE TABLE IF NOT EXISTS service_requests (
    request_id INT AUTO_INCREMENT PRIMARY KEY,

    user_id INT NOT NULL,
    service_type VARCHAR(150) NOT NULL,
    description TEXT NOT NULL,

    department VARCHAR(100),

    status ENUM(
        'submitted',
        'in_progress',
        'resolved',
        'closed',
        'cancelled'
    ) DEFAULT 'submitted',

    priority ENUM(
        'low',
        'normal',
        'high',
        'urgent'
    ) DEFAULT 'normal',

    assigned_to INT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_request_user
        FOREIGN KEY (user_id)
        REFERENCES users(user_id)
        ON DELETE CASCADE,

    CONSTRAINT fk_request_assigned
        FOREIGN KEY (assigned_to)
        REFERENCES users(user_id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ==========================================
-- COMPLAINTS
-- ==========================================

CREATE TABLE IF NOT EXISTS complaints (
    complaint_id INT AUTO_INCREMENT PRIMARY KEY,

    user_id INT NOT NULL,

    subject VARCHAR(200) NOT NULL,
    details TEXT NOT NULL,

    evidence_path VARCHAR(255) NULL,

    category ENUM(
        'service',
        'personnel',
        'facility',
        'dispute',
        'other'
    ) DEFAULT 'other',

    status ENUM(
        'filed',
        'investigating',
        'mediation',
        'resolved',
        'dismissed'
    ) DEFAULT 'filed',

    assigned_to INT NULL,

    resolution_notes TEXT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_complaint_user
        FOREIGN KEY (user_id)
        REFERENCES users(user_id)
        ON DELETE CASCADE,

    CONSTRAINT fk_complaint_assigned
        FOREIGN KEY (assigned_to)
        REFERENCES users(user_id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ==========================================
-- DECISION LOGS
-- ==========================================

CREATE TABLE IF NOT EXISTS decision_logs (
    decision_id INT AUTO_INCREMENT PRIMARY KEY,

    reference_type ENUM(
        'proposal',
        'service_request',
        'complaint'
    ) NOT NULL,

    reference_id INT NOT NULL,

    decided_by INT NOT NULL,

    decision ENUM(
        'approved',
        'rejected',
        'escalated',
        'closed'
    ) NOT NULL,

    justification TEXT NOT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_decision_user
        FOREIGN KEY (decided_by)
        REFERENCES users(user_id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ==========================================
-- CONSULTATIONS
-- ==========================================

CREATE TABLE IF NOT EXISTS consultations (
    consultation_id INT AUTO_INCREMENT PRIMARY KEY,

    created_by INT NOT NULL,

    title VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,

    start_date DATE NOT NULL,
    end_date DATE NOT NULL,

    status ENUM(
        'open',
        'closed'
    ) DEFAULT 'open',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_consultation_user
        FOREIGN KEY (created_by)
        REFERENCES users(user_id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ==========================================
-- CONSULTATION RESPONSES
-- ==========================================

CREATE TABLE IF NOT EXISTS consultation_responses (
    response_id INT AUTO_INCREMENT PRIMARY KEY,

    consultation_id INT NOT NULL,
    user_id INT NOT NULL,

    response_text TEXT,

    choice ENUM(
        'support',
        'oppose',
        'neutral'
    ) DEFAULT 'neutral',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY unique_response (
        consultation_id,
        user_id
    ),

    CONSTRAINT fk_response_consultation
        FOREIGN KEY (consultation_id)
        REFERENCES consultations(consultation_id)
        ON DELETE CASCADE,

    CONSTRAINT fk_response_user
        FOREIGN KEY (user_id)
        REFERENCES users(user_id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ==========================================
-- FEEDBACK
-- ==========================================

CREATE TABLE IF NOT EXISTS feedback (
    feedback_id INT AUTO_INCREMENT PRIMARY KEY,

    user_id INT NOT NULL,

    reference_type ENUM(
        'service_request',
        'complaint',
        'general'
    ) DEFAULT 'general',

    reference_id INT NULL,

    rating TINYINT NOT NULL,

    comments TEXT,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT chk_feedback_rating
        CHECK (rating BETWEEN 1 AND 5),

    CONSTRAINT fk_feedback_user
        FOREIGN KEY (user_id)
        REFERENCES users(user_id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ==========================================
-- NOTIFICATIONS
-- ==========================================

CREATE TABLE IF NOT EXISTS notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,

    user_id INT NOT NULL,

    message VARCHAR(255) NOT NULL,
    link VARCHAR(255) NULL,

    is_read TINYINT(1) DEFAULT 0,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_notification_user
        FOREIGN KEY (user_id)
        REFERENCES users(user_id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ==========================================
-- AUDIT LOGS
-- ==========================================

CREATE TABLE IF NOT EXISTS audit_logs (
    audit_id INT AUTO_INCREMENT PRIMARY KEY,

    user_id INT NULL,

    action VARCHAR(150) NOT NULL,
    details VARCHAR(255) NULL,

    ip_address VARCHAR(45) NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_audit_user
        FOREIGN KEY (user_id)
        REFERENCES users(user_id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ==========================================
-- ADMIN ACCOUNT
-- ==========================================

INSERT IGNORE INTO users (
    full_name,
    email,
    password_hash,
    role,
    status
)
VALUES (
    'System Administrator',
    'admin@civicbridge.gov',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'admin',
    'active'
);