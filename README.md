<?php

require __DIR__ . "/../src/helpers.php";
require __DIR__ . "/../src/db.php";
require __DIR__ . "/../src/jwt.php";
$config = require __DIR__ . "/../src/config.php";

$token = getBearerToken();
if (!$token) {
    jsonFail("missing bearer token", 401);
}

try {
    $claims = jwt_decode($token, $config["jwt_secret"]);
} catch (Exception $e) {
    jsonFail("invalid token", 401);
}

if (($claims["role"] ?? null) !== "admin") {
    jsonFail("forbidden", 403);
}

$pdo = db();

$path = $_SERVER["REQUEST_URI"] ?? "";
$method = $_SERVER["REQUEST_METHOD"] ?? "";

// GET /api/users
if ($path === "/api/users" && $method === "GET") {
    $stmt = $pdo->query("SELECT id, email, name, role, created_at FROM users ORDER BY created_at DESC");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    jsonResponse($users);
    exit;
}

// POST /api/users (admin creates employee)
if ($path === "/api/users" && $method === "POST") {
    $data = readJsonBody();

    $email = strtolower(trim($data["email"] ?? ""));
    $name  = trim($data["name"] ?? "");

    if ($email === "" || $name === "") {
        jsonFail("email and name are required", 422);
    }

    $tempPassword = bin2hex(random_bytes(6));
    $hash = password_hash($tempPassword, PASSWORD_BCRYPT);

    try {
        $stmt = $pdo->prepare("
            INSERT INTO users (email, name, password_hash)
            VALUES (:email, :name, :hash)
            RETURNING id, email, name, role, created_at
        ");
        $stmt->execute([
            ':email' => $email,
            ':name'  => $name,
            ':hash'  => $hash,
        ]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) {
            jsonFail('failed to create user', 500);
        }

    } catch (PDOException $e) {
        if ($e->getCode() === '23505') {
            jsonFail('email already exists', 409);
        }
        jsonFail('db error', 500);
    }

    jsonResponse([
        'user' => $user,
        'temporary_password' => $tempPassword,
    ], 201);
    exit;
}

// Other routes and logic below...