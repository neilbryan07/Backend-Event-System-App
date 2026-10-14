<?php

chdir(dirname(__DIR__));

echo shell_exec('php artisan cache:clear 2>&1');
echo shell_exec('php artisan config:clear 2>&1');
echo shell_exec('php artisan route:clear 2>&1');
echo shell_exec('php artisan view:clear 2>&1');
echo shell_exec('php artisan optimize:clear 2>&1');

