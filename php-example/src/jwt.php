<?php

function base64url_encode(string $data): string
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function base64url_decode(string $data): string
{
    $remainder = strlen($data) % 4;
    if ($remainder) {
        $data .= str_repeat('=', 4 - $remainder);
    }
    return base64_decode(strtr($data, '-_', '+/'));
}

function jwt_encode(array $payload, string $secret): string
{
    $header = ["alg" => "HS256", "typ" => "JWT"];

    $segments = [];
    $segments[] = base64url_encode(json_encode($header));
    $segments[] = base64url_encode(json_encode($payload));

    $signingInput = implode(".", $segments);
    $signature = hash_hmac("sha256", $signingInput, $secret, true);

    $segments[] = base64url_encode($signature);

    return implode(".", $segments);
}

function jwt_decode(string $token, string $secret): array
{
    $parts = explode(".", $token);
    if (count($parts) !== 3) {
        throw new Exception("Invalid token format");
    }

    [$h, $p, $s] = $parts;

    $payload = json_decode(base64url_decode($p), true);
    if (!$payload) {
        throw new Exception("Invalid payload");
    }

    $signingInput = $h . "." . $p;
    $expected = base64url_encode(hash_hmac("sha256", $signingInput, $secret, true));

    if (!hash_equals($expected, $s)) {
        throw new Exception("Invalid signature");
    }

    if (isset($payload["exp"]) && time() > (int)$payload["exp"]) {
        throw new Exception("Token expired");
    }

    return $payload;
}