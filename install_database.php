<?php
// EV Mobile Station - Database Installer
// Creates the MySQL database and imports schema from database.sql

// Basic settings (match your local XAMPP defaults)
$host = getenv('DB_HOST') ?: 'localhost';
$username = getenv('DB_USERNAME') ?: 'root';
$password = getenv('DB_PASSWORD') ?: '';
$dbName = getenv('DB_NAME') ?: 'ev_mobile_station';

$sqlFile = __DIR__ . DIRECTORY_SEPARATOR . 'database.sql';

function out($msg, $type = 'info') {
	echo '<p style="padding:8px;border-left:4px solid #ddd;background:#fff;border-radius:4px;margin:8px 0">'
		. '<strong>' . htmlspecialchars(strtoupper($type)) . ':</strong> '
		. htmlspecialchars($msg) . '</p>';
}

function escHtml($str) {
	return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8" />
	<title>EV Mobile Station - Install Database</title>
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<style>
		body{font-family:Arial,Helvetica,sans-serif;background:#f5f5f5;margin:0;padding:24px;max-width:880px}
		h1{margin:0 0 8px;color:#222}
		.small{color:#666;margin:0 0 16px}
		.box{background:#fff;border-radius:8px;padding:16px;margin:16px 0;border:1px solid #eee}
		.btn{display:inline-block;background:#1b74e4;color:#fff;text-decoration:none;padding:10px 14px;border-radius:6px}
		.btn:active{transform:translateY(1px)}
		.code{font-family:ui-monospace,Consolas,monospace;background:#f8fafc;border:1px solid #eef2f7;padding:10px;border-radius:6px;overflow:auto}
	</style>
</head>
<body>
	<h1>EV Mobile Station - Install Database</h1>
	<p class="small">This tool will create the MySQL database and import schema from <code>database.sql</code>.</p>
	<div class="box">
		<div><strong>Connection</strong></div>
		<div class="code">Host: <?=escHtml($host)?> | Username: <?=escHtml($username)?> | Database: <?=escHtml($dbName)?></div>
	</div>
	<div class="box">
<?php
try {
	if (!file_exists($sqlFile)) {
		throw new RuntimeException('database.sql not found at ' . $sqlFile);
	}

	// 1) Connect to MySQL server (no DB yet)
	$dsn = 'mysql:host=' . $host . ';charset=utf8mb4';
	$options = [
		PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
		PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
		PDO::ATTR_EMULATE_PREPARES => false,
	];
	$pdo = new PDO($dsn, $username, $password, $options);
	out('Connected to MySQL server successfully', 'success');

	// 2) Create database if not exists
	$pdo->exec('CREATE DATABASE IF NOT EXISTS `' . str_replace('`','``',$dbName) . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
	out('Database `' . $dbName . '` ensured (created if missing)', 'success');

	// 3) Use the database
	$pdo->exec('USE `' . str_replace('`','``',$dbName) . '`');

	// 4) Read SQL file
	$sql = file_get_contents($sqlFile);
	if ($sql === false) {
		throw new RuntimeException('Failed to read database.sql');
	}

	// Split SQL into individual statements safely
	$statements = [];
	$buffer = '';
	$inString = false;
	$stringChar = '';
	$len = strlen($sql);
	for ($i = 0; $i < $len; $i++) {
		$ch = $sql[$i];
		$next = $i + 1 < $len ? $sql[$i+1] : '';
		if ($inString) {
			$buffer .= $ch;
			if ($ch === $stringChar && $sql[$i-1] !== '\\') {
				$inString = false;
			}
			continue;
		}
		if ($ch === '\'' || $ch === '"') {
			$inString = true;
			$stringChar = $ch;
			$buffer .= $ch;
			continue;
		}
		// Handle line comments
		if ($ch === '-' && $next === '-' ) {
			// skip until end of line
			while ($i < $len && $sql[$i] !== "\n") { $i++; }
			continue;
		}
		// Handle block comments
		if ($ch === '/' && $next === '*') {
			$i += 2;
			while ($i < $len && !($sql[$i] === '*' && ($i+1 < $len && $sql[$i+1] === '/'))) { $i++; }
			$i++; // skip closing '/'
			continue;
		}
		if ($ch === ';') {
			$statements[] = trim($buffer);
			$buffer = '';
			continue;
		}
		$buffer .= $ch;
	}
	$last = trim($buffer);
	if ($last !== '') { $statements[] = $last; }

	// 5) Execute statements
	$executed = 0;
	foreach ($statements as $stmt) {
		if ($stmt === '') { continue; }
		$pdo->exec($stmt);
		$executed++;
	}
	out('Imported schema and seed data from database.sql (' . $executed . ' statements)', 'success');

	// 6) Basic verification of required tables
	$required = ['users','service_vehicles','technicians','service_requests','payments','service_history','admin_users'];
	$missing = [];
	foreach ($required as $t) {
		$q = $pdo->query("SHOW TABLES LIKE '" . str_replace("'","''", $t) . "'");
		if ($q->rowCount() === 0) { $missing[] = $t; }
	}
	if (empty($missing)) {
		out('All required tables exist', 'success');
	} else {
		out('Missing tables: ' . implode(', ', $missing), 'warning');
	}

	// 7) Show next steps
	echo '<a class="btn" href="setup_database.php">Open setup_database.php health check</a>';
} catch (Throwable $e) {
	out('Installation failed: ' . $e->getMessage(), 'error');
	echo '<div class="code">' . escHtml($e->getTraceAsString()) . '</div>';
	echo '<p>Check credentials in <code>config/config.php</code> and <code>config/database.php</code>.</p>';
}
?>
	</div>
</body>
</html>

