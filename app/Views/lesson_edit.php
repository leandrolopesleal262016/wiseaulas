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
                <input type="radio" name="content_type" value="youtube" <?= $selectedContentType === 'youtube' ? 'checked' : ''; ?>>
                <span>Link do YouTube</span>
            </label>
            <label class="checkbox-inline">
                <input type="radio" name="content_type" value="file" <?= $selectedContentType === 'file' ? 'checked' : ''; ?>>
                <span>Arquivo PDF, documento ou slides</span>
            </label>
            <label class="checkbox-inline">
                <input type="radio" name="content_type" value="none" <?= $selectedContentType === 'none' ? 'checked' : ''; ?>>
                <span>Sem conteudo principal por enquanto</span>
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
                    <p class="small">Formato <?= e(lesson_plan_label($lesson['content_file_path'])); ?>. Envie um novo arquivo para substituir ou selecione outra opcao acima para trocar o conteudo principal.</p>
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
        <?php if ($editScope === 'admin'): ?>
            <?php $featuredValue = old('is_featured', !empty($lesson['is_featured']) ? '1' : '0'); ?>
            <label class="checkbox-inline">
                <input type="checkbox" name="is_featured" value="1" <?= $featuredValue === '1' ? 'checked' : ''; ?>>
                <span>Fixar esta aula no topo da pagina</span>
            </label>
        <?php endif; ?>
        <p class="small">O conteudo principal pode ser alterado depois, assim como a chamada, fotos e materiais de apoio.</p>
        <datalist id="lesson-category-suggestions">
            <option value="Excel"></option>
            <option value="IA"></option>
            <option value="Habilidades Interpessoais"></option>
        </datalist>
        <button class="button" type="submit">Salvar alteracoes</button>
    </form>
</section>

<section class="panel">
    <div class="section-head">
        <div>
            <span class="eyebrow">Materiais de apoio</span>
            <h2>Arquivos para os alunos</h2>
        </div>
    </div>

    <form method="post" enctype="multipart/form-data" class="stack gap-md" data-auto-upload-form>
        <?= csrf_field(); ?>
        <input type="hidden" name="lesson_id" value="<?= (int) $lesson['id']; ?>">
        <input type="hidden" name="action" value="upload_materials">
        <label>
            <span>Selecionar materiais</span>
            <input type="file" name="lesson_materials[]" multiple data-auto-upload-input>
        </label>
        <p class="small" data-auto-upload-message>Assim que voce selecionar os arquivos, o envio comeca automaticamente. Qualquer tipo de arquivo e aceito.</p>
    </form>

    <?php if (($materials ?? []) === []): ?>
        <p class="empty-state">Nenhum material de apoio enviado para esta aula ainda.</p>
    <?php else: ?>
        <div class="material-list">
            <?php foreach ($materials as $material): ?>
                <?php
                $materialUrl = uploaded_file_url($material['file_path']);
                $materialTypeLabel = lesson_plan_label($material['file_path']);
                ?>
                <?php if ($materialUrl === null) {
                    continue;
                } ?>
                <article class="material-card">
                    <div>
                        <span class="eyebrow">Material</span>
                        <strong><?= e($material['original_name']); ?></strong>
                        <p class="small"><?= e($materialTypeLabel); ?> | <?= date('d/m/Y H:i', strtotime($material['created_at'])); ?></p>
                    </div>
                    <div class="resource-actions">
                        <a class="button ghost" href="<?= e($materialUrl); ?>" target="_blank" rel="noreferrer">Abrir</a>
                        <a class="button ghost" href="<?= e(route('file', ['path' => $material['file_path'], 'download' => 1, 'name' => $material['original_name']])); ?>">Baixar</a>
                        <form method="post" class="inline-action">
                            <?= csrf_field(); ?>
                            <input type="hidden" name="lesson_id" value="<?= (int) $lesson['id']; ?>">
                            <input type="hidden" name="material_id" value="<?= (int) $material['id']; ?>">
                            <input type="hidden" name="action" value="delete_material">
                            <button class="button danger" type="submit">Excluir</button>
                        </form>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<section class="panel">
    <div class="section-head">
        <div>
            <span class="eyebrow">Fotos da turma</span>
            <h2>Galeria da aula</h2>
        </div>
    </div>

    <form method="post" enctype="multipart/form-data" class="stack gap-md" data-auto-upload-form>
        <?= csrf_field(); ?>
        <input type="hidden" name="lesson_id" value="<?= (int) $lesson['id']; ?>">
        <input type="hidden" name="action" value="upload_photos">
        <label>
            <span>Selecionar fotos</span>
            <input type="file" name="lesson_photos[]" accept="image/*" multiple data-auto-upload-input>
        </label>
        <p class="small" data-auto-upload-message>No celular, voce pode escolher varias imagens da galeria de uma vez. O envio comeca automaticamente apos a selecao.</p>
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
<script>
    (() => {
        const uploadForms = document.querySelectorAll('[data-auto-upload-form]');

        uploadForms.forEach((form) => {
            const input = form.querySelector('[data-auto-upload-input]');
            const message = form.querySelector('[data-auto-upload-message]');

            input?.addEventListener('change', () => {
                if (!input.files || input.files.length === 0 || input.dataset.uploading === 'true') {
                    return;
                }

                if (message) {
                    message.textContent = `Enviando ${input.files.length} arquivo(s)...`;
                }

                input.dataset.uploading = 'true';
                form.requestSubmit();
            });
        });
    })();
</script>
<?php require base_path('app/Views/partials/footer.php'); ?>
