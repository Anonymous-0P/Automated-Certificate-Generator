CREATE DATABASE IF NOT EXISTS certificates_db;
USE certificates_db;

CREATE TABLE IF NOT EXISTS certificates (
  id INT AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(255) NOT NULL,
  reg_number VARCHAR(100) NOT NULL,
  organization_name VARCHAR(255) NOT NULL,
  course_name VARCHAR(255) NOT NULL,
  issue_date DATE,  -- ✅ New column added here
  start_date DATE NOT NULL,
  end_date DATE NOT NULL,
  total_hours INT,
  grade VARCHAR(10),
  course_content TEXT NULL,
  activities_json TEXT,
  unique_code VARCHAR(25) NOT NULL UNIQUE,
  qr_code_path VARCHAR(255) NOT NULL,
  pdf_path VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY reg_course_unique (reg_number(50), course_name(50))
);

CREATE TABLE IF NOT EXISTS admins (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) UNIQUE NOT NULL,
  password_hash VARCHAR(255) NOT NULL
);
