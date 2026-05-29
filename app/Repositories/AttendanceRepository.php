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
            "SELECT s.id AS student_id,
                    COALESCE(SUM(CASE
                        WHEN l.teacher_id = ?
                         AND (
                             s.attendance_start_lesson_id IS NULL
                             OR start_lesson.id IS NULL
                             OR l.created_at > start_lesson.created_at
                             OR (l.created_at = start_lesson.created_at AND l.id >= start_lesson.id)
                         )
                         AND a.status = 'absent'
                        THEN 1
                        ELSE 0
                    END), 0) AS absence_count
             FROM students s
             LEFT JOIN lessons start_lesson ON start_lesson.id = s.attendance_start_lesson_id
             LEFT JOIN attendance a ON a.student_id = s.id
             LEFT JOIN lessons l ON l.id = a.lesson_id
             WHERE s.id IN ({$placeholders})
             GROUP BY s.id"
        );
        $statement->execute(array_merge([$teacherId], $studentIds));

        $counts = [];

        foreach ($statement->fetchAll() as $row) {
            $counts[(int) $row['student_id']] = (int) $row['absence_count'];
        }

        return $counts;
    }

    public function absenceReportForTeacher(int $teacherId): array
    {
        $statement = Database::connection()->prepare(
            "SELECT s.id AS student_id,
                    s.name AS student_name,
                    c.name AS course_name,
                    COALESCE(SUM(CASE
                        WHEN l.teacher_id = :teacher_id
                         AND (
                             s.attendance_start_lesson_id IS NULL
                             OR start_lesson.id IS NULL
                             OR l.created_at > start_lesson.created_at
                             OR (l.created_at = start_lesson.created_at AND l.id >= start_lesson.id)
                         )
                         AND a.status = 'absent'
                        THEN 1
                        ELSE 0
                    END), 0) AS absence_count,
                    COALESCE(SUM(CASE
                        WHEN l.teacher_id = :teacher_id
                         AND (
                             s.attendance_start_lesson_id IS NULL
                             OR start_lesson.id IS NULL
                             OR l.created_at > start_lesson.created_at
                             OR (l.created_at = start_lesson.created_at AND l.id >= start_lesson.id)
                         )
                         AND a.id IS NOT NULL
                        THEN 1
                        ELSE 0
                    END), 0) AS recorded_lessons_count
             FROM students s
             INNER JOIN courses c ON c.id = s.course_id
             LEFT JOIN lessons start_lesson ON start_lesson.id = s.attendance_start_lesson_id
             LEFT JOIN attendance a ON a.student_id = s.id
             LEFT JOIN lessons l ON l.id = a.lesson_id
             WHERE EXISTS (
                 SELECT 1
                 FROM lessons teacher_lessons
                 WHERE teacher_lessons.course_id = s.course_id
                   AND teacher_lessons.teacher_id = :teacher_id
             )
             GROUP BY s.id, s.name, c.name
             ORDER BY absence_count DESC, c.name ASC, s.name ASC"
        );
        $statement->execute(['teacher_id' => $teacherId]);

        return $statement->fetchAll();
    }

    public function absenceReportForAdmin(): array
    {
        return Database::connection()->query(
            "SELECT s.id AS student_id,
                    s.name AS student_name,
                    c.name AS course_name,
                    COALESCE(SUM(CASE
                        WHEN (
                            s.attendance_start_lesson_id IS NULL
                            OR start_lesson.id IS NULL
                            OR l.created_at > start_lesson.created_at
                            OR (l.created_at = start_lesson.created_at AND l.id >= start_lesson.id)
                        )
                         AND a.status = 'absent'
                        THEN 1
                        ELSE 0
                    END), 0) AS absence_count,
                    COALESCE(SUM(CASE
                        WHEN (
                            s.attendance_start_lesson_id IS NULL
                            OR start_lesson.id IS NULL
                            OR l.created_at > start_lesson.created_at
                            OR (l.created_at = start_lesson.created_at AND l.id >= start_lesson.id)
                        )
                         AND a.id IS NOT NULL
                        THEN 1
                        ELSE 0
                    END), 0) AS recorded_lessons_count
             FROM students s
             INNER JOIN courses c ON c.id = s.course_id
             LEFT JOIN lessons start_lesson ON start_lesson.id = s.attendance_start_lesson_id
             LEFT JOIN attendance a ON a.student_id = s.id
             LEFT JOIN lessons l ON l.id = a.lesson_id
             GROUP BY s.id, s.name, c.name
             ORDER BY absence_count DESC, c.name ASC, s.name ASC"
        )->fetchAll();
    }

    public function attendanceLessonCountForTeacher(int $teacherId): int
    {
        $statement = Database::connection()->prepare(
            'SELECT COUNT(DISTINCT l.id)
             FROM lessons l
             INNER JOIN attendance a ON a.lesson_id = l.id
             WHERE l.teacher_id = :teacher_id'
        );
        $statement->execute(['teacher_id' => $teacherId]);

        return (int) $statement->fetchColumn();
    }

    public function attendanceLessonCountForAdmin(): int
    {
        return (int) Database::connection()->query(
            'SELECT COUNT(DISTINCT lesson_id) FROM attendance'
        )->fetchColumn();
    }
}
