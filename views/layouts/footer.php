</div><!-- /main-content -->

<!-- ローディングインジケータ -->
<div id="page-loader" style="display: none;">
    <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
    </div>
</div>

<!-- フッター -->
<footer class="gw-footer">
    <span>&copy; 2024-2026 Tukurossa Co. Ltd. All rights reserved.</span>
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

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js"></script>
<!-- Toastr JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<!-- Moment.js -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/locale/ja.js"></script>
<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
<!-- jstree JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jstree/3.3.12/jstree.min.js"></script>
<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<!-- Flatpickr JS -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/ja.js"></script>
<!-- FullCalendar JS -->
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.2/main.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.2/locales/ja.js"></script>

<!-- JSのベースパス設定 -->
<script>
    var BASE_PATH = "<?php echo BASE_PATH; ?>";
</script>

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
