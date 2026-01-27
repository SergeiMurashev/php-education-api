<?php


// src/Support/Json.php

namespace App\Support;

final class Json
{
    public static function response(mixed $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');

        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function ok(mixed $data): void
    {
        self::response($data, 200);
    }

    public static function created(mixed $data): void
    {
        self::response($data, 201);
    }

    public static function error(string $message, int $status = 400): void
    {
        self::response(['error' => $message], $status);
    }

    public static function read(): array
    {
        $raw = file_get_contents('php://input');
        if ($raw === false || $raw === '') {
            return [];
        }

        $data = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            self::error('Invalid JSON', 400);
        }

        return $data ?? [];
    }
}
