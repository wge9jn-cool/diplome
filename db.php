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
    if (defined('APP_TIMEZONE') && APP_TIMEZONE !== '') {
        $tz = new DateTimeZone(APP_TIMEZONE);
        $offset = $tz->getOffset(new DateTime('now', $tz));
        $sign = $offset >= 0 ? '+' : '-';
        $offset = abs($offset);
        $pdo->exec(sprintf("SET time_zone = '%s%02d:%02d'", $sign, intdiv($offset, 3600), intdiv($offset % 3600, 60)));
    }
} catch (PDOException $e) {
    die('Ошибка подключения к базе данных');
}

