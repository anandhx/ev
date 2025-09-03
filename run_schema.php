<?php
// EV Mobile Station - Run Idempotent Schema
// Ensures database exists and executes schema_idempotent.sql

$host = getenv('DB_HOST') ?: 'localhost';
$username = getenv('DB_USERNAME') ?: 'root';
$password = getenv('DB_PASSWORD') ?: '';
$dbName = getenv('DB_NAME') ?: 'ev_mobile_station';
$schemaPath = __DIR__ . DIRECTORY_SEPARATOR . 'schema_idempotent.sql';

header('Content-Type: text/html; charset=utf-8');

echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Run Schema</title>';
echo '<style>body{font-family:Arial,sans-serif;background:#f5f5f5;padding:20px;max-width:860px;margin:0 auto}';
echo '.card{background:#fff;border:1px solid #eee;border-radius:8px;padding:16px;margin:12px 0}';
echo '.ok{color:#0a7d16}.err{color:#b00020}.warn{color:#b36b00}.muted{color:#666}.btn{display:inline-block;background:#1b74e4;color:#fff;padding:8px 12px;border-radius:6px;text-decoration:none;margin-top:8px}</style>';
echo '</head><body>';
echo '<h2>EV Mobile Station - Apply Idempotent Schema</h2>';

echo '<div class="card">';
echo '<div><strong>Connection</strong></div>';
echo '<div class="muted">Host: ' . htmlspecialchars($host) . ' | User: ' . htmlspecialchars($username) . ' | DB: ' . htmlspecialchars($dbName) . '</div>';
echo '</div>';

try {
	if (!file_exists($schemaPath)) {
		throw new RuntimeException('Schema file not found: ' . $schemaPath);
	}

	$dsn = 'mysql:host=' . $host . ';charset=utf8mb4';
	$options = [
		PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
		PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
		PDO::ATTR_EMULATE_PREPARES => false,
	];
	$pdo = new PDO($dsn, $username, $password, $options);
	echo '<div class="card ok">Connected to MySQL server</div>';

	// Ensure database exists
	$pdo->exec('CREATE DATABASE IF NOT EXISTS `' . str_replace('`','``',$dbName) . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
	echo '<div class="card ok">Database ensured: ' . htmlspecialchars($dbName) . '</div>';

	$pdo->exec('USE `' . str_replace('`','``',$dbName) . '`');

	// Load and execute schema file as a whole (MySQL can handle batch with PDO->exec)
	$schemaSql = file_get_contents($schemaPath);
	if ($schemaSql === false) {
		throw new RuntimeException('Failed to read schema file');
	}

	$pdo->exec($schemaSql);
	echo '<div class="card ok">Schema executed successfully (idempotent)</div>';

	// Verify a few tables
	$tables = ['users','service_vehicles','technicians','service_requests','payments','service_history','admin_users','services','user_vehicles','support_tickets','spare_part_requests','spare_orders'];
	$missing = [];
	foreach ($tables as $t) {
		$stmt = $pdo->query("SHOW TABLES LIKE '" . str_replace("'","''", $t) . "'");
		if ($stmt->rowCount() === 0) { $missing[] = $t; }
	}
	if (empty($missing)) {
		echo '<div class="card ok">All required tables are present.</div>';
	} else {
		echo '<div class="card warn">Missing tables: ' . htmlspecialchars(implode(', ', $missing)) . '</div>';
	}

	// Lightweight migrations for columns that CREATE IF NOT EXISTS can't add
	echo '<div class="card"><strong>Migrations</strong><br/>';
	try {
		$col = $pdo->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='" . str_replace("'","''", $dbName) . "' AND TABLE_NAME='service_requests' AND COLUMN_NAME='user_vehicle_id'");
		if ($col && $col->rowCount() === 0) {
			$pdo->exec("ALTER TABLE service_requests ADD COLUMN user_vehicle_id INT NULL AFTER user_id");
			echo '<div class="ok">+ Added service_requests.user_vehicle_id</div>';
			try {
				$pdo->exec("ALTER TABLE service_requests ADD CONSTRAINT fk_sr_user_vehicle FOREIGN KEY (user_vehicle_id) REFERENCES user_vehicles(id) ON UPDATE CASCADE ON DELETE SET NULL");
				echo '<div class="ok">+ Added FK fk_sr_user_vehicle</div>';
			} catch (Throwable $ee) {
				echo '<div class="warn">! FK add skipped: ' . htmlspecialchars($ee->getMessage()) . '</div>';
			}
		} else {
			echo '<div class="muted">= user_vehicle_id already exists</div>';
		}

		// Technicians: ensure email and password columns exist
		$colEmail = $pdo->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='" . str_replace("'","''", $dbName) . "' AND TABLE_NAME='technicians' AND COLUMN_NAME='email'");
		if ($colEmail && $colEmail->rowCount() === 0) {
			$pdo->exec("ALTER TABLE technicians ADD COLUMN email VARCHAR(150) UNIQUE NULL AFTER full_name");
			echo '<div class="ok">+ Added technicians.email</div>';
		} else { echo '<div class="muted">= technicians.email exists</div>'; }

		$colPwd = $pdo->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='" . str_replace("'","''", $dbName) . "' AND TABLE_NAME='technicians' AND COLUMN_NAME='password'");
		if ($colPwd && $colPwd->rowCount() === 0) {
			$pdo->exec("ALTER TABLE technicians ADD COLUMN password VARCHAR(255) NULL AFTER assigned_vehicle_id");
			echo '<div class="ok">+ Added technicians.password</div>';
		} else { echo '<div class="muted">= technicians.password exists</div>'; }

		// Spare orders: ensure updated_at exists
		$colUpd = $pdo->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='" . str_replace("'","''", $dbName) . "' AND TABLE_NAME='spare_orders' AND COLUMN_NAME='updated_at'");
		if ($colUpd && $colUpd->rowCount() === 0) {
			$pdo->exec("ALTER TABLE spare_orders ADD COLUMN updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at");
			echo '<div class="ok">+ Added spare_orders.updated_at</div>';
		} else { echo '<div class="muted">= spare_orders.updated_at exists</div>'; }

		// Ensure spare parts tables exist (in case schema execution was partial)
		$stmt = $pdo->query("SHOW TABLES LIKE 'spare_part_requests'");
		if ($stmt->rowCount() === 0) {
			$pdo->exec("CREATE TABLE IF NOT EXISTS spare_part_requests (
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
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
			echo '<div class="ok">+ Created spare_part_requests</div>';
		} else { echo '<div class="muted">= spare_part_requests exists</div>'; }

		$stmt = $pdo->query("SHOW TABLES LIKE 'spare_orders'");
		if ($stmt->rowCount() === 0) {
			$pdo->exec("CREATE TABLE IF NOT EXISTS spare_orders (
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
				updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				INDEX idx_so_req (request_id),
				INDEX idx_so_user (user_id),
				CONSTRAINT fk_so_request FOREIGN KEY (request_id) REFERENCES spare_part_requests(id) ON DELETE CASCADE ON UPDATE CASCADE,
				CONSTRAINT fk_so_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
			echo '<div class="ok">+ Created spare_orders</div>';
		} else { echo '<div class="muted">= spare_orders exists</div>'; }
	} catch (Throwable $m) {
		echo '<div class="warn">! Migration check failed: ' . htmlspecialchars($m->getMessage()) . '</div>';
	}
	echo '</div>';

	echo '<a class="btn" href="setup_database.php">Run setup_database health check</a>';
} catch (Throwable $e) {
	echo '<div class="card err">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
	echo '<pre class="card" style="white-space:pre-wrap">' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
}

echo '</body></html>';
