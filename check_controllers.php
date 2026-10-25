<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$controllers = [
    'ConcertController'  => \App\Http\Controllers\ConcertController::class,
    'BookingController'  => \App\Http\Controllers\BookingController::class,
    'PaymentController'  => \App\Http\Controllers\PaymentController::class,
    'TicketController'   => \App\Http\Controllers\TicketController::class,
    'UserController'     => \App\Http\Controllers\UserController::class,
];

foreach ($controllers as $name => $class) {
    $methods = get_class_methods($class);
    $own = array_filter($methods, function($m) use ($class) {
        return (new ReflectionMethod($class, $m))->getDeclaringClass()->getName() === $class;
    });
    echo $name . ': ' . implode(', ', array_values($own)) . PHP_EOL;
}
