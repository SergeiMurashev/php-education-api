<?php

require __DIR__ . "/../src/helpers.php";
require __DIR__ . "/../src/db.php";

$pdo = db();

$path = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
$method = $_SERVER["REQUEST_METHOD"];

// GET /api/users
if ($path === "/api/users" && $method === "GET") {

    $all = isset($_GET["all"]) && $_GET["all"] == "1";
    $deleted = isset($_GET["deleted"]) && $_GET["deleted"] == "1";

    if ($all) {
        $stmt = $pdo->query("SELECT id, email, name, created_at, updated_at, deleted_at FROM users ORDER BY created_at DESC");
        jsonResponse($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    if ($deleted) {
        $stmt = $pdo->query("SELECT id, email, name, created_at, updated_at, deleted_at FROM users WHERE deleted_at IS NOT NULL ORDER BY created_at DESC");
        jsonResponse($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    $stmt = $pdo->query("SELECT id, email, name, created_at, updated_at, deleted_at FROM users WHERE deleted_at IS NULL ORDER BY created_at DESC");
    jsonResponse($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

// POST /api/auth/register
if ($path === "/api/users" && $method === "POST") {
    jsonResponse([
        "error" => "Use POST /api/auth/register to create user"
    ], 405);
    exit;
}

// /api/users/{uuid}
if (preg_match("#^/api/users/([0-9a-fA-F-]{36})$#", $path, $m)) {
    $id = $m[1]; // ✅ UUID строкой

    // GET /api/users/{id}
    if ($method === "GET") {
        $stmt = $pdo->prepare("
            SELECT id, email, name, created_at, updated_at, deleted_at
            FROM users
            WHERE id = :id
            LIMIT 1
        ");
        $stmt->execute([":id" => $id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            jsonResponse(["error" => "User not found"], 404);
            exit;
        }

        jsonResponse($user);
        exit;
    }

    // PUT /api/users/{id}
    if ($method === "PUT") {
        $data = readJsonBody();

        $email = isset($data["email"]) ? strtolower(trim($data["email"])) : null;
        $name = isset($data["name"]) ? trim($data["name"]) : null;

        if ($email === null && $name === null) {
            jsonResponse(["error" => "nothing to update"], 422);
            exit;
        }

        try {
            if ($email !== null && $name !== null) {
                $stmt = $pdo->prepare("
                    UPDATE users
                    SET email = :email,
                        name = :name,
                        updated_at = NOW()
                    WHERE id = :id
                    RETURNING id, email, name, created_at, updated_at, deleted_at
                ");
                $stmt->execute([":email" => $email, ":name" => $name, ":id" => $id]);
            } elseif ($email !== null) {
                $stmt = $pdo->prepare("
                    UPDATE users
                    SET email = :email,
                        updated_at = NOW()
                    WHERE id = :id
                    RETURNING id, email, name, created_at, updated_at, deleted_at
                ");
                $stmt->execute([":email" => $email, ":id" => $id]);
            } else {
                $stmt = $pdo->prepare("
                    UPDATE users
                    SET name = :name,
                        updated_at = NOW()
                    WHERE id = :id
                    RETURNING id, email, name, created_at, updated_at, deleted_at
                ");
                $stmt->execute([":name" => $name, ":id" => $id]);
            }

            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                jsonResponse(["error" => "User not found"], 404);
                exit;
            }

            jsonResponse($user);
            exit;

        } catch (PDOException $e) {
            if ($e->getCode() === "23505") {
                jsonResponse(["error" => "email already exists"], 409);
                exit;
            }

            jsonResponse(["error" => "db error"], 500);
            exit;
        }
    }

    // DELETE /api/users/{id} (soft delete)
    if ($method === "DELETE") {
        $stmt = $pdo->prepare("
            UPDATE users
            SET deleted_at = NOW(),
                updated_at = NOW()
            WHERE id = :id AND deleted_at IS NULL
            RETURNING id, email, name, created_at, updated_at, deleted_at
        ");
        $stmt->execute([":id" => $id]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            jsonResponse(["error" => "User not found or already deleted"], 404);
            exit;
        }

        jsonResponse(["ok" => true, "user" => $user]);
        exit;
    }

    jsonResponse(["error" => "Method not allowed"], 405);
    exit;
}

// POST /api/users/{id}/restore
if (preg_match("#^/api/users/([0-9a-fA-F-]{36})/restore$#", $path, $m) && $method === "POST") {
    $id = $m[1]; // ✅ UUID строкой

    $stmt = $pdo->prepare("
        UPDATE users
        SET deleted_at = NULL,
            updated_at = NOW()
        WHERE id = :id AND deleted_at IS NOT NULL
        RETURNING id, email, name, created_at, updated_at, deleted_at
    ");
    $stmt->execute([":id" => $id]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        jsonResponse(["error" => "User not found or not deleted"], 404);
        exit;
    }

    jsonResponse(["ok" => true, "user" => $user]);
    exit;
}

jsonResponse(["error" => "Not found"], 404);