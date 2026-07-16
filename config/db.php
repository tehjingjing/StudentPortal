<?php
// Prevent direct access to this file for security
if (basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME'])) {
    header('HTTP/1.1 403 Forbidden');
    exit('Access Denied');
}

// Data configuration variables
$host = 'db';             // Matches the Docker service name 'db'
$db   = 'studentportal';  // Matches your docker-compose MYSQL_DATABASE
$user = 'appuser';        // Matches your docker-compose MYSQL_USER
$pass = 'apppass';        // Matches your docker-compose MYSQL_PASSWORD
$port = 3306;             // Internal Docker port for MySQL

// mysqli is the built-in PHP extension for talking to MySQL.
// This line dials the phone and connects.
$conn = new mysqli($host, $user, $pass, $db, $port);

// Always check the call connected. If not, stop everything and show why.
// (In a real app you'd log this, not print it -- but for learning, show it.)
// Check for database connection failure immediately after initialization
// Terminate script and output error message if connection cannot be created
// Production applications should log errors instead of printing them directly for security
if ($conn->connect_error) {
    error_log('Database connection failed: ' . $conn->connect_error);
    die("A secure database connectivity error occurred. Please contact the portal administrator.");
}

// Set database character set to utf8mb4
// Make sure text with emojis / accents is stored correctly.
// Set database character set to utf8mb4
$conn->set_charset('utf8mb4');
?>
