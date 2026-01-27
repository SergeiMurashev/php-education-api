<?php
// src/Http/Request.php

namespace App\Http;

use App\Support\Json;

final class Request
{
    public static function json(): array
    {
        return Json::read();
    }

    public static function bearerToken(): ?string
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (preg_match('/Bearer\s+(.*)/i', $header, $m)) {
            return trim($m[1]);
        }
        return null;
    }
}
