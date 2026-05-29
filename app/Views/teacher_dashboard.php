<?php require base_path('app/Views/partials/header.php'); ?>
<section class="hero compact">
    <div class="hero-copy">
        <span class="eyebrow">Professor</span>
        <h1>Painel de publicacao de aulas</h1>
        <p>Bem-vindo, <?= e($authUser['name'] ?? 'Professor'); ?>. Este e o ambiente do Instituto Sabia Loucura para publicar aulas, registrar chamadas e acompanhar a turma.</p>
    </div>
    <div class="hero-meta">
        <a class="button ghost" href="<?= e(route('teacher/term')); ?>">Ler termo</a>
        <a class="button ghost" href="<?= e(route('teacher/students')); ?>">Ver alunos</a>
    </div>
</section>

<?php if (!empty($authUser['teacher_terms_accepted_at'])): ?>
    <section class="panel agreement-panel">
        <div>
            <span class="eyebrow">Termo institucional</span>
            <h2>Concordancia registrada</h2>
            <p>
                Aceite realizado em <?= date('d/m/Y H:i', strtotime((string) $authUser['teacher_terms_accepted_at'])); ?>.
                O documento continua disponivel para consulta sempre que precisar revisar as orientacoes.
            </p>
        </div>
        <div class="agreement-actions">
            <span class="agreement-chip">Termo aceito</span>
            <a class="button ghost" href="<?= e(route('teacher/term')); ?>">Rever documento</a>
        </div>
    </section>
<?php endif; ?>

<div class="grid two-columns">
    <section class="panel">
        <div class="section-head">
            <div>
                <span class="eyebrow">Nova aula</span>
                <h2>Enviar conteudo</h2>
            </div>
        </div>

        <form method="post" enctype="multipart/form-data" class="stack gap-md">
            <?= csrf_field(); ?>
            <label>
                <span>Professor</span>
                <input type="text" value="<?= e($authUser['name'] ?? ''); ?>" disabled>
            </label>
            <label>
                <span>Turma</span>
                <select name="course_id" required>
                    <option value="">Selecione</option>
                    <?php foreach ($courses as $course): ?>
                        <option value="<?= (int) $course['id']; ?>" <?= old('course_id') === (string) $course['id'] ? 'selected' : ''; ?>>
                            <?= e($course['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                <span>Titulo da aula</span>
                <input type="text" name="title" value="<?= e(old('title')); ?>" required>
            </label>
            <label>
                <span>Categoria</span>
                <input type="text" name="category_name" value="<?= e(old('category_name')); ?>" list="lesson-category-suggestions" placeholder="Ex.: Excel, IA, Habilidades Interpessoais" required>
            </label>
            <?php $selectedContentType = old('content_type', 'none'); ?>
            <div class="content-type-group">
                <span class="field-label">Conteudo principal da aula</span>
                <label class="checkbox-inline">
                    <input type="radio" name="content_type" value="youtube" <?= $selectedContentType === 'youtube' ? 'checked' : ''; ?>>
                    <span>Link do YouTube</span>
                </label>
                <label class="checkbox-inline">
                    <input type="radio" name="content_type" value="file" <?= $selectedContentType === 'file' ? 'checked' : ''; ?>>
                    <span>Arquivo PDF, documento ou slides</span>
                </label>
                <label class="checkbox-inline">
                    <input type="radio" name="content_type" value="none" <?= $selectedContentType === 'none' ? 'checked' : ''; ?>>
                    <span>Publicar sem conteudo principal por enquanto</span>
                </label>
            </div>
            <label>
                <span>Link do YouTube</span>
                <input type="url" name="youtube_url" value="<?= e(old('youtube_url')); ?>" placeholder="https://www.youtube.com/watch?v=...">
            </label>
            <label>
                <span>Arquivo da aula</span>
                <input type="file" name="lesson_file" accept=".pdf,.doc,.docx,.ppt,.pptx,.pps,.ppsx,.odp,.html,.htm">
            </label>
            <p class="small">O conteudo principal e opcional. Limite atual: <?= e(upload_limit_label()); ?> por envio. Arquivos PDF e HTML abrem direto na plataforma; documentos e slides usam um visualizador integrado quando a URL publica estiver acessivel.</p>
            <label>
                <span>Google Forms</span>
                <input type="url" name="form_url" value="<?= e(old('form_url')); ?>" placeholder="https://docs.google.com/forms/...">
            </label>
            <label>
                <span>Materiais de apoio</span>
                <input type="file" name="lesson_materials[]" accept=".pdf,.doc,.docx,.ppt,.pptx,.pps,.ppsx,.odp,.html,.htm" multiple>
            </label>
            <p class="small">Voce pode publicar a aula sem YouTube e anexar varios materiais de apoio para download pelos alunos.</p>
            <button class="button" type="submit">Salvar aula</button>
        </form>
        <datalist id="lesson-category-suggestions">
            <option value="Excel"></option>
            <option value="IA"></option>
            <option value="Habilidades Interpessoais"></option>
        </datalist>
    </section>

    <section class="panel">
        <div class="section-head">
            <div>
                <span class="eyebrow">Aulas cadastradas</span>
                <h2>Historico</h2>
            </div>
        </div>

        <?php if ($lessons === []): ?>
            <p class="empty-state">Nenhuma aula cadastrada ainda.</p>
        <?php else: ?>
            <div class="stack gap-sm">
                <?php foreach ($lessons as $lesson): ?>
                    <article class="lesson-row">
                        <div>
                            <strong><?= e($lesson['title']); ?></strong>
                            <span><?= e(lesson_category_label($lesson['category_name'] ?? null)); ?> | <?= e($lesson['course_name']); ?></span>
                            <small>
                                <?= date('d/m/Y H:i', strtotime($lesson['created_at'])); ?>
                                | <?= e(lesson_content_label($lesson)); ?>
                                | <?= (int) ($lesson['photo_count'] ?? 0); ?> fotos
                                | <?= (int) ($lesson['material_count'] ?? 0); ?> materiais
                                <?php if (!empty($lesson['is_featured'])): ?>
                                    | fixada no topo
                                <?php endif; ?>
                            </small>
                        </div>
                        <div class="lesson-metrics">
                            <span><?= (int) $lesson['present_count']; ?>/<?= (int) $lesson['total_students']; ?> presentes</span>
                            <a class="button ghost" href="<?= e(route('teacher/lesson/edit', ['lesson_id' => (int) $lesson['id']])); ?>">Editar aula</a>
                            <a class="button ghost" href="<?= e(route('teacher/attendance', ['lesson_id' => (int) $lesson['id']])); ?>">Chamada</a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>
<?php require base_path('app/Views/partials/footer.php'); ?>
