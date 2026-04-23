<?php

declare(strict_types=1);

namespace App\Support;

use RuntimeException;

final class Csrf
{
    public static function token(): string
    {
        if (!isset($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }

        return (string) $_SESSION['_csrf'];
    }

    public static function validate(?string $token): void
    {
        if (!$token || !hash_equals((string) ($_SESSION['_csrf'] ?? ''), $token)) {
            throw new RuntimeException('Falha de validacao do formulario.');
        }
    }
}
