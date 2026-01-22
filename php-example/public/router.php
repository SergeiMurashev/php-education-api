<?php

$GLOBALS["_req_start"] = microtime(true);

$path = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);

if ($path === "/api/ping") {
    require __DIR__ . "/../api/ping.php";
    exit;
}

if (str_starts_with($path, "/api/users")) {
    require __DIR__ . "/../api/users.php";
    exit;
}

if (str_starts_with($path, "/api/auth")) {
    require __DIR__ . "/../api/auth.php";
    exit;
}

if (str_starts_with($path, "/api/posts")) {
    require __DIR__ . "/../api/posts.php";
    exit;
}

if ($path === "/") {
    require __DIR__ . "/index.php";
    exit;
}

http_response_code(404);
header("Content-Type: application/json; charset=utf-8");
echo json_encode(["error" => "Not found", "path" => $path], JSON_UNESCAPED_UNICODE);