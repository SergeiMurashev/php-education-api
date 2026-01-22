<?php

require __DIR__ . "/../src/helpers.php";
require __DIR__ . "/../src/db.php";
require __DIR__ . "/../src/jwt.php";

$config = require __DIR__ . "/../src/config.php";
$pdo = db();

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    jsonFail("Method not allowed", 405);
}

$token = getBearerToken();
if (!$token) {
    jsonFail("missing bearer token", 401);
}

try {
    $claims = jwt_decode($token, $config["jwt_secret"]);
} catch (Exception $e) {
    jsonFail("invalid token", 401);
}

$userId = $claims["sub"] ?? null;
if (!$userId) {
    jsonFail("invalid token payload", 401);
}

$stmt = $pdo->prepare("
    SELECT id, email, name, role, created_at, updated_at
    FROM users
    WHERE id = :id AND deleted_at IS NULL
    LIMIT 1
");
$stmt->execute([":id" => $userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    jsonFail("user not found", 404);
}

jsonResponse(["user" => $user]);