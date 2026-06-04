-- =====================================================================
-- UniMove Res Essentials — Database Schema
-- Engine: InnoDB | Charset: utf8mb4
-- Import this file into phpMyAdmin (InfinityFree cPanel) before use.
-- =====================================================================

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS reports;
DROP TABLE IF EXISTS reviews;
DROP TABLE IF EXISTS messages;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS listing_images;
DROP TABLE IF EXISTS listings;
DROP TABLE IF EXISTS timeslots;
DROP TABLE IF EXISTS pickup_zones;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------
-- users
-- ---------------------------------------------------------------------
CREATE TABLE users (
  user_id INT AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('student', 'admin', 'moderator') DEFAULT 'student',
  is_verified TINYINT(1) DEFAULT 0,
  otp_code VARCHAR(10) DEFAULT NULL,
  otp_expires_at DATETIME DEFAULT NULL,
  profile_pic VARCHAR(255) DEFAULT NULL,
  university VARCHAR(100) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  is_suspended TINYINT(1) DEFAULT 0,
  INDEX idx_email (email),
  INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- categories
-- ---------------------------------------------------------------------
CREATE TABLE categories (
  category_id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  icon VARCHAR(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO categories (name, icon) VALUES
('Furniture', 'fa-couch'),
('Electronics', 'fa-laptop'),
('Textbooks', 'fa-book'),
('Clothing', 'fa-tshirt'),
('Bedding', 'fa-bed'),
('Appliances', 'fa-plug'),
('Storage', 'fa-box'),
('Bundle / Starter Pack', 'fa-layer-group');

-- ---------------------------------------------------------------------
-- pickup_zones
-- ---------------------------------------------------------------------
CREATE TABLE pickup_zones (
  zone_id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  location_description TEXT,
  is_active TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO pickup_zones (name, location_description, is_active) VALUES
('Main Gate Security Point', 'Designated pickup bay next to the main campus gate, monitored by campus security 24/7.', 1),
('Library Front Plaza', 'Open paved area in front of the main library, well-lit and CCTV-covered.', 1),
('Residence Quad', 'Central residence courtyard, accessible to all residence students with staff oversight.', 1);

-- ---------------------------------------------------------------------
-- timeslots
-- ---------------------------------------------------------------------
CREATE TABLE timeslots (
  slot_id INT AUTO_INCREMENT PRIMARY KEY,
  zone_id INT NOT NULL,
  slot_date DATE NOT NULL,
  slot_time TIME NOT NULL,
  is_booked TINYINT(1) DEFAULT 0,
  FOREIGN KEY (zone_id) REFERENCES pickup_zones(zone_id) ON DELETE CASCADE,
  INDEX idx_zone_date (zone_id, slot_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- listings
-- ---------------------------------------------------------------------
CREATE TABLE listings (
  listing_id INT AUTO_INCREMENT PRIMARY KEY,
  seller_id INT NOT NULL,
  title VARCHAR(200) NOT NULL,
  description TEXT NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  category_id INT NOT NULL,
  condition_type ENUM('New', 'Like New', 'Good', 'Fair', 'Poor') NOT NULL,
  pickup_zone_id INT DEFAULT NULL,
  is_bundle TINYINT(1) DEFAULT 0,
  status ENUM('pending', 'active', 'sold', 'flagged', 'removed') DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (seller_id) REFERENCES users(user_id),
  FOREIGN KEY (category_id) REFERENCES categories(category_id),
  FOREIGN KEY (pickup_zone_id) REFERENCES pickup_zones(zone_id),
  INDEX idx_status (status),
  INDEX idx_category (category_id),
  INDEX idx_seller (seller_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- listing_images
-- ---------------------------------------------------------------------
CREATE TABLE listing_images (
  image_id INT AUTO_INCREMENT PRIMARY KEY,
  listing_id INT NOT NULL,
  image_path VARCHAR(255) NOT NULL,
  is_primary TINYINT(1) DEFAULT 0,
  FOREIGN KEY (listing_id) REFERENCES listings(listing_id) ON DELETE CASCADE,
  INDEX idx_listing (listing_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- orders
-- ---------------------------------------------------------------------
CREATE TABLE orders (
  order_id INT AUTO_INCREMENT PRIMARY KEY,
  listing_id INT NOT NULL,
  buyer_id INT NOT NULL,
  seller_id INT NOT NULL,
  slot_id INT DEFAULT NULL,
  status ENUM('pending', 'confirmed', 'completed', 'cancelled', 'disputed') DEFAULT 'pending',
  buyer_otp VARCHAR(10) DEFAULT NULL,
  seller_otp VARCHAR(10) DEFAULT NULL,
  buyer_confirmed TINYINT(1) DEFAULT 0,
  seller_confirmed TINYINT(1) DEFAULT 0,
  total_price DECIMAL(10,2) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  completed_at DATETIME DEFAULT NULL,
  FOREIGN KEY (listing_id) REFERENCES listings(listing_id),
  FOREIGN KEY (buyer_id) REFERENCES users(user_id),
  FOREIGN KEY (seller_id) REFERENCES users(user_id),
  FOREIGN KEY (slot_id) REFERENCES timeslots(slot_id),
  INDEX idx_buyer (buyer_id),
  INDEX idx_seller (seller_id),
  INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- messages
-- ---------------------------------------------------------------------
CREATE TABLE messages (
  message_id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT DEFAULT NULL,
  sender_id INT NOT NULL,
  receiver_id INT NOT NULL,
  listing_id INT DEFAULT NULL,
  message_text TEXT NOT NULL,
  is_read TINYINT(1) DEFAULT 0,
  sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (sender_id) REFERENCES users(user_id),
  FOREIGN KEY (receiver_id) REFERENCES users(user_id),
  INDEX idx_thread (sender_id, receiver_id, listing_id),
  INDEX idx_receiver_unread (receiver_id, is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- reviews
-- ---------------------------------------------------------------------
CREATE TABLE reviews (
  review_id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  reviewer_id INT NOT NULL,
  reviewee_id INT NOT NULL,
  rating TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
  comment TEXT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (order_id) REFERENCES orders(order_id),
  FOREIGN KEY (reviewer_id) REFERENCES users(user_id),
  FOREIGN KEY (reviewee_id) REFERENCES users(user_id),
  INDEX idx_reviewee (reviewee_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- reports
-- ---------------------------------------------------------------------
CREATE TABLE reports (
  report_id INT AUTO_INCREMENT PRIMARY KEY,
  reporter_id INT NOT NULL,
  listing_id INT DEFAULT NULL,
  user_id INT DEFAULT NULL,
  reason TEXT NOT NULL,
  status ENUM('open', 'reviewed', 'resolved') DEFAULT 'open',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (reporter_id) REFERENCES users(user_id),
  INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- First-run admin seeding
--
-- Do NOT seed the admin row with a hardcoded password_hash here — every
-- install needs its own freshly-generated bcrypt hash. Instead, after
-- importing this schema, open `install-admin.php` ONCE in the browser
-- to create the first admin account, then delete that file.
-- ---------------------------------------------------------------------
