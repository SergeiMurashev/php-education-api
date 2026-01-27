<?php


// src/Support/Logger.php

namespace App\Support;

final class Logger
{
    public static function request(
        string $method,
        string $path,
        int    $status,
        float  $ms
    ): void
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '-';

        $line = sprintf(
            '[%s] %s %s %s -> %d (%.1fms)',
            date('H:i:s'),
            $ip,
            str_pad($method, 6),
            $path,
            $status,
            $ms
        );

        error_log($line);
    }

    public static function error(string $message, array $context = []): void
    {
        error_log('[ERROR] ' . $message . ' ' . json_encode($context));
    }

    public static function info(string $message, array $context = []): void
    {
        error_log('[INFO] ' . $message . ' ' . json_encode($context));
    }
}
