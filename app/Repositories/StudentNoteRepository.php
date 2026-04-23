<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

final class StudentNoteRepository
{
    public function create(int $studentId, int $teacherId, string $note): int
    {
        $statement = Database::connection()->prepare(
            'INSERT INTO student_notes (student_id, teacher_id, note, created_at, updated_at, is_legacy_import)
             VALUES (:student_id, :teacher_id, :note, :created_at, :updated_at, :is_legacy_import)'
        );
        $statement->execute([
            'student_id' => $studentId,
            'teacher_id' => $teacherId,
            'note' => trim($note),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'is_legacy_import' => 0,
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public function groupedByStudentIds(array $studentIds): array
    {
        $studentIds = array_values(array_filter(array_map('intval', $studentIds), static fn (int $id): bool => $id > 0));

        if ($studentIds === []) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($studentIds), '?'));
        $statement = Database::connection()->prepare(
            "SELECT sn.*,
                    u.name AS teacher_name
             FROM student_notes sn
             INNER JOIN users u ON u.id = sn.teacher_id
             WHERE sn.student_id IN ({$placeholders})
             ORDER BY sn.created_at DESC, sn.id DESC"
        );
        $statement->execute($studentIds);

        $grouped = [];

        foreach ($statement->fetchAll() as $note) {
            $grouped[(int) $note['student_id']][] = $note;
        }

        return $grouped;
    }
}
