<?php

declare(strict_types=1);

use App\Core\Database;
use App\Core\Installer;
use App\Support\Auth;
use App\Support\Env;

require __DIR__ . '/../app/Support/helpers.php';

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/';

    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (is_file($file)) {
        require $file;
    }
});

Env::load(dirname(__DIR__) . '/.env');

date_default_timezone_set(env('APP_TIMEZONE', 'America/Sao_Paulo'));

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name(env('SESSION_NAME', 'site_professor'));
    session_start();
}

$pdo = Database::connection();
Installer::run($pdo);
Auth::boot();
