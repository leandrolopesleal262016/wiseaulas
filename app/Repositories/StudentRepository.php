<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

final class StudentRepository
{
    public function create(int $courseId, string $name): void
    {
        $statement = Database::connection()->prepare(
            'INSERT INTO students (course_id, name, notes) VALUES (:course_id, :name, :notes)'
        );
        $statement->execute([
            'course_id' => $courseId,
            'name' => trim($name),
            'notes' => null,
        ]);
    }

    public function byCourse(int $courseId): array
    {
        $statement = Database::connection()->prepare(
            'SELECT * FROM students WHERE course_id = :course_id ORDER BY name ASC'
        );
        $statement->execute(['course_id' => $courseId]);

        return $statement->fetchAll();
    }

    public function find(int $id): ?array
    {
        $statement = Database::connection()->prepare('SELECT * FROM students WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);

        return $statement->fetch() ?: null;
    }

    public function delete(int $id): void
    {
        $statement = Database::connection()->prepare('DELETE FROM students WHERE id = :id');
        $statement->execute(['id' => $id]);
    }

    public function updateAttendanceStartLessonId(int $id, ?int $lessonId): void
    {
        $statement = Database::connection()->prepare(
            'UPDATE students
             SET attendance_start_lesson_id = :attendance_start_lesson_id
             WHERE id = :id'
        );
        $statement->bindValue(':id', $id, \PDO::PARAM_INT);

        if ($lessonId === null || $lessonId <= 0) {
            $statement->bindValue(':attendance_start_lesson_id', null, \PDO::PARAM_NULL);
        } else {
            $statement->bindValue(':attendance_start_lesson_id', $lessonId, \PDO::PARAM_INT);
        }

        $statement->execute();
    }

    public function belongsToTeacherCourses(int $studentId, int $teacherId): bool
    {
        $statement = Database::connection()->prepare(
            'SELECT COUNT(*)
             FROM students s
             WHERE s.id = :student_id
               AND EXISTS (
                   SELECT 1
                   FROM lessons l
                   WHERE l.course_id = s.course_id
                     AND l.teacher_id = :teacher_id
               )'
        );
        $statement->execute([
            'student_id' => $studentId,
            'teacher_id' => $teacherId,
        ]);

        return (int) $statement->fetchColumn() > 0;
    }

    public function groupedByTeacherCourses(int $teacherId): array
    {
        $statement = Database::connection()->prepare(
            'SELECT s.*, c.name AS course_name
             FROM students s
             INNER JOIN courses c ON c.id = s.course_id
             WHERE EXISTS (
                 SELECT 1
                 FROM lessons l
                 WHERE l.course_id = s.course_id
                   AND l.teacher_id = :teacher_id
             )
             ORDER BY c.name ASC, s.name ASC'
        );
        $statement->execute(['teacher_id' => $teacherId]);

        $grouped = [];

        foreach ($statement->fetchAll() as $student) {
            $grouped[(string) $student['course_name']][] = $student;
        }

        return $grouped;
    }
}
