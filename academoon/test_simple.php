<?php
// test_simple.php - Simple test to check if PHP is working

echo "=== PHP is working! ===\n\n";

// Check your API key
$api_key = 'AQ.Ab8RN6I0xhFB8vjVLYkNzD-BKICWU4e_4ZWn9wLIIXvMtO0fmw';
echo "API Key: " . substr($api_key, 0, 20) . "...\n\n";

// Test 1: Check cURL
echo "Test 1: Checking cURL...\n";
if (function_exists('curl_version')) {
    $version = curl_version();
    echo "✅ cURL is enabled (Version: " . $version['version'] . ")\n\n";
} else {
    echo "❌ cURL is NOT enabled!\n";
    echo "To fix: Open C:\\xampp\\php\\php.ini and remove ; from ;extension=curl\n\n";
    exit;
}

// Test 2: Try to call Gemini API with Bearer Token
echo "Test 2: Calling Gemini API with Bearer Token...\n";

$url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent';

$payload = [
    'contents' => [
        [
            'parts' => [
                ['text' => 'Say "OK"']
            ]
        ]
    ]
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $api_key
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) {
    echo "❌ cURL Error: " . $curlError . "\n";
    exit;
}

if ($httpCode === 200) {
    $data = json_decode($response, true);
    if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
        echo "✅ SUCCESS! Your API key works!\n";
        echo "Response: " . $data['candidates'][0]['content']['parts'][0]['text'] . "\n\n";
        echo "🎉 Your setup is correct! Now test reviewbot.php\n";
    } else {
        echo "❌ Unexpected response\n";
        print_r($data);
    }
} else {
    echo "❌ FAILED (HTTP " . $httpCode . ")\n";
    $errorData = json_decode($response, true);
    if (isset($errorData['error']['message'])) {
        echo "Error: " . $errorData['error']['message'] . "\n";
    } else {
        echo "Response: " . substr($response, 0, 200) . "...\n";
    }
}
?>