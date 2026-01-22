<?php

$path = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);

if ($path === "/api/ping") {
    require __DIR__ . "/ping.php";
    exit;
}

if ($path === "/") {
    require __DIR__ . "/index.php";
    exit;
}
// Вызовы осуществляются сами в файле users.php
if (str_starts_with($path, "/api/users")) {
    require __DIR__ . "/users.php";
    exit;
}

http_response_code(404);
header("Content-Type: application/json; charset=utf-8");
echo json_encode(["error" => "Not found", "path" => $path], JSON_UNESCAPED_UNICODE);