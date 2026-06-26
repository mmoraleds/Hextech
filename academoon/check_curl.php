<?php
echo "=== PHP Configuration Check ===\n\n";

// Check CURL
if (function_exists('curl_version')) {
    $version = curl_version();
    echo "✅ CURL is ENABLED\n";
    echo "   Version: " . $version['version'] . "\n";
    echo "   SSL Version: " . $version['ssl_version'] . "\n";
} else {
    echo "❌ CURL is DISABLED\n";
    echo "   To enable CURL in XAMPP:\n";
    echo "   1. Open C:\\xampp\\php\\php.ini\n";
    echo "   2. Find: ;extension=curl\n";
    echo "   3. Remove the semicolon: extension=curl\n";
    echo "   4. Save and restart Apache\n";
}

echo "\n";

// Check if we can connect to Google
echo "Testing connection to Google API...\n";
$ch = curl_init('https://generativelanguage.googleapis.com');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
curl_setopt($ch, CURLOPT_NOBODY, true);
$result = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== false) {
    echo "✅ Can connect to Google API\n";
} else {
    echo "❌ Cannot connect to Google API. Check your internet connection.\n";
}

echo "\n";

// Check PHP version
echo "PHP Version: " . phpversion() . "\n";

// Check if json is enabled
if (function_exists('json_encode')) {
    echo "✅ JSON is ENABLED\n";
} else {
    echo "❌ JSON is DISABLED\n";
}
?>