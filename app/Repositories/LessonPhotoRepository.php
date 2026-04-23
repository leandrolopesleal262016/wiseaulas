<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

final class LessonPhotoRepository
{
    public function create(int $lessonId, string $filePath, string $originalName): int
    {
        $statement = Database::connection()->prepare(
            'INSERT INTO lesson_photos (lesson_id, file_path, original_name, created_at)
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
            'SELECT lp.*,
                    l.teacher_id
             FROM lesson_photos lp
             INNER JOIN lessons l ON l.id = lp.lesson_id
             WHERE lp.id = :id
             LIMIT 1'
        );
        $statement->execute(['id' => $id]);

        return $statement->fetch() ?: null;
    }

    public function byLesson(int $lessonId): array
    {
        $statement = Database::connection()->prepare(
            'SELECT *
             FROM lesson_photos
             WHERE lesson_id = :lesson_id
             ORDER BY created_at DESC, id DESC'
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
             FROM lesson_photos
             WHERE lesson_id IN ({$placeholders})
             ORDER BY created_at DESC, id DESC"
        );
        $statement->execute($lessonIds);

        $grouped = [];

        foreach ($statement->fetchAll() as $photo) {
            $grouped[(int) $photo['lesson_id']][] = $photo;
        }

        return $grouped;
    }

    public function delete(int $id): void
    {
        $statement = Database::connection()->prepare('DELETE FROM lesson_photos WHERE id = :id');
        $statement->execute(['id' => $id]);
    }
}
