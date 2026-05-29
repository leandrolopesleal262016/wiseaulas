<?php require base_path('app/Views/partials/header.php'); ?>
<section class="hero compact">
    <div class="hero-copy">
        <span class="eyebrow">White-label</span>
        <h1>Painel administrativo</h1>
        <p>Ajuste identidade visual da marca e mantenha o cadastro de turmas e alunos para alimentar a chamada do professor.</p>
    </div>
</section>

<div class="grid two-columns">
    <section class="panel">
        <div class="section-head">
            <div>
                <span class="eyebrow">Marca</span>
                <h2>Personalizacao</h2>
            </div>
        </div>
        <form method="post" enctype="multipart/form-data" class="stack gap-md">
            <?= csrf_field(); ?>
            <input type="hidden" name="action" value="branding">
            <label>
                <span>Nome do site</span>
                <input type="text" name="site_name" value="<?= e($brandingData['site_name']); ?>" required>
            </label>
            <div class="stack gap-sm">
                <span class="field-label">Tema base</span>
                <div class="theme-grid">
                    <?php foreach ($themeOptions as $themeKey => $themeOption): ?>
                        <label class="theme-option">
                            <input type="radio" name="theme_key" value="<?= e($themeKey); ?>" <?= ($brandingData['theme_key'] ?? 'classic-slate') === $themeKey ? 'checked' : ''; ?>>
                            <span class="theme-card" data-theme-key="<?= e($themeKey); ?>">
                                <strong><?= e($themeOption['label']); ?></strong>
                                <small><?= e($themeOption['description']); ?></small>
                                <?php if ($themeKey !== 'custom'): ?>
                                    <span class="theme-swatches">
                                        <i style="background: <?= e($themeOption['primary_color']); ?>"></i>
                                        <i style="background: <?= e($themeOption['secondary_color']); ?>"></i>
                                        <i style="background: <?= e($themeOption['accent_color']); ?>"></i>
                                    </span>
                                <?php else: ?>
                                    <span class="theme-custom-note">Libera os seletores manuais abaixo.</span>
                                <?php endif; ?>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="grid three-columns">
                <label>
                    <span>Cor principal</span>
                    <input type="color" name="primary_color" value="<?= e($brandingData['primary_color']); ?>" data-custom-color="primary" required>
                </label>
                <label>
                    <span>Cor de fundo</span>
                    <input type="color" name="secondary_color" value="<?= e($brandingData['secondary_color']); ?>" data-custom-color="secondary" required>
                </label>
                <label>
                    <span>Cor de destaque</span>
                    <input type="color" name="accent_color" value="<?= e($brandingData['accent_color']); ?>" data-custom-color="accent" required>
                </label>
            </div>
            <label>
                <span>Logo</span>
                <input type="file" name="logo" accept=".png,.jpg,.jpeg,.jfif,.webp">
            </label>
            <?php if (!empty($brandingData['logo_path'])): ?>
                <img class="logo-preview" src="<?= e(branding_logo_url($brandingData['logo_path'])); ?>" alt="Logo atual">
            <?php endif; ?>
            <label>
                <span>Imagem de fundo</span>
                <input type="file" name="background_image" accept=".png,.jpg,.jpeg,.jfif,.webp">
            </label>
            <?php if (!empty($brandingData['background_image_path'])): ?>
                <img class="background-preview" src="<?= e(branding_logo_url($brandingData['background_image_path'])); ?>" alt="Imagem de fundo atual">
                <label class="checkbox-inline">
                    <input type="checkbox" name="remove_background_image" value="1">
                    <span>Remover imagem atual</span>
                </label>
            <?php endif; ?>
            <label>
                <span>Hero image da home</span>
                <input type="file" name="hero_image" accept=".png,.jpg,.jpeg,.jfif,.webp">
            </label>
            <?php if (!empty($brandingData['hero_image_path'])): ?>
                <img class="background-preview" src="<?= e(branding_logo_url($brandingData['hero_image_path'])); ?>" alt="Hero image atual">
                <label class="checkbox-inline">
                    <input type="checkbox" name="remove_hero_image" value="1">
                    <span>Remover hero image atual</span>
                </label>
            <?php endif; ?>
            <button class="button" type="submit">Salvar marca</button>
        </form>
    </section>

    <section class="panel">
        <div class="section-head">
            <div>
                <span class="eyebrow">Cadastros</span>
                <h2>Turmas, alunos e professores</h2>
            </div>
        </div>

        <form method="post" class="stack gap-md inline-form">
            <?= csrf_field(); ?>
            <input type="hidden" name="action" value="course">
            <label>
                <span>Nova turma</span>
                <input type="text" name="course_name" placeholder="Ex.: Historia - 3C" required>
            </label>
            <button class="button" type="submit">Adicionar turma</button>
        </form>

        <form method="post" class="stack gap-md inline-form">
            <?= csrf_field(); ?>
            <input type="hidden" name="action" value="student">
            <label>
                <span>Turma</span>
                <select name="student_course_id" required>
                    <option value="">Selecione</option>
                    <?php foreach ($courses as $course): ?>
                        <option value="<?= (int) $course['id']; ?>" <?= ($selectedStudentCourseId ?? 0) === (int) $course['id'] ? 'selected' : ''; ?>>
                            <?= e($course['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                <span>Aluno</span>
                <input type="text" name="student_name" required>
            </label>
            <button class="button" type="submit">Adicionar aluno</button>
        </form>

        <form method="post" class="stack gap-md inline-form">
            <?= csrf_field(); ?>
            <input type="hidden" name="action" value="teacher">
            <label>
                <span>Professor</span>
                <input type="text" name="teacher_name" placeholder="Nome de acesso do professor" required>
            </label>
            <label>
                <span>Senha</span>
                <input type="password" name="teacher_password" required>
            </label>
            <button class="button" type="submit">Adicionar professor</button>
        </form>

        <div class="stack gap-sm inline-form">
            <div class="section-head">
                <div>
                    <span class="eyebrow">Acessos</span>
                    <h2>Professores</h2>
                </div>
            </div>
            <?php if (($teachers ?? []) === []): ?>
                <p class="empty-state small">Nenhum professor cadastrado.</p>
            <?php else: ?>
                <ul class="student-admin-list">
                    <?php foreach ($teachers as $teacher): ?>
                        <li>
                            <div>
                                <span><?= e($teacher['name']); ?></span>
                                <strong><?= e($teacher['login_name']); ?></strong>
                            </div>
                            <form method="post" class="inline-action">
                                <?= csrf_field(); ?>
                                <input type="hidden" name="action" value="delete_teacher">
                                <input type="hidden" name="teacher_id" value="<?= (int) $teacher['id']; ?>">
                                <button class="button ghost danger-text" type="submit" onclick="return confirm('Remover este professor e as aulas vinculadas a ele?');">Excluir</button>
                            </form>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <div class="stack gap-sm">
            <?php foreach ($courses as $course): ?>
                <article class="course-block">
                    <header>
                        <div>
                            <strong><?= e($course['name']); ?></strong>
                            <span><?= (int) $course['students_count']; ?> aluno(s)</span>
                        </div>
                        <form method="post" class="inline-action">
                            <?= csrf_field(); ?>
                            <input type="hidden" name="action" value="delete_course">
                            <input type="hidden" name="course_id" value="<?= (int) $course['id']; ?>">
                            <button class="button danger" type="submit" onclick="return confirm('Remover esta turma e todos os alunos/aulas vinculados?');">Excluir turma</button>
                        </form>
                    </header>
                    <?php if (($studentsByCourse[(int) $course['id']] ?? []) === []): ?>
                        <p class="empty-state small">Sem alunos nesta turma.</p>
                    <?php else: ?>
                        <ul class="student-admin-list">
                            <?php foreach ($studentsByCourse[(int) $course['id']] as $student): ?>
                                <?php
                                $studentAttendanceStartLessonId = (int) ($student['attendance_start_lesson_id'] ?? 0);
                                $courseLessons = $lessonsByCourse[(int) $course['id']] ?? [];
                                $selectedAttendanceStartLesson = null;

                                foreach ($courseLessons as $courseLesson) {
                                    if ((int) ($courseLesson['id'] ?? 0) === $studentAttendanceStartLessonId) {
                                        $selectedAttendanceStartLesson = $courseLesson;
                                        break;
                                    }
                                }
                                ?>
                                <li>
                                    <div class="student-admin-content">
                                        <strong><?= e($student['name']); ?></strong>
                                        <span class="student-admin-meta">
                                            <?php if ($selectedAttendanceStartLesson): ?>
                                                Faltas contadas a partir de: <?= e($selectedAttendanceStartLesson['title']); ?>
                                            <?php else: ?>
                                                Faltas contadas desde o inicio do curso.
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                    <div class="student-admin-actions">
                                        <form method="post" class="student-admin-form">
                                            <?= csrf_field(); ?>
                                            <input type="hidden" name="action" value="update_student_attendance_start">
                                            <input type="hidden" name="student_id" value="<?= (int) $student['id']; ?>">
                                            <label class="student-admin-adjustment">
                                                <span>Contar faltas desde</span>
                                                <select name="attendance_start_lesson_id">
                                                    <option value="0">Inicio do curso</option>
                                                    <?php foreach ($courseLessons as $lessonOption): ?>
                                                        <option value="<?= (int) $lessonOption['id']; ?>" <?= $studentAttendanceStartLessonId === (int) $lessonOption['id'] ? 'selected' : ''; ?>>
                                                            <?= e($lessonOption['title']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </label>
                                            <button class="button ghost" type="submit">Salvar</button>
                                        </form>
                                        <form method="post" class="inline-action">
                                            <?= csrf_field(); ?>
                                            <input type="hidden" name="action" value="delete_student">
                                            <input type="hidden" name="student_id" value="<?= (int) $student['id']; ?>">
                                            <button class="button ghost danger-text" type="submit" onclick="return confirm('Remover este aluno?');">Excluir</button>
                                        </form>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>

        <div class="stack gap-sm inline-form">
            <div class="section-head">
                <div>
                    <span class="eyebrow">Aulas</span>
                    <h2>Edicao geral</h2>
                </div>
            </div>
            <?php if (($lessons ?? []) === []): ?>
                <p class="empty-state small">Nenhuma aula cadastrada.</p>
            <?php else: ?>
                <form method="post" class="stack gap-sm lesson-order-form" data-lesson-order-form>
                    <?= csrf_field(); ?>
                    <input type="hidden" name="action" value="reorder_lessons">
                    <input type="hidden" name="lesson_order" value="<?= e(implode(',', array_map(static fn (array $lesson): int => (int) $lesson['id'], $lessons))); ?>" data-lesson-order-input>
                    <div class="resource-card">
                        <div>
                            <span class="eyebrow">Painel principal</span>
                            <strong>Arraste para definir a ordem publica das aulas</strong>
                            <p class="small">As aulas fixadas continuam acima das demais. Entre aulas com a mesma prioridade, esta ordem sera respeitada na home.</p>
                        </div>
                        <div class="resource-actions">
                            <button class="button ghost" type="submit" data-lesson-order-save disabled>Salvar ordem</button>
                        </div>
                    </div>
                    <div class="lesson-order-list" data-lesson-order-list>
                        <?php foreach ($lessons as $lesson): ?>
                            <article class="lesson-order-item" draggable="true" data-lesson-order-item data-lesson-id="<?= (int) $lesson['id']; ?>">
                                <button class="drag-handle" type="button" aria-label="Arrastar aula">::</button>
                                <div>
                                    <strong><?= e($lesson['title']); ?></strong>
                                    <span><?= e($lesson['teacher_name']); ?> | <?= e($lesson['course_name']); ?></span>
                                </div>
                                <div class="lesson-order-meta">
                                    <?php if (!empty($lesson['is_featured'])): ?>
                                        <span class="lesson-badge">Fixada</span>
                                    <?php endif; ?>
                                    <span class="lesson-badge lesson-content-badge"><?= e(lesson_content_label($lesson)); ?></span>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </form>

                <?php foreach ($lessons as $lesson): ?>
                    <article class="lesson-row">
                        <div>
                            <strong><?= e($lesson['title']); ?></strong>
                            <span><?= e(lesson_category_label($lesson['category_name'] ?? null)); ?> | <?= e($lesson['teacher_name']); ?> | <?= e($lesson['course_name']); ?></span>
                            <small><?= date('d/m/Y H:i', strtotime($lesson['created_at'])); ?> | <?= e(lesson_content_label($lesson)); ?> | <?= (int) ($lesson['material_count'] ?? 0); ?> materiais | <?= (int) ($lesson['photo_count'] ?? 0); ?> fotos</small>
                        </div>
                        <div class="lesson-metrics lesson-admin-tools">
                            <form method="post" class="lesson-admin-inline-form">
                                <?= csrf_field(); ?>
                                <input type="hidden" name="action" value="update_lesson_meta">
                                <input type="hidden" name="lesson_id" value="<?= (int) $lesson['id']; ?>">
                                <input
                                    class="lesson-admin-category-input"
                                    type="text"
                                    name="category_name"
                                    value="<?= e((string) ($lesson['category_name'] ?? '')); ?>"
                                    list="lesson-category-suggestions-admin"
                                    placeholder="Categoria"
                                    required
                                >
                                <label class="checkbox-inline lesson-admin-checkbox">
                                    <input type="checkbox" name="is_featured" value="1" <?= !empty($lesson['is_featured']) ? 'checked' : ''; ?>>
                                    <span>Fixar no topo</span>
                                </label>
                                <button class="button ghost" type="submit">Salvar</button>
                            </form>
                            <a class="button ghost" href="<?= e(route('admin/lesson/edit', ['lesson_id' => (int) $lesson['id']])); ?>">Editar completo</a>
                            <form method="post" class="inline-action">
                                <?= csrf_field(); ?>
                                <input type="hidden" name="action" value="delete_lesson">
                                <input type="hidden" name="lesson_id" value="<?= (int) $lesson['id']; ?>">
                                <button class="button danger" type="submit" onclick="return confirm('Remover esta aula, chamada, fotos e arquivos vinculados?');">Excluir aula</button>
                            </form>
                        </div>
                    </article>
                <?php endforeach; ?>
                <datalist id="lesson-category-suggestions-admin">
                    <option value="Excel"></option>
                    <option value="IA"></option>
                    <option value="Habilidades Interpessoais"></option>
                </datalist>
            <?php endif; ?>
        </div>

        <div class="stack gap-sm inline-form">
            <div class="section-head">
                <div>
                    <span class="eyebrow">Monitoramento</span>
                    <h2>Acessos de professores</h2>
                </div>
            </div>
            <?php if (($teacherAccessLogs ?? []) === []): ?>
                <p class="empty-state small">Nenhum acesso de professor registrado ainda.</p>
            <?php else: ?>
                <?php foreach ($teacherAccessLogs as $accessLog): ?>
                    <article class="lesson-row">
                        <div>
                            <strong><?= e($accessLog['teacher_name']); ?></strong>
                            <span><?= e($accessLog['login_name']); ?> | IP: <?= e($accessLog['ip_address'] ?? 'nao identificado'); ?></span>
                            <small><?= date('d/m/Y H:i', strtotime($accessLog['accessed_at'])); ?></small>
                        </div>
                        <div class="lesson-metrics teacher-access-meta">
                            <?php $userAgent = (string) ($accessLog['user_agent'] ?? 'Navegador nao identificado'); ?>
                            <span><?= e(strlen($userAgent) > 80 ? substr($userAgent, 0, 77) . '...' : $userAgent); ?></span>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>
</div>
<script>
    (() => {
        const themeOptions = <?= json_encode($themeOptions, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
        const radios = document.querySelectorAll('input[name="theme_key"]');
        const colorInputs = {
            primary: document.querySelector('[data-custom-color="primary"]'),
            secondary: document.querySelector('[data-custom-color="secondary"]'),
            accent: document.querySelector('[data-custom-color="accent"]')
        };

        const syncThemeState = () => {
            const selected = document.querySelector('input[name="theme_key"]:checked')?.value || 'classic-slate';
            const isCustom = selected === 'custom';

            Object.values(colorInputs).forEach((input) => {
                input.disabled = !isCustom;
            });

            if (!isCustom && themeOptions[selected]) {
                colorInputs.primary.value = themeOptions[selected].primary_color;
                colorInputs.secondary.value = themeOptions[selected].secondary_color;
                colorInputs.accent.value = themeOptions[selected].accent_color;
            }
        };

        radios.forEach((radio) => radio.addEventListener('change', syncThemeState));
        syncThemeState();
    })();

    (() => {
        const list = document.querySelector('[data-lesson-order-list]');
        const input = document.querySelector('[data-lesson-order-input]');
        const saveButton = document.querySelector('[data-lesson-order-save]');

        if (!list || !input || !saveButton) {
            return;
        }

        let draggedItem = null;

        const syncOrder = () => {
            input.value = Array.from(list.querySelectorAll('[data-lesson-order-item]'))
                .map((item) => item.dataset.lessonId)
                .filter(Boolean)
                .join(',');
            saveButton.disabled = false;
        };

        list.querySelectorAll('[data-lesson-order-item]').forEach((item) => {
            item.addEventListener('dragstart', () => {
                draggedItem = item;
                item.classList.add('is-dragging');
            });

            item.addEventListener('dragend', () => {
                item.classList.remove('is-dragging');
                draggedItem = null;
            });

            item.addEventListener('dragover', (event) => {
                event.preventDefault();

                if (!draggedItem || draggedItem === item) {
                    return;
                }

                const rect = item.getBoundingClientRect();
                const shouldInsertBefore = event.clientY < rect.top + rect.height / 2;
                const referenceNode = shouldInsertBefore ? item : item.nextElementSibling;

                if (referenceNode !== draggedItem) {
                    list.insertBefore(draggedItem, referenceNode);
                    syncOrder();
                }
            });
        });
    })();
</script>
<?php require base_path('app/Views/partials/footer.php'); ?>
