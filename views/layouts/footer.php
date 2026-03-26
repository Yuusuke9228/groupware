</div><!-- /main-content -->

<!-- ローディングインジケータ -->
<div id="page-loader" style="display: none;">
    <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
    </div>
</div>

<!-- フッター -->
<footer class="gw-footer">
    <span>&copy; 2024-<?php echo date('Y'); ?> Yuusuke9228. All rights reserved.</span>
    <span class="gw-footer-links">
        <a href="<?= BASE_PATH ?>/help" target="_blank">ヘルプ</a>
        <a href="<?= BASE_PATH ?>/terms" target="_blank">利用規約</a>
    </span>
</footer>

<!-- モバイル ボトムナビゲーション -->
<nav class="mobile-quick-nav d-lg-none">
    <a href="<?= BASE_PATH ?>/" class="mobile-quick-nav-item <?= (isset($currentPage) && $currentPage === 'home') ? 'active' : '' ?>">
        <i class="fas fa-home"></i>
        <span>トップ</span>
    </a>
    <a href="<?= BASE_PATH ?>/schedule" class="mobile-quick-nav-item <?= (isset($currentPage) && $currentPage === 'schedule') ? 'active' : '' ?>">
        <i class="far fa-calendar-alt"></i>
        <span>予定</span>
    </a>
    <a href="<?= BASE_PATH ?>/messages/inbox" class="mobile-quick-nav-item <?= (isset($currentPage) && $currentPage === 'messages') ? 'active' : '' ?>">
        <i class="far fa-envelope"></i>
        <span>メッセージ</span>
        <?php if (isset($unreadMessageCount) && $unreadMessageCount > 0): ?>
            <span class="nav-badge"><?= $unreadMessageCount ?></span>
        <?php endif; ?>
    </a>
    <a href="<?= BASE_PATH ?>/workflow/approvals" class="mobile-quick-nav-item <?= (isset($currentPage) && $currentPage === 'workflow') ? 'active' : '' ?>">
        <i class="fas fa-check-circle"></i>
        <span>承認</span>
    </a>
    <a href="<?= BASE_PATH ?>/task" class="mobile-quick-nav-item <?= (isset($currentPage) && $currentPage === 'task') ? 'active' : '' ?>">
        <i class="fas fa-tasks"></i>
        <span>タスク</span>
    </a>
</nav>

<div class="footer-spacer"></div>

<!-- アプリケーションJSファイル -->
<?php $appJsVersion = @filemtime(__DIR__ . '/../../public/js/app.js') ?: time(); ?>
<script src="<?= BASE_PATH ?>/js/app.js?v=<?= $appJsVersion ?>"></script>

<!-- モジュールナビ スクロールインジケータ -->
<script>
(function() {
    var nav = document.getElementById('moduleNav');
    var list = document.getElementById('moduleList');
    var leftBtn = document.getElementById('navScrollLeft');
    var rightBtn = document.getElementById('navScrollRight');
    if (!nav || !list) return;

    function updateIndicators() {
        var sl = nav.scrollLeft;
        var maxScroll = nav.scrollWidth - nav.clientWidth;
        if (leftBtn) leftBtn.classList.toggle('visible', sl > 10);
        if (rightBtn) rightBtn.classList.toggle('visible', sl < maxScroll - 10);
    }

    nav.addEventListener('scroll', updateIndicators);
    window.addEventListener('resize', updateIndicators);
    setTimeout(updateIndicators, 100);

    if (leftBtn) {
        leftBtn.addEventListener('click', function() {
            nav.scrollBy({ left: -200, behavior: 'smooth' });
        });
    }
    if (rightBtn) {
        rightBtn.addEventListener('click', function() {
            nav.scrollBy({ left: 200, behavior: 'smooth' });
        });
    }

    // アクティブなモジュールを自動で画面内にスクロール
    var activeLink = nav.querySelector('.gw-module-link.active');
    if (activeLink) {
        var itemRect = activeLink.getBoundingClientRect();
        var navRect = nav.getBoundingClientRect();
        if (itemRect.left < navRect.left || itemRect.right > navRect.right) {
            activeLink.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
        }
    }
})();
</script>

<!-- ページ固有のJSファイル -->
<?php if (isset($jsFiles) && is_array($jsFiles)): ?>
    <?php foreach ($jsFiles as $jsFile): ?>
        <?php $jsFileVersion = @filemtime(__DIR__ . '/../../public/js/' . $jsFile) ?: $appJsVersion; ?>
        <script src="<?= BASE_PATH ?>/js/<?= $jsFile ?>?v=<?= $jsFileVersion ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>

</body>

</html>
