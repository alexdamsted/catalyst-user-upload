<?php

class UserUpload {
	
	private $options = [];
	private $pdo = null;
	private $dryRun = false;

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
	public function __construct() {
		$this->cli_get_params("u:p:h:", array("file:", "create_table", "dry_run", "help"));
	}

	private function cli_get_params(string $shortos, array $longos): void {
		$this->options = getopt($shortos, $longos);

		// Since this is in the constructor we can show the help first
		if (isset($this->options['help'])) {
			$this->showHelp();
			exit;
		}

		$this->dryRun = isset($this->options['dry_run']);
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

		// MySQL passwords can be empty but this will force people to set one which makes the world a better place
		if (!empty($host) && !empty($username) && !empty($password)) {
        		return [$host, $username, $password];
		}

		return false;
	}

	private function connect(): void {
		if($creds = $this->get()) {
			[$host, $username, $password] = $creds;
		} else {
			throw new Exception("Missing a host, username, or password");
		}

		try {
			$dsn = "mysql:host={$host}";

			// Get pdo exceptions and arrays with column name keys
            		$this->pdo = new PDO($dsn, $username, $password, [
                		PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                		PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
			]);
			
			$this->pdo->exec("CREATE DATABASE IF NOT EXISTS users");
            		$this->pdo->exec("USE users");
            
            		echo "Connected to MySQL database successfully.\n";
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

		$this->connect($host, $username, $password);
	}

	private function createTable(): void {
		$sql = "
            	CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                surname VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL UNIQUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

		try {
			// Rebuild the table and or  create as new
            		$this->pdo->exec("DROP TABLE IF EXISTS users");
            		$this->pdo->exec($sql);
            		echo "Table created.\n";
        	} catch (PDOException $e) {
            		throw new Exception("Failed to create table: " . $e->getMessage());
        	}
	}

    	private function validateEmail($email): string|bool {
        	return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    	}
    
    	private function formatName($name): string {
        	return ucfirst(strtolower(trim($name)));
    	}
    
    	private function formatEmail($email): string {
        	return strtolower(trim($email));
    	}
    
	private function processCSVFile(): void {
		$filename = $this->options['file'];

        	// Validate file existence and readability
        	if (!file_exists($filename)) {
            		throw new Exception("CSV file '{$filename}' not found.");
        	}

        	if (!is_readable($filename)) {
            		throw new Exception("CSV file '{$filename}' is not readable.");
        	}

        	// Read entire file
        	$fileContent = file_get_contents($filename);
        	if ($fileContent === false) {
            		throw new Exception("Failed to read CSV file '{$filename}'.");
        	}

		// An array of lines
		// Take the file content, separate by new lines, trim each line, then only keep it if true
		$lines = array_filter(
			array_map('trim', explode("\n", $fileContent)), 
			function($line) {
            			return !empty($line);
			}
		);

        	// Initialize counters
        	$successCount = 0;
        	$errorCount = 0;
        	$totalLines = count($lines);

        	// Prepare database statement once for the dry run
        	$stmt = null;
        	if (!$this->dryRun) {
            		$stmt = $this->pdo->prepare("INSERT INTO users (name, surname, email) VALUES (?, ?, ?)");
        	}

        	echo "Processing CSV file: {$filename}\n";
        	if ($this->dryRun) {
            		echo "DRY RUN MODE\n";
        	}

        	// Process each line
        	foreach ($lines as $lineIndex => $line) {
			$lineNumber = $lineIndex + 1;

			if ($lineNumber === 1) {
				echo "Skip the heading row: {$line}\n";
				continue;
			}

			// Needed for CSV reading
            		$columns = str_getcsv($line);

            		// Validate column count
            		if (count($columns) !== 3) {
                		echo "Error on line {$lineNumber} - Expected 3 columns, got " . count($columns) . "\n";
                		$errorCount++;
                		continue;	
			}

            		// Extract and clean data
            		$rawData = array_map('trim', $columns);

			// Here we get the raw gold we want!
			list($rawName, $rawSurname, $rawEmail) = $rawData;

            		// Check for empty fields
            		if (empty($rawName) || empty($rawSurname) || empty($rawEmail)) {
                		echo "Error on line {$lineNumber}. Missing required data (name: '{$rawName}', surname: '{$rawSurname}', email: '{$rawEmail}')\n";
                		$errorCount++;
                		continue;
            		}

            		// Format the data
            		$formattedData = $this->formatUserData($rawName, $rawSurname, $rawEmail);

            		// Validate email format
            		if (!$this->validateEmail($formattedData['email'])) {
                		echo "Error on line {$lineNumber}. An invalid email format: '{$formattedData['email']}'\n";
                		$errorCount++;
                		continue;
            		}

            		// Process the record finally
            		$result = $this->insertUserRecord($stmt, $formattedData, $lineNumber);
            		if ($result['success']) {
                		$successCount++;
            		} else {
                		$errorCount++;
            		}
		}
        	// Display summary
        	$this->showProcessingSummary($totalLines, $successCount, $errorCount);
	}
    
	private function formatUserData($name, $surname, $email): array {
		return [
			'name' => $this->formatName($name),
			'surname' => $this->formatName($surname),
			'email' => $this->formatEmail($email)
		];
	}
    
	private function insertUserRecord($stmt, $userData, $lineNumber): array {
		if ($this->dryRun) {
			echo "Dry run line {$lineNumber} would insert: {$userData['name']} {$userData['surname']} ({$userData['email']})\n";
			return ['success' => true];
		}

		try {
			$stmt->execute([
				$userData['name'],
				$userData['surname'],
				$userData['email']
			]);
            		echo "Success line {$lineNumber} inserted: {$userData['name']} {$userData['surname']} ({$userData['email']})\n";
			return ['success' => true];
		} catch (PDOException $e) {
			// Duplicate entry code
			if ($e->getCode() == 23000) {
				echo "Error on line {$lineNumber} a duplicate email: '{$userData['email']}'\n";
			} else {
				echo "Error on line {$lineNumber} a database error: " . $e->getMessage() . "\n";
			}
			return ['success' => false];
		}
	}
    
	private function showProcessingSummary($totalLines, $successCount, $errorCount): void {
		echo "Processing complete:\n";
		echo "Total lines in file: {$totalLines}\n";
		echo "Successful records: {$successCount}\n";
		echo "Error records: {$errorCount}\n";

		if ($this->dryRun) {
			echo "Mode: DRY RUN (no data inserted)\n";
		}
	}

	public function run(): void {
		try {
			// Handle create_table option
			if (isset($this->options['create_table'])) {
				$this->start();
				$this->createTable();
				return;
	    		}

			// Handle file processing
	    		if (isset($this->options['file'])) {
				$this->start();
				$this->createTable();
				$this->processCSVFile($this->options['file']);
	       			return;
	    		}
			
			// If no main option provided, show help
	    		echo "Error: Please specify either the --file or --create_table option.\n\n";
	    		$this->showHelp();
		} catch (Exception $e) {
			echo "Error: " . $e->getMessage() . "\n";
    			exit(1);
		}
	}
}

$script = new UserUpload();
$script->run();

?>
