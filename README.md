User Upload Script

Installation
Prerequisites

PHP 8.1 or higher
MySQL 8.0+
PHP PDO MySQL extension

Setup
Clone/Download the repository

HTTPS:
git clone https://github.com/alexdamsted/catalyst-user-upload.git catalyst-user-upload

SSH:
git clone git@github.com:alexdamsted/catalyst-user-upload.git catalyst-user-upload  

# This script requires you have a password for your mysql database
mysql -u root -p

Show  the script directives
php user_upload.php --help

Database Setup
The script automatically:

Creates database user_upload_db if it doesn't exist
Creates/rebuilds the users table

Usage Examples
# Show help
php user_upload.php --help

# Create table only
php user_upload.php --create_table -u root -p password -h localhost

# Dry run
php user_upload.php --file users.csv -u root -p password -h localhost --dry_run

# Process CSV file
php user_upload.php --file users.csv -u root -p password -h localhost

CSV File Issue Handling
The script  handles various CSV file issues found in the provided users.csv file. Here's where each issue is addressed in the code:


1. Header Row Detection & Skipping
Location: processCSVFile() method
// Skip header row if it exists
 if ($lineNumber === 1) {
         echo "Skip the heading row: {$line}\n";
         continue;
 }

2. Whitespace and Tab Character Cleanup

Removes leading and trailing spaces and tabs from all fields
Filters out completely empty lines

Location: processCSVFile() method
$rawData = array_map('trim', $columns);

$lines = array_filter(array_map('trim', explode("\n", $fileContent)), function($line) {
    return !empty($line);
});


3. Email Validation
Location: validateEmail() method
private function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}
Used in: processCSVFile() method

Catches invalid email formats like edward@jikes@com.au


4. Duplicate Email Prevention
Location: Database schema in createUsersTable() method
email VARCHAR(255) NOT NULL UNIQUE
Error Handling: insertUserRecord() method
} catch (PDOException $e) {
    if ($e->getCode() == 23000) { // Duplicate entry

Database UNIQUE constraint prevents duplicates


5. Name Capitalization and Formatting
Location: formatName() method
private function formatName($name) {
    return ucfirst(strtolower(trim($name)));
}
Used in: formatUserData() method

Converts "WILLIAM" to "William" and "haMish" "Hamish"


6. Email Lowercase Conversion
Location: formatEmail() method
private function formatEmail($email) {
    return strtolower(trim($email));
}


7. Column Count Validation
Location: processCSVFile() method

if (count($columns) !== 3) {


8. Empty Field Detection
Location: processCSVFile() method

if (empty($rawName) || empty($rawSurname) || empty($rawEmail)) {


9. Special Characters in Names
The script preserves special characters like:

Exclamation marks
Apostrophes
This is handled by the formatName() method which only affects capitalization

Error Recovery with stdout
the script continues processing even when encountering errors, providing a summary at the end with counts of successful and failed records
