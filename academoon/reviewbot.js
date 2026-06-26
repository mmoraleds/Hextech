<?php
// ============================================================
//  Academon · ReviewBot
//  AI-powered code review assistant with chat interface
// ============================================================

require_once __DIR__ . '/auth_guard.php';
require_once __DIR__ . '/config.php';

// Get user info
$username = htmlspecialchars($current_user['username']);
$user_id = $current_user['id'];

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    $action = $_POST['action'];
    $response = ['success' => false, 'message' => 'Invalid action'];
    
    switch ($action) {
        case 'analyze':
            $response = analyzeCode($_POST['code'] ?? '', $_POST['language'] ?? 'php');
            break;
        case 'chat':
            $response = chatWithBot($_POST['message'] ?? '', $user_id);
            break;
        case 'get_history':
            $response = getChatHistory($user_id);
            break;
        case 'save_review':
            $response = saveReview($user_id, $_POST['code'] ?? '', $_POST['review'] ?? '');
            break;
    }
    
    echo json_encode($response);
    exit;
}

// Helper functions
function analyzeCode($code, $language) {
    if (empty($code)) {
        return ['success' => false, 'message' => 'No code provided'];
    }
    
    // Basic code analysis (expandable)
    $issues = [];
    $suggestions = [];
    $score = 100;
    
    // PHP specific checks
    if ($language === 'php') {
        // Check for common issues
        if (strpos($code, 'mysql_') !== false) {
            $issues[] = 'Deprecated mysql_* functions detected. Use PDO or MySQLi instead.';
            $score -= 10;
            $suggestions[] = 'Replace mysql_* functions with PDO prepared statements.';
        }
        
        if (strpos($code, 'eval(') !== false) {
            $issues[] = 'eval() usage detected - security risk!';
            $score -= 15;
            $suggestions[] = 'Avoid using eval(). Find alternative approaches.';
        }
        
        if (strpos($code, '$_GET') !== false && strpos($code, 'htmlspecialchars') === false) {
            $issues[] = 'Direct $_GET usage without sanitization.';
            $score -= 8;
            $suggestions[] = 'Always sanitize user input with htmlspecialchars() or filter_var().';
        }
        
        if (strpos($code, 'password') !== false && strpos($code, 'password_hash') === false) {
            $issues[] = 'Password handling detected - ensure you\'re using password_hash().';
            $score -= 10;
            $suggestions[] = 'Use password_hash() for storing passwords and password_verify() for checking.';
        }
        
        // Check for SQL injection
        if (preg_match('/\$_(GET|POST|REQUEST)\s*\[.*\]\s*.*\s*\$.*query/', $code)) {
            $issues[] = 'Potential SQL injection vulnerability detected.';
            $score -= 20;
            $suggestions[] = 'Always use prepared statements with PDO or MySQLi.';
        }
        
        // Check for missing error handling
        if (strpos($code, 'try') === false && strpos($code, 'catch') === false) {
            $issues[] = 'No error handling detected.';
            $score -= 5;
            $suggestions[] = 'Add try-catch blocks for error handling.';
        }
        
        // Check for proper authentication
        if (strpos($code, 'session_start') !== false && strpos($code, 'session_regenerate_id') === false) {
            $issues[] = 'Session started without regeneration.';
            $score -= 5;
            $suggestions[] = 'Use session_regenerate_id(true) after login to prevent session fixation.';
        }
    }
    
    // JavaScript specific checks
    if ($language === 'javascript') {
        if (strpos($code, 'eval(') !== false) {
            $issues[] = 'eval() usage detected - security risk!';
            $score -= 15;
            $suggestions[] = 'Avoid using eval(). Use JSON.parse() or other alternatives.';
        }
        
        if (strpos($code, 'innerHTML') !== false && strpos($code, 'textContent') === false) {
            $issues[] = 'innerHTML usage without sanitization - XSS risk!';
            $score -= 10;
            $suggestions[] = 'Use textContent or sanitize HTML before using innerHTML.';
        }
        
        if (strpos($code, 'var ') !== false) {
            $issues[] = 'var keyword used. Consider using let or const.';
            $score -= 3;
            $suggestions[] = 'Use let for mutable variables and const for constants.';
        }
    }
    
    // Check for comments
    if (strpos($code, '//') === false && strpos($code, '/*') === false) {
        $issues[] = 'No comments found in the code.';
        $score -= 3;
        $suggestions[] = 'Add comments to explain complex logic.';
    }
    
    // Check for consistent indentation (basic)
    $lines = explode("\n", $code);
    $hasMixedIndent = false;
    foreach ($lines as $line) {
        if (preg_match('/^\t+\s/', $line) || preg_match('/^    +\t/', $line)) {
            $hasMixedIndent = true;
            break;
        }
    }
    if ($hasMixedIndent) {
        $issues[] = 'Mixed indentation detected.';
        $score -= 3;
        $suggestions[] = 'Use consistent indentation (spaces or tabs, but not both).';
    }
    
    // Calculate final score
    $score = max(0, min(100, $score));
    
    $status = $score >= 80 ? '✅ Good' : ($score >= 60 ? '⚠️ Needs Improvement' : '❌ Needs Significant Work');
    
    return [
        'success' => true,
        'score' => $score,
        'status' => $status,
        'issues' => $issues,
        'suggestions' => $suggestions,
        'line_count' => count($lines),
        'language' => $language
    ];
}

function chatWithBot($message, $user_id) {
    if (empty($message)) {
        return ['success' => false, 'message' => 'No message provided'];
    }
    
    // Store user message
    try {
        $db = get_db();
        $stmt = $db->prepare('INSERT INTO reviewbot_chat (user_id, message, is_bot) VALUES (?, ?, 0)');
        $stmt->execute([$user_id, $message]);
    } catch (PDOException $e) {
        // Log error but continue
        error_log('ReviewBot chat storage error: ' . $e->getMessage());
    }
    
    // Generate response based on keywords
    $response = generateBotResponse($message);
    
    // Store bot response
    try {
        $db = get_db();
        $stmt = $db->prepare('INSERT INTO reviewbot_chat (user_id, message, is_bot) VALUES (?, ?, 1)');
        $stmt->execute([$user_id, $response]);
    } catch (PDOException $e) {
        // Log error but continue
        error_log('ReviewBot response storage error: ' . $e->getMessage());
    }
    
    return [
        'success' => true,
        'response' => $response
    ];
}

function generateBotResponse($message) {
    $message = strtolower(trim($message));
    
    // Code review requests
    if (strpos($message, 'review') !== false || strpos($message, 'check') !== false || strpos($message, 'analyze') !== false) {
        return "🤖 I'm ready to review your code! Please paste your code in the editor above and click 'Analyze Code'. I'll check for:
        • Security vulnerabilities
        • Code quality issues
        • Best practices
        • Performance optimization
        What language are you using? (PHP, JavaScript, Python, etc.)";
    }
    
    // Help requests
    if (strpos($message, 'help') !== false || strpos($message, 'how') !== false || strpos($message, 'what') !== false) {
        return "🤖 Here's how I can help you:
        • 📝 Review your code for issues
        • 🔒 Find security vulnerabilities  
        • 💡 Suggest improvements
        • 📚 Explain best practices
        • 🐛 Help debug problems
        Just paste your code and click 'Analyze Code'!";
    }
    
    // Security questions
    if (strpos($message, 'security') !== false || strpos($message, 'vulnerable') !== false || strpos($message, 'hack') !== false) {
        return "🔒 Security is crucial! Here are some key practices:
        • Always use prepared statements for database queries
        • Sanitize all user input (htmlspecialchars, filter_var)
        • Use password_hash() and password_verify()
        • Enable session_regenerate_id() after login
        • Implement CSRF protection for forms
        Want me to check your code for these?";
    }
    
    // PHP questions
    if (strpos($message, 'php') !== false) {
        return "🐘 PHP Best Practices:
        • Use PDO or MySQLi with prepared statements
        • Enable error reporting during development
        • Use type declarations when possible
        • Follow PSR standards
        • Implement proper error handling with try-catch
        Share your PHP code and I'll review it!";
    }
    
    // JavaScript questions
    if (strpos($message, 'javascript') !== false || strpos($message, 'js') !== false) {
        return "🟨 JavaScript Best Practices:
        • Use const and let instead of var
        • Avoid using eval()
        • Use proper event listeners
        • Implement debouncing for performance
        • Follow ES6+ standards
        Share your JavaScript code for review!";
    }
    
    // General responses
    $responses = [
        "🤖 Hi there! I'm ReviewBot, your AI code review assistant. Want me to review some code? Just paste it in the editor above!",
        "💡 Did you know? I can help you find security vulnerabilities in your code. Try me!",
        "🚀 Ready to level up your code? Paste your code and let's get started!",
        "📚 Good code is clean code. I'll help you write better code with best practices.",
        "🎯 Focus on writing secure and maintainable code. I'm here to help!"
    ];
    
    return $responses[array_rand($responses)];
}

function getChatHistory($user_id) {
    try {
        $db = get_db();
        $stmt = $db->prepare('SELECT message, is_bot, created_at FROM reviewbot_chat WHERE user_id = ? ORDER BY created_at DESC LIMIT 50');
        $stmt->execute([$user_id]);
        $history = $stmt->fetchAll();
        
        return [
            'success' => true,
            'history' => array_reverse($history)
        ];
    } catch (PDOException $e) {
        return [
            'success' => false,
            'message' => 'Error fetching chat history'
        ];
    }
}

function saveReview($user_id, $code, $review) {
    try {
        $db = get_db();
        $stmt = $db->prepare('INSERT INTO reviewbot_reviews (user_id, code, review, created_at) VALUES (?, ?, ?, NOW())');
        $stmt->execute([$user_id, $code, $review]);
        
        return [
            'success' => true,
            'message' => 'Review saved successfully!'
        ];
    } catch (PDOException $e) {
        return [
            'success' => false,
            'message' => 'Error saving review'
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>ReviewBot · AI Code Reviewer</title>
    <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet" />
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            min-height: 100vh;
            background: url('sunset.png') no-repeat center center fixed;
            background-size: cover;
            font-family: 'Press Start 2P', monospace;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        /* Header */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(255,241,203,0.92);
            border-radius: 20px;
            padding: 20px 30px;
            margin-bottom: 30px;
            border: 3px solid rgba(255,215,100,0.8);
        }
        
        .header h1 {
            font-size: 14px;
            color: #1e1a2b;
        }
        
        .header h1 .emoji {
            font-size: 24px;
        }
        
        .header-actions {
            display: flex;
            gap: 15px;
            align-items: center;
        }
        
        .header-actions .user-name {
            font-size: 9px;
            color: #5a4e70;
        }
        
        .back-btn {
            background: #2c2025;
            color: white;
            font-family: 'Press Start 2P', monospace;
            font-size: 9px;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 40px;
            box-shadow: 0 4px 0 #0f0b0e;
            transition: 0.15s;
        }
        
        .back-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 0 #0f0b0e;
        }
        
        /* Grid Layout */
        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .panel {
            background: rgba(255,241,203,0.92);
            border-radius: 20px;
            padding: 25px;
            border: 3px solid rgba(255,215,100,0.8);
            backdrop-filter: blur(4px);
        }
        
        .panel h2 {
            font-size: 11px;
            color: #1e1a2b;
            margin-bottom: 15px;
            text-align: center;
        }
        
        /* Code Editor */
        .code-editor {
            width: 100%;
            min-height: 400px;
            background: #1e1a2b;
            color: #d4d4d4;
            border: 2px solid #3a3050;
            border-radius: 12px;
            padding: 15px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            line-height: 1.6;
            resize: vertical;
            outline: none;
        }
        
        .code-editor:focus {
            border-color: #8f8df4;
        }
        
        .editor-controls {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 10px 20px;
            font-family: 'Press Start 2P', monospace;
            font-size: 9px;
            border: none;
            border-radius: 40px;
            cursor: pointer;
            transition: 0.15s;
            box-shadow: 0 4px 0 #6b69b8;
            color: white;
        }
        
        .btn-primary {
            background: #8f8df4;
        }
        
        .btn-primary:hover {
            background: #7a78d6;
            transform: translateY(-2px);
            box-shadow: 0 6px 0 #6b69b8;
        }
        
        .btn-primary:active {
            transform: translateY(4px);
            box-shadow: 0 2px 0 #6b69b8;
        }
        
        .btn-success {
            background: #4caf50;
            box-shadow: 0 4px 0 #388e3c;
        }
        
        .btn-success:hover {
            background: #43a047;
            transform: translateY(-2px);
            box-shadow: 0 6px 0 #388e3c;
        }
        
        .btn-success:active {
            transform: translateY(4px);
            box-shadow: 0 2px 0 #388e3c;
        }
        
        .btn-warning {
            background: #ff9800;
            box-shadow: 0 4px 0 #f57c00;
        }
        
        .btn-warning:hover {
            background: #fb8c00;
            transform: translateY(-2px);
            box-shadow: 0 6px 0 #f57c00;
        }
        
        .btn-warning:active {
            transform: translateY(4px);
            box-shadow: 0 2px 0 #f57c00;
        }
        
        .btn-danger {
            background: #f44336;
            box-shadow: 0 4px 0 #d32f2f;
        }
        
        .btn-danger:hover {
            background: #e53935;
            transform: translateY(-2px);
            box-shadow: 0 6px 0 #d32f2f;
        }
        
        .btn-danger:active {
            transform: translateY(4px);
            box-shadow: 0 2px 0 #d32f2f;
        }
        
        /* Results Panel */
        .results {
            min-height: 400px;
            max-height: 600px;
            overflow-y: auto;
        }
        
        .result-empty {
            text-align: center;
            color: #7a7090;
            font-size: 9px;
            padding: 40px 20px;
        }
        
        .result-empty .big-emoji {
            font-size: 48px;
            display: block;
            margin-bottom: 15px;
        }
        
        .score-card {
            text-align: center;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
        }
        
        .score-card .score {
            font-size: 48px;
            font-weight: bold;
        }
        
        .score-card .status {
            font-size: 12px;
            margin-top: 5px;
        }
        
        .score-good { background: rgba(76, 175, 80, 0.2); border: 2px solid #4caf50; }
        .score-good .score { color: #4caf50; }
        
        .score-warning { background: rgba(255, 152, 0, 0.2); border: 2px solid #ff9800; }
        .score-warning .score { color: #ff9800; }
        
        .score-danger { background: rgba(244, 67, 54, 0.2); border: 2px solid #f44336; }
        .score-danger .score { color: #f44336; }
        
        .issue-list {
            list-style: none;
            padding: 0;
            margin: 10px 0;
        }
        
        .issue-list li {
            padding: 8px 12px;
            margin-bottom: 5px;
            border-radius: 8px;
            font-size: 9px;
            line-height: 1.6;
            background: rgba(0,0,0,0.05);
        }
        
        .issue-list li .issue-icon {
            margin-right: 8px;
        }
        
        .issue-list li.issue-error {
            border-left: 3px solid #f44336;
        }
        
        .issue-list li.issue-warning {
            border-left: 3px solid #ff9800;
        }
        
        .issue-list li.issue-info {
            border-left: 3px solid #2196f3;
        }
        
        .suggestion-list {
            list-style: none;
            padding: 0;
            margin: 10px 0;
        }
        
        .suggestion-list li {
            padding: 8px 12px;
            margin-bottom: 5px;
            border-radius: 8px;
            font-size: 9px;
            line-height: 1.6;
            background: rgba(76, 175, 80, 0.1);
            border-left: 3px solid #4caf50;
        }
        
        /* Chat Interface */
        .chat-container {
            display: flex;
            flex-direction: column;
            height: 500px;
        }
        
        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 10px;
            background: rgba(255,255,255,0.3);
            border-radius: 12px;
            margin-bottom: 10px;
            min-height: 300px;
            max-height: 400px;
        }
        
        .chat-message {
            margin-bottom: 12px;
            padding: 10px 14px;
            border-radius: 12px;
            font-size: 9px;
            line-height: 1.6;
            max-width: 85%;
        }
        
        .chat-message.user {
            background: #8f8df4;
            color: white;
            margin-left: auto;
            border-bottom-right-radius: 4px;
        }
        
        .chat-message.bot {
            background: rgba(30, 26, 43, 0.1);
            color: #1e1a2b;
            margin-right: auto;
            border-bottom-left-radius: 4px;
        }
        
        .chat-message .timestamp {
            font-size: 7px;
            opacity: 0.6;
            display: block;
            margin-top: 5px;
        }
        
        .chat-input-group {
            display: flex;
            gap: 10px;
        }
        
        .chat-input {
            flex: 1;
            padding: 10px 15px;
            border: 2px solid rgba(30, 26, 43, 0.2);
            border-radius: 30px;
            font-family: 'Press Start 2P', monospace;
            font-size: 8px;
            background: rgba(255,255,255,0.7);
            outline: none;
        }
        
        .chat-input:focus {
            border-color: #8f8df4;
        }
        
        .chat-send {
            padding: 10px 20px;
            background: #8f8df4;
            color: white;
            border: none;
            border-radius: 30px;
            font-family: 'Press Start 2P', monospace;
            font-size: 8px;
            cursor: pointer;
            transition: 0.15s;
            box-shadow: 0 4px 0 #6b69b8;
        }
        
        .chat-send:hover {
            background: #7a78d6;
            transform: translateY(-2px);
            box-shadow: 0 6px 0 #6b69b8;
        }
        
        .chat-send:active {
            transform: translateY(4px);
            box-shadow: 0 2px 0 #6b69b8;
        }
        
        /* Language Selector */
        .language-select {
            padding: 8px 15px;
            border: 2px solid rgba(30, 26, 43, 0.2);
            border-radius: 30px;
            font-family: 'Press Start 2P', monospace;
            font-size: 8px;
            background: rgba(255,255,255,0.7);
            outline: none;
        }
        
        .language-select:focus {
            border-color: #8f8df4;
        }
        
        /* Loading */
        .loading {
            display: none;
            text-align: center;
            padding: 20px;
            color: #8f8df4;
            font-size: 9px;
        }
        
        .loading.active {
            display: block;
        }
        
        .loading .spinner {
            display: inline-block;
            animation: spin 1s linear infinite;
            font-size: 24px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .grid {
                grid-template-columns: 1fr;
            }
            
            .header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .header-actions {
                flex-wrap: wrap;
                justify-content: center;
            }
        }
        
        @media (max-width: 480px) {
            .panel {
                padding: 15px;
            }
            
            .code-editor {
                min-height: 200px;
                font-size: 11px;
            }
            
            .btn {
                font-size: 7px;
                padding: 8px 14px;
            }
        }
        
        /* Scrollbar Styling */
        ::-webkit-scrollbar {
            width: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: rgba(0,0,0,0.05);
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #8f8df4;
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: #7a78d6;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>
                <span class="emoji">🤖</span> REVIEWBOT
                <span style="font-size: 8px; color: #7a7090;">AI Code Reviewer</span>
            </h1>
            <div class="header-actions">
                <span class="user-name">👤 <?php echo $username; ?></span>
                <a href="dashboard.php" class="back-btn">⬅ BACK</a>
            </div>
        </div>
        
        <!-- Main Grid -->
        <div class="grid">
            <!-- Left Panel - Code Editor -->
            <div class="panel">
                <h2>📝 CODE EDITOR</h2>
                <textarea class="code-editor" id="codeEditor" placeholder="Paste your code here for review..."><?php
// Example code
echo "<?php\n";
echo "// Your code here\n";
echo "?>";
?></textarea>
                
                <div class="editor-controls">
                    <select class="language-select" id="languageSelect">
                        <option value="php">🐘 PHP</option>
                        <option value="javascript">🟨 JavaScript</option>
                        <option value="python">🐍 Python</option>
                        <option value="html">🌐 HTML</option>
                        <option value="css">🎨 CSS</option>
                    </select>
                    <button class="btn btn-primary" onclick="analyzeCode()">🔍 ANALYZE</button>
                    <button class="btn btn-success" onclick="saveReview()">💾 SAVE</button>
                    <button class="btn btn-warning" onclick="clearEditor()">🗑 CLEAR</button>
                </div>
                
                <div class="loading" id="loadingIndicator">
                    <span class="spinner">⚙️</span> Analyzing code...
                </div>
            </div>
            
            <!-- Right Panel - Results & Chat -->
            <div class="panel">
                <h2>📊 ANALYSIS RESULTS</h2>
                <div class="results" id="resultsContainer">
                    <div class="result-empty">
                        <span class="big-emoji">🤖</span>
                        <p>Paste your code and click<br><strong>ANALYZE</strong> for review</p>
                        <p style="margin-top: 10px; font-size: 7px; color: #7a7090;">
                            I'll check for security issues,<br>code quality, and best practices
                        </p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Chat Panel -->
        <div class="panel" style="margin-top: 20px;">
            <h2>💬 CHAT WITH REVIEWBOT</h2>
            <div class="chat-container">
                <div class="chat-messages" id="chatMessages">
                    <div class="chat-message bot">
                        👋 Hi <?php echo $username; ?>! I'm ReviewBot. Paste your code above and I'll review it for you! 🚀
                        <span class="timestamp">Just now</span>
                    </div>
                </div>
                <div class="chat-input-group">
                    <input type="text" class="chat-input" id="chatInput" placeholder="Ask ReviewBot a question..." onkeypress="if(event.key==='Enter') sendChat()">
                    <button class="chat-send" onclick="sendChat()">SEND</button>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // ============================================================
        // ReviewBot JavaScript
        // ============================================================
        
        // Analyze code
        async function analyzeCode() {
            const code = document.getElementById('codeEditor').value;
            const language = document.getElementById('languageSelect').value;
            
            if (!code.trim()) {
                showResult('Please paste some code to review.', 'error');
                return;
            }
            
            // Show loading
            document.getElementById('loadingIndicator').classList.add('active');
            document.getElementById('resultsContainer').innerHTML = '<div class="result-empty">⏳ Analyzing...</div>';
            
            try {
                const formData = new FormData();
                formData.append('action', 'analyze');
                formData.append('code', code);
                formData.append('language', language);
                
                const response = await fetch('reviewbot.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    displayResults(data);
                    addChatMessage('bot', '✅ Analysis complete! Score: ' + data.score + '/100');
                } else {
                    showResult('❌ Error: ' + data.message, 'error');
                }
            } catch (error) {
                showResult('❌ Network error. Please try again.', 'error');
            } finally {
                document.getElementById('loadingIndicator').classList.remove('active');
            }
        }
        
        // Display results
        function displayResults(data) {
            let html = '';
            
            // Score card
            let scoreClass = 'score-good';
            if (data.score < 60) scoreClass = 'score-danger';
            else if (data.score < 80) scoreClass = 'score-warning';
            
            html += `
                <div class="score-card ${scoreClass}">
                    <div class="score">${data.score}/100</div>
                    <div class="status">${data.status}</div>
                    <div style="font-size: 8px; margin-top: 5px; color: #7a7090;">
                        📄 ${data.line_count} lines · ${data.language}
                    </div>
                </div>
            `;
            
            // Issues
            if (data.issues && data.issues.length > 0) {
                html += `<h3 style="font-size: 9px; margin: 10px 0 5px; color: #f44336;">⚠️ Issues Found (${data.issues.length})</h3>
                        <ul class="issue-list">`;
                data.issues.forEach(issue => {
                    html += `<li class="issue-error"><span class="issue-icon">🔴</span> ${escapeHtml(issue)}</li>`;
                });
                html += `</ul>`;
            } else {
                html += `<div style="padding: 10px; background: rgba(76,175,80,0.1); border-radius: 8px; margin: 10px 0; font-size: 9px; color: #4caf50;">
                    ✅ No issues found! Great code!
                </div>`;
            }
            
            // Suggestions
            if (data.suggestions && data.suggestions.length > 0) {
                html += `<h3 style="font-size: 9px; margin: 10px 0 5px; color: #4caf50;">💡 Suggestions</h3>
                        <ul class="suggestion-list">`;
                data.suggestions.forEach(suggestion => {
                    html += `<li>💡 ${escapeHtml(suggestion)}</li>`;
                });
                html += `</ul>`;
            }
            
            document.getElementById('resultsContainer').innerHTML = html;
        }
        
        // Show error result
        function showResult(message, type) {
            document.getElementById('resultsContainer').innerHTML = `
                <div class="result-empty" style="color: ${type === 'error' ? '#f44336' : '#7a7090'};">
                    <span class="big-emoji">${type === 'error' ? '❌' : 'ℹ️'}</span>
                    <p>${escapeHtml(message)}</p>
                </div>
            `;
        }
        
        // Save review
        async function saveReview() {
            const code = document.getElementById('codeEditor').value;
            const results = document.getElementById('resultsContainer').innerHTML;
            
            if (!code.trim()) {
                alert('Please add some code before saving.');
                return;
            }
            
            try {
                const formData = new FormData();
                formData.append('action', 'save_review');
                formData.append('code', code);
                formData.append('review', results);
                
                const response = await fetch('reviewbot.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    addChatMessage('bot', '✅ ' + data.message);
                } else {
                    alert('Error saving review: ' + data.message);
                }
            } catch (error) {
                alert('Network error saving review.');
            }
        }
        
        // Chat functions
        async function sendChat() {
            const input = document.getElementById('chatInput');
            const message = input.value.trim();
            
            if (!message) return;
            
            // Add user message
            addChatMessage('user', message);
            input.value = '';
            
            try {
                const formData = new FormData();
                formData.append('action', 'chat');
                formData.append('message', message);
                
                const response = await fetch('reviewbot.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    addChatMessage('bot', data.response);
                } else {
                    addChatMessage('bot', '❌ Sorry, I encountered an error. Please try again.');
                }
            } catch (error) {
                addChatMessage('bot', '❌ Network error. Please check your connection.');
            }
        }
        
        // Add chat message
        function addChatMessage(type, message) {
            const container = document.getElementById('chatMessages');
            const div = document.createElement('div');
            div.className = 'chat-message ' + type;
            
            const timestamp = new Date().toLocaleTimeString();
            
            div.innerHTML = `
                ${escapeHtml(message)}
                <span class="timestamp">${timestamp}</span>
            `;
            
            container.appendChild(div);
            container.scrollTop = container.scrollHeight;
        }
        
        // Clear editor
        function clearEditor() {
            if (confirm('Clear the code editor?')) {