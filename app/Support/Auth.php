<?php

declare(strict_types=1);

namespace App\Support;

use App\Repositories\UserRepository;
use RuntimeException;

final class Auth
{
    public static function boot(): void
    {
        if (isset($_SESSION['auth_user_id'])) {
            app('auth.user', static fn () => (new UserRepository())->find((int) $_SESSION['auth_user_id']));
        }
    }

    public static function attempt(string $login, string $password): bool
    {
        $user = (new UserRepository())->findByLogin($login);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return false;
        }

        $_SESSION['auth_user_id'] = (int) $user['id'];
        app('auth.user', static fn () => $user);

        return true;
    }

    public static function user(): ?array
    {
        if (!isset($_SESSION['auth_user_id'])) {
            return null;
        }

        $resolver = app('auth.user');
        $user = is_callable($resolver) ? $resolver() : $resolver;

        return is_array($user) ? $user : null;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function requireRole(array|string $roles): void
    {
        $user = self::user();
        $roles = (array) $roles;

        if (!$user || !in_array($user['role'], $roles, true)) {
            flash('error', 'Voce precisa acessar com permissao valida.');
            redirect(route('login'));
        }
    }

    public static function logout(): void
    {
        unset($_SESSION['auth_user_id']);
        app('auth.user', null);
        session_regenerate_id(true);
    }
}
