<?php

$artisan = realpath(__DIR__ . '/../artisan');

if (!$artisan || !file_exists($artisan)) {
    die("Artisan file not found.");
}

echo "<pre>";
echo shell_exec("php $artisan storage:link 2>&1");
echo "</pre>";