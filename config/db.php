<?php
// Prevent direct access to this file for security
if (basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME'])) {
    header('HTTP/1.1 403 Forbidden');
    exit('Access Denied');
}

// Data configuration variables - Updated for Railway with Docker fallbacks
$host = getenv('MYSQLHOST') ?: 'db';             // Fallback to Docker service 'db'
$db   = getenv('MYSQLDATABASE') ?: 'studentportal';  // Fallback to local DB
$user = getenv('MYSQLUSER') ?: 'appuser';        // Fallback to local user
$pass = getenv('MYSQLPASSWORD') ?: 'apppass';        // Fallback to local pass
$port = getenv('MYSQLPORT') ?: 3306;             // Fallback to default port

// mysqli is the built-in PHP extension for talking to MySQL.
// This line dials the phone and connects.
$conn = new mysqli($host, $user, $pass, $db, $port);

// Always check the call connected. If not, stop everything and show why.
// Production applications should log errors instead of printing them directly for security
if ($conn->connect_error) {
    error_log('Database connection failed: ' . $conn->connect_error);
    die("A secure database connectivity error occurred. Please contact the portal administrator.");
}

// Set database character set to utf8mb4
// Make sure text with emojis / accents is stored correctly.
$conn->set_charset('utf8mb4');
?>
