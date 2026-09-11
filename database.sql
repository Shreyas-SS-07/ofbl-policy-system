-- ====================================================================
-- OFBL POLICY MANAGEMENT SYSTEM (ENTERPRISE EDITION)
-- Ordnance Factory Badmal, Munitions India Limited (MIL)
-- Ministry of Defence, Government of India
-- Lead Developer: Shreyas Sankalp Sahu (KIIT Deemed to be University)
-- ====================================================================

CREATE DATABASE IF NOT EXISTS ofb_policy_db DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ofb_policy_db;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS udit_logs;
DROP TABLE IF EXISTS comments;
DROP TABLE IF EXISTS policies;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS sections;
SET FOREIGN_KEY_CHECKS = 1;

-- --------------------------------------------------------------------
-- 1. SECTIONS / DEPARTMENTS TABLE
-- --------------------------------------------------------------------
CREATE TABLE sections (
  id INT(11) NOT NULL AUTO_INCREMENT,
  section_code VARCHAR(20) NOT NULL UNIQUE,
  section_name VARCHAR(150) NOT NULL,
  description TEXT DEFAULT NULL,
  icon VARCHAR(50) DEFAULT 'bi-building',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO sections (id, section_code, section_name, description, icon) VALUES
(1, 'ITC', 'Information Technology Centre', 'Enterprise infrastructure, secure network, software and cyber policies.', 'bi-cpu'),
(2, 'PROD', 'Ordnance Production Division', 'Munitions manufacturing protocols, assembly guidelines, and operating procedures.', 'bi-gear-wide-connected'),
(3, 'DGQA', 'Quality Assurance & Inspection', 'Defective tolerance criteria, calibration norms, and DGQA standards.', 'bi-shield-check'),
(4, 'SAFE', 'Industrial Safety & Explosives', 'Hazardous materials handling, PPE compliance, and emergency protocols.', 'bi-exclamation-triangle'),
(5, 'FIN', 'Finance & Defence Accounts', 'Procurement directives, fiscal authorizations, and budget compliance.', 'bi-cash-coin'),
(6, 'HRD', 'Human Resource Development', 'Staff conduct, apprenticeship directives, welfare, and training policies.', 'bi-people');

-- --------------------------------------------------------------------
-- 2. USERS TABLE
-- --------------------------------------------------------------------
CREATE TABLE users (
  id INT(11) NOT NULL AUTO_INCREMENT,
  
ame VARCHAR(120) NOT NULL,
  email VARCHAR(120) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  
ole ENUM('admin', 'officer', 'user') NOT NULL DEFAULT 'user',
  department_id INT(11) DEFAULT NULL,
  status ENUM('approved', 'pending', 'rejected', 'suspended') NOT NULL DEFAULT 'pending',
  profile_image VARCHAR(255) DEFAULT 'default.png',
  ip_address VARCHAR(45) DEFAULT NULL,
  last_login TIMESTAMP NULL DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY k_user_department (department_id),
  CONSTRAINT k_user_department FOREIGN KEY (department_id) REFERENCES sections (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Standard Passwords:
-- Admin Pass: Admin@OFBL2026!
-- Demo User Pass: User@OFBL2026!
-- (Pre-generated Bcrypt hashes)
INSERT INTO users (id, 
ame, email, password, 
ole, department_id, status, profile_image, ip_address, created_at) VALUES
(1, 'Shreyas Sankalp Sahu', 'admin@ofbl.gov.in', '', 'admin', 1, 'approved', 'default.png', '127.0.0.1', '2026-05-04 09:00:00'),
(2, 'Minati Pradhan (JWM/ITC)', 'minati.pradhan@ofbl.gov.in', '', 'admin', 1, 'approved', 'default.png', '127.0.0.1', '2026-05-04 09:30:00'),
(3, 'Rajesh Kumar Sharma', 'rajesh.sharma@ofbl.gov.in', '/OU3h8r3M0yQ3gQ0V5kG8xH9Z0X8mY0tW', 'user', 2, 'approved', 'default.png', '192.168.1.45', '2026-05-10 10:15:00'),
(4, 'Pooja Verma', 'pooja.verma@ofbl.gov.in', '/OU3h8r3M0yQ3gQ0V5kG8xH9Z0X8mY0tW', 'user', 4, 'approved', 'default.png', '192.168.1.82', '2026-05-12 11:20:00'),
(5, 'Alok Mohanty', 'alok.mohanty@ofbl.gov.in', '/OU3h8r3M0yQ3gQ0V5kG8xH9Z0X8mY0tW', 'user', 3, 'pending', 'default.png', '192.168.1.110', '2026-05-18 14:05:00'),
(6, 'Sujit Dhal', 'sujitdhal434@gmail.com', '', 'admin', 1, 'approved', 'default.png', '127.0.0.1', '2026-05-04 09:10:00');

-- --------------------------------------------------------------------
-- 3. POLICIES TABLE
-- --------------------------------------------------------------------
CREATE TABLE policies (
  id INT(11) NOT NULL AUTO_INCREMENT,
  policy_number VARCHAR(50) NOT NULL UNIQUE,
  	itle VARCHAR(220) NOT NULL,
  section_id INT(11) NOT NULL,
  policy_date DATE NOT NULL,
  pdf_file VARCHAR(255) NOT NULL,
  pdf_hint TEXT DEFAULT NULL,
  ile_size_kb INT(11) DEFAULT 120,
  ersion VARCHAR(20) DEFAULT '1.0',
  status ENUM('active', 'under_review', 'archived') NOT NULL DEFAULT 'active',
  uploaded_by INT(11) NOT NULL,
  download_count INT(11) DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY k_policy_section (section_id),
  KEY k_policy_uploader (uploaded_by),
  CONSTRAINT k_policy_section FOREIGN KEY (section_id) REFERENCES sections (id) ON DELETE CASCADE,
  CONSTRAINT k_policy_uploader FOREIGN KEY (uploaded_by) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO policies (id, policy_number, 	itle, section_id, policy_date, pdf_file, pdf_hint, ile_size_kb, ersion, status, uploaded_by, download_count, created_at) VALUES
(1, 'POL-ITC-2026-001', 'Cybersecurity & Official IT Usage Policy', 1, '2026-04-01', 'OFBL_IT_Security_Policy_2026.pdf', 'Mandatory cyber hygiene, email confidentiality, and authorized device usage rules for OFBL network.', 245, '2.1', 'active', 1, 48, '2026-04-01 10:00:00'),
(2, 'POL-SAFE-2026-002', 'Ordnance Safety & Hazardous Materials Protocol', 4, '2026-04-05', 'OFBL_Industrial_Safety_Protocol.pdf', 'Operating safety directives inside explosive storage, PPE regulations, and emergency fire evacuation.', 312, '1.4', 'active', 1, 92, '2026-04-05 11:30:00'),
(3, 'POL-DGQA-2026-003', 'Munitions Production Quality Assurance Manual', 3, '2026-04-12', 'OFBL_Quality_Control_Standards.pdf', 'Quality benchmarks, inspection sampling techniques, and calibration frequency for ordnance production.', 410, '3.0', 'active', 2, 34, '2026-04-12 14:00:00'),
(4, 'POL-HRD-2026-004', 'Employee Code of Conduct & Ethics Regulations', 6, '2026-04-18', 'OFBL_Employee_Conduct_Regulations.pdf', 'Disciplinary rules, working hours, leave rules, and confidentiality obligations for MIL personnel.', 198, '1.2', 'active', 1, 65, '2026-04-18 16:45:00'),
(5, 'POL-ADM-2026-005', 'Defence Information Handling & Document Classification SOP', 1, '2026-04-25', 'OFBL_Information_Classification_SOP.pdf', 'Guidelines on handling classified documents, physical and digital record archiving, and non-disclosure.', 280, '1.0', 'active', 2, 29, '2026-04-25 09:15:00');

-- --------------------------------------------------------------------
-- 4. COMMENTS TABLE
-- --------------------------------------------------------------------
CREATE TABLE comments (
  id INT(11) NOT NULL AUTO_INCREMENT,
  policy_id INT(11) NOT NULL,
  user_id INT(11) NOT NULL,
  comment TEXT NOT NULL,
  status ENUM('published', 'flagged', 'hidden') DEFAULT 'published',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY k_comment_policy (policy_id),
  KEY k_comment_user (user_id),
  CONSTRAINT k_comment_policy FOREIGN KEY (policy_id) REFERENCES policies (id) ON DELETE CASCADE,
  CONSTRAINT k_comment_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO comments (id, policy_id, user_id, comment, created_at) VALUES
(1, 1, 3, 'Requesting clarification regarding multi-factor authentication requirements for remote intranet terminals.', '2026-05-06 11:20:00'),
(2, 2, 4, 'Annual safety drill schedule for Ordnance Bay-4 has been coordinated in compliance with Section 4.2.', '2026-05-08 14:45:00'),
(3, 3, 3, 'Received the updated inspection checklist. Calibration of batch gauges completed yesterday.', '2026-05-14 16:10:00');

-- --------------------------------------------------------------------
-- 5. AUDIT LOGS TABLE (DEFENCE-GRADE AUDIT TRAIL)
-- --------------------------------------------------------------------
CREATE TABLE udit_logs (
  id INT(11) NOT NULL AUTO_INCREMENT,
  user_id INT(11) DEFAULT NULL,
  user_name VARCHAR(120) DEFAULT NULL,
  ction VARCHAR(100) NOT NULL,
  details TEXT DEFAULT NULL,
  ip_address VARCHAR(45) DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY k_audit_user (user_id),
  CONSTRAINT k_audit_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO udit_logs (user_id, user_name, ction, details, ip_address, created_at) VALUES
(1, 'Shreyas Sankalp Sahu', 'SYSTEM_INITIALIZATION', 'OFBL Policy Governance Core v2.0 initialized.', '127.0.0.1', '2026-05-04 09:00:00'),
(1, 'Shreyas Sankalp Sahu', 'POLICY_UPLOAD', 'Published policy POL-ITC-2026-001 (Cybersecurity)', '127.0.0.1', '2026-05-04 10:05:00'),
(1, 'Shreyas Sankalp Sahu', 'USER_APPROVED', 'Approved user account for Rajesh Kumar Sharma', '127.0.0.1', '2026-05-10 10:18:00'),
(3, 'Rajesh Kumar Sharma', 'POLICY_VIEW', 'Downloaded PDF for POL-SAFE-2026-002', '192.168.1.45', '2026-05-12 11:40:00');
