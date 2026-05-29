<?php

declare(strict_types=1);

use App\Repositories\BrandingRepository;
use App\Support\Auth;
use App\Support\Csrf;
use App\Support\ThemeCatalog;

function base_path(string $path = ''): string
{
    $base = dirname(__DIR__, 2);

    return $path === '' ? $base : $base . '/' . ltrim($path, '/');
}

function public_path(string $path = ''): string
{
    $publicDir = trim((string) env('PUBLIC_DIR', 'public'));
    $relativePath = ltrim($path, '/');

    if ($publicDir === '.' || $publicDir === './') {
        return base_path($relativePath);
    }

    return base_path(trim($publicDir, '/\\') . '/' . $relativePath);
}

function env(string $key, ?string $default = null): ?string
{
    $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

    if ($value === false || $value === null || $value === '') {
        return $default;
    }

    return (string) $value;
}

function app(string $key, mixed $value = '__codex_default__'): mixed
{
    static $container = [];

    if ($value !== '__codex_default__') {
        $container[$key] = $value;
    }

    return $container[$key] ?? null;
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function route(string $page, array $params = []): string
{
    $query = array_merge(['page' => $page], $params);

    return 'index.php?' . http_build_query($query);
}

function app_url(string $path = ''): string
{
    $baseUrl = rtrim((string) env('APP_URL', ''), '/');

    if ($path === '') {
        return $baseUrl;
    }

    if (preg_match('#^https?://#i', $path) === 1) {
        return $path;
    }

    return $baseUrl . '/' . ltrim($path, '/');
}

function request_base_url(): ?string
{
    $host = trim((string) ($_SERVER['HTTP_HOST'] ?? ''));

    if ($host === '') {
        return null;
    }

    $forwardedProto = trim(explode(',', (string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''))[0]);
    $scheme = in_array($forwardedProto, ['http', 'https'], true)
        ? $forwardedProto
        : (((string) ($_SERVER['HTTPS'] ?? '') !== '' && strtolower((string) $_SERVER['HTTPS']) !== 'off')
            || (string) ($_SERVER['SERVER_PORT'] ?? '') === '443'
                ? 'https'
                : 'http');
    $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    $basePath = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');

    if ($basePath === '.' || $basePath === '/') {
        $basePath = '';
    }

    return $scheme . '://' . $host . $basePath;
}

function absolute_route(string $page, array $params = []): string
{
    $relativeUrl = route($page, $params);
    $requestBaseUrl = request_base_url();

    return $requestBaseUrl !== null
        ? $requestBaseUrl . '/' . ltrim($relativeUrl, '/')
        : app_url($relativeUrl);
}

function absolute_url(?string $path): ?string
{
    if ($path === null || $path === '') {
        return null;
    }

    if (preg_match('#^https?://#i', $path) === 1) {
        return $path;
    }

    return app_url($path);
}

function current_page_url(): string
{
    $page = (string) ($_GET['page'] ?? 'home');
    $params = $_GET;
    unset($params['page']);

    if ($page === 'home' && $params === []) {
        return app_url('/');
    }

    return absolute_route($page, $params);
}

function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

function flash(string $key, ?string $message = null): ?string
{
    if ($message !== null) {
        $_SESSION['_flash'][$key] = $message;

        return null;
    }

    if (!isset($_SESSION['_flash'][$key])) {
        return null;
    }

    $stored = $_SESSION['_flash'][$key];
    unset($_SESSION['_flash'][$key]);

    return $stored;
}

function old(string $key, string $default = ''): string
{
    return (string) ($_SESSION['_old'][$key] ?? $default);
}

function keep_old(array $data): void
{
    $_SESSION['_old'] = $data;
}

function clear_old(): void
{
    unset($_SESSION['_old']);
}

function render(string $view, array $data = []): void
{
    $branding = (new BrandingRepository())->current();
    $theme = ThemeCatalog::resolve($branding);
    $authUser = Auth::user();
    $success = flash('success');
    $error = flash('error');
    extract($data, EXTR_SKIP);
    require base_path('app/Views/' . $view . '.php');
    clear_old();
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(Csrf::token()) . '">';
}

function php_size_to_bytes(?string $value): int
{
    $value = trim((string) $value);

    if ($value === '') {
        return 0;
    }

    $unit = strtolower(substr($value, -1));
    $number = (float) $value;

    return (int) match ($unit) {
        'g' => $number * 1024 * 1024 * 1024,
        'm' => $number * 1024 * 1024,
        'k' => $number * 1024,
        default => $number,
    };
}

function human_file_size(int $bytes): string
{
    if ($bytes >= 1024 * 1024) {
        return rtrim(rtrim(number_format($bytes / 1024 / 1024, 1, ',', '.'), '0'), ',') . ' MB';
    }

    if ($bytes >= 1024) {
        return rtrim(rtrim(number_format($bytes / 1024, 1, ',', '.'), '0'), ',') . ' KB';
    }

    return $bytes . ' bytes';
}

function upload_limit_label(): string
{
    $limits = array_filter([
        php_size_to_bytes(ini_get('upload_max_filesize')),
        php_size_to_bytes(ini_get('post_max_size')),
    ], static fn (int $bytes): bool => $bytes > 0);

    if ($limits === []) {
        return 'limite do servidor';
    }

    return human_file_size(min($limits));
}

function post_limit_exceeded(): bool
{
    $contentLength = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
    $postMaxSize = php_size_to_bytes(ini_get('post_max_size'));

    return $contentLength > 0
        && $postMaxSize > 0
        && $contentLength > $postMaxSize
        && $_POST === []
        && $_FILES === [];
}

function upload_error_message(int $error): string
{
    return match ($error) {
        UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'O arquivo excede o limite de upload do servidor (' . upload_limit_label() . ').',
        UPLOAD_ERR_PARTIAL => 'O envio do arquivo foi interrompido. Tente novamente.',
        UPLOAD_ERR_NO_FILE => 'Selecione um arquivo para enviar.',
        UPLOAD_ERR_NO_TMP_DIR => 'O servidor nao encontrou a pasta temporaria de upload.',
        UPLOAD_ERR_CANT_WRITE => 'O servidor nao conseguiu gravar o arquivo enviado.',
        UPLOAD_ERR_EXTENSION => 'Uma extensao do servidor bloqueou o upload.',
        default => 'Nao foi possivel enviar o arquivo.',
    };
}

function youtube_video_id(string $url): ?string
{
    $parsed = parse_url(trim($url));

    if (!$parsed) {
        return null;
    }

    $host = $parsed['host'] ?? '';
    $path = $parsed['path'] ?? '';

    if (str_contains($host, 'youtu.be')) {
        return trim($path, '/') ?: null;
    }

    if (str_contains($host, 'youtube.com')) {
        parse_str($parsed['query'] ?? '', $query);

        if (!empty($query['v'])) {
            return (string) $query['v'];
        }

        if (str_starts_with($path, '/embed/')) {
            return basename($path);
        }
    }

    return null;
}

function youtube_thumbnail(?string $videoId): ?string
{
    if (!$videoId) {
        return null;
    }

    return 'https://img.youtube.com/vi/' . rawurlencode($videoId) . '/hqdefault.jpg';
}

function google_form_embed_url(?string $url): ?string
{
    if (!$url) {
        return null;
    }

    $normalized = trim($url);

    if (str_contains($normalized, 'embedded=true')) {
        return $normalized;
    }

    if (str_contains($normalized, '/viewform')) {
        return $normalized . (str_contains($normalized, '?') ? '&' : '?') . 'embedded=true';
    }

    return $normalized;
}

function branding_logo_url(?string $logoPath): ?string
{
    if (!$logoPath) {
        return null;
    }

    if (preg_match('#^https?://#i', $logoPath) !== 1) {
        return uploaded_file_url($logoPath);
    }

    return absolute_url($logoPath);
}

function uploaded_file_url(?string $path): ?string
{
    if ($path === null || $path === '') {
        return null;
    }

    if (preg_match('#^https?://#i', $path) === 1) {
        return $path;
    }

    return route('file', ['path' => $path]);
}

function absolute_uploaded_file_url(?string $path): ?string
{
    if ($path === null || $path === '') {
        return null;
    }

    if (preg_match('#^https?://#i', $path) === 1) {
        return $path;
    }

    return absolute_route('file', ['path' => $path]);
}

function lesson_category_label(?string $categoryName): string
{
    $categoryName = trim((string) $categoryName);

    return $categoryName !== '' ? $categoryName : 'Sem categoria';
}

function public_file_path(?string $path): ?string
{
    if (!$path) {
        return null;
    }

    if (preg_match('#^https?://#i', $path) === 1) {
        return null;
    }

    if (preg_match('#^[A-Za-z]:[\\\\/]#', $path) === 1 || str_starts_with($path, '/')) {
        return $path;
    }

    return base_path($path);
}

function relative_storage_path(string $absolutePath): string
{
    $base = rtrim(str_replace('\\', '/', base_path()), '/');
    $normalized = str_replace('\\', '/', $absolutePath);

    if (str_starts_with($normalized, $base . '/')) {
        return ltrim(substr($normalized, strlen($base)), '/');
    }

    $parentBase = rtrim(str_replace('\\', '/', dirname(base_path())), '/');

    if (str_starts_with($normalized, $parentBase . '/')) {
        return '../' . ltrim(substr($normalized, strlen($parentBase)), '/');
    }

    return ltrim($normalized, '/');
}

function output_uploaded_file(?string $path, bool $download = false, ?string $downloadName = null): never
{
    if ($path === null || $path === '' || preg_match('#^https?://#i', $path) === 1) {
        http_response_code(404);
        exit;
    }

    $fullPath = public_file_path($path);
    $uploadsRoot = realpath(upload_dir());
    $resolvedPath = $fullPath !== null ? realpath($fullPath) : false;

    if (
        !$uploadsRoot
        || !$resolvedPath
        || !is_file($resolvedPath)
        || !str_starts_with($resolvedPath, $uploadsRoot)
    ) {
        http_response_code(404);
        exit;
    }

    header('Content-Type: ' . file_mime_type_from_path($resolvedPath));
    header('Content-Length: ' . (string) filesize($resolvedPath));
    header('Cache-Control: public, max-age=3600');
    header('X-Content-Type-Options: nosniff');

    if ($download) {
        $fileName = sanitize_uploaded_file_name($downloadName ?: basename($resolvedPath), 'arquivo');
        header('Content-Disposition: attachment; filename="' . addslashes($fileName) . '"');
    }

    readfile($resolvedPath);
    exit;
}

function file_mime_type_from_path(string $path): string
{
    $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

    return match ($extension) {
        'png' => 'image/png',
        'jpg', 'jpeg', 'jfif' => 'image/jpeg',
        'webp' => 'image/webp',
        'svg' => 'image/svg+xml',
        'pdf' => 'application/pdf',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'ppt', 'pps' => 'application/vnd.ms-powerpoint',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'ppsx' => 'application/vnd.openxmlformats-officedocument.presentationml.slideshow',
        'odp' => 'application/vnd.oasis.opendocument.presentation',
        'html', 'htm' => 'text/html; charset=UTF-8',
        default => 'application/octet-stream',
    };
}

function file_data_uri(?string $path): ?string
{
    $fullPath = public_file_path($path);

    if (!$fullPath || !is_file($fullPath)) {
        return null;
    }

    $contents = file_get_contents($fullPath);

    if ($contents === false) {
        return null;
    }

    return 'data:' . file_mime_type_from_path($fullPath) . ';base64,' . base64_encode($contents);
}

function site_initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $initials = '';

    foreach ($parts as $part) {
        if ($part === '') {
            continue;
        }

        $initials .= mb_strtoupper(mb_substr($part, 0, 1));

        if (mb_strlen($initials) >= 2) {
            break;
        }
    }

    return $initials !== '' ? $initials : 'SL';
}

function teacher_term_title(): string
{
    return 'TERMO DE CIENCIA E CONCORDANCIA - PROFESSORES';
}

function teacher_term_version(): string
{
    return 'wise360-professores-2026-05-29-v2';
}

function teacher_term_body(): string
{
    return <<<'TEXT'
TERMO DE CIENCIA E CONCORDANCIA - PROFESSORES
Plataforma Educacional - Wise360

Este termo estabelece as diretrizes para a organizacao e registro das atividades pedagogicas realizadas pelos professores, bem como o uso da plataforma educacional disponibilizada pela instituicao.

Ao aceitar este termo, o professor declara estar ciente e de acordo com as seguintes orientacoes:

1. Registro das Aulas na Plataforma
Toda aula prevista no cronograma devera estar disponivel na plataforma em formato de video, com o objetivo de servir como material de revisao para os alunos.
- A aula podera ser publicada com video, arquivo ou apenas materiais de apoio, a criterio do professor.
- O conteudo principal podera ser acompanhado opcionalmente de um formulario de avaliacao no Google Forms, a criterio do professor.
- O conteudo disponibilizado devera corresponder a aula realizada presencialmente.

2. Entrega do Plano de Aula
O professor devera entregar previamente o plano de aula, garantindo a organizacao do conteudo que sera ministrado e registrado na plataforma.

3. Horario de Entrada do Professor
O professor devera chegar as 8h30, com a finalidade de:
- Preparar o ambiente da sala de aula
- Organizar os equipamentos necessarios
- Verificar materiais didaticos e recursos utilizados na aula

O horario da aula sera:
- Inicio: 9h00
- Termino: 11h00
- Nao havera intervalo durante a aula

4. Registro de Presenca (Chamada)
A chamada dos alunos devera ser realizada obrigatoriamente pela plataforma, no inicio de cada aula.

5. Registro de Observacoes
Qualquer comentario, observacao pedagogica ou anotacao relevante sobre algum aluno devera ser registrada na plataforma para acompanhamento institucional.

6. Acesso dos Pais e Alunos
Os pais e alunos terao acesso a plataforma, podendo acompanhar:
- Conteudo das aulas
- Videos disponibilizados
- Frequencia dos alunos

7. Plataforma Oficial
Todas as atividades descritas neste termo deverao ser realizadas na seguinte plataforma:
https://wise360.org/aulas

Declaracao de Concordancia
Declaro que li, compreendi e concordo com todas as diretrizes estabelecidas neste termo, comprometendo-me a seguir os procedimentos descritos para o adequado funcionamento das atividades pedagogicas.
TEXT;
}

function app_icon_svg(array $branding, int $size = 512): string
{
    $size = max(96, min(1024, $size));
    $siteName = (string) ($branding['site_name'] ?? env('APP_NAME', 'Sistema de Aulas Online'));
    $primary = sanitize_hex_color($branding['primary_color'] ?? '#12355b', '#12355b');
    $accent = sanitize_hex_color($branding['accent_color'] ?? '#ef476f', '#ef476f');
    $logoDataUri = file_data_uri($branding['logo_path'] ?? null);
    $initials = site_initials($siteName);

    $svg = [
        sprintf('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 %1$d %1$d" width="%1$d" height="%1$d">', $size),
        '<defs>',
        '<linearGradient id="bg" x1="0%" y1="0%" x2="100%" y2="100%">',
        '<stop offset="0%" stop-color="' . e($primary) . '"/>',
        '<stop offset="100%" stop-color="' . e($accent) . '"/>',
        '</linearGradient>',
        '<clipPath id="iconClip"><rect x="' . (int) ($size * 0.14) . '" y="' . (int) ($size * 0.14) . '" width="' . (int) ($size * 0.72) . '" height="' . (int) ($size * 0.72) . '" rx="' . (int) ($size * 0.18) . '" ry="' . (int) ($size * 0.18) . '"/></clipPath>',
        '</defs>',
        '<rect width="100%" height="100%" rx="' . (int) ($size * 0.22) . '" fill="url(#bg)"/>',
        '<circle cx="' . (int) ($size * 0.82) . '" cy="' . (int) ($size * 0.18) . '" r="' . (int) ($size * 0.12) . '" fill="rgba(255,255,255,0.18)"/>',
    ];

    if ($logoDataUri !== null) {
        $svg[] = '<rect x="' . (int) ($size * 0.14) . '" y="' . (int) ($size * 0.14) . '" width="' . (int) ($size * 0.72) . '" height="' . (int) ($size * 0.72) . '" rx="' . (int) ($size * 0.18) . '" fill="rgba(255,255,255,0.16)"/>';
        $svg[] = '<image href="' . e($logoDataUri) . '" x="' . (int) ($size * 0.14) . '" y="' . (int) ($size * 0.14) . '" width="' . (int) ($size * 0.72) . '" height="' . (int) ($size * 0.72) . '" preserveAspectRatio="xMidYMid slice" clip-path="url(#iconClip)"/>';
    } else {
        $svg[] = '<text x="50%" y="54%" text-anchor="middle" dominant-baseline="middle" font-family="Georgia, Times New Roman, serif" font-size="' . (int) ($size * 0.30) . '" fill="#ffffff">' . e($initials) . '</text>';
    }

    $svg[] = '</svg>';

    return implode('', $svg);
}

function output_app_icon(array $branding, int $size = 512): never
{
    header('Content-Type: image/svg+xml; charset=UTF-8');
    header('Cache-Control: public, max-age=3600');
    echo app_icon_svg($branding, $size);
    exit;
}

function upload_dir(): string
{
    return base_path(env('UPLOAD_DIR', 'public/uploads'));
}

function client_ip(): ?string
{
    $forwarded = trim((string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? ''));

    if ($forwarded !== '') {
        $parts = array_map('trim', explode(',', $forwarded));

        return $parts[0] !== '' ? $parts[0] : null;
    }

    $realIp = trim((string) ($_SERVER['HTTP_X_REAL_IP'] ?? ''));

    if ($realIp !== '') {
        return $realIp;
    }

    $remoteAddr = trim((string) ($_SERVER['REMOTE_ADDR'] ?? ''));

    return $remoteAddr !== '' ? $remoteAddr : null;
}

function sanitize_hex_color(?string $value, string $default): string
{
    $value = strtolower(trim((string) $value));

    return preg_match('/^#[0-9a-f]{6}$/', $value) === 1 ? $value : $default;
}

function uploaded_image_extension(array $file): string
{
    $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);

    if ($error !== UPLOAD_ERR_OK) {
        throw new RuntimeException(upload_error_message($error));
    }

    $allowedByExtension = [
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'jfif' => 'image/jpeg',
        'webp' => 'image/webp',
    ];

    $extension = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));

    if (!isset($allowedByExtension[$extension])) {
        throw new RuntimeException('Envie uma imagem PNG, JPG, JFIF ou WEBP.');
    }

    $tmpName = (string) ($file['tmp_name'] ?? '');

    if ($tmpName === '' || !is_file($tmpName)) {
        throw new RuntimeException('Arquivo de imagem invalido.');
    }

    if (function_exists('exif_imagetype')) {
        $imageType = @exif_imagetype($tmpName);
        $allowedTypes = [
            IMAGETYPE_PNG,
            IMAGETYPE_JPEG,
            IMAGETYPE_WEBP,
        ];

        if ($imageType === false || !in_array($imageType, $allowedTypes, true)) {
            throw new RuntimeException('Envie uma imagem PNG, JPG, JFIF ou WEBP.');
        }
    } elseif (function_exists('getimagesize')) {
        $imageInfo = @getimagesize($tmpName);
        $mime = (string) ($imageInfo['mime'] ?? '');

        if (!in_array($mime, ['image/png', 'image/jpeg', 'image/webp'], true)) {
            throw new RuntimeException('Envie uma imagem PNG, JPG, JFIF ou WEBP.');
        }
    }

    return in_array($extension, ['jpeg', 'jfif'], true) ? 'jpg' : $extension;
}

function store_uploaded_image(array $file, string $prefix): string
{
    $extension = uploaded_image_extension($file);
    $targetDir = upload_dir();

    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    $fileName = sprintf('%s-%s-%s.%s', $prefix, time(), bin2hex(random_bytes(4)), $extension);
    $targetPath = rtrim($targetDir, '/\\') . DIRECTORY_SEPARATOR . $fileName;

    if (!move_uploaded_file((string) $file['tmp_name'], $targetPath)) {
        throw new RuntimeException('Nao foi possivel salvar a imagem enviada.');
    }

    return relative_storage_path($targetPath);
}

function uploaded_plan_extension(array $file): string
{
    $allowedExtensions = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'pps', 'ppsx', 'odp', 'html', 'htm'];
    $extension = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));

    if (!in_array($extension, $allowedExtensions, true)) {
        throw new RuntimeException('Envie um arquivo em PDF, DOC, DOCX, PPT, PPTX, PPS, ODP ou HTML.');
    }

    $tmpName = (string) ($file['tmp_name'] ?? '');

    if ($tmpName === '' || !is_file($tmpName)) {
        throw new RuntimeException('Arquivo enviado invalido.');
    }

    return $extension === 'htm' ? 'html' : $extension;
}

function sanitize_uploaded_file_name(string $name, string $fallback): string
{
    $name = trim(basename($name));

    if ($name === '') {
        return $fallback;
    }

    $name = preg_replace('/[^A-Za-z0-9._ -]/', '-', $name) ?? $fallback;
    $name = preg_replace('/\s+/', ' ', $name) ?? $fallback;

    return trim($name) !== '' ? trim($name) : $fallback;
}

function sanitize_html_document(string $html): string
{
    $cleanHtml = preg_replace('#<script\b[^>]*>.*?</script>#is', '', $html) ?? $html;
    $cleanHtml = preg_replace('#<(iframe|object|embed|base|link|meta)\b[^>]*?>.*?</\1>#is', '', $cleanHtml) ?? $cleanHtml;
    $cleanHtml = preg_replace('#<(iframe|object|embed|base|link|meta)\b[^>]*/?>#is', '', $cleanHtml) ?? $cleanHtml;
    $cleanHtml = preg_replace('/\son[a-z]+\s*=\s*(".*?"|\'.*?\'|[^\s>]+)/is', '', $cleanHtml) ?? $cleanHtml;
    $cleanHtml = preg_replace('/\s(href|src)\s*=\s*("|\')\s*javascript:.*?\2/is', '', $cleanHtml) ?? $cleanHtml;

    if (stripos($cleanHtml, '<html') === false) {
        $cleanHtml = '<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Plano de aula</title></head><body>'
            . $cleanHtml
            . '</body></html>';
    }

    return $cleanHtml;
}

function store_uploaded_plan(array $file, string $prefix = 'lesson-plan'): array
{
    $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);

    if ($error !== UPLOAD_ERR_OK) {
        throw new RuntimeException(upload_error_message($error));
    }

    $extension = uploaded_plan_extension($file);
    $targetDir = upload_dir();

    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    $fileName = sprintf('%s-%s-%s.%s', $prefix, time(), bin2hex(random_bytes(4)), $extension);
    $targetPath = rtrim($targetDir, '/\\') . DIRECTORY_SEPARATOR . $fileName;
    $originalName = sanitize_uploaded_file_name(
        (string) ($file['name'] ?? ''),
        'arquivo-da-aula.' . $extension
    );

    if ($extension === 'html') {
        $contents = file_get_contents((string) $file['tmp_name']);

        if ($contents === false) {
            throw new RuntimeException('Nao foi possivel ler o HTML enviado.');
        }

        if (file_put_contents($targetPath, sanitize_html_document($contents)) === false) {
            throw new RuntimeException('Nao foi possivel salvar o arquivo HTML.');
        }
    } elseif (!move_uploaded_file((string) $file['tmp_name'], $targetPath)) {
        throw new RuntimeException('Nao foi possivel salvar o arquivo enviado.');
    }

    return [
        'file_path' => relative_storage_path($targetPath),
        'original_name' => $originalName,
    ];
}

function store_lesson_content_file(array $file): array
{
    return store_uploaded_plan($file, 'lesson-content');
}

function lesson_plan_extension(?string $path): ?string
{
    if (!$path) {
        return null;
    }

    $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

    return $extension !== '' ? $extension : null;
}

function lesson_plan_type(?string $path): ?string
{
    return match (lesson_plan_extension($path)) {
        'html', 'htm' => 'html',
        'pdf' => 'pdf',
        'doc', 'docx' => 'document',
        'ppt', 'pptx', 'pps', 'ppsx', 'odp' => 'presentation',
        default => null,
    };
}

function lesson_plan_label(?string $path): string
{
    return match (lesson_plan_type($path)) {
        'html' => 'HTML',
        'pdf' => 'PDF',
        'document' => in_array(lesson_plan_extension($path), ['doc', 'docx'], true)
            ? strtoupper((string) lesson_plan_extension($path))
            : 'Documento',
        'presentation' => in_array(lesson_plan_extension($path), ['ppt', 'pptx', 'pps', 'ppsx', 'odp'], true)
            ? strtoupper((string) lesson_plan_extension($path))
            : 'Apresentacao',
        default => 'Arquivo',
    };
}

function lesson_content_type(array $lesson): string
{
    if (($lesson['content_type'] ?? 'youtube') === 'file' && !empty($lesson['content_file_path'])) {
        return 'file';
    }

    if (!empty($lesson['youtube_video_id'])) {
        return 'youtube';
    }

    return 'none';
}

function lesson_content_label(array $lesson): string
{
    return match (lesson_content_type($lesson)) {
        'file' => lesson_plan_label($lesson['content_file_path'] ?? null),
        'youtube' => 'YouTube',
        default => 'Sem conteudo principal',
    };
}

function lesson_file_viewer_url(?string $path): ?string
{
    $fileUrl = uploaded_file_url($path);

    if ($fileUrl === null) {
        return null;
    }

    return match (lesson_plan_type($path)) {
        'document', 'presentation' => 'https://view.officeapps.live.com/op/embed.aspx?src=' . rawurlencode(absolute_uploaded_file_url($path) ?? $fileUrl),
        default => $fileUrl,
    };
}

function uploaded_files(string $field): array
{
    $files = $_FILES[$field] ?? null;

    if (!is_array($files) || !isset($files['name'])) {
        return [];
    }

    $names = $files['name'];

    if (!is_array($names)) {
        if ((int) ($files['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return [];
        }

        return [$files];
    }

    $normalized = [];

    foreach (array_keys($names) as $index) {
        $error = (int) ($files['error'][$index] ?? UPLOAD_ERR_NO_FILE);

        if ($error === UPLOAD_ERR_NO_FILE) {
            continue;
        }

        $normalized[] = [
            'name' => (string) ($files['name'][$index] ?? ''),
            'type' => (string) ($files['type'][$index] ?? ''),
            'tmp_name' => (string) ($files['tmp_name'][$index] ?? ''),
            'error' => $error,
            'size' => (int) ($files['size'][$index] ?? 0),
        ];
    }

    return $normalized;
}

function delete_uploaded_file(?string $path): void
{
    if (!$path || preg_match('#^https?://#i', $path) === 1) {
        return;
    }

    $fullPath = public_file_path($path);
    $uploadsRoot = realpath(upload_dir());
    $resolvedPath = $fullPath !== null ? realpath($fullPath) : false;

    if (
        $uploadsRoot
        && $resolvedPath
        && is_file($resolvedPath)
        && str_starts_with($resolvedPath, $uploadsRoot)
    ) {
        @unlink($resolvedPath);
    }
}
