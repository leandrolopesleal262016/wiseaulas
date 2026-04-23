<?php require base_path('app/Views/partials/header.php'); ?>
<section class="hero compact">
    <div class="hero-copy">
        <span class="eyebrow">Erro</span>
        <h1>O sistema nao conseguiu concluir esta pagina</h1>
        <p><?= e((string) ($errorMessage ?? 'Ocorreu um erro inesperado.')); ?></p>
    </div>
    <div class="hero-meta">
        <a class="button ghost" href="<?= e(route('home')); ?>">Voltar para home</a>
    </div>
</section>
<?php require base_path('app/Views/partials/footer.php'); ?>
