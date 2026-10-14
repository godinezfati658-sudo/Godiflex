<?php

function database(): PDO
{
    static $connection;

    if ($connection instanceof PDO) {
        return $connection;
    }

    $host = getenv('GODIFLEX_DB_HOST') ?: '127.0.0.1';
    $name = getenv('GODIFLEX_DB_NAME') ?: 'godiflex';
    $user = getenv('GODIFLEX_DB_USER') ?: 'root';
    $password = getenv('GODIFLEX_DB_PASSWORD') ?: '';

    try {
        $connection = new PDO(
            "mysql:host={$host};dbname={$name};charset=utf8mb4",
            $user,
            $password,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
    } catch (PDOException $exception) {
        throw new RuntimeException(
            'GODIFLEX could not connect to MySQL. Start MySQL in XAMPP, import database.sql in phpMyAdmin, and check the database settings.',
            0,
            $exception
        );
    }

    return $connection;
}
