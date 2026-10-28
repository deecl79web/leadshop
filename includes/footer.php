</main>

<footer class="site-footer">
    <div class="container footer-inner">
        <span>© <?= date('Y') ?> <?= e(SHOP_NAME) ?> · <?= e(SHOP_CITY) ?></span>
        <a href="<?= e(wa_link('Bonjour, j\'ai une question.')) ?>" target="_blank" rel="noopener">
            <img class="card-icon" src="<?= e(url('assets/icones/comment.svg')) ?>" alt="" aria-hidden="true" style="width: 1.25rem; height: 1.25rem;"> Nous écrire sur WhatsApp
        </a>
    </div>
</footer>

<script src="<?= e(asset('js/app.js')) ?>" defer></script>
</body>
</html>
