<?php

function db(): PDO
{
    $host = "127.0.0.1";
    $port = "5440";
    $db   = "royalty_db";
    $user = "royalty_db";
    $pass = "1234";

    $dsn = "pgsql:host=$host;port=$port;dbname=$db";

    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    return $pdo;
}