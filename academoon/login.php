<?php
// ============================================================
//  Academon · login.php
//  Handles both GET (show login form) and POST (process login)
// ============================================================

session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/config.php';

// ---- Check if user is already logged in ----
if (isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => true,
        'message' => 'Already logged in',
        'redirect' => defined('REDIRECT_AFTER_LOGIN') ? REDIRECT_AFTER_LOGIN : 'dashboard.php'
    ]);
    exit;
}

// ---- Handle GET request - show login form ----
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // If it's a browser, show the HTML form
    if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'text/html') !== false) {
        ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Academon · Login</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: url('sunset.png') no-repeat center center fixed;
            background-size: cover;
            font-family: 'Press Start 2P', monospace;
        }
        .login-container {
            background: rgba(255, 241, 203, 0.95);
            padding: 40px;
            border-radius: 20px;
            border: 3px solid rgba(255, 215, 100, 0.85);
            width: 100%;
            max-width: 400px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        h1 {
            font-size: 14px;
            text-align: center;
            margin-bottom: 20px;
            color: #1e1a2b;
        }
        .logo {
            text-align: center;
            font-size: 48px;
            margin-bottom: 10px;
        }
        label {
            font-size: 8px;
            display: block;
            margin-top: 15px;
            color: #4a4060;
        }
        input {
            width: 100%;
            padding: 10px;
            border: 2px solid rgba(30, 26, 43, 0.2);
            border-radius: 10px;
            font-family: 'Press Start 2P', monospace;
            font-size: 8px;
            margin-top: 5px;
            background: rgba(255,255,255,0.7);
        }
        input:focus {
            border-color: #8f8df4;
            outline: none;
        }
        button {
            width: 100%;
            padding: 12px;
            margin-top: 20px;
            font-family: 'Press Start 2P', monospace;
            font-size: 8px;
            background: #8f8df4;
            color: white;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            box-shadow: 0 4px 0 #6b69b8;
            transition: 0.12s;
        }
        button:hover {
            background: #7a78d6;
            transform: translateY(-1px);
            box-shadow: 0 5px 0 #6b69b8;
        }
        button:active {
            transform: translateY(3px);
            box-shadow: 0 1px 0 #6b69b8;
        }
        .error {
            color: #721c24;
            background: #f8d7da;
            padding: 10px;
            border-radius: 8px;
            font-size: 7px;
            margin-top: 10px;
            display: none;
        }
        .info {
            text-align: center;
            font-size: 6px;
            color: #7a7090;
            margin-top: 15px;
        }
        .info span {
            color: #8f8df4;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">🧠</div>
        <h1>ACADEMON · LOGIN</h1>
        
        <form id="loginForm" onsubmit="handleLogin(event)">
            <label for="username">👤 USERNAME</label>
            <input type="text" id="username" placeholder="admin" required />
            
            <label for="password">🔒 PASSWORD</label>
            <input type="password" id="password" placeholder="password123" required />
            
            <div id="error" class="error"></div>
            
            <button type="submit">⚔️ LOGIN</button>
        </form>
        
        <div class="info">Default: <span>admin</span> / <span>password123</span></div>
    </div>

    <script>
        async function handleLogin(event) {
            event.preventDefault();
            
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value.trim();
            const errorEl = document.getElementById('error');
            
            errorEl.style.display = 'none';
            
            try {
                const response = await fetch('login.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ username, password })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Redirect to dashboard or homepage
                    window.location.href = data.redirect || 'dashboard.php';
                } else {
                    errorEl.textContent = '❌ ' + data.message;
                    errorEl.style.display = 'block';
                }
            } catch (error) {
                errorEl.textContent = '❌ Network error. Please try again.';
                errorEl.style.display = 'block';
            }
        }
        
        // Enter key support
        document.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                const form = document.getElementById('loginForm');
                if (form) {
                    form.dispatchEvent(new Event('submit'));
                }
            }
        });
    </script>
</body>
</html>
        <?php
        exit;
    }
    
    // For API clients, return JSON
    echo json_encode(['success' => false, 'message' => 'Please use POST to login']);
    exit;
}

// ---- Handle POST request - process login ----
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Parse JSON body
$body = json_decode(file_get_contents('php://input'), true);

// Check if JSON is valid
if ($body === null && json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
    exit;
}

$username = isset($body['username']) ? trim($body['username']) : '';
$password = isset($body['password']) ? $body['password'] : '';

// ---- Basic validation ----
if ($username === '' || $password === '') {
    echo json_encode(['success' => false, 'message' => 'Username and password are required']);
    exit;
}

// ---- Lookup user ----
try {
    $db = get_db();
    
    $stmt = $db->prepare('SELECT id, username, password FROM users WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($password, $user['password'])) {
        echo json_encode(['success' => false, 'message' => 'Incorrect username or password']);
        exit;
    }

    // ---- Start session ----
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];

    echo json_encode([
        'success' => true,
        'message' => 'Welcome back, ' . $user['username'] . '!',
        'redirect' => defined('REDIRECT_AFTER_LOGIN') ? REDIRECT_AFTER_LOGIN : 'dashboard.php',
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>