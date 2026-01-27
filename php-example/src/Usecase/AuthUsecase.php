<?php

namespace App\Usecase;

use App\Repository\UserRepository;
use App\Service\TokenService;
use App\Entity\User;
use DomainException;

final class AuthUsecase
{
    public function __construct(
        private UserRepository $users,
        private TokenService $tokens,
    ) {}

    public function register(string $email, string $name, string $password): array
    {
        if ($email === '' || $name === '' || $password === '') {
            throw new DomainException('invalid input');
        }

        $user = $this->users->create(
            strtolower($email),
            $name,
            password_hash($password, PASSWORD_BCRYPT)
        );

        return [
            'user' => $user,
            'tokens' => $this->tokens->issue($user),
        ];
    }

    public function login(string $email, string $password): array
    {
        $row = $this->users->findByEmail(strtolower($email));
        if (!$row || !password_verify($password, $row['password_hash'])) {
            throw new DomainException('invalid credentials');
        }

        $user = new User(
            $row['id'],
            $row['email'],
            $row['name'],
            $row['role'],
        );

        return [
            'user' => $user,
            'tokens' => $this->tokens->issue($user),
        ];
    }
}
