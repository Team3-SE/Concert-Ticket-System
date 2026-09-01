<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$request = \Illuminate\Http\Request::create('/dev/models/methods', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['model' => 'Concert']));

$controller = app(\App\Http\Controllers\Dev\ModelTesterController::class);
$response = $controller->getMethods($request);

echo $response->getContent();