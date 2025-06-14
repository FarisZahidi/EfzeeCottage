-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jun 03, 2025 at 05:49 AM
-- Server version: 8.0.30
-- PHP Version: 8.1.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `cozyhomestay`
--

-- --------------------------------------------------------

--
-- Table structure for table `amenities`
--

CREATE TABLE `amenities` (
  `amenity_id` int NOT NULL,
  `name` varchar(50) NOT NULL,
  `icon` varchar(255) NOT NULL,
  `price` float NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `amenities`
--

INSERT INTO `amenities` (`amenity_id`, `name`, `icon`, `price`) VALUES
(1, 'WiFi', 'fas fa-wifi', 15),
(2, 'Air Conditioning', 'fas fa-snowflake', 30),
(3, 'Kitchen', 'fas fa-utensils', 25),
(4, 'Parking', 'fas fa-parking', 20),
(5, 'TV', 'fas fa-tv', 20),
(6, 'Washing Machine', 'fas fa-tshirt', 15);

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `booking_id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `homestay_id` int DEFAULT NULL,
  `check_in_date` date NOT NULL,
  `check_out_date` date NOT NULL,
  `total_guests` int NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `status` enum('pending','confirmed','cancelled','completed') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`booking_id`, `user_id`, `homestay_id`, `check_in_date`, `check_out_date`, `total_guests`, `total_price`, `status`) VALUES
(1, 3, 1, '2025-06-02', '2025-06-06', 8, '500.00', 'pending'),
(4, 2, 1, '2025-06-07', '2025-06-12', 9, '0.00', 'pending'),
(5, 2, 1, '2025-06-03', '2025-06-04', 9, '0.00', 'pending'),
(6, 2, 1, '2025-06-03', '2025-06-04', 9, '0.00', 'pending'),
(7, 2, 2, '2025-06-03', '2025-06-04', 3, '371.00', 'pending'),
(8, 2, 1, '2025-06-04', '2025-06-05', 8, '545.00', 'pending'),
(9, 2, 1, '2025-06-03', '2025-06-04', 8, '540.00', 'pending'),
(10, 2, 1, '2025-06-03', '2025-06-04', 6, '610.00', 'pending'),
(11, 2, 1, '2025-06-03', '2025-06-04', 5, '475.00', 'pending'),
(12, 2, 2, '2025-06-03', '2025-06-04', 5, '384.75', 'confirmed');

-- --------------------------------------------------------

--
-- Table structure for table `booking_amenities`
--

CREATE TABLE `booking_amenities` (
  `booking_id` int NOT NULL,
  `amenity_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customer_loyalty`
--

CREATE TABLE `customer_loyalty` (
  `user_id` int NOT NULL,
  `loyalty_points` int DEFAULT '0',
  `total_bookings` int DEFAULT '0',
  `total_spent` decimal(10,2) DEFAULT '0.00',
  `current_tier` int DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `customer_loyalty`
--

INSERT INTO `customer_loyalty` (`user_id`, `loyalty_points`, `total_bookings`, `total_spent`, `current_tier`) VALUES
(2, 200, 4, '2009.75', 2);

-- --------------------------------------------------------

--
-- Table structure for table `homestays`
--

CREATE TABLE `homestays` (
  `homestay_id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text,
  `address` text NOT NULL,
  `price_per_night` decimal(10,2) NOT NULL,
  `max_guests` int NOT NULL,
  `bedrooms` int NOT NULL,
  `bathrooms` int NOT NULL,
  `status` enum('available','booked','maintenance') DEFAULT 'available'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `homestays`
--

INSERT INTO `homestays` (`homestay_id`, `name`, `description`, `address`, `price_per_night`, `max_guests`, `bedrooms`, `bathrooms`, `status`) VALUES
(1, 'EFZEE COTTAGE MAIN UNIT', 'Spacious and modern homestay unit with garden view', 'Jalan Zabedah, Taman Zabedah, 83000 Batu Pahat, Johor', '500.00', 10, 4, 3, 'available'),
(2, 'EFZEE COTTAGE SECOND UNIT', 'Spacious and modern homestay unit with garden view', 'Jalan Zabedah, Taman Zabedah, 83000 Batu Pahat, Johor', '350.00', 8, 3, 2, 'available'),
(4, 'Robin Webb', 'Adipisci enim et vol', 'Qui dolor veritatis ', '628.00', 14, 32, 9, 'booked');

-- --------------------------------------------------------

--
-- Table structure for table `homestay_amenities`
--

CREATE TABLE `homestay_amenities` (
  `homestay_id` int NOT NULL,
  `amenity_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `homestay_amenities`
--

INSERT INTO `homestay_amenities` (`homestay_id`, `amenity_id`) VALUES
(1, 1),
(4, 1),
(1, 2),
(2, 2),
(4, 2),
(1, 3),
(2, 3),
(1, 4),
(1, 5),
(2, 5),
(4, 5),
(1, 6),
(2, 6);

-- --------------------------------------------------------

--
-- Table structure for table `loyalty_program`
--

CREATE TABLE `loyalty_program` (
  `program_id` int NOT NULL,
  `program_name` varchar(100) NOT NULL,
  `description` text,
  `discount_percentage` decimal(5,2) DEFAULT '0.00',
  `min_bookings` int DEFAULT '1',
  `active` tinyint(1) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `loyalty_tiers`
--

CREATE TABLE `loyalty_tiers` (
  `tier_id` int NOT NULL,
  `tier_name` varchar(50) NOT NULL,
  `min_points` int NOT NULL,
  `discount_percentage` decimal(5,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `loyalty_tiers`
--

INSERT INTO `loyalty_tiers` (`tier_id`, `tier_name`, `min_points`, `discount_percentage`) VALUES
(1, 'Bronze', 0, '0.00'),
(2, 'Silver', 100, '5.00'),
(3, 'Gold', 250, '10.00'),
(4, 'Platinum', 500, '15.00'),
(5, 'Diamond', 1000, '20.00');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `payment_id` int NOT NULL,
  `booking_id` int DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` enum('credit_card','debit_card','bank_transfer') NOT NULL,
  `status` enum('pending','completed','failed','refunded') DEFAULT 'pending',
  `payment_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`payment_id`, `booking_id`, `amount`, `payment_method`, `status`, `payment_date`) VALUES
(1, 4, '0.00', 'credit_card', 'pending', '2025-06-03'),
(2, 5, '0.00', 'credit_card', 'pending', '2025-06-03'),
(3, 6, '0.00', 'credit_card', 'pending', '2025-06-03'),
(4, 7, '371.00', 'credit_card', 'pending', '2025-06-03'),
(5, 8, '545.00', '', 'pending', '2025-06-03'),
(6, 9, '540.00', 'credit_card', 'pending', '2025-06-03'),
(7, 10, '610.00', 'credit_card', 'pending', '2025-06-03'),
(8, 11, '475.00', '', 'pending', '2025-06-03'),
(9, 12, '384.75', 'bank_transfer', 'completed', '2025-06-03');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `review_id` int NOT NULL,
  `booking_id` int DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `homestay_id` int DEFAULT NULL,
  `rating` int NOT NULL,
  `comment` text,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role` enum('guest','admin') DEFAULT 'guest',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `name`, `email`, `password`, `phone`, `role`, `created_at`) VALUES
(1, 'Encik Fadzil', 'admin@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0123456789', 'admin', '2025-06-02 15:15:24'),
(2, 'John Doe', 'john@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0123456781', 'guest', '2025-06-02 15:15:24'),
(3, 'Jane Smith', 'jane@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0123456782', 'guest', '2025-06-02 15:15:24'),
(4, 'MUHAMMAD IRFAN', 'muhammadirfanrozlan@gmail.com', '$2y$10$KBfmwrJm7WnziDmxYWPr/uhFICCOwwwmbgYmpqXTcdJd8xQW3RjTe', '0163636087', 'guest', '2025-06-03 05:34:27');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `amenities`
--
ALTER TABLE `amenities`
  ADD PRIMARY KEY (`amenity_id`);

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`booking_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `homestay_id` (`homestay_id`);

--
-- Indexes for table `booking_amenities`
--
ALTER TABLE `booking_amenities`
  ADD PRIMARY KEY (`booking_id`,`amenity_id`),
  ADD KEY `amenity_id` (`amenity_id`);

--
-- Indexes for table `customer_loyalty`
--
ALTER TABLE `customer_loyalty`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `homestays`
--
ALTER TABLE `homestays`
  ADD PRIMARY KEY (`homestay_id`);

--
-- Indexes for table `homestay_amenities`
--
ALTER TABLE `homestay_amenities`
  ADD PRIMARY KEY (`homestay_id`,`amenity_id`),
  ADD KEY `amenity_id` (`amenity_id`);

--
-- Indexes for table `loyalty_program`
--
ALTER TABLE `loyalty_program`
  ADD PRIMARY KEY (`program_id`);

--
-- Indexes for table `loyalty_tiers`
--
ALTER TABLE `loyalty_tiers`
  ADD PRIMARY KEY (`tier_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `booking_id` (`booking_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`review_id`),
  ADD KEY `booking_id` (`booking_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `homestay_id` (`homestay_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `amenities`
--
ALTER TABLE `amenities`
  MODIFY `amenity_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `booking_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `homestays`
--
ALTER TABLE `homestays`
  MODIFY `homestay_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `loyalty_program`
--
ALTER TABLE `loyalty_program`
  MODIFY `program_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `loyalty_tiers`
--
ALTER TABLE `loyalty_tiers`
  MODIFY `tier_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `review_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`homestay_id`) REFERENCES `homestays` (`homestay_id`);

--
-- Constraints for table `booking_amenities`
--
ALTER TABLE `booking_amenities`
  ADD CONSTRAINT `booking_amenities_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `booking_amenities_ibfk_2` FOREIGN KEY (`amenity_id`) REFERENCES `amenities` (`amenity_id`);

--
-- Constraints for table `customer_loyalty`
--
ALTER TABLE `customer_loyalty`
  ADD CONSTRAINT `customer_loyalty_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `homestay_amenities`
--
ALTER TABLE `homestay_amenities`
  ADD CONSTRAINT `homestay_amenities_ibfk_1` FOREIGN KEY (`homestay_id`) REFERENCES `homestays` (`homestay_id`),
  ADD CONSTRAINT `homestay_amenities_ibfk_2` FOREIGN KEY (`amenity_id`) REFERENCES `amenities` (`amenity_id`);

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`);

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`),
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `reviews_ibfk_3` FOREIGN KEY (`homestay_id`) REFERENCES `homestays` (`homestay_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
