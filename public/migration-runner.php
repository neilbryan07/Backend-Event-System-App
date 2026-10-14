<?php

/*************************************************
 * CONFIGURATION
 *************************************************/
// The Laravel Artisan command to run
$artisanCmd = 'php artisan migrate --force';

/*************************************************
 * ENVIRONMENT CHECK (Simplified)
 *************************************************/

// Check if shell_exec is enabled (This check is kept as it is critical for functionality)
if (!function_exists('shell_exec')) {
    http_response_code(500);
    exit('500 Internal Server Error: shell_exec is disabled on this server.');
}

// Determine the Laravel root directory (assuming this script is one level up from the 'artisan' file)
$root = dirname(__DIR__);

// Check if the 'artisan' file exists in the expected location (This check is also kept)
if (!file_exists($root . '/artisan')) {
    http_response_code(500);
    exit('500 Internal Server Error: artisan file not found. Wrong path.');
}

// Change the current directory to the Laravel root to execute artisan
chdir($root);

/*************************************************
 * RUN MIGRATION
 *************************************************/
// '2>&1' redirects standard error (stderr) to standard output (stdout)
$output = shell_exec($artisanCmd . ' 2>&1');

/*************************************************
 * ERROR DETECTION
 *************************************************/
$errors = ['Error', 'exception', 'SQLSTATE', 'failed'];
$statusCode = 200;

// Search the output for common error indicators (case-insensitive)
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
echo " Laravel Migration Runner\n";
echo " Status: SECURITY CHECKS DISABLED\n";
echo " Command: {$artisanCmd}\n";
echo " Timestamp: " . date('Y-m-d H:i:s') . "\n";
echo " Status Code: {$statusCode}\n";
echo "====================================\n\n";

if ($statusCode === 200) {
    echo "🟢 Migration completed successfully\n\n";
} else {
    echo "❌ Migration failed (check output below)\n\n";
}

echo "----------- Artisan Output ----------\n";
echo trim($output) ?: "[ No migrations to run or silent output ]\n";
echo "-------------------------------------\n";