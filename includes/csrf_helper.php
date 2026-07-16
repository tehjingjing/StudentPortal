<?php
/**
 * Reusable CSRF protection helper.
 * Include this file on any page that needs CSRF tokens or validation.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Generate a new CSRF token and store it in the session.
 * Called automatically when you get a token if none exists.
 */
function csrf_generate(): void
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}

/**
 * Get the current CSRF token (generates one if missing).
 */
function csrf_token(): string
{
    csrf_generate();
    return $_SESSION['csrf_token'];
}

/**
 * Output a hidden HTML input with the CSRF token.
 * Use this inside your <form> tags.
 */
function csrf_field(): void
{
    echo '<input type="hidden" name="csrf_token" value="'
        . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8')
        . '">';
}

/**
 * Validate a submitted CSRF token against the session.
 * Returns true if valid, false otherwise.
 */
function csrf_validate(string $token = null): bool
{
    if ($token === null) {
        $token = $_POST['csrf_token'] ?? '';
    }

    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }

    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Regenerate the CSRF token.
 * Call this after login, logout, password change, or role change.
 */
function csrf_regenerate(): void
{
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}