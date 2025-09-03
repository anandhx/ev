-- EV Mobile Power & Service Station - Idempotent Schema
-- Safe to run multiple times

-- Create database if not exists
CREATE DATABASE IF NOT EXISTS ev_mobile_station CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ev_mobile_station;

-- Users table
CREATE TABLE IF NOT EXISTS users (
	id INT AUTO_INCREMENT PRIMARY KEY,
	username VARCHAR(50) UNIQUE NOT NULL,
	email VARCHAR(100) UNIQUE NOT NULL,
	password VARCHAR(255) NOT NULL,
	full_name VARCHAR(100) NOT NULL,
	phone VARCHAR(20),
	vehicle_model VARCHAR(100),
	vehicle_plate VARCHAR(20),
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- User vehicles table (supports multiple vehicles per user)
CREATE TABLE IF NOT EXISTS user_vehicles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    make VARCHAR(100) NOT NULL,
    model VARCHAR(100) NOT NULL,
    plate VARCHAR(20),
    vin VARCHAR(32),
    color VARCHAR(40),
    is_primary TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_uv_user (user_id),
    CONSTRAINT fk_uv_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Service vehicles table
CREATE TABLE IF NOT EXISTS service_vehicles (
	id INT AUTO_INCREMENT PRIMARY KEY,
	vehicle_number VARCHAR(20) UNIQUE NOT NULL,
	vehicle_type ENUM('charging', 'mechanical', 'hybrid') NOT NULL,
	capacity VARCHAR(50),
	current_location_lat DECIMAL(10, 8),
	current_location_lng DECIMAL(11, 8),
	status ENUM('available', 'busy', 'maintenance', 'offline') DEFAULT 'available',
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Technicians table
CREATE TABLE IF NOT EXISTS technicians (
	id INT AUTO_INCREMENT PRIMARY KEY,
	full_name VARCHAR(100) NOT NULL,
	email VARCHAR(150) UNIQUE,
	phone VARCHAR(20) NOT NULL,
	specialization ENUM('electrical', 'mechanical', 'both') NOT NULL,
	experience_years INT DEFAULT 0,
	status ENUM('available', 'busy', 'offline') DEFAULT 'available',
	assigned_vehicle_id INT NULL,
	password VARCHAR(255) NULL,
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	INDEX idx_tech_vehicle (assigned_vehicle_id),
	CONSTRAINT fk_tech_vehicle FOREIGN KEY (assigned_vehicle_id) REFERENCES service_vehicles(id)
		ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Services catalog table (admin-managed)
CREATE TABLE IF NOT EXISTS services (
	id INT AUTO_INCREMENT PRIMARY KEY,
	name VARCHAR(100) UNIQUE NOT NULL,
	description VARCHAR(255),
	base_price DECIMAL(10,2) DEFAULT 0.00,
	is_active TINYINT(1) DEFAULT 1,
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Service requests table
CREATE TABLE IF NOT EXISTS service_requests (
	id INT AUTO_INCREMENT PRIMARY KEY,
	user_id INT NOT NULL,
	user_vehicle_id INT NULL,
	request_type ENUM('charging', 'mechanical', 'both') NOT NULL,
	vehicle_location_lat DECIMAL(10, 8) NOT NULL,
	vehicle_location_lng DECIMAL(11, 8) NOT NULL,
	description TEXT,
	urgency_level ENUM('low', 'medium', 'high', 'emergency') DEFAULT 'medium',
	status ENUM('pending', 'assigned', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
	assigned_vehicle_id INT NULL,
	assigned_technician_id INT NULL,
	estimated_arrival_time DATETIME NULL,
	actual_arrival_time DATETIME NULL,
	completion_time DATETIME NULL,
	total_cost DECIMAL(10, 2) NULL,
	payment_status ENUM('pending', 'paid', 'failed') DEFAULT 'pending',
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	INDEX idx_sr_user (user_id),
	INDEX idx_sr_vehicle (assigned_vehicle_id),
	INDEX idx_sr_technician (assigned_technician_id),
	CONSTRAINT fk_sr_user FOREIGN KEY (user_id) REFERENCES users(id)
		ON UPDATE CASCADE ON DELETE CASCADE,
	CONSTRAINT fk_sr_user_vehicle FOREIGN KEY (user_vehicle_id) REFERENCES user_vehicles(id)
		ON UPDATE CASCADE ON DELETE SET NULL,
	CONSTRAINT fk_sr_vehicle FOREIGN KEY (assigned_vehicle_id) REFERENCES service_vehicles(id)
		ON UPDATE CASCADE ON DELETE SET NULL,
	CONSTRAINT fk_sr_technician FOREIGN KEY (assigned_technician_id) REFERENCES technicians(id)
		ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Service request history table (tracks status changes)
CREATE TABLE IF NOT EXISTS service_request_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_request_id INT NOT NULL,
    status ENUM('pending', 'in_progress', 'completed', 'cancelled') NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_srh_request (service_request_id),
    CONSTRAINT fk_srh_request FOREIGN KEY (service_request_id) REFERENCES service_requests(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Payments table
CREATE TABLE IF NOT EXISTS payments (
	id INT AUTO_INCREMENT PRIMARY KEY,
	service_request_id INT NOT NULL,
	amount DECIMAL(10, 2) NOT NULL,
	payment_method ENUM('credit_card', 'debit_card', 'digital_wallet', 'cash') NOT NULL,
	transaction_id VARCHAR(100),
	status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	INDEX idx_pay_req (service_request_id),
	CONSTRAINT fk_pay_request FOREIGN KEY (service_request_id) REFERENCES service_requests(id)
		ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Service history table
CREATE TABLE IF NOT EXISTS service_history (
	id INT AUTO_INCREMENT PRIMARY KEY,
	service_request_id INT NOT NULL,
	action VARCHAR(100) NOT NULL,
	description TEXT,
	performed_by ENUM('user', 'technician', 'admin', 'system') NOT NULL,
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	INDEX idx_hist_req (service_request_id),
	CONSTRAINT fk_hist_request FOREIGN KEY (service_request_id) REFERENCES service_requests(id)
		ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Admin users table
CREATE TABLE IF NOT EXISTS admin_users (
	id INT AUTO_INCREMENT PRIMARY KEY,
	username VARCHAR(50) UNIQUE NOT NULL,
	email VARCHAR(100) UNIQUE NOT NULL,
	password VARCHAR(255) NOT NULL,
	full_name VARCHAR(100) NOT NULL,
	role ENUM('super_admin', 'admin', 'operator') DEFAULT 'operator',
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Support tickets table
CREATE TABLE IF NOT EXISTS support_tickets (
	id INT AUTO_INCREMENT PRIMARY KEY,
	user_id INT NOT NULL,
	name VARCHAR(100) NOT NULL,
	email VARCHAR(150) NOT NULL,
	subject VARCHAR(50) NOT NULL,
	message TEXT NOT NULL,
	status ENUM('open','in_progress','closed') DEFAULT 'open',
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	INDEX idx_ticket_user (user_id),
	CONSTRAINT fk_ticket_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Spare parts request table (user-initiated, admin replies with quote)
CREATE TABLE IF NOT EXISTS spare_part_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    vehicle_make VARCHAR(100),
    vehicle_model VARCHAR(100),
    part_name VARCHAR(150) NOT NULL,
    part_description VARCHAR(255),
    quantity INT DEFAULT 1,
    status ENUM('requested','quoted','declined','cancelled','ordered','shipped','delivered') DEFAULT 'requested',
    admin_part_code VARCHAR(100) NULL,
    admin_available TINYINT(1) NULL,
    admin_price DECIMAL(10,2) NULL,
    admin_note VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_spr_user (user_id),
    CONSTRAINT fk_spr_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Spare orders table (user places order based on quote)
CREATE TABLE IF NOT EXISTS spare_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,
    user_id INT NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    shipping_name VARCHAR(120) NOT NULL,
    shipping_phone VARCHAR(30) NOT NULL,
    shipping_address TEXT NOT NULL,
    shipping_city VARCHAR(100) NOT NULL,
    shipping_state VARCHAR(100) NOT NULL,
    shipping_postal VARCHAR(20) NOT NULL,
    status ENUM('pending','paid','processing','shipped','delivered','cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_so_req (request_id),
    INDEX idx_so_user (user_id),
    CONSTRAINT fk_so_request FOREIGN KEY (request_id) REFERENCES spare_part_requests(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_so_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed data (idempotent)
INSERT IGNORE INTO admin_users (id, username, email, password, full_name, role)
VALUES
	(1,'admin','admin@evstation.com','$2y$10$U6eF7cJueOP88oox6IERxOc2K7O8CBixFP6RlVG/8ATUHj.dkKrwq','System Administrator','super_admin');

-- Seed services (idempotent)
INSERT IGNORE INTO services (id, name, description, base_price, is_active) VALUES
	(1,'Mobile EV Charging','On-site fast charging service',75.00,1),
	(2,'Mechanical Support','On-site mechanical assistance',80.00,1),
	(3,'Charging + Mechanical','Combined service offering',120.00,1);

INSERT IGNORE INTO service_vehicles (id, vehicle_number, vehicle_type, capacity, current_location_lat, current_location_lng, status) VALUES
	(1,'EV-CHG-001','charging','50kW Fast Charger',40.7128,-74.0060,'available'),
	(2,'EV-MECH-001','mechanical','Full Tool Kit',40.7589,-73.9851,'available'),
	(3,'EV-HYB-001','hybrid','30kW Charger + Tools',40.7505,-73.9934,'available');

INSERT IGNORE INTO technicians (id, full_name, phone, specialization, experience_years, assigned_vehicle_id) VALUES
	(1,'John Smith','+1-555-0101','electrical',5,1),
	(2,'Mike Johnson','+1-555-0102','mechanical',7,2),
	(3,'Sarah Wilson','+1-555-0103','both',4,3);

INSERT IGNORE INTO users (id, username, email, password, full_name, phone, vehicle_model, vehicle_plate) VALUES
	(1,'john_doe','john@example.com','$2y$10$U6eF7cJueOP88oox6IERxOc2K7O8CBixFP6RlVG/8ATUHj.dkKrwq','John Doe','+1-555-0201','Tesla Model 3','ABC-123'),
	(2,'jane_smith','jane@example.com','$2y$10$U6eF7cJueOP88oox6IERxOc2K7O8CBixFP6RlVG/8ATUHj.dkKrwq','Jane Smith','+1-555-0202','Nissan Leaf','XYZ-789');
