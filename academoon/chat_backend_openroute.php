<?php
// ============================================================
//  chat_backend_openrouter.php - OpenRouter API Backend
//  🔑 REPLACE WITH YOUR NEW API KEY (DO NOT SHARE)
// ============================================================

// Enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');

// 🔑 YOUR OPENROUTER API KEY - Get from https://openrouter.ai/keys
// ⚠️ DO NOT paste this key in chat or share it publicly!
$API_KEY = "YOUR_NEW_OPENROUTER_KEY_HERE"; // Replace with your NEW key

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get the request data
$input = json_decode(file_get_contents('php://input'), true);
if (!$input || !isset($input['question']) || !isset($input['subject'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

$question = $input['question'];
$subject = $input['subject'];

// Subject contexts
$subjectContexts = [
    'math' => 'You are a Math professor. Explain mathematical concepts clearly with examples. Use simple language and show step-by-step reasoning. Keep explanations educational and engaging.',
    'science' => 'You are a Science professor. Explain scientific concepts with accurate information. Use examples and relate to real-world applications. Make learning fun and interesting.',
    'history' => 'You are a History professor. Provide historical context with important dates, figures, and significance. Explain causes and effects in an engaging way.',
    'english' => 'You are an English professor. Explain grammar, literature, and language concepts clearly. Provide examples of usage and make language learning enjoyable.',
    'filipino' => 'You are a Filipino professor. Explain Filipino language, culture, and literature. Use both English and Filipino when appropriate. Make learning about Filipino culture engaging.',
    'all' => 'You are a knowledgeable professor. Answer questions across all subjects accurately and helpfully. Keep explanations clear and educational.'
];

$context = $subjectContexts[$subject] ?? $subjectContexts['all'];

// FREE models available on OpenRouter
$models = [
    'llama' => 'meta-llama/llama-3.1-70b-instruct:free',
    'mistral' => 'mistralai/mistral-7b-instruct:free',
    'phi' => 'microsoft/phi-3.5-mini-128k-instruct:free',
    'gemma' => 'google/gemma-2-9b-it:free'
];

// Use Llama by default
$selectedModel = $models['llama'];

// Get model from request if specified
if (isset($input['model']) && isset($models[$input['model']])) {
    $selectedModel = $models[$input['model']];
}

// Prepare the API request
$payload = [
    'model' => $selectedModel,
    'messages' => [
        [
            'role' => 'system',
            'content' => $context
        ],
        [
            'role' => 'user',
            'content' => $question
        ]
    ],
    'temperature' => 0.7,
    'max_tokens' => 1000,
    'top_p' => 0.9
];

// Make the API call to OpenRouter
$ch = curl_init('https://openrouter.ai/api/v1/chat/completions');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $API_KEY,
    'HTTP-Referer: ' . (isset($_SERVER['HTTP_HOST']) ? 'http://' . $_SERVER['HTTP_HOST'] : 'http://localhost'),
    'X-Title: Academon AI'
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

// Handle curl errors
if ($curlError) {
    http_response_code(500);
    echo json_encode(['error' => 'Connection error: ' . $curlError]);
    exit;
}

// Handle API errors
if ($httpCode !== 200) {
    $errorData = json_decode($response, true);
    $errorMessage = 'Unknown API error';
    
    if (json_last_error() === JSON_ERROR_NONE && isset($errorData['error'])) {
        $errorMessage = $errorData['error']['message'] ?? 'Unknown error';
    } elseif (strpos($response, '<html') !== false) {
        $errorMessage = 'Server returned HTML error. Check your internet connection.';
    } else {
        $errorMessage = 'Error: ' . substr($response, 0, 100);
    }
    
    // User-friendly error messages
    if (strpos($errorMessage, 'quota') !== false || strpos($errorMessage, 'exceeded') !== false) {
        $errorMessage = 'API quota exceeded. Please try again later. 💰';
    } elseif (strpos($errorMessage, 'key') !== false || strpos($errorMessage, 'auth') !== false) {
        $errorMessage = 'Invalid API key. Make sure you\'re using the correct OpenRouter key. 🔑';
    } elseif (strpos($errorMessage, 'model') !== false || strpos($errorMessage, 'not found') !== false) {
        $errorMessage = 'Model not available. Please try another model. 🤖';
    }
    
    http_response_code($httpCode);
    echo json_encode(['error' => $errorMessage]);
    exit;
}

// Parse and return the response
$data = json_decode($response, true);

if (isset($data['choices'][0]['message']['content'])) {
    $answer = $data['choices'][0]['message']['content'];
    
    // Format the response
    $answer = nl2br(htmlspecialchars($answer));
    $answer = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $answer);
    $answer = preg_replace('/\*(.*?)\*/', '<em>$1</em>', $answer);
    
    echo json_encode(['response' => $answer]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Unexpected API response format']);
}
?>