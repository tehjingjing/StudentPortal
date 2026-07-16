<?php
// index.php (project root)
// Single entry point for http://localhost:8080/
// Redirect logic:
// 1. Not logged in → auth/login.php
// 2. Logged in admin → dashboard/admin_dashboard.php
// 3. Logged in student → dashboard/student_dashboard.php

// Start session to access login user data stored in session
session_start();

// Check if user has a role stored in session (meaning already logged in)
if (!empty($_SESSION['role'])) {
    // Admin account
    if ($_SESSION['role'] === 'admin') {
        header('Location: dashboard/admin_dashboard.php');
    } 
    // Student account
    else {
        header('Location: dashboard/student_dashboard.php');
    }
} 
// No role in session = visitor / logged out user
else {
    header('Location: auth/login.php');
}

// Terminate script immediately after sending redirect header
exit();