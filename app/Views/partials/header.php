<?php
/** @var array $branding */
/** @var array|null $authUser */
$siteName = (string) ($branding['site_name'] ?? 'Sistema de Aulas');
$fullTitle = ($pageTitle ?? 'Sistema') . ' | ' . $siteName;
$metaDescription = $metaDescription ?? 'Portal de aulas online com conteudo, presencas, atividades e acesso rapido para professores e alunos.';
$uploadedMetaUrl = static function (?string $path): ?string {
    if (function_exists('absolute_uploaded_file_url')) {
        return absolute_uploaded_file_url($path);
    }

    if ($path === null || $path === '') {
        return null;
    }

    if (preg_match('#^https?://#i', $path) === 1) {
        return $path;
    }

    return absolute_route('file', ['path' => $path]);
};
$metaImage = $uploadedMetaUrl($branding['hero_image_path'] ?? null)
    ?? $uploadedMetaUrl($branding['logo_path'] ?? null)
    ?? absolute_route('app-icon', ['size' => 512]);
$canonicalUrl = current_page_url();
$currentPage = (string) ($_GET['page'] ?? 'home');
$isTeacher = ($authUser['role'] ?? null) === 'teacher';
$isAdmin = ($authUser['role'] ?? null) === 'admin';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($fullTitle); ?></title>
    <meta name="description" content="<?= e($metaDescription); ?>">
    <meta name="theme-color" content="<?= e($branding['primary_color'] ?? '#12355b'); ?>">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="<?= e($siteName); ?>">
    <link rel="canonical" href="<?= e($canonicalUrl); ?>">
    <link rel="manifest" href="<?= e(absolute_route('manifest')); ?>">
    <link rel="icon" href="<?= e(absolute_route('app-icon', ['size' => 192])); ?>" type="image/svg+xml">
    <link rel="apple-touch-icon" href="<?= e(absolute_route('app-icon', ['size' => 192])); ?>">

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="<?= e($siteName); ?>">
    <meta property="og:title" content="<?= e($fullTitle); ?>">
    <meta property="og:description" content="<?= e($metaDescription); ?>">
    <meta property="og:url" content="<?= e($canonicalUrl); ?>">
    <meta property="og:image" content="<?= e($metaImage); ?>">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= e($fullTitle); ?>">
    <meta name="twitter:description" content="<?= e($metaDescription); ?>">
    <meta name="twitter:image" content="<?= e($metaImage); ?>">
    <link rel="stylesheet" href="assets/styles.css">
    <style>
        :root {
            --surface: <?= $theme['surface'] ?? '#ffffff'; ?>;
            --surface-soft: <?= $theme['surface_soft'] ?? 'rgba(255, 255, 255, 0.72)'; ?>;
            --text: <?= $theme['text'] ?? '#132238'; ?>;
            --muted: <?= $theme['muted'] ?? '#55657d'; ?>;
            --border: <?= $theme['border'] ?? 'rgba(19, 34, 56, 0.12)'; ?>;
            --shadow: <?= $theme['shadow'] ?? '0 24px 60px rgba(18, 53, 91, 0.14)'; ?>;
            --primary: <?= e($branding['primary_color'] ?? '#12355b'); ?>;
            --secondary: <?= e($branding['secondary_color'] ?? '#f7efe5'); ?>;
            --accent: <?= e($branding['accent_color'] ?? '#ef476f'); ?>;
            --page-background: <?= $theme['background_gradient'] ?? 'linear-gradient(135deg, #f7efe5, #ffffff 68%)'; ?>;
        }
    </style>
</head>
<?php
$bodyBackgroundStyle = '';
if (!empty($branding['background_image_path'])) {
    $bodyBackgroundStyle = '--page-background: ' . ($theme['background_overlay'] ?? 'linear-gradient(135deg, rgba(18, 53, 91, 0.76), rgba(239, 71, 111, 0.22))') .
        ", url('" . branding_logo_url($branding['background_image_path']) . "');";
}
?>
<body<?= $bodyBackgroundStyle !== '' ? ' style="' . e($bodyBackgroundStyle) . '"' : ''; ?>>
<div class="page-shell">
    <header class="topbar">
        <a class="brand" href="<?= e(route('home')); ?>">
            <?php if (!empty($branding['logo_path'])): ?>
                <img src="<?= e(branding_logo_url($branding['logo_path'])); ?>" alt="Logo">
            <?php endif; ?>
            <div>
                <strong><?= e($siteName); ?></strong>
                <span>Chamada e aulas online</span>
            </div>
        </a>

        <nav class="topbar-nav">
            <a class="<?= $currentPage === 'home' ? 'is-active' : ''; ?>" href="<?= e(route('home')); ?>">Home</a>
            <a class="<?= str_starts_with($currentPage, 'teacher/') ? 'is-active' : ''; ?>" href="<?= e($isTeacher ? route('teacher/dashboard') : route('login')); ?>">Professor</a>
            <?php if ($isTeacher): ?>
                <a class="<?= $currentPage === 'teacher/term' ? 'is-active' : ''; ?>" href="<?= e(route('teacher/term')); ?>">Termo</a>
            <?php endif; ?>
            <a class="<?= $currentPage === 'report' ? 'is-active' : ''; ?>" href="<?= e(($isTeacher || $isAdmin) ? route('report') : route('login')); ?>">Relatorio</a>
            <a class="<?= str_starts_with($currentPage, 'admin/') ? 'is-active' : ''; ?>" href="<?= e($isAdmin ? route('admin/panel') : route('login')); ?>">Admin</a>
        </nav>
    </header>

    <?php if ($success): ?>
        <div class="flash success"><?= e($success); ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="flash error"><?= e($error); ?></div>
    <?php endif; ?>

    <main class="content">
