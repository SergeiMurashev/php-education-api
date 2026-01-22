<?php

require __DIR__ . "/../src/helpers.php";
require __DIR__ . "/../src/db.php";
require __DIR__ . "/../src/jwt.php";

$config = require __DIR__ . "/../src/config.php";
$pdo = db();

$path = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
$method = $_SERVER["REQUEST_METHOD"];

// POST /api/auth/register
if ($path === "/api/auth/register" && $method === "POST") {
    $data = readJsonBody();

    $email = strtolower(trim($data["email"] ?? ""));
    $name  = trim($data["name"] ?? "");
    $pass  = (string)($data["password"] ?? "");

    if ($email === "" || $name === "" || $pass === "") {
        jsonFail("email, name, password are required", 422);
    }

    $hash = password_hash($pass, PASSWORD_BCRYPT);

    try {
        $stmt = $pdo->prepare("
            INSERT INTO users (email, name, password_hash)
            VALUES (:email, :name, :hash)
            RETURNING id, email, name
        ");
        $stmt->execute([
            ":email" => $email,
            ":name" => $name,
            ":hash" => $hash,
        ]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        if ($e->getCode() === "23505") {
            jsonFail("email already exists", 409);
        }
        jsonFail("db error", 500);
    }

    $now = time();
    $payload = [
        "sub" => $user["id"],
        "email" => $user["email"],
        "iat" => $now,
        "exp" => $now + (int)$config["jwt_ttl_seconds"],
    ];

    $token = jwt_encode($payload, $config["jwt_secret"]);

    jsonResponse([
        "token" => $token,
        "user" => $user,
    ], 201);
    exit;
}

// POST /api/auth/login
if ($path === "/api/auth/login" && $method === "POST") {
    $data = readJsonBody();

    $email = strtolower(trim($data["email"] ?? ""));
    $pass  = (string)($data["password"] ?? "");

    if ($email === "" || $pass === "") {
        jsonFail("email and password are required", 422);
    }

    $stmt = $pdo->prepare("
        SELECT id, email, name, password_hash
        FROM users
        WHERE email = :email AND deleted_at IS NULL
        LIMIT 1
    ");
    $stmt->execute([":email" => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($pass, $user["password_hash"])) {
        jsonFail("invalid credentials", 401);
    }

    $now = time();
    $payload = [
        "sub" => $user["id"],
        "email" => $user["email"],
        "iat" => $now,
        "exp" => $now + (int)$config["jwt_ttl_seconds"],
    ];

    $token = jwt_encode($payload, $config["jwt_secret"]);

    jsonResponse([
        "token" => $token,
        "user" => [
            "id" => $user["id"],
            "email" => $user["email"],
            "name" => $user["name"],
        ]
    ]);
    exit;
}

jsonFail("Not found", 404);