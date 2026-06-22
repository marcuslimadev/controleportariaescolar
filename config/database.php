<?php
declare(strict_types=1);

function config(): array {
    static $config;
    if ($config === null) {
        $file = __DIR__ . '/config.php';
        if (!is_file($file)) { http_response_code(503); exit('Configure config/config.php a partir de config.example.php.'); }
        $config = require $file;
    }
    return $config;
}

function db(): PDO {
    static $pdo;
    if (!$pdo) {
        $d = config()['db'];
        $pdo = new PDO("mysql:host={$d['host']};port={$d['port']};dbname={$d['name']};charset=utf8mb4", $d['user'], $d['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
    return $pdo;
}

