<?php
session_start();

// 1. Clear $_SESSION data
$_SESSION = [];

// 2. Destroy the session server-side
session_destroy();

// 3. Expire the session cookie itself (not just the server-side data)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 3600,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

header("Location: login.php");
exit();