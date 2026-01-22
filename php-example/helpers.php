<?php

function jsonResponse($data, int $status = 200): void
{
    http_response_code($status);
    header("Content-Type: application/json; charset=utf-8");
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
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