<?php require base_path('app/Views/partials/header.php'); ?>
<section class="hero compact">
    <div class="hero-copy">
        <span class="eyebrow">Acesso restrito</span>
        <h1>Login de professor e administrador</h1>
        <p>Entre para acessar o ambiente interno do Instituto Sabia Loucura e gerenciar aulas, chamadas e personalizacao.</p>
    </div>
</section>

<section class="panel auth-panel">
    <form method="post" class="stack gap-md">
        <?= csrf_field(); ?>
        <label>
            <span>Nome de acesso</span>
            <input type="text" name="login" value="<?= e(old('login')); ?>" required>
        </label>
        <label>
            <span>Senha</span>
            <input type="password" name="password" required>
        </label>
        <button class="button" type="submit">Entrar</button>
    </form>

    <div class="credentials">
        <h2>Instituto Sabia Loucura</h2>
        <p>Bem-vindo ao ambiente interno dos professores. Use seu nome de acesso e sua senha para entrar no painel.</p>
        <p class="small">No primeiro acesso do professor, o sistema solicita a leitura e a concordancia com o termo institucional da plataforma.</p>
    </div>
</section>
<?php require base_path('app/Views/partials/footer.php'); ?>
