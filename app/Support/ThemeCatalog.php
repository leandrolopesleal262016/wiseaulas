<?php

declare(strict_types=1);

namespace App\Support;

final class ThemeCatalog
{
    public static function all(): array
    {
        return [
            'classic-slate' => [
                'label' => 'Azul Classico',
                'description' => 'Institucional, limpo e seguro.',
                'primary_color' => '#12355b',
                'secondary_color' => '#f7efe5',
                'accent_color' => '#ef476f',
                'surface' => '#ffffff',
                'surface_soft' => 'rgba(255, 255, 255, 0.74)',
                'text' => '#132238',
                'muted' => '#55657d',
                'border' => 'rgba(19, 34, 56, 0.12)',
                'shadow' => '0 24px 60px rgba(18, 53, 91, 0.14)',
                'background_gradient' => 'radial-gradient(circle at top left, rgba(239, 71, 111, 0.18), transparent 34%), linear-gradient(135deg, #f7efe5, #ffffff 68%)',
                'background_overlay' => 'linear-gradient(135deg, rgba(18, 53, 91, 0.78), rgba(239, 71, 111, 0.28))',
            ],
            'forest-mint' => [
                'label' => 'Verde Aurora',
                'description' => 'Calmo, moderno e arejado.',
                'primary_color' => '#1f5c4a',
                'secondary_color' => '#eef6f1',
                'accent_color' => '#57cc99',
                'surface' => '#fbfffd',
                'surface_soft' => 'rgba(255, 255, 255, 0.78)',
                'text' => '#16322b',
                'muted' => '#53756d',
                'border' => 'rgba(31, 92, 74, 0.14)',
                'shadow' => '0 24px 60px rgba(31, 92, 74, 0.16)',
                'background_gradient' => 'radial-gradient(circle at top left, rgba(87, 204, 153, 0.22), transparent 36%), linear-gradient(145deg, #eef6f1, #ffffff 70%)',
                'background_overlay' => 'linear-gradient(135deg, rgba(31, 92, 74, 0.74), rgba(87, 204, 153, 0.26))',
            ],
            'sand-terracotta' => [
                'label' => 'Areia Terracota',
                'description' => 'Acolhedor e elegante.',
                'primary_color' => '#8a4f3d',
                'secondary_color' => '#fbf1e7',
                'accent_color' => '#d17a52',
                'surface' => '#fffaf5',
                'surface_soft' => 'rgba(255, 248, 241, 0.8)',
                'text' => '#3f291f',
                'muted' => '#7a6457',
                'border' => 'rgba(138, 79, 61, 0.14)',
                'shadow' => '0 24px 60px rgba(138, 79, 61, 0.16)',
                'background_gradient' => 'radial-gradient(circle at top left, rgba(209, 122, 82, 0.22), transparent 34%), linear-gradient(135deg, #fbf1e7, #fffdf9 68%)',
                'background_overlay' => 'linear-gradient(135deg, rgba(138, 79, 61, 0.72), rgba(209, 122, 82, 0.24))',
            ],
            'graphite-gold' => [
                'label' => 'Grafite Dourado',
                'description' => 'Mais premium e contrastado.',
                'primary_color' => '#1d2530',
                'secondary_color' => '#f4efe4',
                'accent_color' => '#c89b3c',
                'surface' => '#ffffff',
                'surface_soft' => 'rgba(255, 255, 255, 0.76)',
                'text' => '#151b22',
                'muted' => '#5d6671',
                'border' => 'rgba(29, 37, 48, 0.14)',
                'shadow' => '0 24px 60px rgba(29, 37, 48, 0.18)',
                'background_gradient' => 'radial-gradient(circle at top left, rgba(200, 155, 60, 0.2), transparent 32%), linear-gradient(135deg, #f4efe4, #ffffff 70%)',
                'background_overlay' => 'linear-gradient(135deg, rgba(29, 37, 48, 0.78), rgba(200, 155, 60, 0.22))',
            ],
            'ocean-breeze' => [
                'label' => 'Oceano Claro',
                'description' => 'Leve, luminoso e fresco.',
                'primary_color' => '#165a72',
                'secondary_color' => '#edf8fb',
                'accent_color' => '#2bb3d6',
                'surface' => '#fefefe',
                'surface_soft' => 'rgba(255, 255, 255, 0.76)',
                'text' => '#12313f',
                'muted' => '#56727e',
                'border' => 'rgba(22, 90, 114, 0.14)',
                'shadow' => '0 24px 60px rgba(22, 90, 114, 0.16)',
                'background_gradient' => 'radial-gradient(circle at top left, rgba(43, 179, 214, 0.2), transparent 34%), linear-gradient(135deg, #edf8fb, #ffffff 70%)',
                'background_overlay' => 'linear-gradient(135deg, rgba(22, 90, 114, 0.72), rgba(43, 179, 214, 0.24))',
            ],
            'custom' => [
                'label' => 'Personalizado',
                'description' => 'Controle manual das cores.',
            ],
        ];
    }

    public static function keys(): array
    {
        return array_keys(self::all());
    }

    public static function exists(string $key): bool
    {
        return array_key_exists($key, self::all());
    }

    public static function resolve(array $branding): array
    {
        $themes = self::all();
        $themeKey = (string) ($branding['theme_key'] ?? 'classic-slate');

        if ($themeKey !== 'custom' && isset($themes[$themeKey])) {
            return $themes[$themeKey] + ['key' => $themeKey];
        }

        return [
            'key' => 'custom',
            'label' => 'Personalizado',
            'description' => 'Tema ajustado manualmente.',
            'primary_color' => (string) ($branding['primary_color'] ?? '#12355b'),
            'secondary_color' => (string) ($branding['secondary_color'] ?? '#f7efe5'),
            'accent_color' => (string) ($branding['accent_color'] ?? '#ef476f'),
            'surface' => '#ffffff',
            'surface_soft' => 'rgba(255, 255, 255, 0.76)',
            'text' => '#132238',
            'muted' => '#55657d',
            'border' => 'rgba(19, 34, 56, 0.12)',
            'shadow' => '0 24px 60px rgba(18, 53, 91, 0.14)',
            'background_gradient' => sprintf(
                'radial-gradient(circle at top left, %s, transparent 34%%), linear-gradient(135deg, %s, #ffffff 70%%)',
                self::hexToRgba((string) ($branding['accent_color'] ?? '#ef476f'), 0.18),
                (string) ($branding['secondary_color'] ?? '#f7efe5')
            ),
            'background_overlay' => sprintf(
                'linear-gradient(135deg, %s, %s)',
                self::hexToRgba((string) ($branding['primary_color'] ?? '#12355b'), 0.78),
                self::hexToRgba((string) ($branding['accent_color'] ?? '#ef476f'), 0.24)
            ),
        ];
    }

    private static function hexToRgba(string $hex, float $alpha): string
    {
        $normalized = ltrim($hex, '#');

        if (strlen($normalized) !== 6) {
            return sprintf('rgba(18, 53, 91, %.2f)', $alpha);
        }

        return sprintf(
            'rgba(%d, %d, %d, %.2f)',
            hexdec(substr($normalized, 0, 2)),
            hexdec(substr($normalized, 2, 2)),
            hexdec(substr($normalized, 4, 2)),
            $alpha
        );
    }
}
