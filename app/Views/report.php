<?php require base_path('app/Views/partials/header.php'); ?>
<?php
$reportRows = $reportRows ?? [];
$totalStudents = count($reportRows);
$totalAbsences = array_sum(array_map(
    static fn (array $row): int => (int) ($row['absence_count'] ?? 0),
    $reportRows
));
$absenceLevels = array_values(array_unique(array_map(
    static fn (array $row): int => (int) ($row['absence_count'] ?? 0),
    $reportRows
)));
rsort($absenceLevels);
$topAbsenceCount = $absenceLevels[0] ?? 0;
$secondAbsenceCount = $absenceLevels[1] ?? null;
$lowestAbsenceCount = $absenceLevels === [] ? 0 : $absenceLevels[count($absenceLevels) - 1];
$attendanceLessonCount = (int) ($attendanceLessonCount ?? 0);
$systemAttendanceLessonCount = (int) ($systemAttendanceLessonCount ?? $attendanceLessonCount);
$reportCourseId = (int) ($reportCourseId ?? 0);
$reportCourseName = (string) ($reportCourseName ?? '');
$reportCourses = $reportCourses ?? [];
?>
<section class="hero compact">
    <div class="hero-copy">
        <span class="eyebrow">Relatorio</span>
        <h1>Faltas dos alunos</h1>
        <p>
            <?= $reportScope === 'admin'
                ? 'Visao consolidada de todas as turmas, ordenada por quantidade de faltas registrada nas listas de presenca.'
                : 'Visao das suas turmas, ordenada por quantidade de faltas registrada nas listas de presenca. Use o filtro para isolar uma turma especifica.'; ?>
        </p>
        <?php if ($reportCourseName !== ''): ?>
            <p class="report-filter-caption">Turma selecionada: <strong><?= e($reportCourseName); ?></strong></p>
        <?php endif; ?>
    </div>
    <div class="hero-meta report-hero-metrics">
        <div class="metric-card">
            <strong>Total de alunos</strong>
            <span><?= $totalStudents; ?></span>
        </div>
        <div class="metric-card">
            <strong>Aulas</strong>
            <span><?= $attendanceLessonCount; ?></span>
        </div>
        <?php if ($systemAttendanceLessonCount !== $attendanceLessonCount): ?>
            <div class="metric-card">
                <strong>Aulas no sistema</strong>
                <span><?= $systemAttendanceLessonCount; ?></span>
            </div>
        <?php endif; ?>
        <div class="metric-card">
            <strong>Faltas registradas</strong>
            <span><?= $totalAbsences; ?></span>
        </div>
    </div>
</section>

<section class="panel">
    <div class="section-head">
        <div>
            <span class="eyebrow">Ranking</span>
            <h2>Lista de faltas</h2>
        </div>
        <?php if ($reportCourses !== []): ?>
            <form method="get" action="<?= e(route('report')); ?>" class="report-filter-form">
                <label>
                    <span>Turma</span>
                    <select name="course_id">
                        <option value="0">Todas as turmas</option>
                        <?php foreach ($reportCourses as $course): ?>
                            <option value="<?= (int) $course['id']; ?>" <?= $reportCourseId === (int) $course['id'] ? 'selected' : ''; ?>>
                                <?= e($course['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <div class="report-filter-actions">
                    <button class="button ghost" type="submit">Filtrar</button>
                    <?php if ($reportCourseId > 0): ?>
                        <a class="button ghost" href="<?= e(route('report')); ?>">Limpar</a>
                    <?php endif; ?>
                </div>
            </form>
        <?php endif; ?>
    </div>

    <?php if ($reportRows === []): ?>
        <p class="empty-state">Nenhum aluno encontrado para este relatorio.</p>
    <?php else: ?>
        <ol class="report-list" aria-label="Ranking de faltas dos alunos">
            <?php foreach ($reportRows as $index => $row): ?>
                <?php
                $absenceCount = (int) ($row['absence_count'] ?? 0);
                $recordedLessonsCount = (int) ($row['recorded_lessons_count'] ?? 0);
                $absencePercentage = $recordedLessonsCount > 0
                    ? round(($absenceCount / $recordedLessonsCount) * 100, 1)
                    : 0.0;
                $absenceClass = 'absence-pill absence-pill-neutral';
                $absencePercentageClass = $absencePercentage > 50.0
                    ? 'report-stat-value report-stat-value-danger'
                    : 'report-stat-value';

                if ($topAbsenceCount > 0 && $absenceCount === $topAbsenceCount) {
                    $absenceClass = 'absence-pill absence-pill-red';
                } elseif ($secondAbsenceCount !== null && $secondAbsenceCount > 0 && $absenceCount === $secondAbsenceCount) {
                    $absenceClass = 'absence-pill absence-pill-orange';
                } elseif ($absenceCount === $lowestAbsenceCount) {
                    $absenceClass = 'absence-pill absence-pill-green';
                }
                ?>
                <li class="report-row">
                    <div class="report-row-main">
                        <span class="report-rank"><?= $index + 1; ?></span>
                        <div class="report-student">
                            <strong class="report-student-name"><?= e($row['student_name']); ?></strong>
                            <span class="report-student-course"><?= e($row['course_name']); ?></span>
                        </div>
                    </div>

                    <div class="report-stats">
                        <div class="report-stat">
                            <span class="report-stat-label">Aulas</span>
                            <strong class="report-stat-value"><?= $recordedLessonsCount; ?></strong>
                        </div>
                        <div class="report-stat">
                            <span class="report-stat-label">Faltas</span>
                            <strong class="<?= e($absenceClass); ?>"><?= $absenceCount; ?></strong>
                        </div>
                        <div class="report-stat">
                            <span class="report-stat-label">% Faltas</span>
                            <strong class="<?= e($absencePercentageClass); ?>"><?= number_format($absencePercentage, 1, ',', '.'); ?>%</strong>
                        </div>
                    </div>
                </li>
            <?php endforeach; ?>
        </ol>
    <?php endif; ?>
</section>
<?php require base_path('app/Views/partials/footer.php'); ?>
