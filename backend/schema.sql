-- MySQL schema for Property Rental Web Application

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('user','admin') NOT NULL DEFAULT 'user'
);

CREATE TABLE IF NOT EXISTS properties (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(200) NOT NULL,
  description TEXT,
  price DECIMAL(10,2) NOT NULL,
  location VARCHAR(255) NOT NULL,
  type ENUM('apartment','house','studio') NOT NULL DEFAULT 'apartment',
  owner_id INT NULL,
  status ENUM('available','rented') NOT NULL DEFAULT 'available',
  image_url VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Add FKs if table exists and columns are present
ALTER TABLE properties
  ADD CONSTRAINT fk_properties_owner
  FOREIGN KEY (owner_id) REFERENCES users(id)
  ON DELETE SET NULL;

CREATE TABLE IF NOT EXISTS favorites (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  property_id INT NOT NULL,
  UNIQUE KEY unique_fav (user_id, property_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS inquiries (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  property_id INT NOT NULL,
  message TEXT NOT NULL,
  phone VARCHAR(32) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
);

-- Seed an admin user (email: admin@example.com, password: admin123) — change after first login
INSERT INTO users (name, email, password, role)
VALUES ('Admin', 'admin@example.com', '$2y$10$9FcvlQ2Lw8MifkQF8g0L0e1s5kTzq1e0qgYb0Q6Qb1Q3tmZLLkQyS', 'admin')
ON DUPLICATE KEY UPDATE email = email;
