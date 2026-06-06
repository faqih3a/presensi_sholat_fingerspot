<?php
	//ini_set("display_errors", 0);
	//error_reporting(0);

	$base_path		= (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') . '/';
	// Default database configuration
	$db_host = "127.0.0.1";
	$db_user = "root";
	$db_pass = "";
	$db_name = "demo_webhook";

	// Try to load DB credentials from Laravel's .env file
	$envPath = __DIR__ . '/../../.env';
	if (file_exists($envPath)) {
		$lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		foreach ($lines as $line) {
			if (strpos(trim($line), '#') === 0) continue;
			$parts = explode('=', $line, 2);
			if (count($parts) === 2) {
				$name = trim($parts[0]);
				$value = trim($parts[1], '"\' ');
				if ($name === 'DB_HOST') $db_host = $value;
				if ($name === 'DB_USERNAME') $db_user = $value;
				if ($name === 'DB_PASSWORD') $db_pass = $value;
				if ($name === 'DB_DATABASE') $db_name = $value;
			}
		}
	}

	// Create PDO connection
	try {
		$conn = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
		$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	} catch (PDOException $e) {
		if ($db_host !== '127.0.0.1' && $db_host !== 'localhost') {
			// Fallback to 127.0.0.1 if docker host fails
			try {
				$conn = new PDO("mysql:host=127.0.0.1;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
				$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			} catch (PDOException $e2) {
				die("Database connection failed: " . $e2->getMessage());
			}
		} else {
			die("Database connection failed: " . $e->getMessage());
		}
	}

	// Auto-create t_log table if it doesn't exist
	try {
		$conn->exec("CREATE TABLE IF NOT EXISTS `t_log` (
			`cloud_id` varchar(32) NOT NULL,
			`type` varchar(32) NOT NULL,
			`created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
			`original_data` longtext NOT NULL,
			PRIMARY KEY (`cloud_id`,`type`,`created_at`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
	} catch (PDOException $e) {
		// Ignore or handle table creation error
	}
	//mysql_select_db($db_name, $conn) or die("Can not connect to database!");
?>
