<?php require base_path('app/Views/partials/header.php'); ?>
<section class="hero compact">
    <div class="hero-copy">
        <span class="eyebrow">Editar aula</span>
        <h1><?= e($lesson['title']); ?></h1>
        <p><?= e($lesson['teacher_name']); ?> | <?= e($lesson['course_name']); ?></p>
    </div>
    <div class="hero-meta">
        <a class="button ghost" href="<?= e($editScope === 'admin' ? route('admin/panel') : route('teacher/dashboard')); ?>">
            Voltar
        </a>
    </div>
</section>

<section class="panel">
    <div class="section-head">
        <div>
            <span class="eyebrow">Atualizacao</span>
            <h2>Dados da aula</h2>
        </div>
    </div>

    <form method="post" enctype="multipart/form-data" class="stack gap-md">
        <?= csrf_field(); ?>
        <input type="hidden" name="lesson_id" value="<?= (int) $lesson['id']; ?>">
        <input type="hidden" name="action" value="update_lesson">
        <label>
            <span>Professor</span>
            <input type="text" value="<?= e($lesson['teacher_name']); ?>" disabled>
        </label>
        <label>
            <span>Turma</span>
            <select name="course_id" required>
                <option value="">Selecione</option>
                <?php foreach ($courses as $course): ?>
                    <?php $selectedCourseId = old('course_id', (string) $lesson['course_id']); ?>
                    <option value="<?= (int) $course['id']; ?>" <?= $selectedCourseId === (string) $course['id'] ? 'selected' : ''; ?>>
                        <?= e($course['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            <span>Titulo da aula</span>
            <input type="text" name="title" value="<?= e(old('title', $lesson['title'])); ?>" required>
        </label>
        <label>
            <span>Categoria</span>
            <input type="text" name="category_name" value="<?= e(old('category_name', (string) ($lesson['category_name'] ?? ''))); ?>" list="lesson-category-suggestions" placeholder="Ex.: Excel, IA, Habilidades Interpessoais" required>
        </label>
        <?php $selectedContentType = old('content_type', lesson_content_type($lesson)); ?>
        <div class="content-type-group">
            <span class="field-label">Conteudo principal da aula</span>
            <label class="checkbox-inline">
                <input type="radio" name="content_type" value="youtube" <?= $selectedContentType !== 'file' ? 'checked' : ''; ?>>
                <span>Link do YouTube</span>
            </label>
            <label class="checkbox-inline">
                <input type="radio" name="content_type" value="file" <?= $selectedContentType === 'file' ? 'checked' : ''; ?>>
                <span>Arquivo PDF, documento ou slides</span>
            </label>
        </div>
        <label>
            <span>Link do YouTube</span>
            <input type="url" name="youtube_url" value="<?= e(old('youtube_url', $lesson['youtube_url'])); ?>" placeholder="https://www.youtube.com/watch?v=...">
        </label>
        <label>
            <span>Arquivo da aula</span>
            <input type="file" name="lesson_file" accept=".pdf,.doc,.docx,.ppt,.pptx,.pps,.ppsx,.odp,.html,.htm">
        </label>
        <?php if (!empty($lesson['content_file_path'])): ?>
            <div class="resource-card">
                <div>
                    <span class="eyebrow">Arquivo principal atual</span>
                    <strong><?= e($lesson['content_original_name'] ?? basename((string) $lesson['content_file_path'])); ?></strong>
                    <p class="small">Formato <?= e(lesson_plan_label($lesson['content_file_path'])); ?>. Envie novo arquivo para substituir ou selecione YouTube para trocar o tipo da aula.</p>
                </div>
                <div class="resource-actions">
                    <a class="button ghost" href="<?= e(uploaded_file_url($lesson['content_file_path'])); ?>" target="_blank" rel="noreferrer">Abrir</a>
                </div>
            </div>
        <?php endif; ?>
        <label>
            <span>Google Forms</span>
            <input type="url" name="form_url" value="<?= e(old('form_url', $lesson['form_url'] ?? '')); ?>" placeholder="https://docs.google.com/forms/...">
        </label>
        <label>
            <span>Material complementar</span>
            <input type="file" name="lesson_plan" accept=".pdf,.doc,.docx,.ppt,.pptx,.pps,.ppsx,.odp,.html,.htm">
        </label>
        <?php $featuredValue = old('is_featured', !empty($lesson['is_featured']) ? '1' : '0'); ?>
        <label class="checkbox-inline">
            <input type="checkbox" name="is_featured" value="1" <?= $featuredValue === '1' ? 'checked' : ''; ?>>
            <span>Fixar esta aula no topo da pagina</span>
        </label>
        <p class="small">Limite atual: <?= e(upload_limit_label()); ?> por envio. O material complementar e opcional e fica abaixo do conteudo principal da aula.</p>
        <datalist id="lesson-category-suggestions">
            <option value="Excel"></option>
            <option value="IA"></option>
            <option value="Habilidades Interpessoais"></option>
        </datalist>
        <?php if (!empty($lesson['plan_file_path'])): ?>
            <div class="resource-card">
                <div>
                    <span class="eyebrow">Material complementar atual</span>
                    <strong><?= e($lesson['plan_original_name'] ?? basename((string) $lesson['plan_file_path'])); ?></strong>
                    <p class="small">Formato <?= e(lesson_plan_label($lesson['plan_file_path'])); ?></p>
                </div>
                <div class="resource-actions">
                    <a class="button ghost" href="<?= e(uploaded_file_url($lesson['plan_file_path'])); ?>" target="_blank" rel="noreferrer">Abrir</a>
                    <label class="checkbox-inline">
                        <input type="checkbox" name="remove_plan_file" value="1">
                        <span>Remover arquivo atual</span>
                    </label>
                </div>
            </div>
        <?php endif; ?>
        <button class="button" type="submit">Salvar alteracoes</button>
    </form>
</section>

<section class="panel">
    <div class="section-head">
        <div>
            <span class="eyebrow">Fotos da turma</span>
            <h2>Galeria da aula</h2>
        </div>
    </div>

    <form method="post" enctype="multipart/form-data" class="stack gap-md">
        <?= csrf_field(); ?>
        <input type="hidden" name="lesson_id" value="<?= (int) $lesson['id']; ?>">
        <input type="hidden" name="action" value="upload_photos">
        <label>
            <span>Selecionar fotos</span>
            <input type="file" name="lesson_photos[]" accept="image/*" multiple>
        </label>
        <p class="small">No celular, voce pode escolher varias imagens da galeria de uma vez. Formatos aceitos: PNG, JPG, JFIF e WEBP.</p>
        <button class="button" type="submit">Enviar fotos</button>
    </form>

    <?php if (($photos ?? []) === []): ?>
        <p class="empty-state">Nenhuma foto enviada para esta aula ainda.</p>
    <?php else: ?>
        <div class="lesson-photo-grid">
            <?php foreach ($photos as $photo): ?>
                <?php $photoViewUrl = route('file/view', [
                    'path' => $photo['file_path'],
                    'name' => $photo['original_name'],
                ]); ?>
                <article class="lesson-photo-card">
                    <a class="lesson-photo-thumb" href="<?= e($photoViewUrl); ?>">
                        <img src="<?= e(uploaded_file_url($photo['file_path'])); ?>" alt="<?= e($photo['original_name']); ?>" loading="lazy">
                    </a>
                    <div class="lesson-photo-meta">
                        <strong><?= e($photo['original_name']); ?></strong>
                        <span><?= date('d/m/Y H:i', strtotime($photo['created_at'])); ?></span>
                    </div>
                    <div class="lesson-photo-actions">
                        <a class="button ghost" href="<?= e(uploaded_file_url($photo['file_path'])); ?>" download="<?= e($photo['original_name']); ?>">Baixar</a>
                        <form method="post" class="inline-action">
                            <?= csrf_field(); ?>
                            <input type="hidden" name="lesson_id" value="<?= (int) $lesson['id']; ?>">
                            <input type="hidden" name="photo_id" value="<?= (int) $photo['id']; ?>">
                            <input type="hidden" name="action" value="delete_photo">
                            <button class="button danger" type="submit">Excluir</button>
                        </form>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
<?php require base_path('app/Views/partials/footer.php'); ?>
