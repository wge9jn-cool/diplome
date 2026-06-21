<?php

require_once __DIR__ . '/config.php';

if (defined('APP_TIMEZONE') && APP_TIMEZONE !== '') {
    date_default_timezone_set(APP_TIMEZONE);
}

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
    if (defined('APP_DB_TIMEZONE') && APP_DB_TIMEZONE !== '') {
        $pdo->exec("SET time_zone = '" . str_replace("'", '', APP_DB_TIMEZONE) . "'");
    }
} catch (PDOException $e) {
    die('Ошибка подключения к базе данных');
}

