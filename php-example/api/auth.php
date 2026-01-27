<?php

use App\Repository\UserRepository;
use App\Repository\TokenRepository;
use App\Service\TokenService;
use App\Usecase\AuthService;

$pdo = db();
$config = require __DIR__ . '/../src/Config/config.php';

$usecase = new AuthUsecase(
    new UserRepository($pdo),
    new TokenService(
        new TokenRepository($pdo),
        $config
    )
);

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

$data = Request::json();

try {
    if ($path === '/api/auth/register' && $method === 'POST') {
        Response::created(
            $usecase->register(
                $data['email'] ?? '',
                $data['name'] ?? '',
                $data['password'] ?? '',
            )
        );
    }

    if ($path === '/api/auth/login' && $method === 'POST') {
        Response::ok(
            $usecase->login(
                $data['email'] ?? '',
                $data['password'] ?? '',
            )
        );
    }

    Response::notFound();

} catch (DomainException $e) {
    Response::error($e->getMessage(), 422);
}