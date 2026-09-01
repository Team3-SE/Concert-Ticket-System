<?php
$start = microtime(true);
require __DIR__.'/vendor/autoload.php';
echo "Autoload: " . (microtime(true) - $start) . "\n";
$app = require_once __DIR__.'/bootstrap/app.php';
echo "App created: " . (microtime(true) - $start) . "\n";
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
echo "Bootstrapped: " . (microtime(true) - $start) . "\n";