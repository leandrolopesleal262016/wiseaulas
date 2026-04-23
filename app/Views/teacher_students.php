<?php require base_path('app/Views/partials/header.php'); ?>
<section class="hero compact">
    <div class="hero-copy">
        <span class="eyebrow">Professor</span>
        <h1>Alunos e anotacoes</h1>
        <p>Consulte os alunos das suas turmas e veja as anotacoes internas que foram salvas para cada um.</p>
    </div>
    <div class="hero-meta">
        <a class="button ghost" href="<?= e(route('teacher/dashboard')); ?>">Voltar ao painel</a>
    </div>
</section>

<section class="stack gap-md">
    <?php if (($studentsByCourse ?? []) === []): ?>
        <article class="panel">
            <p class="empty-state">Ainda nao ha alunos vinculados as turmas deste professor.</p>
        </article>
    <?php else: ?>
        <?php foreach ($studentsByCourse as $courseName => $students): ?>
            <article class="panel">
                <div class="section-head">
                    <div>
                        <span class="eyebrow">Turma</span>
                        <h2><?= e($courseName); ?></h2>
                    </div>
                </div>
                <div class="stack gap-sm">
                    <?php foreach ($students as $student): ?>
                        <?php
                        $studentId = (int) $student['id'];
                        $studentNotes = $notesByStudent[$studentId] ?? [];
                        $latestNote = $studentNotes[0] ?? null;
                        $absenceCount = (int) ($absenceCountsByStudent[$studentId] ?? 0);
                        ?>
                        <details class="student-compact-item" id="student-<?= $studentId; ?>">
                            <summary class="student-compact-summary">
                                <strong class="student-compact-name"><?= e($student['name']); ?></strong>
                                <span class="student-compact-comment" data-comment-preview>
                                    <?php if ($latestNote): ?>
                                        <?= e($latestNote['teacher_name']); ?>: <?= e(mb_strimwidth((string) $latestNote['note'], 0, 90, '...')); ?>
                                    <?php else: ?>
                                        Sem comentarios
                                    <?php endif; ?>
                                </span>
                                <span class="student-compact-absences"><?= $absenceCount; ?> faltas</span>
                            </summary>

                            <div class="student-compact-body">
                                <form method="post" class="stack gap-sm student-autosave-form">
                                    <?= csrf_field(); ?>
                                    <input type="hidden" name="student_id" value="<?= $studentId; ?>">
                                    <input type="hidden" name="response_type" value="json">
                                    <label class="notes-field">
                                        <span>Nova anotacao</span>
                                        <textarea
                                            name="note"
                                            rows="3"
                                            placeholder="Digite a anotacao. Ao sair do campo, ela sera salva automaticamente."
                                            data-autosave-note
                                        ></textarea>
                                    </label>
                                    <small class="student-save-status" data-save-status></small>
                                </form>

                                <div class="student-note-history" data-note-history>
                                    <?php if ($studentNotes === []): ?>
                                        <p class="student-empty-history" data-empty-history>Nenhuma anotacao salva para este aluno.</p>
                                    <?php else: ?>
                                        <?php foreach ($studentNotes as $note): ?>
                                            <article class="student-note-entry">
                                                <div class="student-note-entry-head">
                                                    <strong><?= e($note['teacher_name']); ?></strong>
                                                    <span><?= date('d/m/Y H:i', strtotime($note['created_at'])); ?></span>
                                                </div>
                                                <p><?= nl2br(e($note['note'])); ?></p>
                                            </article>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </details>
                    <?php endforeach; ?>
                </div>
            </article>
        <?php endforeach; ?>
    <?php endif; ?>
</section>
<script>
    (() => {
        const forms = document.querySelectorAll('.student-autosave-form');

        const escapeHtml = (value) => value
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');

        forms.forEach((form) => {
            const textarea = form.querySelector('[data-autosave-note]');
            const status = form.querySelector('[data-save-status]');
            const container = form.closest('.student-compact-item');
            const history = container?.querySelector('[data-note-history]');
            const preview = container?.querySelector('[data-comment-preview]');
            const emptyHistory = () => history?.querySelector('[data-empty-history]');

            const saveNote = async () => {
                const noteValue = textarea?.value.trim() ?? '';

                if (!textarea || noteValue === '' || textarea.dataset.saving === 'true') {
                    return;
                }

                textarea.dataset.saving = 'true';
                status.textContent = 'Salvando...';

                const formData = new FormData(form);
                formData.set('note', noteValue);

                try {
                    const response = await fetch(form.action || window.location.href, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
                    });

                    if (!response.ok) {
                        throw new Error('Falha ao salvar a anotacao.');
                    }

                    const payload = await response.json();

                    if (!payload.ok) {
                        throw new Error(payload.message || 'Falha ao salvar a anotacao.');
                    }

                    emptyHistory()?.remove();
                    history?.insertAdjacentHTML('afterbegin', `
                        <article class="student-note-entry">
                            <div class="student-note-entry-head">
                                <strong>${escapeHtml(payload.note.teacher_name)}</strong>
                                <span>${escapeHtml(payload.note.created_at_label)}</span>
                            </div>
                            <p>${escapeHtml(payload.note.note).replaceAll('\n', '<br>')}</p>
                        </article>
                    `);

                    if (preview) {
                        const previewText = `${payload.note.teacher_name}: ${payload.note.note}`;
                        preview.textContent = previewText.length > 90 ? `${previewText.slice(0, 87)}...` : previewText;
                    }

                    textarea.value = '';
                    status.textContent = 'Anotacao salva.';
                    window.setTimeout(() => {
                        status.textContent = '';
                    }, 1800);
                } catch (error) {
                    status.textContent = 'Nao foi possivel salvar agora.';
                } finally {
                    delete textarea.dataset.saving;
                }
            };

            textarea?.addEventListener('blur', saveNote);
        });
    })();
</script>
<?php require base_path('app/Views/partials/footer.php'); ?>
