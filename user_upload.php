<?php

class UserUpload {
	
	private $options = [];

	/**
	 * u - MySQL username
	 * p - MySQL password
	 * h - MySQL host
	 *
	 * --file the CSV filename being parsed
	 * --create_table the MYSQL users table build
	 * --dry_run used with --file to run the script without db alteration (useful for tests)
	 * --help used to help people run the script
	 */
	__construct() {
		$this->cli_get_params(array("file:", "create_table", "dry_run", "help"), "u:p:h:");
	}

	private function cli_get_params(array longos, string shortos): void {
		$this->options = getopt($longos, $shortos);

		// Since this is in the constructor we can show the help first
		if (isset($this->options['help'])) {
			$this->showHelp();
			exit;
		}
	}

	private function showHelp(): void {
        	echo "Options:\n";
        	echo "  --file [csv_file]     CSV file to process\n";
        	echo "  --create_table        Create the users table (no other action taken)\n";
        	echo "  --dry_run            Run without inserting into database\n";
        	echo "  -u [username]        MySQL username\n";
        	echo "  -p [password]        MySQL password\n";
        	echo "  -h [host]            MySQL host (default: localhost)\n";
        	echo "  --help               Show this help message\n\n";
        	echo "Examples:\n";
        	echo "  php user_upload.php --help\n";
        	echo "  php user_upload.php --create_table -u user -p password -h localhost --dry_run\n";
    	}

	private function get(): array|bool {
		$host = $this->options['h'] ?? null;
		$username = $this->options['u'] ?? null;
		$password = $this->options['p'] ?? null;

		if (!empty($host) && !empty($username) && !empty($password)) {
        		return [$host, $username, $password];
		}

		return false;
	}

	private function connect(): PDO {
		try {
			$dsn = "mysql:host={$host}";

			// Get pdo exceptions and arrays with column name keys
            		$pdo = new PDO($dsn, $username, $password, [
                		PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                		PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
			]);
			
			$pdo->exec("CREATE DATABASE IF NOT EXISTS user_upload_db");
            		$pdo->exec("USE user_upload_db");
            
            		echo "Connected to MySQL database successfully.\n";
		
			return $pdo;
		} catch(PDOException $e) {	
			throw new Exception("Database startup failed: " . $e->getMessage());
		}
	}	

	private function start(): void {
		if($creds = $this->get()) {
			[$host, $username, $password] = $creds;
		} else {
			throw new Exception("Missing host, username, or password");
		}

		connect($host, $username, $password);
	}



}
