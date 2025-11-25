-- 1. Create the Database (if not exists)
CREATE DATABASE IF NOT EXISTS university_db;

-- 2. Select the Database
USE university_db;

-- 3. Create the 'applications' table
CREATE TABLE IF NOT EXISTS applications (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    app_id VARCHAR(20) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    dob DATE NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    program VARCHAR(100) NOT NULL,
    statement TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Optional: Insert dummy data for testing
INSERT INTO applications (app_id, full_name, dob, email, phone, program, statement, created_at)
VALUES 
('APP-883712', 'Alice Smith', '2000-05-15', 'alice@example.com', '555-0101', 'Computer Science', 'I love coding.', NOW()),
('APP-192834', 'Bob Jones', '1999-11-20', 'bob@example.com', '555-0202', 'Business Administration', 'Interested in management.', NOW());
