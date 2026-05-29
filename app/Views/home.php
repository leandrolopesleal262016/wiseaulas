<?php require base_path('app/Views/partials/header.php'); ?>
<?php $openFirstLessonId = (int) ($lessons[0]['id'] ?? 0); ?>
<section class="panel home-intro-card<?= !empty($branding['hero_image_path']) ? ' home-intro-card-has-media' : ''; ?>">
    <div class="home-intro-copy">
        <span class="eyebrow">Portal aberto</span>
        <h1>Chamada visivel e revisao de conteudo</h1>
        <p>Os alunos acessam sem login, acompanham o conteudo publicado pelo professor e consultam presencas e atividades no mesmo lugar.</p>
    </div>

    <?php if (!empty($branding['hero_image_path'])): ?>
        <div class="home-intro-media">
            <img src="<?= e(branding_logo_url($branding['hero_image_path'])); ?>" alt="Imagem de destaque do portal">
        </div>
    <?php endif; ?>
</section>

<section class="stack gap-md">
    <?php if ($lessons === []): ?>
        <article class="panel empty-card">
            <h2>Nenhuma aula disponivel</h2>
            <p>Quando um professor publicar uma aula, ela aparecera aqui com conteudo, chamada e atividade.</p>
        </article>
    <?php else: ?>
        <div class="section-head">
            <div>
                <span class="eyebrow">Fila de aulas</span>
                <h2>Aulas publicadas</h2>
            </div>
            <span class="category-count"><?= count($lessons); ?> aula(s)</span>
        </div>

        <?php foreach ($lessons as $lesson): ?>
            <?php
            $lessonId = (int) $lesson['id'];
            $categoryLabel = lesson_category_label($lesson['category_name'] ?? null);
            $lessonPhotos = $photosByLesson[$lessonId] ?? [];
            $lessonMaterials = $materialsByLesson[$lessonId] ?? [];
            $contentType = lesson_content_type($lesson);
            $thumbnail = $contentType === 'youtube'
                ? youtube_thumbnail((string) ($lesson['youtube_video_id'] ?? ''))
                : null;

            if ($thumbnail === null && $lessonPhotos !== []) {
                $thumbnail = uploaded_file_url($lessonPhotos[0]['file_path'] ?? null);
            }

            $embedUrl = $contentType === 'youtube'
                ? 'https://www.youtube.com/embed/' . rawurlencode((string) ($lesson['youtube_video_id'] ?? ''))
                : null;
            $contentFileUrl = $contentType === 'file' ? uploaded_file_url($lesson['content_file_path'] ?? null) : null;
            $contentViewerUrl = $contentType === 'file' ? lesson_file_viewer_url($lesson['content_file_path'] ?? null) : null;
            $contentFileType = $contentType === 'file' ? lesson_plan_type($lesson['content_file_path'] ?? null) : null;
            $attendanceItems = $attendanceByLesson[$lessonId] ?? [];
            ?>
            <details id="lesson-<?= $lessonId; ?>" class="lesson-card<?= !empty($lesson['is_featured']) ? ' lesson-card-featured' : ''; ?>" <?= $lessonId === $openFirstLessonId ? 'open' : ''; ?>>
                <summary>
                    <?php if ($thumbnail): ?>
                        <img src="<?= e($thumbnail); ?>" alt="Miniatura da aula <?= e($lesson['title']); ?>">
                    <?php else: ?>
                        <div class="lesson-file-thumbnail">
                            <span><?= e(lesson_content_label($lesson)); ?></span>
                            <strong><?= $contentType === 'none' ? 'Aula publicada' : 'Arquivo da aula'; ?></strong>
                        </div>
                    <?php endif; ?>
                    <div>
                        <div class="lesson-summary-topline">
                            <span class="eyebrow"><?= e($categoryLabel); ?></span>
                            <span class="lesson-badge lesson-content-badge"><?= e(lesson_content_label($lesson)); ?></span>
                            <?php if (!empty($lesson['is_featured'])): ?>
                                <span class="lesson-badge">Fixada</span>
                            <?php endif; ?>
                            <?php if ($lessonMaterials !== []): ?>
                                <span class="lesson-badge lesson-material-badge"><?= count($lessonMaterials); ?> materiais</span>
                            <?php endif; ?>
                        </div>
                        <h2><?= e($lesson['title']); ?></h2>
                        <p><?= e($lesson['teacher_name']); ?> | <?= e($lesson['course_name']); ?> | <?= date('d/m/Y H:i', strtotime($lesson['created_at'])); ?></p>
                    </div>
                </summary>

                <div class="lesson-card-body">
                    <div class="media-grid">
                        <div class="media-frame">
                            <?php if ($contentType === 'youtube' && $embedUrl !== null): ?>
                                <iframe src="<?= e($embedUrl); ?>" title="<?= e($lesson['title']); ?>" allowfullscreen loading="lazy"></iframe>
                            <?php elseif ($contentViewerUrl !== null && $contentFileUrl !== null): ?>
                                <div class="lesson-file-viewer-head">
                                    <div>
                                        <span class="eyebrow">Arquivo da aula</span>
                                        <h3><?= e($lesson['content_original_name'] ?? basename((string) $lesson['content_file_path'])); ?></h3>
                                    </div>
                                    <a class="button ghost" href="<?= e($contentFileUrl); ?>" target="_blank" rel="noreferrer">
                                        Abrir arquivo
                                    </a>
                                </div>

                                <?php if ($contentFileType === 'html'): ?>
                                    <iframe class="lesson-file-frame" src="<?= e($contentViewerUrl); ?>" title="<?= e($lesson['title']); ?>" loading="lazy" sandbox=""></iframe>
                                <?php else: ?>
                                    <iframe class="lesson-file-frame" src="<?= e($contentViewerUrl); ?>" title="<?= e($lesson['title']); ?>" loading="lazy"></iframe>
                                <?php endif; ?>

                                <?php if (in_array($contentFileType, ['document', 'presentation'], true)): ?>
                                    <p class="small viewer-note">Documentos e slides usam visualizador integrado. Se ele nao carregar no ambiente local, abra o arquivo pelo botao acima.</p>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="resource-card">
                                    <div>
                                        <span class="eyebrow">Conteudo principal</span>
                                        <strong>Aula publicada sem video ou arquivo principal</strong>
                                        <p class="small">Os materiais de apoio, fotos e a chamada podem ser consultados normalmente abaixo.</p>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="attendance-panel">
                            <h3>Presencas</h3>
                            <?php if ($attendanceItems === []): ?>
                                <p class="empty-state small">Chamada ainda nao registrada.</p>
                            <?php else: ?>
                                <ul class="attendance-summary">
                                    <?php foreach ($attendanceItems as $item): ?>
                                        <li>
                                            <span><?= e($item['student_name']); ?></span>
                                            <strong class="<?= $item['status'] === 'present' ? 'status-ok' : 'status-off'; ?>">
                                                <?= $item['status'] === 'present' ? 'Presente' : 'Ausente'; ?>
                                            </strong>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if (!empty($lesson['form_url'])): ?>
                        <details class="activity-block activity-accordion">
                            <summary class="activity-accordion-summary">
                                <div>
                                    <span class="eyebrow">Atividade</span>
                                    <h3>Atividade</h3>
                                </div>
                                <span class="activity-accordion-toggle">Abrir</span>
                            </summary>
                            <div class="activity-accordion-body">
                                <iframe class="activity-frame" src="<?= e(google_form_embed_url($lesson['form_url'])); ?>" loading="lazy"></iframe>
                            </div>
                        </details>
                    <?php endif; ?>

                    <div class="activity-block">
                        <div class="section-head">
                            <div>
                                <span class="eyebrow">Materiais de apoio</span>
                                <h3>Arquivos para download</h3>
                            </div>
                        </div>

                        <?php if ($lessonMaterials === []): ?>
                            <p class="empty-state small">Nenhum material de apoio foi anexado a esta aula.</p>
                        <?php else: ?>
                            <div class="material-list">
                                <?php foreach ($lessonMaterials as $material): ?>
                                    <?php $materialUrl = uploaded_file_url($material['file_path']); ?>
                                    <?php if ($materialUrl === null) {
                                        continue;
                                    } ?>
                                    <article class="material-card">
                                        <div>
                                            <span class="eyebrow">Material</span>
                                            <strong><?= e($material['original_name']); ?></strong>
                                            <p class="small"><?= e(lesson_plan_label($material['file_path'])); ?></p>
                                        </div>
                                        <div class="resource-actions">
                                            <a class="button ghost" href="<?= e($materialUrl); ?>" target="_blank" rel="noreferrer">Abrir</a>
                                            <a class="button ghost" href="<?= e(route('file', ['path' => $material['file_path'], 'download' => 1, 'name' => $material['original_name']])); ?>">Baixar</a>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if ($lessonPhotos !== []): ?>
                        <div class="activity-block">
                            <div class="section-head">
                                <div>
                                    <span class="eyebrow">Fotos da turma</span>
                                    <h3>Miniaturas da aula</h3>
                                </div>
                            </div>
                            <div class="public-photo-grid">
                                <?php foreach ($lessonPhotos as $photo): ?>
                                    <?php $photoUrl = uploaded_file_url($photo['file_path']); ?>
                                    <?php if ($photoUrl === null) {
                                        continue;
                                    } ?>
                                    <?php $photoViewUrl = route('file/view', [
                                        'path' => $photo['file_path'],
                                        'name' => $photo['original_name'],
                                    ]); ?>
                                    <a class="public-photo-card" href="<?= e($photoViewUrl); ?>">
                                        <img src="<?= e($photoUrl); ?>" alt="<?= e($photo['original_name']); ?>" loading="lazy">
                                        <span><?= e($photo['original_name']); ?></span>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </details>
        <?php endforeach; ?>
    <?php endif; ?>
</section>
<?php require base_path('app/Views/partials/footer.php'); ?>
