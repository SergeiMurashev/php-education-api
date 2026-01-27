<?php

require __DIR__ . '/../src/helpers.php';
require __DIR__ . '/../src/db.php';
require __DIR__ . '/../src/jwt.php';

$config = require __DIR__ . '/../src/config.php';
$pdo = db();

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

$path = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
$method = $_SERVER["REQUEST_METHOD"];

/* GET /api/posts/{postId}/comments */
if (preg_match('#^/api/posts/([0-9a-f-]{36})/comments$#', $path, $m) && $method === 'GET') {
    $postId = $m[1];

    $stmt = $pdo->prepare("
        SELECT c.id, c.content, c.created_at, c.updated_at,
               u.id AS user_id, u.name
        FROM comments c
        JOIN users u ON u.id = c.user_id
        WHERE c.post_id = :post_id AND c.deleted_at IS NULL
        ORDER BY c.created_at ASC
    ");
    $stmt->execute([":post_id" => $postId]);

    jsonResponse($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

/* POST /api/posts/{postId}/comments */
if (preg_match('#^/api/posts/([0-9a-f-]{36})/comments$#', $path, $m) && $method === 'POST') {
    $postId = $m[1];
    $data = readJsonBody();

    $content = trim($data["content"] ?? "");
    if ($content === "") {
        jsonFail("content is required", 422);
    }

    $stmt = $pdo->prepare("
        INSERT INTO comments (post_id, user_id, content)
        VALUES (:post_id, :user_id, :content)
        RETURNING id, content, created_at
    ");
    $stmt->execute([
        ":post_id" => $postId,
        ":user_id" => $userId,
        ":content" => $content,
    ]);

    jsonResponse($stmt->fetch(PDO::FETCH_ASSOC), 201);
    exit;
}

/* PUT /api/comments/{id} */
if (preg_match('#^/api/comments/([0-9a-f-]{36})$#', $path, $m) && $method === 'PUT') {
    $commentId = $m[1];
    $data = readJsonBody();

    $content = trim($data["content"] ?? "");
    if ($content === "") {
        jsonFail("content is required", 422);
    }

    $stmt = $pdo->prepare("
        UPDATE comments
        SET content = :content,
            updated_at = NOW()
        WHERE id = :id AND user_id = :user_id AND deleted_at IS NULL
        RETURNING id, content, updated_at
    ");
    $stmt->execute([
        ":id" => $commentId,
        ":user_id" => $userId,
        ":content" => $content,
    ]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        jsonFail("comment not found or forbidden", 404);
    }

    jsonResponse($row);
    exit;
}

/* DELETE /api/comments/{id} */
if (preg_match('#^/api/comments/([0-9a-f-]{36})$#', $path, $m) && $method === 'DELETE') {
    $commentId = $m[1];

    $stmt = $pdo->prepare("
        UPDATE comments
        SET deleted_at = NOW()
        WHERE id = :id AND user_id = :user_id AND deleted_at IS NULL
        RETURNING id
    ");
    $stmt->execute([
        ":id" => $commentId,
        ":user_id" => $userId,
    ]);

    if (!$stmt->fetch()) {
        jsonFail("comment not found or forbidden", 404);
    }

    jsonResponse(["ok" => true]);
    exit;
}

jsonFail("Not found", 404);