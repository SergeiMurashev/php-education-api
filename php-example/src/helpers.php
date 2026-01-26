<?php
require_once __DIR__ . "/logger.php";

function jsonResponse($data, int $status = 200): void
{
    http_response_code($status);
    header("Content-Type: application/json; charset=utf-8");

    $start = $GLOBALS["_req_start"] ?? microtime(true);

    echo json_encode($data, JSON_UNESCAPED_UNICODE);

    $ms = (microtime(true) - $start) * 1000;

    $method = $_SERVER["REQUEST_METHOD"] ?? "-";
    $path = parse_url($_SERVER["REQUEST_URI"] ?? "", PHP_URL_PATH) ?? "-";

    logRequest($method, $path, $status, $ms);
}

function readJsonBody(): array
{
    $raw = file_get_contents("php://input");
    if (!$raw) return [];

    $data = json_decode($raw, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        jsonResponse(["error" => "Invalid JSON"], 400);
        exit;
    }

    return $data ?? [];
}

function getBearerToken(): ?string
{
    $headers = $_SERVER["HTTP_AUTHORIZATION"] ?? "";
    if (preg_match('/Bearer\s(.*)/i', $headers, $m)) {
        return trim($m[1]);
    }
    return null;
}

function jsonFail(string $message, int $status = 400): void
{
    jsonResponse(["error" => $message], $status);
    exit;
}