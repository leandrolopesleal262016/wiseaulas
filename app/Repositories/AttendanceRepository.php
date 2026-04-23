<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class AttendanceRepository
{
    public function lessonStatuses(int $lessonId): array
    {
        $statement = Database::connection()->prepare(
            'SELECT a.*, s.name AS student_name
             FROM attendance a
             INNER JOIN students s ON s.id = a.student_id
             WHERE a.lesson_id = :lesson_id
             ORDER BY s.name ASC'
        );
        $statement->execute(['lesson_id' => $lessonId]);

        $rows = $statement->fetchAll();
        $indexed = [];

        foreach ($rows as $row) {
            $indexed[(int) $row['student_id']] = $row;
        }

        return $indexed;
    }

    public function sync(int $lessonId, int $recordedBy, array $students, array $presentStudentIds): void
    {
        $pdo = Database::connection();
        $presentLookup = array_flip(array_map('intval', $presentStudentIds));
        $select = $pdo->prepare(
            'SELECT id FROM attendance WHERE lesson_id = :lesson_id AND student_id = :student_id LIMIT 1'
        );
        $update = $pdo->prepare(
            'UPDATE attendance
             SET status = :status, recorded_by = :recorded_by, updated_at = :updated_at
             WHERE id = :id'
        );
        $insert = $pdo->prepare(
            'INSERT INTO attendance (lesson_id, student_id, status, recorded_by, updated_at)
             VALUES (:lesson_id, :student_id, :status, :recorded_by, :updated_at)'
        );

        $pdo->beginTransaction();

        foreach ($students as $student) {
            $studentId = (int) $student['id'];
            $status = isset($presentLookup[$studentId]) ? 'present' : 'absent';

            $select->execute([
                'lesson_id' => $lessonId,
                'student_id' => $studentId,
            ]);

            $attendanceId = $select->fetchColumn();
            $payload = [
                'status' => $status,
                'recorded_by' => $recordedBy,
                'updated_at' => date('Y-m-d H:i:s'),
            ];

            if ($attendanceId) {
                $update->execute($payload + ['id' => $attendanceId]);
                continue;
            }

            $insert->execute($payload + [
                'lesson_id' => $lessonId,
                'student_id' => $studentId,
            ]);
        }

        $pdo->commit();
    }

    public function summaryForPublicLessons(array $lessonIds): array
    {
        if ($lessonIds === []) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($lessonIds), '?'));
        $statement = Database::connection()->prepare(
            'SELECT a.lesson_id,
                    a.status,
                    s.name AS student_name
             FROM attendance a
             INNER JOIN students s ON s.id = a.student_id
             WHERE a.lesson_id IN (' . $placeholders . ')
             ORDER BY s.name ASC'
        );
        $statement->execute(array_values($lessonIds));
        $rows = $statement->fetchAll();
        $grouped = [];

        foreach ($rows as $row) {
            $grouped[(int) $row['lesson_id']][] = $row;
        }

        return $grouped;
    }

    public function absenceCountByStudentIdsForTeacher(int $teacherId, array $studentIds): array
    {
        $studentIds = array_values(array_filter(array_map('intval', $studentIds), static fn (int $id): bool => $id > 0));

        if ($studentIds === []) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($studentIds), '?'));
        $statement = Database::connection()->prepare(
            "SELECT a.student_id,
                    COUNT(*) AS absence_count
             FROM attendance a
             INNER JOIN lessons l ON l.id = a.lesson_id
             WHERE l.teacher_id = ?
               AND a.status = 'absent'
               AND a.student_id IN ({$placeholders})
             GROUP BY a.student_id"
        );
        $statement->execute(array_merge([$teacherId], $studentIds));

        $counts = [];

        foreach ($statement->fetchAll() as $row) {
            $counts[(int) $row['student_id']] = (int) $row['absence_count'];
        }

        return $counts;
    }
}
