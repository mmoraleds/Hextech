<?php
// ============================================================
//  Academon · register.php
//  Accepts POST with JSON body: { username, password, password2 }
//  Returns JSON: { success: bool, message: string }
// ============================================================

session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/config.php';

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Parse JSON body
$body = json_decode(file_get_contents('php://input'), true);
$username  = isset($body['username'])  ? trim($body['username'])  : '';
$password  = isset($body['password'])  ? $body['password']        : '';
$password2 = isset($body['password2']) ? $body['password2']       : '';

// ---- Validation ----
if (strlen($username) < 2) {
    echo json_encode(['success' => false, 'message' => 'Username must be at least 2 characters']);
    exit;
}
if (strlen($username) > 50) {
    echo json_encode(['success' => false, 'message' => 'Username too long (max 50 characters)']);
    exit;
}
if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
    echo json_encode(['success' => false, 'message' => 'Username can only contain letters, numbers and underscores']);
    exit;
}
if (strlen($password) < 6) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters']);
    exit;
}
if ($password !== $password2) {
    echo json_encode(['success' => false, 'message' => 'Passwords do not match']);
    exit;
}

// ---- Check duplicate username ----
try {
    $db = get_db();

    $stmt = $db->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Username already taken']);
        exit;
    }

    // ---- Insert new user ----
    $hash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $db->prepare('INSERT INTO users (username, password) VALUES (?, ?)');
    $stmt->execute([$username, $hash]);

    echo json_encode(['success' => true, 'message' => 'Account created! You can now login.']);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error. Please try again.']);
    // Uncomment below for debug (never in production):
    // echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
