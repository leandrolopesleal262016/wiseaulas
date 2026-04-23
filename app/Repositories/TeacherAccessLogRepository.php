<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

final class TeacherAccessLogRepository
{
    public function create(int $userId, ?string $ipAddress, ?string $userAgent): void
    {
        $statement = Database::connection()->prepare(
            'INSERT INTO teacher_access_logs (user_id, ip_address, user_agent, accessed_at)
             VALUES (:user_id, :ip_address, :user_agent, :accessed_at)'
        );
        $statement->execute([
            'user_id' => $userId,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'accessed_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function latest(int $limit = 50): array
    {
        $driver = Database::connection()->getAttribute(\PDO::ATTR_DRIVER_NAME);
        $limitSql = $driver === 'mysql' ? ' LIMIT ' . max(1, $limit) : ' LIMIT ' . max(1, $limit);

        return Database::connection()->query(
            'SELECT l.*,
                    u.name AS teacher_name,
                    u.login_name
             FROM teacher_access_logs l
             INNER JOIN users u ON u.id = l.user_id
             ORDER BY l.accessed_at DESC, l.id DESC' . $limitSql
        )->fetchAll();
    }
}
