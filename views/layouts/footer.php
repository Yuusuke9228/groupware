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
    <?php if (!empty($appVersion)): ?>
        <span class="text-muted"><?php echo htmlspecialchars(t('footer.version')); ?> <?php echo htmlspecialchars($appVersion); ?></span>
    <?php endif; ?>
    <span class="gw-footer-links">
        <?php $languageRedirect = urlencode((string)($_SERVER['REQUEST_URI'] ?? (BASE_PATH . '/'))); ?>
        <a href="<?= BASE_PATH ?>/help" target="_blank"><?php echo htmlspecialchars(t('footer.help')); ?></a>
        <a href="<?= BASE_PATH ?>/terms" target="_blank"><?php echo htmlspecialchars(t('footer.terms')); ?></a>
        <a href="<?= BASE_PATH ?>/locale/ja?redirect=<?= $languageRedirect ?>"><?= htmlspecialchars(t('lang.ja')) ?></a>
        <a href="<?= BASE_PATH ?>/locale/en?redirect=<?= $languageRedirect ?>"><?= htmlspecialchars(t('lang.en')) ?></a>
    </span>
</footer>

<!-- モバイル ボトムナビゲーション -->
<nav class="mobile-quick-nav d-lg-none">
    <a href="<?= BASE_PATH ?>/" class="mobile-quick-nav-item <?= (isset($currentPage) && $currentPage === 'home') ? 'active' : '' ?>">
        <i class="fas fa-home"></i>
        <span><?php echo htmlspecialchars(t('menu.top')); ?></span>
    </a>
    <a href="<?= BASE_PATH ?>/schedule" class="mobile-quick-nav-item <?= (isset($currentPage) && $currentPage === 'schedule') ? 'active' : '' ?>">
        <i class="far fa-calendar-alt"></i>
        <span><?php echo htmlspecialchars(t('menu.schedule')); ?></span>
    </a>
    <a href="<?= BASE_PATH ?>/messages/inbox" class="mobile-quick-nav-item <?= (isset($currentPage) && $currentPage === 'messages') ? 'active' : '' ?>">
        <i class="far fa-envelope"></i>
        <span><?php echo htmlspecialchars(t('menu.messages')); ?></span>
        <?php if (isset($unreadMessageCount) && $unreadMessageCount > 0): ?>
            <span class="nav-badge"><?= $unreadMessageCount ?></span>
        <?php endif; ?>
    </a>
    <a href="<?= BASE_PATH ?>/chat" class="mobile-quick-nav-item <?= (isset($currentPage) && $currentPage === 'chat') ? 'active' : '' ?>">
        <i class="fas fa-comments"></i>
        <span><?= htmlspecialchars(tr_text('チャット', 'Chat')) ?></span>
        <span id="chatMobileBadge" class="nav-badge <?= (isset($unreadChatCount) && $unreadChatCount > 0) ? '' : 'd-none' ?>"><?= (int)($unreadChatCount ?? 0) ?></span>
    </a>
    <a href="<?= BASE_PATH ?>/workflow/approvals" class="mobile-quick-nav-item <?= (isset($currentPage) && $currentPage === 'workflow') ? 'active' : '' ?>">
        <i class="fas fa-check-circle"></i>
        <span><?php echo htmlspecialchars(t('menu.approvals')); ?></span>
    </a>
    <a href="<?= BASE_PATH ?>/task" class="mobile-quick-nav-item <?= (isset($currentPage) && $currentPage === 'task') ? 'active' : '' ?>">
        <i class="fas fa-tasks"></i>
        <span><?php echo htmlspecialchars(t('menu.task')); ?></span>
    </a>
</nav>

<div class="footer-spacer"></div>

<!-- アプリケーションJSファイル -->
<?php $appJsVersion = @filemtime(__DIR__ . '/../../public/js/app.js') ?: time(); ?>
<script src="<?= BASE_PATH ?>/js/app.js?v=<?= $appJsVersion ?>"></script>
<?php if (!empty($currentUser)): ?>
<script>
(function() {
    function updateChatBadge(count) {
        var value = Number(count || 0);
        var ids = ['chatModuleBadge', 'chatMobileBadge'];
        ids.forEach(function(id) {
            var node = document.getElementById(id);
            if (!node) return;
            if (value > 0) {
                node.textContent = String(value);
                node.classList.remove('d-none');
            } else {
                node.textContent = '0';
                node.classList.add('d-none');
            }
        });
    }

    function fetchChatUnread() {
        fetch('<?= BASE_PATH ?>/api/chat/unread-count', {
            method: 'GET',
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        }).then(function(res) {
            return res.json();
        }).then(function(json) {
            if (!json || !json.success || !json.data) return;
            updateChatBadge(Number(json.data.count || 0));
        }).catch(function() {});
    }

    fetchChatUnread();
    setInterval(fetchChatUnread, 15000);
})();
</script>
<?php endif; ?>
<?php if (!empty($pwaEnabled)): ?>
    <?php $pwaJsVersion = @filemtime(__DIR__ . '/../../public/js/pwa.js') ?: $appJsVersion; ?>
    <script src="<?= BASE_PATH ?>/js/pwa.js?v=<?= $pwaJsVersion ?>"></script>
<?php endif; ?>

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
