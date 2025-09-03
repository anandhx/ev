-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 03, 2025 at 10:38 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ev_mobile_station`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_users`
--

CREATE TABLE `admin_users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` enum('super_admin','admin','operator') DEFAULT 'operator',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_users`
--

INSERT INTO `admin_users` (`id`, `username`, `email`, `password`, `full_name`, `role`, `created_at`) VALUES
(1, 'admin', 'admin@evstation.com', '$2y$10$WvN2ODCbi3mokImS5x0T/.ByOtFdc7kfCvFzSbuuT5ZnuVDrF35wO', 'System Administrator', 'super_admin', '2025-09-02 12:31:29');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `service_request_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` enum('credit_card','debit_card','digital_wallet','cash') NOT NULL,
  `transaction_id` varchar(100) DEFAULT NULL,
  `status` enum('pending','completed','failed','refunded') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `service_request_id`, `amount`, `payment_method`, `transaction_id`, `status`, `created_at`) VALUES
(1, 2, 80.00, 'debit_card', 'TXN202509021538141796', 'completed', '2025-09-02 13:38:14'),
(2, 3, 120.00, 'cash', 'TXN202509021637395718', 'completed', '2025-09-02 14:37:39'),
(3, 4, 80.00, 'debit_card', 'TXN202509030844218637', 'completed', '2025-09-03 06:44:21');

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `base_price` decimal(10,2) DEFAULT 0.00,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`id`, `name`, `description`, `base_price`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Mobile EV Charging', 'On-site fast charging service', 75.00, 1, '2025-09-02 12:59:26', '2025-09-02 12:59:26'),
(2, 'Mechanical Supports', 'On-site mechanical assistance', 80.00, 1, '2025-09-02 12:59:26', '2025-09-02 13:00:06'),
(3, 'Charging + Mechanical', 'Combined service offering', 120.00, 1, '2025-09-02 12:59:26', '2025-09-02 12:59:26');

-- --------------------------------------------------------

--
-- Table structure for table `service_history`
--

CREATE TABLE `service_history` (
  `id` int(11) NOT NULL,
  `service_request_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `performed_by` enum('user','technician','admin','system') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `service_history`
--

INSERT INTO `service_history` (`id`, `service_request_id`, `action`, `description`, `performed_by`, `created_at`) VALUES
(1, 1, 'request_created', 'Service request created', 'user', '2025-09-02 13:23:03'),
(2, 2, 'request_created', 'Service request created', 'user', '2025-09-02 13:38:14'),
(3, 3, 'request_created', 'Service request created', 'user', '2025-09-02 14:37:39'),
(4, 3, 'request_assigned', 'Assigned to vehicle 1 and technician 1', 'admin', '2025-09-02 16:18:08'),
(5, 2, 'request_assigned', 'Assigned to vehicle 1 and technician 5', 'admin', '2025-09-03 06:11:34'),
(6, 1, 'request_assigned', 'Assigned to vehicle 1 and technician 2', 'admin', '2025-09-03 06:19:10'),
(7, 4, 'request_created', 'Service request created', 'user', '2025-09-03 06:44:21'),
(8, 4, 'request_assigned', 'Assigned to vehicle 2 and technician 9', 'admin', '2025-09-03 06:48:44');

-- --------------------------------------------------------

--
-- Table structure for table `service_requests`
--

CREATE TABLE `service_requests` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `user_vehicle_id` int(11) DEFAULT NULL,
  `request_type` enum('charging','mechanical','both') NOT NULL,
  `vehicle_location_lat` decimal(10,8) NOT NULL,
  `vehicle_location_lng` decimal(11,8) NOT NULL,
  `description` text DEFAULT NULL,
  `urgency_level` enum('low','medium','high','emergency') DEFAULT 'medium',
  `status` enum('pending','assigned','in_progress','completed','cancelled') DEFAULT 'pending',
  `assigned_vehicle_id` int(11) DEFAULT NULL,
  `assigned_technician_id` int(11) DEFAULT NULL,
  `estimated_arrival_time` datetime DEFAULT NULL,
  `actual_arrival_time` datetime DEFAULT NULL,
  `completion_time` datetime DEFAULT NULL,
  `total_cost` decimal(10,2) DEFAULT NULL,
  `payment_status` enum('pending','paid','failed') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `service_requests`
--

INSERT INTO `service_requests` (`id`, `user_id`, `user_vehicle_id`, `request_type`, `vehicle_location_lat`, `vehicle_location_lng`, `description`, `urgency_level`, `status`, `assigned_vehicle_id`, `assigned_technician_id`, `estimated_arrival_time`, `actual_arrival_time`, `completion_time`, `total_cost`, `payment_status`, `created_at`, `updated_at`) VALUES
(1, 4, NULL, 'both', 9.58482900, 76.47353400, 'ascx', 'low', 'completed', 1, 2, '2025-09-03 12:19:10', NULL, NULL, NULL, 'paid', '2025-09-02 13:23:03', '2025-09-03 07:56:59'),
(2, 4, NULL, 'mechanical', 9.58855300, 76.43456700, 'sdsd', 'medium', 'completed', 1, 5, '2025-09-03 12:11:34', NULL, NULL, NULL, 'paid', '2025-09-02 13:38:14', '2025-09-03 06:18:54'),
(3, 5, 2, 'both', 9.59160000, 76.52220000, 'DFH', 'medium', 'completed', 1, 1, '2025-09-02 22:18:09', NULL, NULL, NULL, 'paid', '2025-09-02 14:37:39', '2025-09-03 06:16:09'),
(4, 4, 4, 'mechanical', 9.59160000, 76.52220000, 'gn', 'medium', 'completed', 2, 9, '2025-09-03 12:48:44', NULL, NULL, NULL, 'paid', '2025-09-03 06:44:21', '2025-09-03 07:55:52');

-- --------------------------------------------------------

--
-- Table structure for table `service_request_history`
--

CREATE TABLE `service_request_history` (
  `id` int(11) NOT NULL,
  `service_request_id` int(11) NOT NULL,
  `status` enum('pending','in_progress','completed','cancelled') NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `service_request_history`
--

INSERT INTO `service_request_history` (`id`, `service_request_id`, `status`, `notes`, `created_at`) VALUES
(1, 2, 'completed', 'Status updated to Completed by admin', '2025-09-03 06:18:55'),
(2, 4, 'in_progress', 'Technician updated status', '2025-09-03 07:46:41'),
(3, 4, 'completed', 'Technician updated status', '2025-09-03 07:55:52'),
(4, 1, 'in_progress', 'Status updated to In_progress by admin', '2025-09-03 07:56:55'),
(5, 1, 'completed', 'Status updated to Completed by admin', '2025-09-03 07:56:59');

-- --------------------------------------------------------

--
-- Table structure for table `service_vehicles`
--

CREATE TABLE `service_vehicles` (
  `id` int(11) NOT NULL,
  `vehicle_number` varchar(20) NOT NULL,
  `vehicle_type` enum('charging','mechanical','hybrid') NOT NULL,
  `capacity` varchar(50) DEFAULT NULL,
  `current_location_lat` decimal(10,8) DEFAULT NULL,
  `current_location_lng` decimal(11,8) DEFAULT NULL,
  `status` enum('available','busy','maintenance','offline') DEFAULT 'available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `service_vehicles`
--

INSERT INTO `service_vehicles` (`id`, `vehicle_number`, `vehicle_type`, `capacity`, `current_location_lat`, `current_location_lng`, `status`, `created_at`) VALUES
(1, 'EV-CHG-001', 'charging', '50kW Fast Charger', 40.71280000, -74.00600000, 'available', '2025-09-02 12:31:29'),
(2, 'EV-MECH-00999', '', 'Full Tool Kit', NULL, NULL, 'available', '2025-09-02 12:31:29'),
(3, 'EV-HYB-001', 'hybrid', '30kW Charger + Tools', 40.75050000, -73.99340000, 'busy', '2025-09-02 12:31:29');

-- --------------------------------------------------------

--
-- Table structure for table `spare_orders`
--

CREATE TABLE `spare_orders` (
  `id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `shipping_name` varchar(120) NOT NULL,
  `shipping_phone` varchar(30) NOT NULL,
  `shipping_address` text NOT NULL,
  `shipping_city` varchar(100) NOT NULL,
  `shipping_state` varchar(100) NOT NULL,
  `shipping_postal` varchar(20) NOT NULL,
  `status` enum('pending','paid','processing','shipped','delivered','cancelled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `spare_orders`
--

INSERT INTO `spare_orders` (`id`, `request_id`, `user_id`, `total_amount`, `shipping_name`, `shipping_phone`, `shipping_address`, `shipping_city`, `shipping_state`, `shipping_postal`, `status`, `created_at`, `updated_at`) VALUES
(1, 2, 4, 0.01, 'sd', 'sdg', 'sdg', 'asdg', 'sdg', 'asdg', 'delivered', '2025-09-03 08:21:22', '2025-09-03 08:29:50'),
(2, 2, 4, 0.01, 'dassdaf', 'sdaf', 'sdaf', 'sdaf', 'sdaf', 'sdfa', 'pending', '2025-09-03 08:30:43', '2025-09-03 08:30:43'),
(3, 2, 4, 0.01, 'dassdaf', 'sdaf', 'sdaf', 'sdaf', 'sdaf', 'sdfa', 'pending', '2025-09-03 08:34:37', '2025-09-03 08:34:37');

-- --------------------------------------------------------

--
-- Table structure for table `spare_part_requests`
--

CREATE TABLE `spare_part_requests` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `vehicle_make` varchar(100) DEFAULT NULL,
  `vehicle_model` varchar(100) DEFAULT NULL,
  `part_name` varchar(150) NOT NULL,
  `part_description` varchar(255) DEFAULT NULL,
  `quantity` int(11) DEFAULT 1,
  `status` enum('requested','quoted','declined','cancelled','ordered','shipped','delivered') DEFAULT 'requested',
  `admin_part_code` varchar(100) DEFAULT NULL,
  `admin_available` tinyint(1) DEFAULT NULL,
  `admin_price` decimal(10,2) DEFAULT NULL,
  `admin_note` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `spare_part_requests`
--

INSERT INTO `spare_part_requests` (`id`, `user_id`, `vehicle_make`, `vehicle_model`, `part_name`, `part_description`, `quantity`, `status`, `admin_part_code`, `admin_available`, `admin_price`, `admin_note`, `created_at`, `updated_at`) VALUES
(1, 5, 'sd', 'rg', 'werg', 'trfh', 1, 'declined', NULL, NULL, NULL, NULL, '2025-09-02 14:55:04', '2025-09-03 08:35:37'),
(2, 4, 'sd', 'swift', 'werg', 'sdf', 1, 'quoted', 'wer434r', 1, 0.01, 'yes avaible', '2025-09-03 06:09:14', '2025-09-03 08:21:33'),
(3, 4, 'gh', 'fgj', 'fgj', 'fgj', 1, 'quoted', 'dvbsdfxgb', 1, 3245.00, 'dfh', '2025-09-03 08:35:04', '2025-09-03 08:35:47');

-- --------------------------------------------------------

--
-- Table structure for table `support_tickets`
--

CREATE TABLE `support_tickets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `subject` varchar(50) NOT NULL,
  `message` text NOT NULL,
  `status` enum('open','in_progress','closed') DEFAULT 'open',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `support_tickets`
--

INSERT INTO `support_tickets` (`id`, `user_id`, `name`, `email`, `subject`, `message`, `status`, `created_at`) VALUES
(1, 4, 'anandhu', 'anandhuashok6959@gmail.com', 'technical', 'ftjrf', 'open', '2025-09-02 13:53:50');

-- --------------------------------------------------------

--
-- Table structure for table `technicians`
--

CREATE TABLE `technicians` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `phone` varchar(20) NOT NULL,
  `specialization` enum('electrical','mechanical','both') NOT NULL,
  `experience_years` int(11) DEFAULT 0,
  `status` enum('available','busy','offline') DEFAULT 'available',
  `assigned_vehicle_id` int(11) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `technicians`
--

INSERT INTO `technicians` (`id`, `full_name`, `email`, `phone`, `specialization`, `experience_years`, `status`, `assigned_vehicle_id`, `password`, `created_at`) VALUES
(1, 'John Smith', NULL, '+1-555-0101', 'electrical', 5, 'available', 1, NULL, '2025-09-02 12:31:29'),
(2, 'Mike Johnson', NULL, '+1-555-0102', 'mechanical', 7, 'available', 2, NULL, '2025-09-02 15:41:32'),
(3, 'Sarah Wilsons', NULL, '+1-555-0103', 'both', 4, 'available', 3, NULL, '2025-09-02 12:31:29'),
(4, 'Port2', NULL, '9544106212', 'electrical', 2, 'available', NULL, NULL, '2025-09-02 15:36:12'),
(5, 'bob2', 'anandhuask6959@gmail.com', '9544106241', 'mechanical', 1, 'available', NULL, '$2y$10$OuC0BSq9vNTrn.hw5wwIW.XRRq2bttdMZuX18VKTZq9sZC3KQPqRm', '2025-09-02 15:49:30'),
(7, 'bob4', '`1@gmail.com', '9544106248', 'electrical', 1, 'available', NULL, '$2y$10$.amTS8OnjonxDEs9PKqyXuJHyOlXW34Xtr8At6NIQ0azLs9Ib3VKO', '2025-09-02 15:55:48'),
(8, 'bob5', 'anandchuashok6959@gmail.com', '9544104246', 'electrical', 1, 'busy', 3, '$2y$10$aQOBTE0EYF6uA2ec40lWx.bL1HGcVTBhk8BC0LYenZxm44zVmwyKi', '2025-09-02 15:58:13'),
(9, 'anandhutech', 'anandhuashok6959@gmail.com', '95441062410', 'both', 5, 'offline', NULL, '$2y$10$jS80TpspiWzHPZBR4h46.Off1P1NXeiYsy3Ksr6n19JSVS3V6/sse', '2025-09-03 06:23:38');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `vehicle_model` varchar(100) DEFAULT NULL,
  `vehicle_plate` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `full_name`, `phone`, `vehicle_model`, `vehicle_plate`, `created_at`, `updated_at`) VALUES
(1, 'john_doe', 'john@example.com', '$2y$10$U6eF7cJueOP88oox6IERxOc2K7O8CBixFP6RlVG/8ATUHj.dkKrwq', 'John Doe', '+1-555-0201', 'Tesla Model 3', 'ABC-123', '2025-09-02 12:31:29', '2025-09-02 12:31:29'),
(2, 'jane_smith', 'jane@example.com', '$2y$10$U6eF7cJueOP88oox6IERxOc2K7O8CBixFP6RlVG/8ATUHj.dkKrwq', 'Jane Smith', '+1-555-0202', 'Nissan Leaf', 'XYZ-789', '2025-09-02 12:31:29', '2025-09-02 12:31:29'),
(3, 'anandhu', 'anandhuashok6959@gmail.com', '$2y$10$WlW87fg5UWt0pgk1mRYhb.vbJJ63.lDEw2JXvOI/sK3opkpWi.TXy', 'anandhu ask', '954-410-6241', 'swift', 'kl 05 ag 3743', '2025-09-02 12:39:51', '2025-09-02 12:39:51'),
(4, 'anandhuo', 'anandhuashok6959o@gmail.com', '$2y$10$QVpe82V8UzZ2kVR.Rl5xNebTgth8KewCg/39wdld1R5klFT8cJZH6', 'anandhu o', '9544106241', 'swift', 'kl 05 ag 0000', '2025-09-02 12:49:05', '2025-09-02 12:49:05'),
(5, 'bobduk', 'anandhsdsduashok6959o@gmail.com', '$2y$10$WvN2ODCbi3mokImS5x0T/.ByOtFdc7kfCvFzSbuuT5ZnuVDrF35wO', 'bob2', '9544106242', 'swift', '', '2025-09-02 14:34:46', '2025-09-02 15:02:27');

-- --------------------------------------------------------

--
-- Table structure for table `user_vehicles`
--

CREATE TABLE `user_vehicles` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `make` varchar(100) NOT NULL,
  `model` varchar(100) NOT NULL,
  `plate` varchar(20) DEFAULT NULL,
  `vin` varchar(32) DEFAULT NULL,
  `color` varchar(40) DEFAULT NULL,
  `is_primary` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_vehicles`
--

INSERT INTO `user_vehicles` (`id`, `user_id`, `make`, `model`, `plate`, `vin`, `color`, `is_primary`, `created_at`) VALUES
(1, 5, 'swift', 'swift', 'kl 05 ag 36664', '', '', 1, '2025-09-02 14:34:46'),
(2, 5, 'swift', 'swift', 'kl 05 a 43748', '3', '', 0, '2025-09-02 14:37:23'),
(3, 5, 'TESla', '3', 'kl 05 ag 3333', '32', 'red', 0, '2025-09-02 14:45:33'),
(4, 4, 'maruthi', '3', 'kl 05 aj 4747', '45', 'red', 1, '2025-09-03 06:43:53');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_users`
--
ALTER TABLE `admin_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pay_req` (`service_request_id`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `service_history`
--
ALTER TABLE `service_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_hist_req` (`service_request_id`);

--
-- Indexes for table `service_requests`
--
ALTER TABLE `service_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sr_user` (`user_id`),
  ADD KEY `idx_sr_vehicle` (`assigned_vehicle_id`),
  ADD KEY `idx_sr_technician` (`assigned_technician_id`),
  ADD KEY `fk_sr_user_vehicle` (`user_vehicle_id`);

--
-- Indexes for table `service_request_history`
--
ALTER TABLE `service_request_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_srh_request` (`service_request_id`);

--
-- Indexes for table `service_vehicles`
--
ALTER TABLE `service_vehicles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `vehicle_number` (`vehicle_number`);

--
-- Indexes for table `spare_orders`
--
ALTER TABLE `spare_orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_so_req` (`request_id`),
  ADD KEY `idx_so_user` (`user_id`);

--
-- Indexes for table `spare_part_requests`
--
ALTER TABLE `spare_part_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_spr_user` (`user_id`);

--
-- Indexes for table `support_tickets`
--
ALTER TABLE `support_tickets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ticket_user` (`user_id`);

--
-- Indexes for table `technicians`
--
ALTER TABLE `technicians`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_tech_vehicle` (`assigned_vehicle_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_vehicles`
--
ALTER TABLE `user_vehicles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_uv_user` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `service_history`
--
ALTER TABLE `service_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `service_requests`
--
ALTER TABLE `service_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `service_request_history`
--
ALTER TABLE `service_request_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `service_vehicles`
--
ALTER TABLE `service_vehicles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `spare_orders`
--
ALTER TABLE `spare_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `spare_part_requests`
--
ALTER TABLE `spare_part_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `support_tickets`
--
ALTER TABLE `support_tickets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `technicians`
--
ALTER TABLE `technicians`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `user_vehicles`
--
ALTER TABLE `user_vehicles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `fk_pay_request` FOREIGN KEY (`service_request_id`) REFERENCES `service_requests` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `service_history`
--
ALTER TABLE `service_history`
  ADD CONSTRAINT `fk_hist_request` FOREIGN KEY (`service_request_id`) REFERENCES `service_requests` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `service_requests`
--
ALTER TABLE `service_requests`
  ADD CONSTRAINT `fk_sr_technician` FOREIGN KEY (`assigned_technician_id`) REFERENCES `technicians` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_sr_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_sr_user_vehicle` FOREIGN KEY (`user_vehicle_id`) REFERENCES `user_vehicles` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_sr_vehicle` FOREIGN KEY (`assigned_vehicle_id`) REFERENCES `service_vehicles` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `service_request_history`
--
ALTER TABLE `service_request_history`
  ADD CONSTRAINT `fk_srh_request` FOREIGN KEY (`service_request_id`) REFERENCES `service_requests` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `spare_orders`
--
ALTER TABLE `spare_orders`
  ADD CONSTRAINT `fk_so_request` FOREIGN KEY (`request_id`) REFERENCES `spare_part_requests` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_so_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `spare_part_requests`
--
ALTER TABLE `spare_part_requests`
  ADD CONSTRAINT `fk_spr_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `support_tickets`
--
ALTER TABLE `support_tickets`
  ADD CONSTRAINT `fk_ticket_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `technicians`
--
ALTER TABLE `technicians`
  ADD CONSTRAINT `fk_tech_vehicle` FOREIGN KEY (`assigned_vehicle_id`) REFERENCES `service_vehicles` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `user_vehicles`
--
ALTER TABLE `user_vehicles`
  ADD CONSTRAINT `fk_uv_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
