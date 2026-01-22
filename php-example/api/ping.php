<?php
require __DIR__ . "/../src/helpers.php";
header("Content-Type: application/json; charset=utf-8");

echo json_encode([
    "ok" => true,
    "time" => date("c")
], JSON_UNESCAPED_UNICODE);