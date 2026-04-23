<?php require base_path('app/Views/partials/header.php'); ?>
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
                <p class="small">Versao salva: <?= e((string) $termVersion); ?></p>
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
                <h2><?= e((string) $termTitle); ?></h2>
            </div>
        </div>

        <div class="term-document"><?= nl2br(e((string) $termBody)); ?></div>
    </article>
</section>
<?php require base_path('app/Views/partials/footer.php'); ?>
