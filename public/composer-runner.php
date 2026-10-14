<?php

/*************************************************
 * CONFIGURATION
 *************************************************/
// Composer install command (optimized for production)
$composerCmd = 'composer install --no-interaction --prefer-dist --optimize-autoloader';

/*************************************************
 * ENVIRONMENT CHECK
 *************************************************/

// Check if shell_exec is enabled
if (!function_exists('shell_exec')) {
    http_response_code(500);
    exit('500 Internal Server Error: shell_exec is disabled on this server.');
}

// Determine Laravel root directory
$root = dirname(__DIR__);

// Check if composer.json exists
if (!file_exists($root . '/composer.json')) {
    http_response_code(500);
    exit('500 Internal Server Error: composer.json not found. Wrong path.');
}

// Change directory to project root
chdir($root);

/*************************************************
 * RUN COMPOSER INSTALL
 *************************************************/
$output = shell_exec($composerCmd . ' 2>&1');

/*************************************************
 * ERROR DETECTION
 *************************************************/
$errors = ['error', 'exception', 'failed', 'fatal'];
$statusCode = 200;

foreach ($errors as $e) {
    if (stripos($output, $e) !== false) {
        $statusCode = 500;
        break;
    }
}

http_response_code($statusCode);

/*************************************************
 * OUTPUT RESULTS
 *************************************************/
header('Content-Type: text/plain; charset=UTF-8');

echo "====================================\n";
echo " Composer Install Runner\n";
echo " Status: SECURITY CHECKS DISABLED\n";
echo " Command: {$composerCmd}\n";
echo " Timestamp: " . date('Y-m-d H:i:s') . "\n";
echo " Status Code: {$statusCode}\n";
echo "====================================\n\n";

if ($statusCode === 200) {
    echo "🟢 Composer install completed successfully\n\n";
} else {
    echo "❌ Composer install failed (check output below)\n\n";
}

echo "----------- Composer Output ----------\n";
echo trim($output) ?: "[ No output ]\n";
echo "-------------------------------------\n";