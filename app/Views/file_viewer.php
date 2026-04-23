<?php require base_path('app/Views/partials/header.php'); ?>
<section class="hero compact">
    <div class="hero-copy">
        <span class="eyebrow">Visualizacao</span>
        <h1><?= e($fileName ?? 'Arquivo'); ?></h1>
        <p>Veja o arquivo na plataforma. Se quiser guardar uma copia, use o botao de download.</p>
    </div>
    <div class="hero-meta">
        <a class="button ghost" href="<?= e($downloadUrl); ?>">Baixar arquivo</a>
        <button class="button ghost" type="button" onclick="history.length > 1 ? history.back() : location.assign('<?= e(route('home')); ?>')">Voltar</button>
    </div>
</section>

<section class="panel file-viewer-panel">
    <?php if (!empty($isImage)): ?>
        <img class="file-viewer-image" src="<?= e($fileUrl); ?>" alt="<?= e($fileName ?? 'Arquivo'); ?>">
    <?php elseif (str_starts_with((string) ($mimeType ?? ''), 'application/pdf')): ?>
        <iframe class="file-viewer-frame" src="<?= e($fileUrl); ?>" title="<?= e($fileName ?? 'Arquivo'); ?>"></iframe>
    <?php else: ?>
        <div class="resource-card">
            <div>
                <span class="eyebrow">Arquivo</span>
                <strong><?= e($fileName ?? 'Arquivo'); ?></strong>
                <p class="small">Este tipo de arquivo pode nao abrir direto no navegador. Use os botoes para abrir ou baixar.</p>
            </div>
            <div class="resource-actions">
                <a class="button ghost" href="<?= e($fileUrl); ?>" target="_blank" rel="noreferrer">Abrir arquivo</a>
                <a class="button" href="<?= e($downloadUrl); ?>">Baixar arquivo</a>
            </div>
        </div>
    <?php endif; ?>
</section>
<?php require base_path('app/Views/partials/footer.php'); ?>
