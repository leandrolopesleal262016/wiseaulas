<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

final class LessonRepository
{
    public function create(array $payload): int
    {
        $statement = Database::connection()->prepare(
            'INSERT INTO lessons (
                course_id,
                teacher_id,
                title,
                category_name,
                is_featured,
                sort_order,
                content_type,
                content_file_path,
                content_original_name,
                youtube_url,
                youtube_video_id,
                form_url,
                plan_file_path,
                plan_original_name,
                created_at
             ) VALUES (
                :course_id,
                :teacher_id,
                :title,
                :category_name,
                :is_featured,
                :sort_order,
                :content_type,
                :content_file_path,
                :content_original_name,
                :youtube_url,
                :youtube_video_id,
                :form_url,
                :plan_file_path,
                :plan_original_name,
                :created_at
             )'
        );
        $statement->execute([
            'course_id' => $payload['course_id'],
            'teacher_id' => $payload['teacher_id'],
            'title' => trim($payload['title']),
            'category_name' => trim((string) ($payload['category_name'] ?? '')) ?: null,
            'is_featured' => !empty($payload['is_featured']) ? 1 : 0,
            'sort_order' => (int) ($payload['sort_order'] ?? $this->nextSortOrder()),
            'content_type' => ($payload['content_type'] ?? 'youtube') === 'file' ? 'file' : 'youtube',
            'content_file_path' => $payload['content_file_path'] ?? null,
            'content_original_name' => $payload['content_original_name'] ?? null,
            'youtube_url' => trim((string) ($payload['youtube_url'] ?? '')),
            'youtube_video_id' => (string) ($payload['youtube_video_id'] ?? ''),
            'form_url' => $payload['form_url'],
            'plan_file_path' => $payload['plan_file_path'] ?? null,
            'plan_original_name' => $payload['plan_original_name'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public function allPublic(): array
    {
        return Database::connection()->query(
            'SELECT l.*,
                    u.name AS teacher_name,
                    c.name AS course_name,
                    (SELECT COUNT(*) FROM lesson_photos lp WHERE lp.lesson_id = l.id) AS photo_count,
                    (SELECT COUNT(*) FROM lesson_materials lm WHERE lm.lesson_id = l.id) AS material_count
             FROM lessons l
             INNER JOIN users u ON u.id = l.teacher_id
             INNER JOIN courses c ON c.id = l.course_id
             ORDER BY COALESCE(l.is_featured, 0) DESC,
                      CASE WHEN COALESCE(l.sort_order, 0) > 0 THEN l.sort_order ELSE 2147483647 END ASC,
                      l.created_at ASC,
                      l.id ASC'
        )->fetchAll();
    }

    public function allByTeacher(int $teacherId): array
    {
        $statement = Database::connection()->prepare(
            'SELECT l.*,
                    c.name AS course_name,
                    (SELECT COUNT(*) FROM attendance a WHERE a.lesson_id = l.id AND a.status = "present") AS present_count,
                    (SELECT COUNT(*) FROM students s WHERE s.course_id = l.course_id) AS total_students,
                    (SELECT COUNT(*) FROM lesson_photos lp WHERE lp.lesson_id = l.id) AS photo_count,
                    (SELECT COUNT(*) FROM lesson_materials lm WHERE lm.lesson_id = l.id) AS material_count
             FROM lessons l
             INNER JOIN courses c ON c.id = l.course_id
             WHERE l.teacher_id = :teacher_id
             ORDER BY l.created_at ASC, l.id ASC'
        );
        $statement->execute(['teacher_id' => $teacherId]);

        return $statement->fetchAll();
    }

    public function find(int $id): ?array
    {
        $statement = Database::connection()->prepare(
            'SELECT l.*,
                    u.name AS teacher_name,
                    c.name AS course_name,
                    (SELECT COUNT(*) FROM lesson_photos lp WHERE lp.lesson_id = l.id) AS photo_count,
                    (SELECT COUNT(*) FROM lesson_materials lm WHERE lm.lesson_id = l.id) AS material_count
             FROM lessons l
             INNER JOIN users u ON u.id = l.teacher_id
             INNER JOIN courses c ON c.id = l.course_id
             WHERE l.id = :id
             LIMIT 1'
        );
        $statement->execute(['id' => $id]);

        return $statement->fetch() ?: null;
    }

    public function update(int $id, array $payload): void
    {
        $statement = Database::connection()->prepare(
            'UPDATE lessons
             SET course_id = :course_id,
                 title = :title,
                 category_name = :category_name,
                 is_featured = :is_featured,
                 content_type = :content_type,
                 content_file_path = :content_file_path,
                 content_original_name = :content_original_name,
                 youtube_url = :youtube_url,
                 youtube_video_id = :youtube_video_id,
                 form_url = :form_url,
                 plan_file_path = :plan_file_path,
                 plan_original_name = :plan_original_name
             WHERE id = :id'
        );
        $statement->execute([
            'id' => $id,
            'course_id' => $payload['course_id'],
            'title' => trim($payload['title']),
            'category_name' => trim((string) ($payload['category_name'] ?? '')) ?: null,
            'is_featured' => !empty($payload['is_featured']) ? 1 : 0,
            'content_type' => ($payload['content_type'] ?? 'youtube') === 'file' ? 'file' : 'youtube',
            'content_file_path' => $payload['content_file_path'] ?? null,
            'content_original_name' => $payload['content_original_name'] ?? null,
            'youtube_url' => trim((string) ($payload['youtube_url'] ?? '')),
            'youtube_video_id' => (string) ($payload['youtube_video_id'] ?? ''),
            'form_url' => $payload['form_url'],
            'plan_file_path' => $payload['plan_file_path'] ?? null,
            'plan_original_name' => $payload['plan_original_name'] ?? null,
        ]);
    }

    public function allForAdmin(): array
    {
        return Database::connection()->query(
            'SELECT l.*,
                    u.name AS teacher_name,
                    c.name AS course_name,
                    (SELECT COUNT(*) FROM lesson_photos lp WHERE lp.lesson_id = l.id) AS photo_count,
                    (SELECT COUNT(*) FROM lesson_materials lm WHERE lm.lesson_id = l.id) AS material_count
             FROM lessons l
             INNER JOIN users u ON u.id = l.teacher_id
             INNER JOIN courses c ON c.id = l.course_id
             ORDER BY COALESCE(l.is_featured, 0) DESC,
                      CASE WHEN COALESCE(l.sort_order, 0) > 0 THEN l.sort_order ELSE 2147483647 END ASC,
                      l.created_at ASC,
                      l.id ASC'
        )->fetchAll();
    }

    public function reorder(array $lessonIds): void
    {
        $lessonIds = array_values(array_filter(array_map('intval', $lessonIds), static fn (int $id): bool => $id > 0));

        if ($lessonIds === []) {
            return;
        }

        $pdo = Database::connection();
        $update = $pdo->prepare(
            'UPDATE lessons
             SET sort_order = :sort_order
             WHERE id = :id'
        );

        try {
            $pdo->beginTransaction();

            foreach ($lessonIds as $index => $lessonId) {
                $update->execute([
                    'sort_order' => $index + 1,
                    'id' => $lessonId,
                ]);
            }

            $pdo->commit();
        } catch (\Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $exception;
        }
    }

    public function delete(int $id): void
    {
        $statement = Database::connection()->prepare('DELETE FROM lessons WHERE id = :id');
        $statement->execute(['id' => $id]);
    }

    private function nextSortOrder(): int
    {
        return (int) Database::connection()
            ->query('SELECT COALESCE(MAX(sort_order), 0) + 1 FROM lessons')
            ->fetchColumn();
    }
}
