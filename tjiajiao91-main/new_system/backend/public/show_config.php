<?php
namespace think;

require __DIR__ . '/../vendor/autoload.php';

$app = new App();
$config = $app->config;

header('Content-Type: application/json');
echo json_encode([
    'database' => [
        'hostname' => $config->get('database.connections.mysql.hostname'),
        'database' => $config->get('database.connections.mysql.database'),
        'username' => $config->get('database.connections.mysql.username'),
        'password' => substr($config->get('database.connections.mysql.password'), 0, 3) . '***',
    ]
], JSON_PRETTY_PRINT);
