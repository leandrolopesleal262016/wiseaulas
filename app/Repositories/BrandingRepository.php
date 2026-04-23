<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

final class BrandingRepository
{
    public function current(): array
    {
        $statement = Database::connection()->query('SELECT * FROM branding ORDER BY id ASC LIMIT 1');
        $branding = $statement->fetch();

        return $branding ?: [
            'site_name' => env('APP_NAME', 'Sistema de Aulas Online'),
            'theme_key' => 'classic-slate',
            'primary_color' => '#12355b',
            'secondary_color' => '#f7efe5',
            'accent_color' => '#ef476f',
            'logo_path' => null,
            'background_image_path' => null,
            'hero_image_path' => null,
        ];
    }

    public function update(array $payload): void
    {
        $existing = $this->current();
        $statement = Database::connection()->prepare(
            'UPDATE branding
             SET site_name = :site_name,
                 theme_key = :theme_key,
                 primary_color = :primary_color,
                 secondary_color = :secondary_color,
                 accent_color = :accent_color,
                 logo_path = :logo_path,
                 background_image_path = :background_image_path,
                 hero_image_path = :hero_image_path
             WHERE id = :id'
        );

        $statement->execute([
            'id' => $existing['id'],
            'site_name' => $payload['site_name'],
            'theme_key' => $payload['theme_key'],
            'primary_color' => $payload['primary_color'],
            'secondary_color' => $payload['secondary_color'],
            'accent_color' => $payload['accent_color'],
            'logo_path' => $payload['logo_path'],
            'background_image_path' => $payload['background_image_path'],
            'hero_image_path' => $payload['hero_image_path'],
        ]);
    }
}
