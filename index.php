<?php

declare(strict_types=1);

use App\Repositories\AttendanceRepository;
use App\Repositories\BrandingRepository;
use App\Repositories\CourseRepository;
use App\Repositories\LessonMaterialRepository;
use App\Repositories\LessonRepository;
use App\Repositories\LessonPhotoRepository;
use App\Repositories\StudentRepository;
use App\Repositories\StudentNoteRepository;
use App\Repositories\TeacherAccessLogRepository;
use App\Repositories\UserRepository;
use App\Support\Auth;
use App\Support\Csrf;
use App\Support\ThemeCatalog;

$bootstrapPath = __DIR__ . '/app/bootstrap.php';

if (!is_file($bootstrapPath)) {
    $bootstrapPath = dirname(__DIR__) . '/app/bootstrap.php';
}

require $bootstrapPath;

$lessonRepository = new LessonRepository();
$courseRepository = new CourseRepository();
$studentRepository = new StudentRepository();
$studentNoteRepository = new StudentNoteRepository();
$lessonMaterialRepository = new LessonMaterialRepository();
$lessonPhotoRepository = new LessonPhotoRepository();
$attendanceRepository = new AttendanceRepository();
$brandingRepository = new BrandingRepository();
$userRepository = new UserRepository();
$teacherAccessLogRepository = new TeacherAccessLogRepository();
$teacherTermTitle = static fn (): string => function_exists('teacher_term_title')
    ? teacher_term_title()
    : 'TERMO DE CIENCIA E CONCORDANCIA - PROFESSORES';
$teacherTermVersion = static fn (): string => function_exists('teacher_term_version')
    ? teacher_term_version()
    : 'wise360-professores-2026-05-29-v2';
$teacherTermBody = static fn (): string => function_exists('teacher_term_body')
    ? teacher_term_body()
    : <<<'TEXT'
TERMO DE CIENCIA E CONCORDANCIA - PROFESSORES
Plataforma Educacional - Wise360

Este termo estabelece as diretrizes para a organizacao e registro das atividades pedagogicas realizadas pelos professores, bem como o uso da plataforma educacional disponibilizada pela instituicao.

Ao aceitar este termo, o professor declara estar ciente e de acordo com as seguintes orientacoes:

1. Registro das Aulas na Plataforma
Toda aula prevista no cronograma devera estar disponivel na plataforma como material de revisao para os alunos.
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
$ensureTeacherTermsColumns = static function (): void {
    static $checked = false;

    if ($checked) {
        return;
    }

    $checked = true;
    $pdo = App\Core\Database::connection();
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

    $columnExists = static function (string $column) use ($pdo, $driver): bool {
        if ($driver === 'mysql') {
            $statement = $pdo->prepare('SHOW COLUMNS FROM users LIKE :column');
            $statement->execute(['column' => $column]);

            return (bool) $statement->fetchColumn();
        }

        $statement = $pdo->query('PRAGMA table_info(users)');

        foreach ($statement->fetchAll() as $row) {
            if (($row['name'] ?? null) === $column) {
                return true;
            }
        }

        return false;
    };

    $columns = [
        'teacher_terms_version' => $driver === 'mysql'
            ? 'ALTER TABLE users ADD COLUMN teacher_terms_version VARCHAR(80) NULL'
            : 'ALTER TABLE users ADD COLUMN teacher_terms_version TEXT NULL',
        'teacher_terms_content' => 'ALTER TABLE users ADD COLUMN teacher_terms_content TEXT NULL',
        'teacher_terms_accepted_at' => $driver === 'mysql'
            ? 'ALTER TABLE users ADD COLUMN teacher_terms_accepted_at DATETIME NULL'
            : 'ALTER TABLE users ADD COLUMN teacher_terms_accepted_at TEXT NULL',
    ];

    foreach ($columns as $column => $sql) {
        if ($columnExists($column)) {
            continue;
        }

        try {
            $pdo->exec($sql);
        } catch (Throwable $exception) {
            if (!$columnExists($column)) {
                throw $exception;
            }
        }
    }
};
$teacherTermsAccepted = static function (array $user) use ($userRepository, $ensureTeacherTermsColumns): bool {
    $ensureTeacherTermsColumns();

    if (method_exists($userRepository, 'hasAcceptedTeacherTerms')) {
        return $userRepository->hasAcceptedTeacherTerms((int) $user['id']);
    }

    return trim((string) ($user['teacher_terms_accepted_at'] ?? '')) !== '';
};
$acceptTeacherTerms = static function (int $userId) use ($userRepository, $teacherTermVersion, $teacherTermBody, $ensureTeacherTermsColumns): void {
    $ensureTeacherTermsColumns();

    if (method_exists($userRepository, 'acceptTeacherTerms')) {
        $userRepository->acceptTeacherTerms($userId, $teacherTermVersion(), $teacherTermBody());

        return;
    }

    App\Core\Database::connection()->prepare(
        'UPDATE users
         SET teacher_terms_version = :teacher_terms_version,
             teacher_terms_content = :teacher_terms_content,
             teacher_terms_accepted_at = :teacher_terms_accepted_at
         WHERE id = :id AND role = :role'
    )->execute([
        'id' => $userId,
        'role' => 'teacher',
        'teacher_terms_version' => $teacherTermVersion(),
        'teacher_terms_content' => $teacherTermBody(),
        'teacher_terms_accepted_at' => date('Y-m-d H:i:s'),
    ]);
};
$lessonContentFromRequest = static function (?array $currentLesson = null): array {
    $defaultContentType = $currentLesson === null ? 'none' : lesson_content_type($currentLesson);
    $contentType = (string) ($_POST['content_type'] ?? $defaultContentType);
    $contentType = in_array($contentType, ['youtube', 'file', 'none'], true) ? $contentType : 'none';
    $youtubeUrl = trim((string) ($_POST['youtube_url'] ?? ''));
    $contentFilePath = $currentLesson['content_file_path'] ?? null;
    $contentOriginalName = $currentLesson['content_original_name'] ?? null;
    $contentUpload = $_FILES['lesson_file'] ?? null;
    $hasContentUpload = is_array($contentUpload)
        && (int) ($contentUpload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;

    if ($contentType === 'none') {
        if (!empty($contentFilePath)) {
            delete_uploaded_file($contentFilePath);
        }

        return [
            'content_type' => 'youtube',
            'content_file_path' => null,
            'content_original_name' => null,
            'youtube_url' => '',
            'youtube_video_id' => '',
        ];
    }

    if ($contentType === 'youtube') {
        if ($youtubeUrl === '') {
            throw new RuntimeException('Informe um link do YouTube valido ou selecione outro tipo de conteudo principal.');
        }

        $videoId = youtube_video_id($youtubeUrl);

        if (!$videoId) {
            throw new RuntimeException('Informe um link valido do YouTube.');
        }

        if (($currentLesson['content_type'] ?? 'youtube') === 'file' && !empty($contentFilePath)) {
            delete_uploaded_file($contentFilePath);
        }

        return [
            'content_type' => 'youtube',
            'content_file_path' => null,
            'content_original_name' => null,
            'youtube_url' => $youtubeUrl,
            'youtube_video_id' => $videoId,
        ];
    }

    if ($hasContentUpload) {
        $storedContent = store_lesson_content_file($contentUpload);

        if (!empty($contentFilePath)) {
            delete_uploaded_file($contentFilePath);
        }

        $contentFilePath = $storedContent['file_path'];
        $contentOriginalName = $storedContent['original_name'];
    }

    if (empty($contentFilePath)) {
        throw new RuntimeException('Envie um arquivo da aula em PDF, DOC, DOCX, PPT, PPTX, PPS, ODP ou HTML.');
    }

    return [
        'content_type' => 'file',
        'content_file_path' => $contentFilePath,
        'content_original_name' => $contentOriginalName,
        'youtube_url' => '',
        'youtube_video_id' => '',
    ];
};
$persistLessonMaterials = static function (int $lessonId, array $uploadedMaterials) use ($lessonMaterialRepository): void {
    foreach ($uploadedMaterials as $uploadedMaterial) {
        $storedMaterial = store_uploaded_attachment($uploadedMaterial, 'lesson-material');
        $lessonMaterialRepository->create(
            $lessonId,
            (string) $storedMaterial['file_path'],
            (string) ($storedMaterial['original_name'] ?? $uploadedMaterial['name'] ?? 'material-da-aula')
        );
    }
};
$deleteLessonWithFiles = static function (array $lesson) use ($lessonRepository, $lessonMaterialRepository, $lessonPhotoRepository): void {
    $lessonId = (int) ($lesson['id'] ?? 0);

    if ($lessonId <= 0) {
        throw new RuntimeException('Aula invalida para exclusao.');
    }

    $filePaths = array_filter([
        $lesson['content_file_path'] ?? null,
        $lesson['plan_file_path'] ?? null,
    ]);

    foreach ($lessonPhotoRepository->byLesson($lessonId) as $photo) {
        if (!empty($photo['file_path'])) {
            $filePaths[] = $photo['file_path'];
        }
    }

    foreach ($lessonMaterialRepository->byLesson($lessonId) as $material) {
        if (!empty($material['file_path'])) {
            $filePaths[] = $material['file_path'];
        }
    }

    $lessonRepository->delete($lessonId);

    foreach ($filePaths as $filePath) {
        delete_uploaded_file((string) $filePath);
    }
};
$renderTeacherTermPage = static function (array $data): void {
    $pageTitle = (string) ($data['pageTitle'] ?? 'Termo do Professor');
    $termTitle = (string) ($data['termTitle'] ?? 'TERMO DE CIENCIA E CONCORDANCIA - PROFESSORES');
    $termVersion = (string) ($data['termVersion'] ?? '');
    $termBody = (string) ($data['termBody'] ?? '');
    $acceptedAt = $data['acceptedAt'] ?? null;
    $headerPath = base_path('app/Views/partials/header.php');
    $footerPath = base_path('app/Views/partials/footer.php');
    $branding = (new BrandingRepository())->current();
    $theme = ThemeCatalog::resolve($branding);
    $authUser = Auth::user();
    $success = flash('success');
    $error = flash('error');

    if (is_file($headerPath)) {
        require $headerPath;
    } else {
        echo '<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>'
            . e($pageTitle)
            . '</title><link rel="stylesheet" href="assets/styles.css"></head><body><div class="page-shell"><main class="content">';
    }
    ?>
    <section class="hero compact">
        <div class="hero-copy">
            <span class="eyebrow">Professor</span>
            <h1>Termo de ciencia e concordancia</h1>
            <p>Leia atentamente as diretrizes da plataforma Wise360. O aceite e solicitado apenas uma vez e fica registrado para consulta posterior.</p>
        </div>
        <div class="hero-meta">
            <a class="button ghost" href="<?= e(route('teacher/dashboard')); ?>">Voltar ao painel</a>
        </div>
    </section>

    <section class="grid two-columns term-grid">
        <article class="panel">
            <div class="section-head">
                <div>
                    <span class="eyebrow">Registro</span>
                    <h2>Status do termo</h2>
                </div>
            </div>

            <?php if (!empty($acceptedAt)): ?>
                <div class="credentials">
                    <strong class="status-ok">Aceite registrado</strong>
                    <p class="small">Data do aceite: <?= date('d/m/Y H:i', strtotime((string) $acceptedAt)); ?></p>
                    <p class="small">Versao salva: <?= e($termVersion); ?></p>
                    <p class="small">Este termo permanece disponivel nesta pagina para leitura sempre que necessario.</p>
                </div>
            <?php else: ?>
                <form method="post" class="stack gap-md">
                    <?= csrf_field(); ?>
                    <label class="checkbox-inline">
                        <input type="checkbox" name="agree_teacher_terms" value="1" required>
                        <span>Li e concordo com o termo acima.</span>
                    </label>
                    <button class="button" type="submit">Registrar concordancia</button>
                </form>
            <?php endif; ?>
        </article>

        <article class="panel">
            <div class="section-head">
                <div>
                    <span class="eyebrow">Documento</span>
                    <h2><?= e($termTitle); ?></h2>
                </div>
            </div>

            <div class="term-document"><?= nl2br(e($termBody)); ?></div>
        </article>
    </section>
    <?php
    if (is_file($footerPath)) {
        require $footerPath;
    } else {
        echo '</main></div></body></html>';
    }
    clear_old();
};

$page = $_GET['page'] ?? 'home';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    if ($page === 'manifest') {
        $branding = $brandingRepository->current();

        header('Content-Type: application/manifest+json; charset=UTF-8');
        echo json_encode([
            'id' => app_url('/'),
            'name' => (string) ($branding['site_name'] ?? env('APP_NAME', 'Sistema de Aulas Online')),
            'short_name' => mb_substr((string) ($branding['site_name'] ?? 'Aulas'), 0, 24),
            'description' => 'Portal de aulas online com conteudo, presencas, atividades e acesso rapido para professores e alunos.',
            'start_url' => './',
            'scope' => './',
            'display' => 'standalone',
            'background_color' => (string) ($branding['secondary_color'] ?? '#f7efe5'),
            'theme_color' => (string) ($branding['primary_color'] ?? '#12355b'),
            'icons' => [
                [
                    'src' => absolute_route('app-icon', ['size' => 192]),
                    'sizes' => '192x192',
                    'type' => 'image/svg+xml',
                    'purpose' => 'any maskable',
                ],
                [
                    'src' => absolute_route('app-icon', ['size' => 512]),
                    'sizes' => '512x512',
                    'type' => 'image/svg+xml',
                    'purpose' => 'any maskable',
                ],
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return;
    }

    if ($page === 'app-icon') {
        output_app_icon($brandingRepository->current(), (int) ($_GET['size'] ?? 512));
    }

    if ($page === 'file') {
        output_uploaded_file(
            (string) ($_GET['path'] ?? ''),
            !empty($_GET['download']),
            isset($_GET['name']) ? (string) $_GET['name'] : null
        );
    }

    if ($page === 'file/view') {
        $filePath = (string) ($_GET['path'] ?? '');
        $fileUrl = uploaded_file_url($filePath);
        $fullPath = public_file_path($filePath);
        $uploadsRoot = realpath(upload_dir());
        $resolvedPath = $fullPath !== null ? realpath($fullPath) : false;

        if (
            $fileUrl === null
            || !$uploadsRoot
            || !$resolvedPath
            || !is_file($resolvedPath)
            || !str_starts_with($resolvedPath, $uploadsRoot)
        ) {
            throw new RuntimeException('Arquivo nao encontrado para visualizacao.');
        }

        $fileName = sanitize_uploaded_file_name(
            (string) ($_GET['name'] ?? basename($resolvedPath)),
            'arquivo'
        );
        $mimeType = file_mime_type_from_path($resolvedPath);

        render('file_viewer', [
            'pageTitle' => 'Visualizar arquivo',
            'filePath' => $filePath,
            'fileUrl' => $fileUrl,
            'downloadUrl' => route('file', [
                'path' => $filePath,
                'download' => 1,
                'name' => $fileName,
            ]),
            'fileName' => $fileName,
            'mimeType' => $mimeType,
            'isImage' => str_starts_with($mimeType, 'image/'),
        ]);

        return;
    }

    if ($method === 'POST') {
        if (post_limit_exceeded()) {
            throw new RuntimeException('O envio excedeu o limite aceito pelo servidor/hospedagem. Tente novamente com um arquivo menor ou ajuste a configuracao do PHP no servidor.');
        }

        Csrf::validate($_POST['_csrf'] ?? null);
    }

    if ($page === 'login') {
        if ($method === 'POST') {
            $login = trim((string) ($_POST['login'] ?? ''));
            $password = (string) ($_POST['password'] ?? '');

            keep_old(['login' => $login]);

            if ($login === '' || $password === '') {
                throw new RuntimeException('Informe nome de acesso e senha.');
            }

            if (!Auth::attempt($login, $password)) {
                throw new RuntimeException('Credenciais invalidas.');
            }

            clear_old();
            $user = Auth::user();

            if (($user['role'] ?? null) === 'teacher') {
                $teacherAccessLogRepository->create(
                    (int) $user['id'],
                    client_ip(),
                    (string) ($_SERVER['HTTP_USER_AGENT'] ?? '')
                );

                unset($_SESSION['teacher_term_seen_session']);
            }

            flash('success', 'Login realizado com sucesso.');
            if (($user['role'] ?? '') === 'admin') {
                redirect(route('admin/panel'));
            }

            redirect(route('teacher/term'));
        }

        render('login', ['pageTitle' => 'Login']);

        return;
    }

    if ($page === 'logout') {
        unset($_SESSION['teacher_term_seen_session']);
        Auth::logout();
        flash('success', 'Sessao encerrada.');
        redirect(route('login'));
    }

    if (str_starts_with($page, 'teacher/')) {
        Auth::requireRole('teacher');

        if ($page !== 'teacher/term' && !$teacherTermsAccepted((array) Auth::user())) {
            flash('error', 'Voce precisa ler e aceitar o termo antes de acessar a area do professor.');
            redirect(route('teacher/term'));
        }
    }

    if ($page === 'teacher/term') {
        Auth::requireRole('teacher');
        $teacher = Auth::user();
        $_SESSION['teacher_term_seen_session'] = true;

        if ($method === 'POST') {
            if ($teacherTermsAccepted((array) $teacher)) {
                flash('success', 'O termo ja foi aceito anteriormente.');
                redirect(route('teacher/term'));
            }

            if (($_POST['agree_teacher_terms'] ?? null) !== '1') {
                throw new RuntimeException('Marque a caixa de concordancia para continuar.');
            }

            $acceptTeacherTerms((int) $teacher['id']);

            flash('success', 'Termo registrado com sucesso.');
            redirect(route('teacher/dashboard'));
        }

        $renderTeacherTermPage([
            'pageTitle' => 'Termo do Professor',
            'termTitle' => $teacherTermTitle(),
            'termVersion' => (string) ($teacher['teacher_terms_version'] ?? $teacherTermVersion()),
            'termBody' => (string) ($teacher['teacher_terms_content'] ?? $teacherTermBody()),
            'acceptedAt' => $teacher['teacher_terms_accepted_at'] ?? null,
        ]);

        return;
    }

    if ($page === 'teacher/dashboard') {
        Auth::requireRole('teacher');

        if ($method === 'POST') {
            $teacher = Auth::user();
            $title = trim((string) ($_POST['title'] ?? ''));
            $categoryName = trim((string) ($_POST['category_name'] ?? ''));
            $contentType = (string) ($_POST['content_type'] ?? 'none');
            $youtubeUrl = trim((string) ($_POST['youtube_url'] ?? ''));
            $formUrl = trim((string) ($_POST['form_url'] ?? ''));
            $courseId = (int) ($_POST['course_id'] ?? 0);

            keep_old([
                'title' => $title,
                'category_name' => $categoryName,
                'content_type' => in_array($contentType, ['youtube', 'file', 'none'], true) ? $contentType : 'none',
                'youtube_url' => $youtubeUrl,
                'form_url' => $formUrl,
                'course_id' => (string) $courseId,
            ]);

            if ($courseId <= 0 || !$courseRepository->find($courseId)) {
                throw new RuntimeException('Selecione uma turma valida.');
            }

            if ($title === '') {
                throw new RuntimeException('Preencha o titulo da aula.');
            }

            if ($categoryName === '') {
                throw new RuntimeException('Informe a categoria da aula.');
            }

            $lessonContent = $lessonContentFromRequest();

            $lessonId = $lessonRepository->create([
                'course_id' => $courseId,
                'teacher_id' => (int) $teacher['id'],
                'title' => $title,
                'category_name' => $categoryName,
                'is_featured' => 0,
                'content_type' => $lessonContent['content_type'],
                'content_file_path' => $lessonContent['content_file_path'],
                'content_original_name' => $lessonContent['content_original_name'],
                'youtube_url' => $lessonContent['youtube_url'],
                'youtube_video_id' => $lessonContent['youtube_video_id'],
                'form_url' => $formUrl === '' ? null : google_form_embed_url($formUrl),
                'plan_file_path' => null,
                'plan_original_name' => null,
            ]);

            $uploadedMaterials = uploaded_files('lesson_materials');

            if ($uploadedMaterials !== []) {
                $persistLessonMaterials($lessonId, $uploadedMaterials);
            }

            clear_old();
            flash('success', 'Aula cadastrada. Agora registre a chamada.');
            redirect(route('teacher/attendance', ['lesson_id' => $lessonId]));
        }

        render('teacher_dashboard', [
            'pageTitle' => 'Painel do Professor',
            'courses' => $courseRepository->all(),
            'lessons' => $lessonRepository->allByTeacher((int) Auth::user()['id']),
        ]);

        return;
    }

    if ($page === 'teacher/lesson/edit') {
        Auth::requireRole('teacher');
        $teacher = Auth::user();
        $lessonId = (int) ($_GET['lesson_id'] ?? $_POST['lesson_id'] ?? 0);
        $lesson = $lessonRepository->find($lessonId);

        if (!$lesson || (int) $lesson['teacher_id'] !== (int) $teacher['id']) {
            throw new RuntimeException('Aula nao encontrada para este professor.');
        }

        if ($method === 'POST') {
            $action = (string) ($_POST['action'] ?? 'update_lesson');

            if ($action === 'upload_photos') {
                $uploadedPhotos = uploaded_files('lesson_photos');

                if ($uploadedPhotos === []) {
                    throw new RuntimeException('Selecione ao menos uma foto para enviar.');
                }

                foreach ($uploadedPhotos as $uploadedPhoto) {
                    $storedPath = store_uploaded_image($uploadedPhoto, 'lesson-photo');
                    $lessonPhotoRepository->create(
                        $lessonId,
                        $storedPath,
                        (string) ($uploadedPhoto['name'] ?? 'foto-da-aula.jpg')
                    );
                }

                flash('success', 'Fotos enviadas para a aula.');
                redirect(route('teacher/lesson/edit', ['lesson_id' => $lessonId]));
            }

            if ($action === 'delete_photo') {
                $photoId = (int) ($_POST['photo_id'] ?? 0);
                $photo = $lessonPhotoRepository->find($photoId);

                if (!$photo || (int) $photo['lesson_id'] !== $lessonId || (int) $photo['teacher_id'] !== (int) $teacher['id']) {
                    throw new RuntimeException('Foto nao encontrada para esta aula.');
                }

                delete_uploaded_file($photo['file_path'] ?? null);
                $lessonPhotoRepository->delete($photoId);
                flash('success', 'Foto removida.');
                redirect(route('teacher/lesson/edit', ['lesson_id' => $lessonId]));
            }

            if ($action === 'upload_materials') {
                $uploadedMaterials = uploaded_files('lesson_materials');

                if ($uploadedMaterials === []) {
                    throw new RuntimeException('Selecione ao menos um material de apoio para enviar.');
                }

                $persistLessonMaterials($lessonId, $uploadedMaterials);
                flash('success', 'Materiais de apoio adicionados a aula.');
                redirect(route('teacher/lesson/edit', ['lesson_id' => $lessonId]));
            }

            if ($action === 'delete_material') {
                $materialId = (int) ($_POST['material_id'] ?? 0);
                $material = $lessonMaterialRepository->find($materialId);

                if (!$material || (int) $material['lesson_id'] !== $lessonId || (int) $material['teacher_id'] !== (int) $teacher['id']) {
                    throw new RuntimeException('Material de apoio nao encontrado para esta aula.');
                }

                delete_uploaded_file($material['file_path'] ?? null);
                $lessonMaterialRepository->delete($materialId);
                flash('success', 'Material de apoio removido.');
                redirect(route('teacher/lesson/edit', ['lesson_id' => $lessonId]));
            }

            $title = trim((string) ($_POST['title'] ?? ''));
            $categoryName = trim((string) ($_POST['category_name'] ?? ''));
            $contentType = (string) ($_POST['content_type'] ?? lesson_content_type($lesson));
            $youtubeUrl = trim((string) ($_POST['youtube_url'] ?? ''));
            $formUrl = trim((string) ($_POST['form_url'] ?? ''));
            $courseId = (int) ($_POST['course_id'] ?? 0);
            $isFeatured = !empty($lesson['is_featured']) ? 1 : 0;

            keep_old([
                'title' => $title,
                'category_name' => $categoryName,
                'content_type' => in_array($contentType, ['youtube', 'file', 'none'], true) ? $contentType : 'none',
                'youtube_url' => $youtubeUrl,
                'form_url' => $formUrl,
                'course_id' => (string) $courseId,
            ]);

            if ($courseId <= 0 || !$courseRepository->find($courseId)) {
                throw new RuntimeException('Selecione uma turma valida.');
            }

            if ($title === '') {
                throw new RuntimeException('Preencha o titulo da aula.');
            }

            if ($categoryName === '') {
                throw new RuntimeException('Informe a categoria da aula.');
            }

            $lessonContent = $lessonContentFromRequest($lesson);

            $lessonRepository->update($lessonId, [
                'course_id' => $courseId,
                'title' => $title,
                'category_name' => $categoryName,
                'is_featured' => $isFeatured,
                'content_type' => $lessonContent['content_type'],
                'content_file_path' => $lessonContent['content_file_path'],
                'content_original_name' => $lessonContent['content_original_name'],
                'youtube_url' => $lessonContent['youtube_url'],
                'youtube_video_id' => $lessonContent['youtube_video_id'],
                'form_url' => $formUrl === '' ? null : google_form_embed_url($formUrl),
                'plan_file_path' => null,
                'plan_original_name' => null,
            ]);

            clear_old();
            flash('success', 'Aula atualizada.');
            redirect(route('teacher/dashboard'));
        }

        render('lesson_edit', [
            'pageTitle' => 'Editar Aula',
            'lesson' => $lesson,
            'courses' => $courseRepository->all(),
            'editScope' => 'teacher',
            'materials' => $lessonMaterialRepository->byLesson($lessonId),
            'photos' => $lessonPhotoRepository->byLesson($lessonId),
        ]);

        return;
    }

    if ($page === 'teacher/attendance') {
        Auth::requireRole('teacher');
        $teacher = Auth::user();
        $lessonId = (int) ($_GET['lesson_id'] ?? $_POST['lesson_id'] ?? 0);
        $lesson = $lessonRepository->find($lessonId);

        if (!$lesson || (int) $lesson['teacher_id'] !== (int) $teacher['id']) {
            throw new RuntimeException('Aula nao encontrada para este professor.');
        }

        $students = $studentRepository->byCourse((int) $lesson['course_id']);

        if ($method === 'POST') {
            $attendanceRepository->sync(
                $lessonId,
                (int) $teacher['id'],
                $students,
                array_map('intval', $_POST['present_students'] ?? [])
            );

            flash('success', 'Chamada atualizada.');
            redirect(route('teacher/attendance', ['lesson_id' => $lessonId]));
        }

        render('attendance', [
            'pageTitle' => 'Chamada',
            'lesson' => $lesson,
            'students' => $students,
            'statuses' => $attendanceRepository->lessonStatuses($lessonId),
        ]);

        return;
    }

    if ($page === 'teacher/students') {
        Auth::requireRole('teacher');
        $teacher = Auth::user();

        if ($method === 'POST') {
            $studentId = (int) ($_POST['student_id'] ?? 0);
            $note = trim((string) ($_POST['note'] ?? ''));
            $responseType = (string) ($_POST['response_type'] ?? '');

            if ($studentId <= 0 || !$studentRepository->belongsToTeacherCourses($studentId, (int) $teacher['id'])) {
                throw new RuntimeException('Aluno nao encontrado para este professor.');
            }

            if ($note === '') {
                throw new RuntimeException('Digite uma anotacao para o aluno.');
            }

            $studentNoteRepository->create($studentId, (int) $teacher['id'], $note);

            if ($responseType === 'json') {
                header('Content-Type: application/json; charset=UTF-8');
                echo json_encode([
                    'ok' => true,
                    'note' => [
                        'teacher_name' => (string) ($teacher['name'] ?? 'Professor'),
                        'note' => $note,
                        'created_at' => date('Y-m-d H:i:s'),
                        'created_at_label' => date('d/m/Y H:i'),
                    ],
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

                return;
            }

            flash('success', 'Anotacao salva para o aluno.');
            redirect(route('teacher/students'));
        }

        $studentsByCourse = $studentRepository->groupedByTeacherCourses((int) $teacher['id']);
        $studentIds = [];

        foreach ($studentsByCourse as $students) {
            foreach ($students as $student) {
                $studentIds[] = (int) $student['id'];
            }
        }

        render('teacher_students', [
            'pageTitle' => 'Alunos e Anotacoes',
            'studentsByCourse' => $studentsByCourse,
            'notesByStudent' => $studentNoteRepository->groupedByStudentIds($studentIds),
            'absenceCountsByStudent' => $attendanceRepository->absenceCountByStudentIdsForTeacher((int) $teacher['id'], $studentIds),
        ]);

        return;
    }

    if ($page === 'report') {
        Auth::requireRole(['teacher', 'admin']);
        $authUser = Auth::user();
        $isAdmin = ($authUser['role'] ?? null) === 'admin';
        $reportCourseId = (int) ($_GET['course_id'] ?? 0);
        $reportCourses = $isAdmin
            ? $courseRepository->all()
            : $courseRepository->forTeacher((int) ($authUser['id'] ?? 0));
        $selectedReportCourse = null;

        foreach ($reportCourses as $course) {
            if ((int) ($course['id'] ?? 0) === $reportCourseId) {
                $selectedReportCourse = $course;
                break;
            }
        }

        if ($reportCourseId > 0 && $selectedReportCourse === null) {
            $reportCourseId = 0;
        }

        $courseFilterId = $reportCourseId > 0 ? $reportCourseId : null;
        $totalAttendanceLessonCount = $attendanceRepository->attendanceLessonCountForAdmin();
        $reportAttendanceLessonCount = $isAdmin
            ? $attendanceRepository->attendanceLessonCountForAdmin($courseFilterId)
            : $attendanceRepository->attendanceLessonCountForTeacher((int) ($authUser['id'] ?? 0), $courseFilterId);

        render('report', [
            'pageTitle' => 'Relatorio de Faltas',
            'reportScope' => $isAdmin ? 'admin' : 'teacher',
            'attendanceLessonCount' => $reportAttendanceLessonCount,
            'reportAttendanceLessonCount' => $reportAttendanceLessonCount,
            'systemAttendanceLessonCount' => $totalAttendanceLessonCount,
            'reportCourseId' => $reportCourseId,
            'reportCourseName' => $selectedReportCourse['name'] ?? null,
            'reportCourses' => $reportCourses,
            'reportRows' => $isAdmin
                ? $attendanceRepository->absenceReportForAdmin($courseFilterId)
                : $attendanceRepository->absenceReportForTeacher((int) ($authUser['id'] ?? 0), $courseFilterId),
        ]);

        return;
    }

    if ($page === 'admin/panel') {
        Auth::requireRole('admin');

        if ($method === 'POST') {
            $action = (string) ($_POST['action'] ?? '');

            if ($action === 'branding') {
                $branding = $brandingRepository->current();
                $siteName = trim((string) ($_POST['site_name'] ?? ''));
                $themeKey = trim((string) ($_POST['theme_key'] ?? 'classic-slate'));
                $primaryColor = trim((string) ($_POST['primary_color'] ?? ''));
                $secondaryColor = trim((string) ($_POST['secondary_color'] ?? ''));
                $accentColor = trim((string) ($_POST['accent_color'] ?? ''));
                $logoPath = $branding['logo_path'] ?? null;
                $backgroundImagePath = $branding['background_image_path'] ?? null;
                $heroImagePath = $branding['hero_image_path'] ?? null;

                if ($siteName === '') {
                    throw new RuntimeException('Informe o nome do site.');
                }

                if (!ThemeCatalog::exists($themeKey)) {
                    throw new RuntimeException('Tema invalido.');
                }

                if ($themeKey !== 'custom') {
                    $themeConfig = ThemeCatalog::all()[$themeKey];
                    $primaryColor = $themeConfig['primary_color'];
                    $secondaryColor = $themeConfig['secondary_color'];
                    $accentColor = $themeConfig['accent_color'];
                } else {
                    $primaryColor = sanitize_hex_color($primaryColor, '#12355b');
                    $secondaryColor = sanitize_hex_color($secondaryColor, '#f7efe5');
                    $accentColor = sanitize_hex_color($accentColor, '#ef476f');
                }

                if (isset($_FILES['logo']) && (int) $_FILES['logo']['error'] !== UPLOAD_ERR_NO_FILE) {
                    if ((int) $_FILES['logo']['error'] !== UPLOAD_ERR_OK) {
                        throw new RuntimeException('Falha no upload da logo.');
                    }

                    $newLogoPath = store_uploaded_image($_FILES['logo'], 'logo');
                    delete_uploaded_file($logoPath);
                    $logoPath = $newLogoPath;
                }

                if (!empty($_POST['remove_background_image'])) {
                    delete_uploaded_file($backgroundImagePath);
                    $backgroundImagePath = null;
                }

                if (isset($_FILES['background_image']) && (int) $_FILES['background_image']['error'] !== UPLOAD_ERR_NO_FILE) {
                    if ((int) $_FILES['background_image']['error'] !== UPLOAD_ERR_OK) {
                        throw new RuntimeException('Falha no upload da imagem de fundo.');
                    }

                    $newBackgroundImagePath = store_uploaded_image($_FILES['background_image'], 'background');
                    delete_uploaded_file($backgroundImagePath);
                    $backgroundImagePath = $newBackgroundImagePath;
                }

                if (!empty($_POST['remove_hero_image'])) {
                    delete_uploaded_file($heroImagePath);
                    $heroImagePath = null;
                }

                if (isset($_FILES['hero_image']) && (int) $_FILES['hero_image']['error'] !== UPLOAD_ERR_NO_FILE) {
                    if ((int) $_FILES['hero_image']['error'] !== UPLOAD_ERR_OK) {
                        throw new RuntimeException('Falha no upload da hero image.');
                    }

                    $newHeroImagePath = store_uploaded_image($_FILES['hero_image'], 'hero');
                    delete_uploaded_file($heroImagePath);
                    $heroImagePath = $newHeroImagePath;
                }

                $brandingRepository->update([
                    'site_name' => $siteName,
                    'theme_key' => $themeKey,
                    'primary_color' => $primaryColor,
                    'secondary_color' => $secondaryColor,
                    'accent_color' => $accentColor,
                    'logo_path' => $logoPath,
                    'background_image_path' => $backgroundImagePath,
                    'hero_image_path' => $heroImagePath,
                ]);

                flash('success', 'Identidade visual atualizada.');
                redirect(route('admin/panel'));
            }

            if ($action === 'course') {
                $name = trim((string) ($_POST['course_name'] ?? ''));

                if ($name === '') {
                    throw new RuntimeException('Informe o nome da turma.');
                }

                $courseRepository->create($name);
                flash('success', 'Turma cadastrada.');
                redirect(route('admin/panel'));
            }

            if ($action === 'student') {
                $courseId = (int) ($_POST['student_course_id'] ?? 0);
                $name = trim((string) ($_POST['student_name'] ?? ''));

                if ($courseId <= 0 || !$courseRepository->find($courseId)) {
                    throw new RuntimeException('Selecione uma turma valida para o aluno.');
                }

                if ($name === '') {
                    throw new RuntimeException('Informe o nome do aluno.');
                }

                $studentRepository->create($courseId, $name);
                $_SESSION['admin_last_student_course_id'] = $courseId;
                flash('success', 'Aluno cadastrado.');
                redirect(route('admin/panel'));
            }

            if ($action === 'teacher') {
                $name = trim((string) ($_POST['teacher_name'] ?? ''));
                $password = (string) ($_POST['teacher_password'] ?? '');

                if ($name === '' || $password === '') {
                    throw new RuntimeException('Informe nome e senha do professor.');
                }

                if ($userRepository->loginNameExists($name)) {
                    throw new RuntimeException('Ja existe um acesso com esse nome.');
                }

                if (mb_strlen($password) < 4) {
                    throw new RuntimeException('A senha do professor precisa ter ao menos 4 caracteres.');
                }

                $userRepository->createTeacher($name, $password);
                flash('success', 'Professor cadastrado.');
                redirect(route('admin/panel'));
            }

            if ($action === 'delete_teacher') {
                $teacherId = (int) ($_POST['teacher_id'] ?? 0);
                $teacher = $userRepository->find($teacherId);

                if (!$teacher || ($teacher['role'] ?? null) !== 'teacher') {
                    throw new RuntimeException('Professor nao encontrado.');
                }

                $userRepository->deleteTeacher($teacherId);
                flash('success', 'Professor removido.');
                redirect(route('admin/panel'));
            }

            if ($action === 'delete_course') {
                $courseId = (int) ($_POST['course_id'] ?? 0);

                if ($courseId <= 0 || !$courseRepository->find($courseId)) {
                    throw new RuntimeException('Turma nao encontrada.');
                }

                $courseRepository->delete($courseId);
                flash('success', 'Turma removida.');
                redirect(route('admin/panel'));
            }

            if ($action === 'delete_student') {
                $studentId = (int) ($_POST['student_id'] ?? 0);
                $student = $studentRepository->find($studentId);

                if (!$student) {
                    throw new RuntimeException('Aluno nao encontrado.');
                }

                $studentRepository->delete($studentId);
                flash('success', 'Aluno removido.');
                redirect(route('admin/panel'));
            }

            if ($action === 'update_student_attendance_start') {
                $studentId = (int) ($_POST['student_id'] ?? 0);
                $student = $studentRepository->find($studentId);

                if (!$student) {
                    throw new RuntimeException('Aluno nao encontrado.');
                }

                $attendanceStartLessonId = (int) ($_POST['attendance_start_lesson_id'] ?? 0);
                $attendanceStartLesson = $attendanceStartLessonId > 0
                    ? $lessonRepository->find($attendanceStartLessonId)
                    : null;

                if ($attendanceStartLessonId > 0 && !$attendanceStartLesson) {
                    throw new RuntimeException('Aula inicial nao encontrada.');
                }

                if (
                    $attendanceStartLesson
                    && (int) ($attendanceStartLesson['course_id'] ?? 0) !== (int) ($student['course_id'] ?? 0)
                ) {
                    throw new RuntimeException('A aula inicial precisa pertencer a mesma turma do aluno.');
                }

                $studentRepository->updateAttendanceStartLessonId(
                    $studentId,
                    $attendanceStartLessonId > 0 ? $attendanceStartLessonId : null
                );

                flash('success', 'Regra de faltas do aluno atualizada.');
                redirect(route('admin/panel'));
            }

            if ($action === 'delete_lesson') {
                $lessonId = (int) ($_POST['lesson_id'] ?? 0);
                $lesson = $lessonRepository->find($lessonId);

                if (!$lesson) {
                    throw new RuntimeException('Aula nao encontrada.');
                }

                $deleteLessonWithFiles($lesson);
                flash('success', 'Aula removida.');
                redirect(route('admin/panel'));
            }

            if ($action === 'reorder_lessons') {
                $lessonOrder = trim((string) ($_POST['lesson_order'] ?? ''));
                $orderedLessonIds = array_values(array_filter(
                    array_map('intval', explode(',', $lessonOrder)),
                    static fn (int $id): bool => $id > 0
                ));

                if ($orderedLessonIds === []) {
                    throw new RuntimeException('Nenhuma aula foi enviada para reordenacao.');
                }

                $lessonRepository->reorder($orderedLessonIds);
                flash('success', 'Ordem das aulas atualizada.');
                redirect(route('admin/panel'));
            }

            if ($action === 'update_lesson_meta') {
                $lessonId = (int) ($_POST['lesson_id'] ?? 0);
                $lesson = $lessonRepository->find($lessonId);
                $categoryName = trim((string) ($_POST['category_name'] ?? ''));
                $isFeatured = !empty($_POST['is_featured']) ? 1 : 0;

                if (!$lesson) {
                    throw new RuntimeException('Aula nao encontrada.');
                }

                if ($categoryName === '') {
                    throw new RuntimeException('Informe a categoria da aula.');
                }

                $lessonRepository->update($lessonId, [
                    'course_id' => (int) $lesson['course_id'],
                    'title' => (string) $lesson['title'],
                    'category_name' => $categoryName,
                    'is_featured' => $isFeatured,
                    'content_type' => (string) ($lesson['content_type'] ?? 'youtube'),
                    'content_file_path' => $lesson['content_file_path'] ?: null,
                    'content_original_name' => $lesson['content_original_name'] ?: null,
                    'youtube_url' => (string) $lesson['youtube_url'],
                    'youtube_video_id' => (string) $lesson['youtube_video_id'],
                    'form_url' => $lesson['form_url'] ?: null,
                    'plan_file_path' => null,
                    'plan_original_name' => null,
                ]);

                flash('success', 'Aula atualizada pelo administrador.');
                redirect(route('admin/panel'));
            }

            throw new RuntimeException('Acao administrativa invalida.');
        }

        $courses = $courseRepository->all();
        $studentsByCourse = [];
        $lessons = $lessonRepository->allForAdmin();
        $lessonsByCourse = [];

        foreach ($lessons as $lesson) {
            $lessonsByCourse[(int) $lesson['course_id']][] = $lesson;
        }

        foreach ($lessonsByCourse as &$courseLessons) {
            usort($courseLessons, static function (array $left, array $right): int {
                $leftCreatedAt = (string) ($left['created_at'] ?? '');
                $rightCreatedAt = (string) ($right['created_at'] ?? '');

                if ($leftCreatedAt === $rightCreatedAt) {
                    return ((int) ($left['id'] ?? 0)) <=> ((int) ($right['id'] ?? 0));
                }

                return $leftCreatedAt <=> $rightCreatedAt;
            });
        }
        unset($courseLessons);

        foreach ($courses as $course) {
            $studentsByCourse[(int) $course['id']] = $studentRepository->byCourse((int) $course['id']);
        }

        render('admin_panel', [
            'pageTitle' => 'Painel Administrativo',
            'courses' => $courses,
            'studentsByCourse' => $studentsByCourse,
            'brandingData' => $brandingRepository->current(),
            'themeOptions' => ThemeCatalog::all(),
            'selectedStudentCourseId' => (int) ($_SESSION['admin_last_student_course_id'] ?? 0),
            'teachers' => $userRepository->allTeachers(),
            'lessons' => $lessons,
            'lessonsByCourse' => $lessonsByCourse,
            'teacherAccessLogs' => $teacherAccessLogRepository->latest(50),
        ]);

        return;
    }

    if ($page === 'admin/lesson/edit') {
        Auth::requireRole('admin');
        $lessonId = (int) ($_GET['lesson_id'] ?? $_POST['lesson_id'] ?? 0);
        $lesson = $lessonRepository->find($lessonId);

        if (!$lesson) {
            throw new RuntimeException('Aula nao encontrada.');
        }

        if ($method === 'POST') {
            $action = (string) ($_POST['action'] ?? 'update_lesson');

            if ($action === 'upload_photos') {
                $uploadedPhotos = uploaded_files('lesson_photos');

                if ($uploadedPhotos === []) {
                    throw new RuntimeException('Selecione ao menos uma foto para enviar.');
                }

                foreach ($uploadedPhotos as $uploadedPhoto) {
                    $storedPath = store_uploaded_image($uploadedPhoto, 'lesson-photo');
                    $lessonPhotoRepository->create(
                        $lessonId,
                        $storedPath,
                        (string) ($uploadedPhoto['name'] ?? 'foto-da-aula.jpg')
                    );
                }

                flash('success', 'Fotos enviadas para a aula.');
                redirect(route('admin/lesson/edit', ['lesson_id' => $lessonId]));
            }

            if ($action === 'delete_photo') {
                $photoId = (int) ($_POST['photo_id'] ?? 0);
                $photo = $lessonPhotoRepository->find($photoId);

                if (!$photo || (int) $photo['lesson_id'] !== $lessonId) {
                    throw new RuntimeException('Foto nao encontrada para esta aula.');
                }

                delete_uploaded_file($photo['file_path'] ?? null);
                $lessonPhotoRepository->delete($photoId);
                flash('success', 'Foto removida.');
                redirect(route('admin/lesson/edit', ['lesson_id' => $lessonId]));
            }

            if ($action === 'upload_materials') {
                $uploadedMaterials = uploaded_files('lesson_materials');

                if ($uploadedMaterials === []) {
                    throw new RuntimeException('Selecione ao menos um material de apoio para enviar.');
                }

                $persistLessonMaterials($lessonId, $uploadedMaterials);
                flash('success', 'Materiais de apoio adicionados a aula.');
                redirect(route('admin/lesson/edit', ['lesson_id' => $lessonId]));
            }

            if ($action === 'delete_material') {
                $materialId = (int) ($_POST['material_id'] ?? 0);
                $material = $lessonMaterialRepository->find($materialId);

                if (!$material || (int) $material['lesson_id'] !== $lessonId) {
                    throw new RuntimeException('Material de apoio nao encontrado para esta aula.');
                }

                delete_uploaded_file($material['file_path'] ?? null);
                $lessonMaterialRepository->delete($materialId);
                flash('success', 'Material de apoio removido.');
                redirect(route('admin/lesson/edit', ['lesson_id' => $lessonId]));
            }

            $title = trim((string) ($_POST['title'] ?? ''));
            $categoryName = trim((string) ($_POST['category_name'] ?? ''));
            $contentType = (string) ($_POST['content_type'] ?? lesson_content_type($lesson));
            $youtubeUrl = trim((string) ($_POST['youtube_url'] ?? ''));
            $formUrl = trim((string) ($_POST['form_url'] ?? ''));
            $courseId = (int) ($_POST['course_id'] ?? 0);
            $isFeatured = !empty($_POST['is_featured']) ? 1 : 0;

            keep_old([
                'title' => $title,
                'category_name' => $categoryName,
                'content_type' => in_array($contentType, ['youtube', 'file', 'none'], true) ? $contentType : 'none',
                'youtube_url' => $youtubeUrl,
                'form_url' => $formUrl,
                'course_id' => (string) $courseId,
                'is_featured' => (string) $isFeatured,
            ]);

            if ($courseId <= 0 || !$courseRepository->find($courseId)) {
                throw new RuntimeException('Selecione uma turma valida.');
            }

            if ($title === '') {
                throw new RuntimeException('Preencha o titulo da aula.');
            }

            if ($categoryName === '') {
                throw new RuntimeException('Informe a categoria da aula.');
            }

            $lessonContent = $lessonContentFromRequest($lesson);

            $lessonRepository->update($lessonId, [
                'course_id' => $courseId,
                'title' => $title,
                'category_name' => $categoryName,
                'is_featured' => $isFeatured,
                'content_type' => $lessonContent['content_type'],
                'content_file_path' => $lessonContent['content_file_path'],
                'content_original_name' => $lessonContent['content_original_name'],
                'youtube_url' => $lessonContent['youtube_url'],
                'youtube_video_id' => $lessonContent['youtube_video_id'],
                'form_url' => $formUrl === '' ? null : google_form_embed_url($formUrl),
                'plan_file_path' => null,
                'plan_original_name' => null,
            ]);

            clear_old();
            flash('success', 'Aula atualizada pelo administrador.');
            redirect(route('admin/panel'));
        }

        render('lesson_edit', [
            'pageTitle' => 'Editar Aula',
            'lesson' => $lesson,
            'courses' => $courseRepository->all(),
            'editScope' => 'admin',
            'materials' => $lessonMaterialRepository->byLesson($lessonId),
            'photos' => $lessonPhotoRepository->byLesson($lessonId),
        ]);

        return;
    }

    $lessons = $lessonRepository->allPublic();
    $attendanceByLesson = $attendanceRepository->summaryForPublicLessons(array_map(
        static fn (array $lesson): int => (int) $lesson['id'],
        $lessons
    ));

    render('home', [
        'pageTitle' => 'Aulas Disponiveis',
        'lessons' => $lessons,
        'attendanceByLesson' => $attendanceByLesson,
        'materialsByLesson' => $lessonMaterialRepository->groupedByLessonIds(array_map(
            static fn (array $lesson): int => (int) $lesson['id'],
            $lessons
        )),
        'photosByLesson' => $lessonPhotoRepository->groupedByLessonIds(array_map(
            static fn (array $lesson): int => (int) $lesson['id'],
            $lessons
        )),
    ]);
} catch (Throwable $exception) {
    $expectsJson = (($method === 'POST')
        && (((string) ($_POST['response_type'] ?? '')) === 'json'
            || strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest'
            || str_contains(strtolower((string) ($_SERVER['HTTP_ACCEPT'] ?? '')), 'application/json')));

    if ($expectsJson) {
        http_response_code(422);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'ok' => false,
            'message' => $exception->getMessage(),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return;
    }

    flash('error', $exception->getMessage());
    if ($method !== 'POST') {
        http_response_code(500);
        render('error', [
            'pageTitle' => 'Erro',
            'errorMessage' => $exception->getMessage(),
        ]);

        return;
    }

    redirect(route($page, array_filter([
        'lesson_id' => $_GET['lesson_id'] ?? $_POST['lesson_id'] ?? null,
    ], static fn ($value): bool => $value !== null && $value !== '')));
}
