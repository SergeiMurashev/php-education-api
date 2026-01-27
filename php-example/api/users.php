<?php

use App\Repository\UserRepository;
use App\Usecase\UserUsecase;

$pdo = db();
$usecase = new UserUsecase(
    new UserRepository($pdo)
);

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

try {
    // GET /api/users
    if ($path === '/api/users' && $method === 'GET') {
        Response::ok(
            $usecase->list(
                ($_GET['all'] ?? '0') === '1',
                ($_GET['deleted'] ?? '0') === '1'
            )
        );
    }

    // POST /api/users
    if ($path === '/api/users' && $method === 'POST') {
        $data = Request::json();
        Response::created(
            $usecase->create(
                $data['email'] ?? '',
                $data['name'] ?? ''
            )
        );
    }

    // /api/users/{id}
    if (preg_match('#^/api/users/([0-9a-f-]{36})$#', $path, $m)) {
        $id = $m[1];

        if ($method === 'GET') {
            Response::ok($usecase->get($id));
        }

        if ($method === 'PUT') {
            $data = Request::json();
            Response::ok(
                $usecase->update(
                    $id,
                    $data['email'] ?? null,
                    $data['name'] ?? null
                )
            );
        }

        if ($method === 'DELETE') {
            Response::ok([
                'ok' => true,
                'user' => $usecase->delete($id),
            ]);
        }
    }

    // POST /api/users/{id}/restore
    if (preg_match('#^/api/users/([0-9a-f-]{36})/restore$#', $path, $m)
        && $method === 'POST'
    ) {
        Response::ok([
            'ok' => true,
            'user' => $usecase->restore($m[1]),
        ]);
    }

    Response::notFound();

} catch (DomainException $e) {
    Response::error($e->getMessage(), 422);
}