<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

final class LessonMaterialRepository
{
    public function create(int $lessonId, string $filePath, string $originalName): int
    {
        $statement = Database::connection()->prepare(
            'INSERT INTO lesson_materials (lesson_id, file_path, original_name, created_at)
             VALUES (:lesson_id, :file_path, :original_name, :created_at)'
        );
        $statement->execute([
            'lesson_id' => $lessonId,
            'file_path' => $filePath,
            'original_name' => trim($originalName),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public function find(int $id): ?array
    {
        $statement = Database::connection()->prepare(
            'SELECT lm.*,
                    l.teacher_id
             FROM lesson_materials lm
             INNER JOIN lessons l ON l.id = lm.lesson_id
             WHERE lm.id = :id
             LIMIT 1'
        );
        $statement->execute(['id' => $id]);

        return $statement->fetch() ?: null;
    }

    public function byLesson(int $lessonId): array
    {
        $statement = Database::connection()->prepare(
            'SELECT *
             FROM lesson_materials
             WHERE lesson_id = :lesson_id
             ORDER BY created_at ASC, id ASC'
        );
        $statement->execute(['lesson_id' => $lessonId]);

        return $statement->fetchAll();
    }

    public function groupedByLessonIds(array $lessonIds): array
    {
        $lessonIds = array_values(array_filter(array_map('intval', $lessonIds), static fn (int $id): bool => $id > 0));

        if ($lessonIds === []) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($lessonIds), '?'));
        $statement = Database::connection()->prepare(
            "SELECT *
             FROM lesson_materials
             WHERE lesson_id IN ({$placeholders})
             ORDER BY created_at ASC, id ASC"
        );
        $statement->execute($lessonIds);

        $grouped = [];

        foreach ($statement->fetchAll() as $material) {
            $grouped[(int) $material['lesson_id']][] = $material;
        }

        return $grouped;
    }

    public function delete(int $id): void
    {
        $statement = Database::connection()->prepare('DELETE FROM lesson_materials WHERE id = :id');
        $statement->execute(['id' => $id]);
    }
}
