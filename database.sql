CREATE DATABASE IF NOT EXISTS `hamro_dera` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `hamro_dera`;
-- Admin table
CREATE TABLE IF NOT EXISTS `admin` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `sec_q1` VARCHAR(255) DEFAULT NULL,
  `sec_a1` VARCHAR(255) DEFAULT NULL,
  `sec_q2` VARCHAR(255) DEFAULT NULL,
  `sec_a2` VARCHAR(255) DEFAULT NULL,
  `sec_q3` VARCHAR(255) DEFAULT NULL,
  `sec_a3` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
-- Owners table
CREATE TABLE IF NOT EXISTS `owners` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `phone` VARCHAR(20) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `status` ENUM('pending','approved','rejected') DEFAULT 'pending',
  `sec_q1` VARCHAR(255) DEFAULT NULL,
  `sec_a1` VARCHAR(255) DEFAULT NULL,
  `sec_q2` VARCHAR(255) DEFAULT NULL,
  `sec_a2` VARCHAR(255) DEFAULT NULL,
  `sec_q3` VARCHAR(255) DEFAULT NULL,
  `sec_a3` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
-- Listings table
CREATE TABLE IF NOT EXISTS `listings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `owner_id` INT NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `location` VARCHAR(255) DEFAULT NULL,
  `price` DECIMAL(12,2) DEFAULT NULL,
  `type` ENUM('apartment','house','room','commercial','land') DEFAULT 'apartment',
  `status` ENUM('pending','approved','rejected') DEFAULT 'pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`owner_id`) REFERENCES `owners`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;
-- Listing images
CREATE TABLE IF NOT EXISTS `listing_images` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `listing_id` INT NOT NULL,
  `image_path` VARCHAR(500) NOT NULL,
  `is_primary` TINYINT(1) DEFAULT 0,
  FOREIGN KEY (`listing_id`) REFERENCES `listings`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Property Reviews / Comments
CREATE TABLE IF NOT EXISTS `reviews` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `listing_id` INT NOT NULL,
  `user_name` VARCHAR(100) NOT NULL,
  `rating` INT NOT NULL CHECK (`rating` >= 1 AND `rating` <= 5),
  `comment` TEXT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`listing_id`) REFERENCES `listings`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;
