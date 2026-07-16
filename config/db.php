<?php
// Prevent direct access to this file for security
if (basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME'])) {
    header('HTTP/1.1 403 Forbidden');
    exit('Access Denied');
}

// Production Render MySQL config (read env variables)
if (getenv('RENDER') === 'true') {
    $host = getenv('DB_HOST');
    $user = getenv('DB_USER');
    $pass = getenv('DB_PASS');
    $db = getenv('DB_NAME');
    $port = 3306;
} else {
    // Docker/local development configuration (MySQL)
    $host = 'db';
    $db = 'studentportal';
    $user = 'appuser';
    $pass = 'apppass';
    $port = 3306;
}

// Uniform MySQL mysqli connection for all environment
$conn = new mysqli($host, $user, $pass, $db, $port);

if ($conn->connect_error) {
    // Log exact error to Render backend logs
    error_log('DB Connect Error: ' . $conn->connect_error . ' | Host:' . $host);
    die("A secure database connectivity error occurred. Please contact the portal administrator.");
}

$conn->set_charset('utf8mb4');
?>
