
CREATE DATABASE SupportSystem;


USE SupportSystem;


CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') DEFAULT 'user' NOT NULL
);

-- Random sample data
INSERT INTO users (username, password, role) 
VALUES 
('admin', 'admin123', 'admin'), 
('user1', 'password123', 'user'),
('user2', 'mypassword', 'user');
