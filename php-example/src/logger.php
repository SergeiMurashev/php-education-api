<?php

function ansi(string $text, string $color): string
{
    if (!supportsAnsi()) {
        return $text;
    }

    $map = [
        "reset" => "\033[0m",
        "gray" => "\033[90m",
        "red" => "\033[31m",
        "green" => "\033[32m",
        "yellow" => "\033[33m",
        "blue" => "\033[34m",
        "magenta" => "\033[35m",
        "cyan" => "\033[36m",
    ];

    return ($map[$color] ?? "") . $text . $map["reset"];
}

function statusColor(int $status): string
{
    if ($status >= 500) return "red";
    if ($status >= 400) return "yellow";
    if ($status >= 300) return "cyan";
    return "green";
}

function logRequest(string $method, string $path, int $status, float $ms): void
{
    $ip = $_SERVER["REMOTE_ADDR"] ?? "-";

    $statusStr = ansi((string)$status, statusColor($status));
    $methodStr = ansi(str_pad($method, 6), "blue");
    $timeStr = ansi(number_format($ms, 1) . "ms", "gray");

    $line = "[" . date("H:i:s") . "] $ip $methodStr $path -> $statusStr ($timeStr)";
    error_log($line);
}

function supportsAnsi(): bool
{
    if (PHP_SAPI === 'cli' && function_exists('posix_isatty')) {
        return posix_isatty(STDOUT);
    }
    return false;
}