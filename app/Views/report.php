<?php require base_path('app/Views/partials/header.php'); ?>
<?php
$absenceLevels = array_values(array_unique(array_map(
    static fn (array $row): int => (int) ($row['absence_count'] ?? 0),
    $reportRows ?? []
)));
rsort($absenceLevels);
$topAbsenceCount = $absenceLevels[0] ?? 0;
$secondAbsenceCount = $absenceLevels[1] ?? null;
?>
<section class="hero compact">
    <div class="hero-copy">
        <span class="eyebrow">Relatorio</span>
        <h1>Faltas dos alunos</h1>
        <p>
            <?= $reportScope === 'admin'
                ? 'Visao consolidada de todas as turmas, ordenada por quantidade de faltas registrada nas listas de presenca.'
                : 'Visao das suas turmas, ordenada por quantidade de faltas registrada nas listas de presenca.'; ?>
        </p>
    </div>
    <div class="hero-meta">
        <div class="metric-card">
            <strong>Total de alunos</strong>
            <span><?= count($reportRows ?? []); ?></span>
        </div>
    </div>
</section>

<section class="panel">
    <div class="section-head">
        <div>
            <span class="eyebrow">Ranking</span>
            <h2>Mais faltas no topo</h2>
        </div>
    </div>

    <?php if (($reportRows ?? []) === []): ?>
        <p class="empty-state">Nenhum aluno encontrado para este relatorio.</p>
    <?php else: ?>
        <div class="report-list">
            <?php foreach ($reportRows as $index => $row): ?>
                <?php
                $absenceCount = (int) ($row['absence_count'] ?? 0);
                $absenceClass = 'absence-pill absence-pill-green';

                if ($topAbsenceCount > 0 && $absenceCount === $topAbsenceCount) {
                    $absenceClass = 'absence-pill absence-pill-red';
                } elseif ($secondAbsenceCount !== null && $secondAbsenceCount > 0 && $absenceCount === $secondAbsenceCount) {
                    $absenceClass = 'absence-pill absence-pill-orange';
                }
                ?>
                <article class="report-row">
                    <div class="report-row-main">
                        <span class="report-rank"><?= $index + 1; ?></span>
                        <div>
                            <strong><?= e($row['student_name']); ?></strong>
                            <span><?= e($row['course_name']); ?></span>
                        </div>
                    </div>
                    <strong class="<?= e($absenceClass); ?>">
                        <?= $absenceCount; ?> falta(s)
                    </strong>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
<?php require base_path('app/Views/partials/footer.php'); ?>
