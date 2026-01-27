<?php

// src/Http/Middleware/AuthMiddleware.php
final class AuthMiddleware
{
    public static function requireUserId(): string
    {
        $token = getBearerToken();
        if (!$token) {
            Response::unauthorized('missing token');
        }

        $claims = jwt_decode($token, config('jwt_secret'));

        if (empty($claims['sub'])) {
            Response::unauthorized('invalid token');
        }

        return $claims['sub'];
    }
}
