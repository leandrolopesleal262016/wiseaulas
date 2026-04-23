<?php require base_path('app/Views/partials/header.php'); ?>
<section class="hero compact">
    <div class="hero-copy">
        <span class="eyebrow">Chamada</span>
        <h1><?= e($lesson['title']); ?></h1>
        <p><?= e($lesson['course_name']); ?> | <?= e($lesson['teacher_name']); ?></p>
    </div>
    <div class="hero-meta">
        <a class="button ghost" href="<?= e(route('teacher/dashboard')); ?>">Voltar ao painel</a>
    </div>
</section>

<section class="panel">
    <div class="section-head">
        <div>
            <span class="eyebrow">Presencas</span>
            <h2>Lista de alunos</h2>
        </div>
    </div>

    <?php if ($students === []): ?>
        <p class="empty-state">Nao ha alunos cadastrados nesta turma. Cadastre-os no painel admin.</p>
    <?php else: ?>
        <form method="post" class="stack gap-md">
            <?= csrf_field(); ?>
            <input type="hidden" name="lesson_id" value="<?= (int) $lesson['id']; ?>">
            <div class="attendance-list compact">
                <?php foreach ($students as $student): ?>
                    <?php
                    $studentId = (int) $student['id'];
                    $isPresent = ($statuses[$studentId]['status'] ?? null) === 'present';
                    ?>
                    <div class="attendance-compact-row" data-attendance-entry>
                        <div class="attendance-row-main">
                            <label class="attendance-check">
                                <input
                                    type="checkbox"
                                    name="present_students[]"
                                    value="<?= $studentId; ?>"
                                    <?= $isPresent ? 'checked' : ''; ?>
                                    data-attendance-checkbox
                                >
                            </label>
                            <span class="attendance-student-name">
                                <?= e($student['name']); ?>
                            </span>
                            <strong class="attendance-status <?= $isPresent ? 'status-ok' : 'status-off'; ?>" data-status-label>
                                <?= $isPresent ? 'Presente' : 'Falta'; ?>
                            </strong>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <button class="button" type="submit">Salvar chamada</button>
        </form>
    <?php endif; ?>
</section>
<script>
    (() => {
        const entries = document.querySelectorAll('[data-attendance-entry]');

        entries.forEach((entry) => {
            const checkbox = entry.querySelector('[data-attendance-checkbox]');
            const status = entry.querySelector('[data-status-label]');

            const syncStatus = () => {
                const present = checkbox?.checked;
                status.textContent = present ? 'Presente' : 'Falta';
                status.classList.toggle('status-ok', !!present);
                status.classList.toggle('status-off', !present);
            };

            checkbox?.addEventListener('change', syncStatus);
            syncStatus();
        });
    })();
</script>
<?php require base_path('app/Views/partials/footer.php'); ?>
