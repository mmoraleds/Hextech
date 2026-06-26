<?php
// ============================================================
//  Academon · ReviewBot - Course Review Assistant
//  AI-powered chatbot for all academic subjects
// ============================================================

require_once __DIR__ . '/auth_guard.php';
require_once __DIR__ . '/config.php';

// Get user info
$username = htmlspecialchars($current_user['username']);
$user_id = $current_user['id'];

// ============================================================
//  AI API Configuration
// ============================================================

// Option 1: OpenAI API (ChatGPT)
define('OPENAI_API_KEY', ''); // Add your API key here
define('OPENAI_API_URL', 'https://api.openai.com/v1/chat/completions');

// Option 2: Google Gemini API
define('GEMINI_API_KEY', ''); // Add your API key here
define('GEMINI_API_URL', 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent');

// Option 3: Simulated AI (fallback)
define('USE_SIMULATED_AI', true);

// ============================================================
//  AI Chat Function - Course Review Focus
// ============================================================

function getAIChatResponse($message, $user_id, $username) {
    // Try OpenAI first if API key is set
    if (defined('OPENAI_API_KEY') && OPENAI_API_KEY !== '') {
        $response = getOpenAIResponse($message, $username);
        if ($response) return $response;
    }
    
    // Try Gemini if API key is set
    if (defined('GEMINI_API_KEY') && GEMINI_API_KEY !== '') {
        $response = getGeminiResponse($message, $username);
        if ($response) return $response;
    }
    
    // Fallback to simulated AI
    return getSimulatedAIResponse($message, $username);
}

// ============================================================
//  OpenAI API Integration
// ============================================================

function getOpenAIResponse($message, $username) {
    try {
        $system_prompt = "You are Professor ReviewBot, an AI academic assistant for Academon platform. 
        You help students with all academic subjects including:
        - Mathematics (Algebra, Calculus, Geometry, Statistics)
        - Science (Physics, Chemistry, Biology)
        - History (World History, Philippine History)
        - English (Grammar, Literature, Writing)
        - Filipino (Grammar, Literature)
        - Programming (PHP, JavaScript, Python, HTML, CSS)
        - And any other academic subject
        
        You are friendly, encouraging, and educational. You explain concepts clearly and provide examples.
        You help students review topics, understand difficult concepts, and prepare for exams.
        The user's name is " . $username . ".
        
        Respond in a helpful, educational manner with occasional emojis. Keep responses concise but informative.";
        
        $data = [
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                ['role' => 'system', 'content' => $system_prompt],
                ['role' => 'user', 'content' => $message]
            ],
            'temperature' => 0.7,
            'max_tokens' => 500
        ];
        
        $ch = curl_init(OPENAI_API_URL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . OPENAI_API_KEY
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpcode === 200) {
            $result = json_decode($response, true);
            if (isset($result['choices'][0]['message']['content'])) {
                return $result['choices'][0]['message']['content'];
            }
        }
        
        return false;
    } catch (Exception $e) {
        error_log('OpenAI API error: ' . $e->getMessage());
        return false;
    }
}

// ============================================================
//  Google Gemini API Integration
// ============================================================

function getGeminiResponse($message, $username) {
    try {
        $prompt = "You are Professor ReviewBot, an AI academic assistant for Academon platform. 
        You help students with all academic subjects including Mathematics, Science, History, English, Filipino, and Programming.
        You explain concepts clearly and provide examples. You are friendly and encouraging.
        The user's name is " . $username . ".
        
        User question: " . $message;
        
        $data = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ]
        ];
        
        $url = GEMINI_API_URL . '?key=' . GEMINI_API_KEY;
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpcode === 200) {
            $result = json_decode($response, true);
            if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
                return $result['candidates'][0]['content']['parts'][0]['text'];
            }
        }
        
        return false;
    } catch (Exception $e) {
        error_log('Gemini API error: ' . $e->getMessage());
        return false;
    }
}

// ============================================================
//  Simulated AI Responses - All Subjects
// ============================================================

function getSimulatedAIResponse($message, $username) {
    $message = strtolower(trim($message));
    
    // -------- MATH --------
    if (preg_match('/\bmath\b|\balgebra\b|\bcalculus\b|\bgeometry\b|\bstatistics\b|\btrigonometry\b|\bderivative\b|\bintegral\b|\bequation\b/', $message)) {
        return "📐 **Math Help for $username:**\n\n" .
               "I'd be happy to help you with math! Here are some key concepts:\n\n" .
               "**Algebra:**\n" .
               "• Quadratic equations: ax² + bx + c = 0\n" .
               "• Use the quadratic formula: x = (-b ± √(b²-4ac))/2a\n" .
               "• Factoring, polynomials, and linear equations\n\n" .
               "**Calculus:**\n" .
               "• Derivatives: rate of change\n" .
               "• Integrals: area under curves\n" .
               "• Chain rule, product rule, quotient rule\n\n" .
               "**Geometry:**\n" .
               "• Area and perimeter of shapes\n" .
               "• Pythagorean theorem: a² + b² = c²\n" .
               "• Circles, triangles, and polygons\n\n" .
               "What specific math topic would you like to learn about?";
    }
    
    // -------- SCIENCE --------
    if (preg_match('/\bscience\b|\bphysics\b|\bchemistry\b|\bbiology\b|\bchemistry\b|\bcell\b|\batom\b|\bmolecule\b|\bforce\b|\benergy\b/', $message)) {
        return "🔬 **Science Help for $username:**\n\n" .
               "I can help you with all science subjects!\n\n" .
               "**Physics:**\n" .
               "• Newton's Laws of Motion\n" .
               "• Force = Mass × Acceleration (F = ma)\n" .
               "• Energy conservation and work\n" .
               "• Electricity and magnetism\n\n" .
               "**Chemistry:**\n" .
               "• Periodic table and elements\n" .
               "• Chemical reactions and equations\n" .
               "• Acids, bases, and pH scale\n" .
               "• Molecular structure\n\n" .
               "**Biology:**\n" .
               "• Cell structure and function\n" .
               "• DNA and genetics\n" .
               "• Human body systems\n" .
               "• Ecology and ecosystems\n\n" .
               "What science topic would you like to explore?";
    }
    
    // -------- HISTORY --------
    if (preg_match('/\bhistory\b|\bworld war\b|\bphilippine history\b|\bamerican history\b|\beuropean history\b|\bancient\b|\bmedieval\b|\brenaissance\b/', $message)) {
        return "📜 **History Help for $username:**\n\n" .
               "I'd love to explore history with you!\n\n" .
               "**World History:**\n" .
               "• Ancient Civilizations (Egypt, Greece, Rome)\n" .
               "• World Wars I & II (1914-1918, 1939-1945)\n" .
               "• Cold War Era (1947-1991)\n" .
               "• Renaissance and Reformation\n\n" .
               "**Philippine History:**\n" .
               "• Pre-colonial Philippines\n" .
               "• Spanish Colonization (1521-1898)\n" .
               "• Philippine Revolution (1896-1898)\n" .
               "• American Period (1898-1946)\n" .
               "• Post-WWII and Independence\n\n" .
               "What period or event would you like to learn about?";
    }
    
    // -------- ENGLISH --------
    if (preg_match('/\benglish\b|\bgrammar\b|\bliterature\b|\bwriting\b|\bessay\b|\bpoem\b|\bshakespeare\b|\bnovel\b/', $message)) {
        return "📚 **English Help for $username:**\n\n" .
               "I can help you with English!\n\n" .
               "**Grammar:**\n" .
               "• Parts of speech (nouns, verbs, adjectives)\n" .
               "• Tenses (past, present, future)\n" .
               "• Sentence structure and punctuation\n" .
               "• Common grammatical errors to avoid\n\n" .
               "**Literature:**\n" .
               "• Literary devices (metaphor, simile, personification)\n" .
               "• Poetry analysis\n" .
               "• Novel and short story analysis\n" .
               "• Shakespeare and classic works\n\n" .
               "**Writing:**\n" .
               "• Essay structure (introduction, body, conclusion)\n" .
               "• Thesis statements\n" .
               "• Research and citations (APA, MLA)\n" .
               "• Creative writing techniques\n\n" .
               "What specific topic would you like to work on?";
    }
    
    // -------- FILIPINO --------
    if (preg_match('/\bfilipino\b|\btagalog\b|\bbayanihan\b|\bsalita\b|\bpabula\b|\bepiko\b|\btula\b/', $message)) {
        return "🇵🇭 **Filipino Help for $username:**\n\n" .
               "Tara tulungan kita sa Filipino!\n\n" .
               "**Wika at Gramatika:**\n" .
               "• Mga bahagi ng pananalita\n" .
               "• Mga uri ng pangungusap\n" .
               "• Pagbaybay at wastong gamit ng salita\n" .
               "• Mga karaniwang pagkakamali sa Filipino\n\n" .
               "**Panitikan:**\n" .
               "• Mga pabula at epiko\n" .
               "• Tula at awit\n" .
               "• Maikling kwento at nobela\n" .
               "• Florante at Laura, Noli Me Tangere\n\n" .
               "**Kultura:**\n" .
               "• Mga tradisyon at kaugalian\n" .
               "• Bayanihan at kultura ng Pilipino\n" .
               "• Mga pista at selebrasyon\n\n" .
               "Anong paksa ang gusto mong pag-aralan?";
    }
    
    // -------- PROGRAMMING --------
    if (preg_match('/\bprogramming\b|\bcode\b|\bphp\b|\bjavascript\b|\bpython\b|\bhtml\b|\bcss\b|\bweb\b|\bdeveloper\b/', $message)) {
        return "💻 **Programming Help for $username:**\n\n" .
               "I can help you with programming!\n\n" .
               "**PHP:**\n" .
               "• Variables, arrays, and functions\n" .
               "• Database connections (PDO, MySQLi)\n" .
               "• Security best practices\n" .
               "• Building web applications\n\n" .
               "**JavaScript:**\n" .
               "• DOM manipulation\n" .
               "• Events and callbacks\n" .
               "• Asynchronous programming (async/await)\n" .
               "• React and Vue.js\n\n" .
               "**Python:**\n" .
               "• Data types and structures\n" .
               "• Functions and classes\n" .
               "• File handling and data processing\n" .
               "• Web frameworks (Django, Flask)\n\n" .
               "What language or concept would you like to learn?";
    }
    
    // -------- EXAM PREP --------
    if (preg_match('/\bexam\b|\btest\b|\bquiz\b|\breview\b|\bstudy\b|\bprepare\b/', $message)) {
        return "🎯 **Exam Preparation Tips for $username:**\n\n" .
               "Here are some effective study strategies:\n\n" .
               "📝 **Study Techniques:**\n" .
               "• Pomodoro method (25 min study, 5 min break)\n" .
               "• Active recall - test yourself regularly\n" .
               "• Spaced repetition - review material over time\n" .
               "• Mind maps and visual learning\n\n" .
               "📚 **Subject-Specific:**\n" .
               "• Math: Practice problems daily\n" .
               "• Science: Understand concepts, not just memorize\n" .
               "• History: Create timelines and connections\n" .
               "• Languages: Read, write, and speak regularly\n\n" .
               "💡 **Tips:**\n" .
               "• Get enough sleep before exams\n" .
               "• Stay hydrated and eat well\n" .
               "• Practice with past exam papers\n" .
               "• Join study groups for discussion\n\n" .
               "What subject are you preparing for? I can give specific advice!";
    }
    
    // -------- GENERAL HELP --------
    if (preg_match('/\bhelp\b|\bhow\b|\bwhat\b|\bguide\b|\btip\b|\blearn\b/', $message)) {
        return "🤖 **Hello $username! I'm Professor ReviewBot!**\n\n" .
               "I'm your AI academic assistant here to help you with all subjects!\n\n" .
               "📚 **I can help you with:**\n\n" .
               "• 📐 **Mathematics** - Algebra, Calculus, Geometry\n" .
               "• 🔬 **Science** - Physics, Chemistry, Biology\n" .
               "• 📜 **History** - World History, Philippine History\n" .
               "• 📝 **English** - Grammar, Literature, Writing\n" .
               "• 🇵🇭 **Filipino** - Wika, Panitikan, Kultura\n" .
               "• 💻 **Programming** - PHP, JavaScript, Python, HTML, CSS\n" .
               "• 📚 **Exam Prep** - Study tips and strategies\n\n" .
               "Just ask me anything about your subjects! What would you like to learn today?";
    }
    
    // -------- GREETINGS --------
    if (preg_match('/\bhi\b|\bhello\b|\bhey\b|\bgood\s*(morning|afternoon|evening)\b/', $message)) {
        $greetings = [
            "👋 Hello $username! I'm Professor ReviewBot, your academic assistant. What subject would you like to review today?",
            "🤖 Hi $username! Ready to learn? I can help with Math, Science, History, English, Filipino, and more!",
            "👋 Hey $username! How can I help you with your studies today?",
            "📚 Hello $username! I'm here to help you understand difficult concepts and prepare for exams!"
        ];
        return $greetings[array_rand($greetings)];
    }
    
    // -------- THANK YOU --------
    if (preg_match('/\bthank\b|\bthanks\b|\bty\b/', $message)) {
        return "🙌 You're welcome, $username! I'm always here to help you learn and grow.\n\n" .
               "📚 Remember: Every expert was once a beginner. Keep asking questions and stay curious!\n\n" .
               "What other subjects would you like to explore?";
    }
    
    // -------- MOTIVATIONAL --------
    if (preg_match('/\bmotivate\b|\binspire\b|\bencourage\b|\bgive up\b|\bhard\b|\bdifficult\b/', $message)) {
        return "🌟 **Stay Motivated, $username!**\n\n" .
               "• Every master was once a beginner\n" .
               "• Mistakes are proof that you're trying\n" .
               "• Learning is a journey, not a destination\n" .
               "• You are capable of amazing things\n" .
               "• One step at a time, you'll get there\n\n" .
               "💪 **Remember:** The struggle you're facing today is developing the strength you need for tomorrow.\n\n" .
               "What subject are you working on? Let me help you understand it better!";
    }
    
    // -------- FUN FACTS --------
    if (preg_match('/\bfact\b|\btrivia\b|\bdid you know\b/', $message)) {
        $facts = [
            "📚 **Did you know?** The Great Wall of China is over 13,000 miles long and was built over 2,000 years!",
            
            "🔬 **Science Fact:** The human body has about 37 trillion cells, each containing DNA that would stretch over 6 feet if unraveled!",
            
            "📐 **Math Fact:** The number Pi (π) has been calculated to over 31 trillion digits!",
            
            "📝 **English Fact:** The word 'set' has the most definitions in the English language - over 400!",
            
            "🇵🇭 **Philippine Fact:** The Philippines has over 7,600 islands and is the world's third-largest English-speaking country!",
            
            "💻 **Tech Fact:** The first computer virus was created in 1983 and was called 'Elk Cloner'!",
            
            "📜 **History Fact:** The shortest war in history was between Britain and Zanzibar in 1896 - it lasted only 38 minutes!"
        ];
        return "🤖 " . $facts[array_rand($facts)] . "\n\n" .
               "Would you like to learn more about this topic?";
    }
    
    // -------- SPECIFIC SUBJECT REQUESTS --------
    if (preg_match('/\bmath\b|\balgebra\b|\bgeometry\b/', $message)) {
        return "📐 **Math Help:**\n\n" .
               "I can help you understand mathematical concepts step by step.\n\n" .
               "Try asking me:\n" .
               "• 'Explain quadratic equations'\n" .
               "• 'How to solve for x'\n" .
               "• 'Geometry formulas'\n" .
               "• 'Derivatives and integrals'\n" .
               "• 'Statistics and probability'\n\n" .
               "What math topic would you like to explore?";
    }
    
    if (preg_match('/\bscience\b|\bphysics\b|\bchemistry\b|\bbiology\b/', $message)) {
        return "🔬 **Science Help:**\n\n" .
               "I can help you understand scientific concepts and theories.\n\n" .
               "Try asking me:\n" .
               "• 'Newton's laws of motion'\n" .
               "• 'Chemical reactions'\n" .
               "• 'Cell division and genetics'\n" .
               "• 'The periodic table'\n" .
               "• 'Energy and thermodynamics'\n\n" .
               "What science topic interests you?";
    }
    
    if (preg_match('/\bhistory\b|\bphilippine\b|\bworld war\b/', $message)) {
        return "📜 **History Help:**\n\n" .
               "I can help you understand historical events and their significance.\n\n" .
               "Try asking me:\n" .
               "• 'World War II timeline'\n" .
               "• 'Philippine revolution'\n" .
               "• 'Ancient civilizations'\n" .
               "• 'Cold War history'\n" .
               "• 'Spanish colonization in the Philippines'\n\n" .
               "What historical period interests you?";
    }
    
    if (preg_match('/\benglish\b|\bgrammar\b|\bliterature\b/', $message)) {
        return "📝 **English Help:**\n\n" .
               "I can help you improve your English skills.\n\n" .
               "Try asking me:\n" .
               "• 'Grammar rules'\n" .
               "• 'Essay writing tips'\n" .
               "• 'Literary analysis'\n" .
               "• 'Poetry interpretation'\n" .
               "• 'Creative writing techniques'\n\n" .
               "What English topic would you like to work on?";
    }
    
    if (preg_match('/\bfilipino\b/', $message)) {
        return "🇵🇭 **Filipino Help:**\n\n" .
               "Tulungan kita sa Filipino!\n\n" .
               "Try asking me:\n" .
               "• 'Wastong gamit ng salita'\n" .
               "• 'Mga bahagi ng pananalita'\n" .
               "• 'Pagsusulat ng sanaysay'\n" .
               "• 'Pabula at epiko'\n" .
               "• 'Kultura at tradisyon'\n\n" .
               "Anong paksa sa Filipino ang gusto mong pag-aralan?";
    }
    
    // -------- DEFAULT RESPONSES --------
    $responses = [
        "🤖 I'm Professor ReviewBot! I can help you with Math, Science, History, English, Filipino, and Programming.\n\nWhat subject would you like to review today?",
        
        "📚 Did you know? I can help with ALL academic subjects! Just ask me about Math, Science, History, English, Filipino, or Programming.\n\nWhat would you like to learn?",
        
        "🎓 Hi $username! I'm your academic assistant. I can explain complex topics, help with homework, and prepare you for exams.\n\nWhat subject are you studying?",
        
        "💡 **Learning Tip:** The best way to learn is to teach others. Try explaining concepts to someone else to reinforce your understanding!\n\nWhat topic can I help you with?",
        
        "🌟 **Success Tip:** Consistency is key! Study a little every day rather than cramming all at once.\n\nWhat subject would you like to review?"
    ];
    
    return $responses[array_rand($responses)];
}

// ============================================================
//  Handle AJAX Requests
// ============================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    $action = $_POST['action'];
    $response = ['success' => false, 'message' => 'Invalid action'];
    
    switch ($action) {
        case 'chat':
            $message = $_POST['message'] ?? '';
            if (empty($message)) {
                $response = ['success' => false, 'message' => 'No message provided'];
            } else {
                // Store user message
                try {
                    $db = get_db();
                    $stmt = $db->prepare('INSERT INTO reviewbot_chat (user_id, message, is_bot, created_at) VALUES (?, ?, 0, NOW())');
                    $stmt->execute([$user_id, $message]);
                } catch (PDOException $e) {
                    error_log('ReviewBot chat storage error: ' . $e->getMessage());
                }
                
                // Get AI response
                $ai_response = getAIChatResponse($message, $user_id, $username);
                
                // Store bot response
                try {
                    $db = get_db();
                    $stmt = $db->prepare('INSERT INTO reviewbot_chat (user_id, message, is_bot, created_at) VALUES (?, ?, 1, NOW())');
                    $stmt->execute([$user_id, $ai_response]);
                } catch (PDOException $e) {
                    error_log('ReviewBot response storage error: ' . $e->getMessage());
                }
                
                $response = [
                    'success' => true,
                    'response' => $ai_response
                ];
            }
            break;
        case 'get_history':
            $response = getChatHistory($user_id);
            break;
    }
    
    echo json_encode($response);
    exit;
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>ReviewBot · Course Review Assistant</title>
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
            max-width: 1000px;
            margin: 0 auto;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(255,241,203,0.92);
            border-radius: 20px;
            padding: 20px 30px;
            margin-bottom: 30px;
            border: 3px solid rgba(255,215,100,0.8);
            flex-wrap: wrap;
            gap: 15px;
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
            flex-wrap: wrap;
        }
        
        .header-actions .user-name {
            font-size: 9px;
            color: #5a4e70;
        }
        
        .api-status {
            font-size: 7px;
            color: #4caf50;
            padding: 4px 10px;
            background: rgba(76, 175, 80, 0.15);
            border-radius: 20px;
            display: inline-block;
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
        
        /* Subject Quick Links */
        .subject-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .subject-btn {
            background: rgba(255,241,203,0.92);
            border: 2px solid rgba(255,215,100,0.6);
            border-radius: 15px;
            padding: 12px 10px;
            text-align: center;
            font-family: 'Press Start 2P', monospace;
            font-size: 8px;
            color: #1e1a2b;
            cursor: pointer;
            transition: 0.15s;
            text-decoration: none;
        }
        
        .subject-btn:hover {
            transform: translateY(-3px);
            background: rgba(255,241,203,0.98);
            box-shadow: 0 6px 16px rgba(0,0,0,0.15);
        }
        
        .subject-btn .emoji {
            font-size: 20px;
            display: block;
            margin-bottom: 5px;
        }
        
        /* Chat Panel */
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
        
        .chat-container {
            display: flex;
            flex-direction: column;
            height: 550px;
        }
        
        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 15px;
            background: rgba(255,255,255,0.3);
            border-radius: 12px;
            margin-bottom: 10px;
            min-height: 400px;
            max-height: 450px;
        }
        
        .chat-message {
            margin-bottom: 12px;
            padding: 12px 16px;
            border-radius: 12px;
            font-size: 8px;
            line-height: 2;
            max-width: 85%;
            word-wrap: break-word;
            white-space: pre-wrap;
        }
        
        .chat-message.user {
            background: #8f8df4;
            color: white;
            margin-left: auto;
            border-bottom-right-radius: 4px;
        }
        
        .chat-message.bot {
            background: rgba(30, 26, 43, 0.08);
            color: #1e1a2b;
            margin-right: auto;
            border-bottom-left-radius: 4px;
        }
        
        .chat-message .timestamp {
            font-size: 6px;
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
        
        /* Loading */
        .typing-indicator {
            display: none;
            padding: 10px 16px;
            margin-bottom: 12px;
            border-radius: 12px;
            background: rgba(30, 26, 43, 0.08);
            color: #1e1a2b;
            font-size: 8px;
            margin-right: auto;
            max-width: 85%;
        }
        
        .typing-indicator.active {
            display: block;
        }
        
        .typing-dots {
            display: inline-block;
            animation: typing 1.4s infinite;
        }
        
        @keyframes typing {
            0%, 20% { content: '.'; }
            40% { content: '..'; }
            60% { content: '...'; }
            80%, 100% { content: ''; }
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .header { flex-direction: column; text-align: center; }
            .header-actions { justify-content: center; }
            .subject-grid { grid-template-columns: repeat(3, 1fr); }
        }
        
        @media (max-width: 480px) {
            .panel { padding: 15px; }
            .subject-grid { grid-template-columns: repeat(2, 1fr); }
            .chat-message { font-size: 7px; padding: 8px 12px; }
            .chat-input { font-size: 7px; padding: 8px 12px; }
            .chat-send { font-size: 7px; padding: 8px 12px; }
            .header h1 { font-size: 11px; }
        }
        
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: rgba(0,0,0,0.05); border-radius: 10px; }
        ::-webkit-scrollbar-thumb { background: #8f8df4; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #7a78d6; }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>
                <span class="emoji">👨‍🏫</span> REVIEWBOT
                <span style="font-size: 8px; color: #7a7090;">Course Review Assistant</span>
            </h1>
            <div class="header-actions">
                <span class="user-name">👤 <?php echo $username; ?></span>
                <span class="api-status">🧠 AI: <?php echo defined('OPENAI_API_KEY') && OPENAI_API_KEY !== '' ? 'OpenAI' : (defined('GEMINI_API_KEY') && GEMINI_API_KEY !== '' ? 'Gemini' : 'Smart AI'); ?></span>
                <a href="dashboard.php" class="back-btn">⬅ BACK</a>
            </div>
        </div>
        
        <!-- Subject Quick Links -->
        <div class="subject-grid">
            <button class="subject-btn" onclick="quickSubject('math')">
                <span class="emoji">📐</span> MATH
            </button>
            <button class="subject-btn" onclick="quickSubject('science')">
                <span class="emoji">🔬</span> SCIENCE
            </button>
            <button class="subject-btn" onclick="quickSubject('history')">
                <span class="emoji">📜</span> HISTORY
            </button>
            <button class="subject-btn" onclick="quickSubject('english')">
                <span class="emoji">📝</span> ENGLISH
            </button>
            <button class="subject-btn" onclick="quickSubject('filipino')">
                <span class="emoji">🇵🇭</span> FILIPINO
            </button>
            <button class="subject-btn" onclick="quickSubject('programming')">
                <span class="emoji">💻</span> PROGRAMMING
            </button>
        </div>
        
        <!-- Chat Panel -->
        <div class="panel">
            <h2>💬 CHAT WITH PROFESSOR REVIEWBOT</h2>
            <div class="chat-container">
                <div class="chat-messages" id="chatMessages">
                    <div class="chat-message bot">
                        👋 Hi <?php echo $username; ?>! I'm Professor ReviewBot, your academic assistant. 🎓
                        
                        I can help you with ALL subjects:
                        📐 Math · 🔬 Science · 📜 History · 📝 English · 🇵🇭 Filipino · 💻 Programming
                        
                        Just ask me anything about your courses!
                        <span class="timestamp">Just now</span>
                    </div>
                </div>
                <div class="typing-indicator" id="typingIndicator">
                    🤖 Thinking <span class="typing-dots">...</span>
                </div>
                <div class="chat-input-group">
                    <input type="text" class="chat-input" id="chatInput" placeholder="Ask about any subject..." onkeypress="if(event.key==='Enter') sendChat()">
                    <button class="chat-send" onclick="sendChat()">➤ SEND</button>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // ============================================================
        // ReviewBot JavaScript
        // ============================================================
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        function quickSubject(subject) {
            const messages = {
                'math': 'I need help with math. Can you explain algebra?',
                'science': 'I need help with science. Can you explain physics?',
                'history': 'I need help with history. Can you explain world history?',
                'english': 'I need help with English grammar.',
                'filipino': 'Tulungan mo ako sa Filipino.',
                'programming': 'I need help with programming.'
            };
            
            document.getElementById('chatInput').value = messages[subject] || 'I need help with this subject.';
            sendChat();
        }
        
        async function sendChat() {
            const input = document.getElementById('chatInput');
            const message = input.value.trim();
            
            if (!message) return;
            
            addChatMessage('user', message);
            input.value = '';
            
            // Show typing indicator
            document.getElementById('typingIndicator').classList.add('active');
            document.getElementById('chatMessages').scrollTop = document.getElementById('chatMessages').scrollHeight;
            
            try {
                const formData = new FormData();
                formData.append('action', 'chat');
                formData.append('message', message);
                
                const response = await fetch('reviewbot.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                document.getElementById('typingIndicator').classList.remove('active');
                
                if (data.success) {
                    addChatMessage('bot', data.response);
                } else {
                    addChatMessage('bot', '❌ Sorry, I encountered an error. Please try again.');
                }
            } catch (error) {
                document.getElementById('typingIndicator').classList.remove('active');
                addChatMessage('bot', '❌ Network error. Please check your connection.');
            }
        }
        
        function addChatMessage(type, message) {
            const container = document.getElementById('chatMessages');
            const div = document.createElement('div');
            div.className = 'chat-message ' + type;
            
            const timestamp = new Date().toLocaleTimeString();
            const formattedMessage = escapeHtml(message).replace(/\n/g, '<br>');
            
            div.innerHTML = `${formattedMessage}<span class="timestamp">${timestamp}</span>`;
            container.appendChild(div);
            container.scrollTop = container.scrollHeight;
        }
    </script>
</body>
</html>