-- ===================================================================
-- Project Title: Heritage Coastal Villa - Booking & Experiential System
-- Location: Mirissa / Ahangama, Southern Province, Sri Lanka
-- Database Name: heritage_villa_db
-- Description: Complete Relational Schema with 5 Normalized Tables & Local High-Res Imagery
-- ===================================================================

CREATE DATABASE IF NOT EXISTS `heritage_villa_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `heritage_villa_db`;

-- NOTE: importing this file DROPS and recreates all tables (existing data is lost).
-- Disable Foreign Key Checks for clean, error-free table recreation
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `booking_services`;
DROP TABLE IF EXISTS `bookings`;
DROP TABLE IF EXISTS `services`;
DROP TABLE IF EXISTS `rooms`;
DROP TABLE IF EXISTS `users`;

-- 1. USERS TABLE
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `full_name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `phone` VARCHAR(25) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('guest', 'admin') DEFAULT 'guest',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. ROOMS TABLE
CREATE TABLE IF NOT EXISTS `rooms` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `room_name` VARCHAR(100) NOT NULL,
    `room_type` VARCHAR(50) NOT NULL,
    `price_per_night` DECIMAL(10, 2) NOT NULL,
    `max_guests` INT NOT NULL DEFAULT 2,
    `description` TEXT,
    `image_url` VARCHAR(255) DEFAULT 'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?auto=format&fit=crop&w=800&q=80',
    `status` ENUM('available', 'maintenance') DEFAULT 'available',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 3. SERVICES TABLE (All Local Curated Experiences & Free Facilities)
CREATE TABLE IF NOT EXISTS `services` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `service_name` VARCHAR(120) NOT NULL,
    `category` ENUM('free_facility', 'in_house', 'wellness', 'marine', 'cultural', 'travel') NOT NULL,
    `price` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    `pricing_unit` VARCHAR(50) DEFAULT 'complimentary',
    `description` TEXT,
    `image_url` VARCHAR(255) DEFAULT '',
    `icon` VARCHAR(50) DEFAULT 'fa-star',
    `status` ENUM('active', 'inactive') DEFAULT 'active'
) ENGINE=InnoDB;

-- 4. BOOKINGS TABLE
CREATE TABLE IF NOT EXISTS `bookings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `room_id` INT NOT NULL,
    `check_in` DATE NOT NULL,
    `check_out` DATE NOT NULL,
    `num_guests` INT NOT NULL DEFAULT 1,
    `room_total` DECIMAL(10, 2) NOT NULL,
    `discount_amount` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    `services_total` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    `grand_total` DECIMAL(10, 2) NOT NULL,
    `booking_status` ENUM('pending', 'confirmed', 'cancelled', 'completed') DEFAULT 'pending',
    `special_requests` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_room_dates` (`room_id`, `check_in`, `check_out`),
    CONSTRAINT `fk_bookings_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_bookings_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- 5. BOOKING_SERVICES (Many-to-Many Junction Table)
CREATE TABLE IF NOT EXISTS `booking_services` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `booking_id` INT NOT NULL,
    `service_id` INT NOT NULL,
    `service_price` DECIMAL(10, 2) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_bs_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_bs_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Re-enable Foreign Key Checks
SET FOREIGN_KEY_CHECKS = 1;

-- ===================================================================
-- SEED DATA: USERS (Default Admin: admin@heritagevilla.lk / admin123)
-- ===================================================================
INSERT INTO `users` (`full_name`, `email`, `phone`, `password`, `role`) VALUES
('Villa General Manager', 'admin@heritagevilla.lk', '+94771234567', '$2y$10$v9vAu0pbqvfP8G6KODWy9O/vHuDGpuwqlgdiriP6mq59MaJTiJOnS', 'admin'),
('John Doe (Guest)', 'guest@example.com', '+94719876543', '$2y$10$v9vAu0pbqvfP8G6KODWy9O/vHuDGpuwqlgdiriP6mq59MaJTiJOnS', 'guest');

-- ===================================================================
-- SEED DATA: ROOMS (Suites & Villas)
-- ===================================================================
INSERT INTO `rooms` (`room_name`, `room_type`, `price_per_night`, `max_guests`, `description`, `image_url`) VALUES
('Oceanfront Royal Suite', 'Suite', 120.00, 2, 'Breathtaking front beach view with direct ocean breeze, private infinity swimming pool with unlimited free access, fully equipped gourmet kitchen, and artisan Ceylon teak interior.', 'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?auto=format&fit=crop&w=800&q=80'),
('Tropical Garden Sanctuary Villa', 'Villa', 95.00, 3, 'Panoramic front beach & tropical garden view, complimentary free access to the luxury swimming pool, private kitchenette with modern cookware, and expansive sun verandah.', 'https://images.unsplash.com/photo-1618773928121-c32242e63f39?auto=format&fit=crop&w=800&q=80'),
('Grand Heritage Master Pavilion', 'Master Suite', 160.00, 4, 'Unobstructed front beach ocean view, private sunset pool with 24/7 free access, full luxury chef-style kitchen, colonial high ceilings, and dedicated butler service.', 'https://images.unsplash.com/photo-1590490360182-c33d57733427?auto=format&fit=crop&w=800&q=80');

-- ===================================================================
-- SEED DATA: SERVICES (Local Curated Experiences & Free Facilities)
-- ===================================================================
INSERT INTO `services` (`service_name`, `category`, `price`, `pricing_unit`, `description`, `image_url`, `icon`) VALUES
-- Free Facilities
('Unlimited Swimming Pool Access', 'free_facility', 0.00, 'complimentary', 'Direct complimentary access to our crystal-clear private plunge pools and outdoor infinity pool with poolside loungers and fresh beach towels.', '', 'fa-water-ladder'),
('Private Kitchen & Cookware Access', 'free_facility', 0.00, 'complimentary', 'Fully equipped self-catering kitchenette with electric stovetop, microwave, refrigerator, artisan Ceylon tea maker, cookware, cutlery, and dining utensils.', '', 'fa-kitchen-set'),
('Front Beach Access & Sunset Deck', 'free_facility', 0.00, 'complimentary', 'Direct private footpath leading onto the pristine Mirissa golden beach sands with complimentary beach hammocks, sunshades, and sunset panoramas.', '', 'fa-umbrella-beach'),
('High-Speed Fiber WiFi & Concierge', 'free_facility', 0.00, 'complimentary', 'Seamless 100Mbps fiber connectivity across all indoor suites, gardens, and poolside, plus complimentary on-site secure parking and personal tour itinerary advice.', '', 'fa-wifi'),
-- 1. In-House Luxuries
('Floating Ceylon Breakfast', 'in_house', 35.00, 'per couple', 'Handcrafted traditional hoppers, tropical dragonfruits, and Ceylon tea served on a floating tray in your private plunge pool.', 'assets/images/floating-breakfast.jpeg', 'fa-utensils'),
('Private Beachfront Candlelight BBQ', 'in_house', 65.00, 'per dinner', 'Freshly caught coastal lobster and jumbo prawns grilled live with a private candlelit seaside table setup on the sand.', 'assets/images/candlelight-dinner.jpeg', 'fa-fire'),
('Sunset Beach Picnic Setup', 'in_house', 45.00, 'per setup', 'Bohemian beach setup with woven mats, plush cushions, lantern lights, chilled coconut water, and artisanal sweet and savory finger foods.', 'assets/images/beach-picnic.jpeg', 'fa-umbrella-beach'),
('Tropical Food Platter & Artisan Fruits', 'in_house', 28.00, 'per platter', 'Exquisite selection of fresh tropical passion fruit, rambutan, mangoes, artisan cheeses, and Ceylon savory bites.', 'assets/images/food-platter.jpeg', 'fa-bowl-food'),
('Starlight Open-Air Cinema', 'in_house', 25.00, 'per night', 'Cozy outdoor theater on the manicured villa lawn under coconut trees with plush bean bags, acoustic sound, and artisan popcorn.', 'assets/images/starlight-cinema.jpeg', 'fa-film'),

-- 2. Wellness, Spa & Herbal Steam Sanctuary
('Herbal Steam Bath & Aromatherapy', 'wellness', 30.00, 'per session', 'Traditional Sri Lankan medicinal herbal steam bath infused with lemongrass, cinnamon leaves, and organic botanical extracts.', 'assets/images/steam-bath.jpeg', 'fa-spa'),
('Ayurvedic Full-Body Massage', 'wellness', 50.00, 'per person', '60-minute therapeutic rejuvenation massage using warm, herb-infused sesame oils by licensed Ayurvedic therapists.', 'assets/images/full-body-massage.jpeg', 'fa-hand-sparkles'),
('Royal Tropical Wellness Package', 'wellness', 95.00, 'combo bundle', 'VIP Half-Day Sanctuary: Full-Body Ayurvedic Massage + Herbal Steam Bath + Floating Breakfast + Evening Herbal Detox Tea.', 'assets/images/royal-wellness.jpeg', 'fa-crown'),

-- 3. Marine Adventures
('Mirissa Dolphin & Whale Watching', 'marine', 45.00, 'per person', 'Early morning luxury private boat safari with certified naturalists exploring blue whales and spinner dolphins.', 'assets/images/dolphins.jpeg', 'fa-water'),
('PADI Scuba Diving & Coral Reef Tour', 'marine', 70.00, 'per person', 'Guided diving expedition exploring vibrant southern coral gardens, exotic tropical reef fish, and historic shipwrecks.', 'assets/images/scuba-diving.jpeg', 'fa-fish'),
('Turtle Hatchery & Sanctuary Visit', 'marine', 15.00, 'per person', 'Educational guided excursion to observe endangered sea turtles, newborn hatchlings, and community marine conservation.', 'assets/images/Turtles.jpeg', 'fa-shield-halved'),

-- 4. Cultural Heritage
('Traditional Village Cooking Masterclass', 'cultural', 25.00, 'per person', 'Hands-on clay-pot culinary session with our master chef using garden-fresh spices, fresh coconut milk, and seafood.', 'assets/images/village-cooking.jpeg', 'fa-bowl-rice'),
('Cinnamon Peeling & Estate Walk', 'cultural', 20.00, 'per person', 'Authentic guided walk through a heritage plantation witnessing centuries-old artisan cinnamon peeling craft.', 'assets/images/cinnamon.jpeg', 'fa-tree'),

-- 5. Travel & Chauffeur Services
('Private Airport Pickup / Drop (BIA)', 'travel', 85.00, 'per vehicle', 'Seamless highway transfer in a luxury air-conditioned sedan or private van with an English-speaking chauffeur.', 'assets/images/airport-pickup.jpeg', 'fa-car'),
('Licensed Chauffeur-Guide (Full Day)', 'travel', 55.00, 'per day', 'Dedicated luxury vehicle and knowledgeable tourist guide for tailor-made day trips to Galle Fort, Weligama & Mirissa.', 'assets/images/tour-guide.jpeg', 'fa-id-badge');
