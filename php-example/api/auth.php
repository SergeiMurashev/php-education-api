<?php

require __DIR__ . "/../src/helpers.php";
require __DIR__ . "/../src/db.php";
require __DIR__ . "/../src/jwt.php";

$config = require __DIR__ . "/../src/config.php";
$pdo = db();

function issueTokens(PDO $pdo, array $user, array $config): array
{
    $now = time();

    $accessTtl  = (int)($config["access_ttl_seconds"] ?? 900);      // 15 мин
    $refreshTtl = (int)($config["refresh_ttl_seconds"] ?? 604800);  // 7 дней

    $accessPayload = [
        "sub"   => $user["id"],
        "email" => $user["email"],
        "role"  => $user["role"] ?? "user",
        "iat"   => $now,
        "exp"   => $now + $accessTtl,
    ];

    $accessToken = jwt_encode($accessPayload, $config["jwt_secret"]);

    // refresh token = random string
    $refreshToken = bin2hex(random_bytes(32));
    $refreshHash  = hash("sha256", $refreshToken);

    $stmt = $pdo->prepare("
        INSERT INTO refresh_tokens (user_id, token_hash, expires_at)
        VALUES (:user_id, :token_hash, NOW() + (:ttl || ' seconds')::interval)
    ");
    $stmt->execute([
        ":user_id"    => $user["id"],
        ":token_hash" => $refreshHash,
        ":ttl"        => (string)$refreshTtl,
    ]);

    return [
        "access_token"  => $accessToken,
        "refresh_token" => $refreshToken,
    ];
}

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
            RETURNING id, email, name, role
        ");
        $stmt->execute([
            ":email" => $email,
            ":name"  => $name,
            ":hash"  => $hash,
        ]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) {
            jsonFail("failed to create user", 500);
        }

    } catch (PDOException $e) {
        if ($e->getCode() === "23505") {
            jsonFail("email already exists", 409);
        }
        jsonFail("db error", 500);
    }

    $tokens = issueTokens($pdo, $user, $config);

    jsonResponse([
        "tokens" => $tokens,
        "user"   => $user,
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
        SELECT id, email, name, role, password_hash
        FROM users
        WHERE email = :email AND deleted_at IS NULL
        LIMIT 1
    ");
    $stmt->execute([":email" => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($pass, $user["password_hash"])) {
        jsonFail("invalid credentials", 401);
    }

    $tokens = issueTokens($pdo, $user, $config);

    jsonResponse([
        "tokens" => $tokens,
        "user" => [
            "id"    => $user["id"],
            "email" => $user["email"],
            "name"  => $user["name"],
            "role"  => $user["role"],
        ]
    ]);
    exit;
}

// POST /api/auth/refresh
if ($path === "/api/auth/refresh" && $method === "POST") {
    $data = readJsonBody();
    $refreshToken = (string)($data["refresh_token"] ?? "");

    if ($refreshToken === "") {
        jsonFail("refresh_token is required", 422);
    }

    $hash = hash("sha256", $refreshToken);

    $stmt = $pdo->prepare("
        SELECT rt.id, rt.user_id, rt.expires_at, rt.revoked_at,
               u.id as uid, u.email, u.name, u.role
        FROM refresh_tokens rt
        JOIN users u ON u.id = rt.user_id
        WHERE rt.token_hash = :hash
        LIMIT 1
    ");
    $stmt->execute([":hash" => $hash]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        jsonFail("invalid refresh token", 401);
    }

    if ($row["revoked_at"] !== null) {
        jsonFail("refresh token revoked", 401);
    }

    if (strtotime($row["expires_at"]) < time()) {
        jsonFail("refresh token expired", 401);
    }

    // rotation: revoke old refresh token
    $stmt = $pdo->prepare("UPDATE refresh_tokens SET revoked_at = NOW() WHERE id = :id");
    $stmt->execute([":id" => $row["id"]]);

    $user = [
        "id"    => $row["uid"],
        "email" => $row["email"],
        "name"  => $row["name"],
        "role"  => $row["role"],
    ];

    $tokens = issueTokens($pdo, $user, $config);

    jsonResponse([
        "tokens" => $tokens,
        "user"   => $user,
    ]);
    exit;
}

jsonFail("Not found", 404);