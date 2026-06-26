<?php
// ============================================================
//  Academon · auth_guard.php
//
//  Include this at the very top of any page that requires login:
//
//      require_once __DIR__ . '/auth_guard.php';
//
//  If the user is not logged in, they are redirected to index.php
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Convenience: $current_user is available on any protected page
$current_user = [
    'id'       => $_SESSION['user_id'],
    'username' => $_SESSION['username'],
];
