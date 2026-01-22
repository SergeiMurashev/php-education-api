<?php

require __DIR__ . "/../src/helpers.php";
require __DIR__ . "/../src/db.php";
require __DIR__ . "/../src/jwt.php";

$config = require __DIR__ . "/../src/config.php";
$pdo = db();

$path = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
$method = $_SERVER["REQUEST_METHOD"];

// auth middleware
$token = getBearerToken();
if (!$token) {
    jsonFail("missing bearer token", 401);
}

try {
    $claims = jwt_decode($token, $config["jwt_secret"]);
} catch (Exception $e) {
    jsonFail("invalid token: " . $e->getMessage(), 401);
}

$userId = $claims["sub"] ?? null;
if (!$userId) {
    jsonFail("invalid token payload", 401);
}

// GET /api/posts
if ($path === "/api/posts" && $method === "GET") {
    $stmt = $pdo->query("
        SELECT id, user_id, title, body, created_at, updated_at
        FROM posts
        WHERE deleted_at IS NULL
        ORDER BY created_at DESC
    ");
    jsonResponse($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

// POST /api/posts (user_id берём из токена!)
if ($path === "/api/posts" && $method === "POST") {
    $data = readJsonBody();

    $title = trim($data["title"] ?? "");
    $body  = trim($data["body"] ?? "");

    if ($title === "" || $body === "") {
        jsonFail("title and body are required", 422);
    }

    $stmt = $pdo->prepare("
        INSERT INTO posts (user_id, title, body)
        VALUES (:user_id, :title, :body)
        RETURNING id, user_id, title, body, created_at, updated_at
    ");
    $stmt->execute([
        ":user_id" => $userId,
        ":title" => $title,
        ":body" => $body,
    ]);

    jsonResponse($stmt->fetch(PDO::FETCH_ASSOC), 201);
    exit;
}

// GET /api/posts/{id}
if (preg_match("#^/api/posts/([0-9a-fA-F-]{36})$#", $path, $m) && $method === "GET") {
    $postId = $m[1];

    $stmt = $pdo->prepare("
        SELECT id, user_id, title, body, created_at, updated_at, deleted_at
        FROM posts
        WHERE id = :id
        LIMIT 1
    ");
    $stmt->execute([":id" => $postId]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$post) {
        jsonFail("post not found", 404);
    }

    jsonResponse($post);
    exit;
}

// PUT /api/posts/{id} (только владелец)
if (preg_match("#^/api/posts/([0-9a-fA-F-]{36})$#", $path, $m) && $method === "PUT") {
    $postId = $m[1];
    $data = readJsonBody();

    $title = isset($data["title"]) ? trim($data["title"]) : null;
    $body  = isset($data["body"]) ? trim($data["body"]) : null;

    if ($title === null && $body === null) {
        jsonFail("nothing to update", 422);
    }

    // обновляем только если post принадлежит текущему user
    $stmt = $pdo->prepare("
        UPDATE posts
        SET title = COALESCE(:title, title),
            body  = COALESCE(:body, body),
            updated_at = NOW()
        WHERE id = :id AND user_id = :user_id AND deleted_at IS NULL
        RETURNING id, user_id, title, body, created_at, updated_at
    ");
    $stmt->execute([
        ":id" => $postId,
        ":user_id" => $userId,
        ":title" => $title,
        ":body" => $body,
    ]);

    $post = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$post) {
        jsonFail("post not found or forbidden", 404);
    }

    jsonResponse($post);
    exit;
}

// DELETE /api/posts/{id} (soft delete + только владелец)
if (preg_match("#^/api/posts/([0-9a-fA-F-]{36})$#", $path, $m) && $method === "DELETE") {
    $postId = $m[1];

    $stmt = $pdo->prepare("
        UPDATE posts
        SET deleted_at = NOW(),
            updated_at = NOW()
        WHERE id = :id AND user_id = :user_id AND deleted_at IS NULL
        RETURNING id
    ");
    $stmt->execute([
        ":id" => $postId,
        ":user_id" => $userId,
    ]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        jsonFail("post not found or forbidden", 404);
    }

    jsonResponse(["ok" => true]);
    exit;
}

jsonFail("Not found", 404);