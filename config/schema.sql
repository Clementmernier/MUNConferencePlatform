-- Create users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    firstname VARCHAR(100) NOT NULL,
    lastname VARCHAR(100) NOT NULL,
    role ENUM('admin', 'chair', 'delegate') NOT NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    committee_id INT,
    country_code VARCHAR(3),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create countries table
CREATE TABLE IF NOT EXISTS countries (
    code VARCHAR(3) PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create committees table
CREATE TABLE IF NOT EXISTS committees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create speakers list table
CREATE TABLE IF NOT EXISTS speakers_list (
    id INT AUTO_INCREMENT PRIMARY KEY,
    committee_id INT NOT NULL,
    user_id INT NOT NULL,
    speaking_time INT NOT NULL,
    position INT NOT NULL DEFAULT 0,
    status ENUM('pending', 'speaking', 'done') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (committee_id) REFERENCES committees(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Add foreign key constraints
ALTER TABLE users
ADD CONSTRAINT fk_user_committee
FOREIGN KEY (committee_id) REFERENCES committees(id),
ADD CONSTRAINT fk_user_country
FOREIGN KEY (country_code) REFERENCES countries(code);
