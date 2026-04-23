<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

final class CourseRepository
{
    public function all(): array
    {
        return Database::connection()->query(
            'SELECT c.*,
                    COUNT(s.id) AS students_count
             FROM courses c
             LEFT JOIN students s ON s.course_id = c.id
             GROUP BY c.id
             ORDER BY c.name ASC'
        )->fetchAll();
    }

    public function create(string $name): void
    {
        $statement = Database::connection()->prepare('INSERT INTO courses (name) VALUES (:name)');
        $statement->execute(['name' => trim($name)]);
    }

    public function find(int $id): ?array
    {
        $statement = Database::connection()->prepare('SELECT * FROM courses WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);

        return $statement->fetch() ?: null;
    }

    public function delete(int $id): void
    {
        $statement = Database::connection()->prepare('DELETE FROM courses WHERE id = :id');
        $statement->execute(['id' => $id]);
    }
}
