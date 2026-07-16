<?php
// Prevent direct access to this file for security
if (basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME'])) {
    header('HTTP/1.1 403 Forbidden');
    exit('Access Denied');
}

// Check if running on Render (production) or Docker (local development)
if (getenv('RENDER') === 'true' && getenv('DATABASE_URL')) {
    // Render deployment - use PostgreSQL
    $dbUrl = getenv('DATABASE_URL');
    $urlParts = parse_url($dbUrl);

    $host = $urlParts['host'];
    $port = $urlParts['port'] ?? 5432;
    $user = $urlParts['user'];
    $pass = $urlParts['pass'];
    $db = ltrim($urlParts['path'], '/');

    // Use PDO for PostgreSQL
    try {
        $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$db", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $conn = $pdo;
    } catch (PDOException $e) {
        error_log('Database connection failed: ' . $e->getMessage());
        die("A secure database connectivity error occurred. Please contact the portal administrator.");
    }
} else {
    // Docker/local development configuration (MySQL)
    $host = 'db';
    $db = 'studentportal';
    $user = 'appuser';
    $pass = 'apppass';
    $port = 3306;

    $conn = new mysqli($host, $user, $pass, $db, $port);

    if ($conn->connect_error) {
        error_log('Database connection failed: ' . $conn->connect_error);
        die("A secure database connectivity error occurred. Please contact the portal administrator.");
    }

    $conn->set_charset('utf8mb4');
}
?>
