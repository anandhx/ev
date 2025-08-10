-- EV Mobile Power & Service Station Database Schema
-- Created for the smart support system for stranded EVs

-- Create database
CREATE DATABASE IF NOT EXISTS ev_mobile_station;
USE ev_mobile_station;

-- Users table
CREATE TABLE users (
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
);

-- Service vehicles table
CREATE TABLE service_vehicles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_number VARCHAR(20) UNIQUE NOT NULL,
    vehicle_type ENUM('charging', 'mechanical', 'hybrid') NOT NULL,
    capacity VARCHAR(50),
    current_location_lat DECIMAL(10, 8),
    current_location_lng DECIMAL(11, 8),
    status ENUM('available', 'busy', 'maintenance', 'offline') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Technicians table
CREATE TABLE technicians (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    specialization ENUM('electrical', 'mechanical', 'both') NOT NULL,
    experience_years INT DEFAULT 0,
    status ENUM('available', 'busy', 'offline') DEFAULT 'available',
    assigned_vehicle_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (assigned_vehicle_id) REFERENCES service_vehicles(id)
);

-- Service requests table
CREATE TABLE service_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    request_type ENUM('charging', 'mechanical', 'both') NOT NULL,
    vehicle_location_lat DECIMAL(10, 8) NOT NULL,
    vehicle_location_lng DECIMAL(11, 8) NOT NULL,
    description TEXT,
    urgency_level ENUM('low', 'medium', 'high', 'emergency') DEFAULT 'medium',
    status ENUM('pending', 'assigned', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
    assigned_vehicle_id INT,
    assigned_technician_id INT,
    estimated_arrival_time DATETIME,
    actual_arrival_time DATETIME,
    completion_time DATETIME,
    total_cost DECIMAL(10, 2),
    payment_status ENUM('pending', 'paid', 'failed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (assigned_vehicle_id) REFERENCES service_vehicles(id),
    FOREIGN KEY (assigned_technician_id) REFERENCES technicians(id)
);

-- Payments table
CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_request_id INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    payment_method ENUM('credit_card', 'debit_card', 'digital_wallet', 'cash') NOT NULL,
    transaction_id VARCHAR(100),
    status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (service_request_id) REFERENCES service_requests(id)
);

-- Service history table
CREATE TABLE service_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_request_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    description TEXT,
    performed_by ENUM('user', 'technician', 'admin', 'system') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (service_request_id) REFERENCES service_requests(id)
);

-- Admin users table
CREATE TABLE admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('super_admin', 'admin', 'operator') DEFAULT 'operator',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert sample data

-- Sample admin user
INSERT INTO admin_users (username, email, password, full_name, role) VALUES
('admin', 'admin@evstation.com', '$2y$10$U6eF7cJueOP88oox6IERxOc2K7O8CBixFP6RlVG/8ATUHj.dkKrwq', 'System Administrator', 'super_admin');

-- Sample service vehicles
INSERT INTO service_vehicles (vehicle_number, vehicle_type, capacity, current_location_lat, current_location_lng, status) VALUES
('EV-CHG-001', 'charging', '50kW Fast Charger', 40.7128, -74.0060, 'available'),
('EV-MECH-001', 'mechanical', 'Full Tool Kit', 40.7589, -73.9851, 'available'),
('EV-HYB-001', 'hybrid', '30kW Charger + Tools', 40.7505, -73.9934, 'available');

-- Sample technicians
INSERT INTO technicians (full_name, phone, specialization, experience_years, assigned_vehicle_id) VALUES
('John Smith', '+1-555-0101', 'electrical', 5, 1),
('Mike Johnson', '+1-555-0102', 'mechanical', 7, 2),
('Sarah Wilson', '+1-555-0103', 'both', 4, 3);

-- Sample users
INSERT INTO users (username, email, password, full_name, phone, vehicle_model, vehicle_plate) VALUES
('john_doe', 'john@example.com', '$2y$10$U6eF7cJueOP88oox6IERxOc2K7O8CBixFP6RlVG/8ATUHj.dkKrwq', 'John Doe', '+1-555-0201', 'Tesla Model 3', 'ABC-123'),
('jane_smith', 'jane@example.com', '$2y$10$U6eF7cJueOP88oox6IERxOc2K7O8CBixFP6RlVG/8ATUHj.dkKrwq', 'Jane Smith', '+1-555-0202', 'Nissan Leaf', 'XYZ-789'); 