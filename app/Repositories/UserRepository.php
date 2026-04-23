<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class UserRepository
{
    public function findByLogin(string $login): ?array
    {
        $normalized = trim($login);
        $statement = Database::connection()->prepare(
            'SELECT * FROM users WHERE login_name = :login OR email = :email LIMIT 1'
        );
        $statement->execute([
            'login' => $normalized,
            'email' => mb_strtolower($normalized),
        ]);

        return $statement->fetch() ?: null;
    }

    public function find(int $id): ?array
    {
        $statement = Database::connection()->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);

        return $statement->fetch() ?: null;
    }

    public function loginNameExists(string $loginName): bool
    {
        $statement = Database::connection()->prepare(
            'SELECT COUNT(*) FROM users WHERE login_name = :login_name'
        );
        $statement->execute(['login_name' => trim($loginName)]);

        return (int) $statement->fetchColumn() > 0;
    }

    public function createTeacher(string $name, string $password): void
    {
        $loginName = trim($name);
        $statement = Database::connection()->prepare(
            'INSERT INTO users (name, login_name, email, password_hash, role)
             VALUES (:name, :login_name, :email, :password_hash, :role)'
        );
        $statement->execute([
            'name' => $loginName,
            'login_name' => $loginName,
            'email' => $this->generateInternalEmail(),
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'role' => 'teacher',
        ]);
    }

    public function allTeachers(): array
    {
        $statement = Database::connection()->query(
            "SELECT id, name, login_name FROM users WHERE role = 'teacher' ORDER BY name ASC"
        );

        return $statement->fetchAll();
    }

    public function deleteTeacher(int $id): void
    {
        $statement = Database::connection()->prepare(
            "DELETE FROM users WHERE id = :id AND role = 'teacher'"
        );
        $statement->execute(['id' => $id]);
    }

    public function hasAcceptedTeacherTerms(int $id): bool
    {
        $statement = Database::connection()->prepare(
            'SELECT teacher_terms_accepted_at FROM users WHERE id = :id AND role = :role LIMIT 1'
        );
        $statement->execute([
            'id' => $id,
            'role' => 'teacher',
        ]);

        return trim((string) $statement->fetchColumn()) !== '';
    }

    public function acceptTeacherTerms(int $id, string $version, string $content): void
    {
        $statement = Database::connection()->prepare(
            'UPDATE users
             SET teacher_terms_version = :teacher_terms_version,
                 teacher_terms_content = :teacher_terms_content,
                 teacher_terms_accepted_at = :teacher_terms_accepted_at
             WHERE id = :id AND role = :role'
        );
        $statement->execute([
            'id' => $id,
            'role' => 'teacher',
            'teacher_terms_version' => trim($version),
            'teacher_terms_content' => trim($content),
            'teacher_terms_accepted_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function generateInternalEmail(): string
    {
        return sprintf('teacher-%s-%s@local.invalid', time(), bin2hex(random_bytes(4)));
    }
}
