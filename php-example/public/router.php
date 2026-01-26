<?php

$path = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);

// root
if ($path === "/") {
    echo "API is running";
    exit;
}

// API
if ($path === "/api/ping") {
    require __DIR__ . "/../api/ping.php";
    exit;
}

if (str_starts_with($path, "/api/auth")) {
    require __DIR__ . "/../api/auth.php";
    exit;
}

if ($path === "/api/me") {
    require __DIR__ . "/../api/me.php";
    exit;
}

if (str_starts_with($path, "/api/users")) {
    require __DIR__ . "/../api/users.php";
    exit;
}

if (str_starts_with($path, "/api/posts")) {
    require __DIR__ . "/../api/posts.php";
    exit;
}

// 404
http_response_code(404);
header("Content-Type: application/json; charset=utf-8");
echo json_encode(
    ["error" => "Not found", "path" => $path],
    JSON_UNESCAPED_UNICODE
);