<?php
try {
    $redis = new Redis();
    $redis->connect('redis', 6379, 2);
    echo "Redis connected.\n";
} catch (Exception $e) {
    echo "Redis error: " . $e->getMessage() . "\n";
}

try {
    $pdo = new PDO('mysql:host=db;dbname=concert_db', 'concert_user', 'secret', [PDO::ATTR_TIMEOUT => 2]);
    echo "MySQL connected.\n";
} catch (Exception $e) {
    echo "MySQL error: " . $e->getMessage() . "\n";
}
