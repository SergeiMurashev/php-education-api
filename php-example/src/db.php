<?php

function db(): PDO
{
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    $host = getenv("DB_HOST") ?: "postgres";
    $port = getenv("DB_PORT") ?: "5432";
    $db   = getenv("DB_NAME") ?: "royalty_db";
    $user = getenv("DB_USER") ?: "royalty_db";
    $pass = getenv("DB_PASS") ?: "1234";

    $dsn = "pgsql:host=$host;port=$port;dbname=$db";

    $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    return $pdo;
}