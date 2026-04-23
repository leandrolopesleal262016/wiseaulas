    </main>
    <?php if ($authUser): ?>
        <footer class="site-footer">
            <a class="button ghost" href="<?= e(route('logout')); ?>">Logout</a>
        </footer>
    <?php endif; ?>
</div>
<script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('./service-worker.js').then((registration) => {
                registration.update().catch(() => {});
            }).catch(() => {});
        });
    }
</script>
</body>
</html>
