<?php


// src/Http/Response.php

namespace App\Http;

use App\Support\Json;
use App\Support\Logger;

final class Response
{
    public static function send(mixed $data, int $status): void
    {
        $start = $GLOBALS['_req_start'] ?? microtime(true);

        Json::response($data, $status);

        $ms = (microtime(true) - $start) * 1000;

        Logger::request(
            $_SERVER['REQUEST_METHOD'] ?? '-',
            parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '-',
            $status,
            $ms
        );
    }

    public static function ok(mixed $data): void
    {
        self::send($data, 200);
    }

    public static function created(mixed $data): void
    {
        self::send($data, 201);
    }

    public static function error(string $message, int $status): void
    {
        self::send(['error' => $message], $status);
    }

    public static function notFound(): void
    {
        self::error('Not found', 404);
    }

    public static function unauthorized(string $msg = 'Unauthorized'): void
    {
        self::error($msg, 401);
    }
}
