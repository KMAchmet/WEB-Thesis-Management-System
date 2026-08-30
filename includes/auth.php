<?php
/**
 * includes/auth.php
 * Include this at the very top of every protected page.
 * Ensures the user is logged in (and optionally has a specific role),
 * otherwise redirects to the login page — per spec, no content may
 * ever be shown to a non-authenticated user.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function require_login(): void
{
    if (empty($_SESSION['user_id'])) {
        header('Location: /login.php');
        exit;
    }
}

function require_role(string $role): void
{
    require_login();
    if ($_SESSION['role'] !== $role) {
        header('Location: /login.php');
        exit;
    }
}

function current_user_id(): ?int
{
    return $_SESSION['user_id'] ?? null;
}

function current_role(): ?string
{
    return $_SESSION['role'] ?? null;
}
