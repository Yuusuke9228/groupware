<?php
// views/message/sidebar.php
// メッセージ機能のサイドバー部分
?>

<div class="col-md-3 col-lg-2 mb-3">
    <div class="list-group">
        <a href="<?php echo BASE_PATH; ?>/messages/compose" class="list-group-item list-group-item-action <?php echo ($section === 'compose') ? 'active' : ''; ?>">
            <i class="fas fa-edit me-2"></i>新規作成
        </a>
        <a href="<?php echo BASE_PATH; ?>/messages/inbox" class="list-group-item list-group-item-action <?php echo ($section === 'inbox') ? 'active' : ''; ?>">
            <i class="fas fa-inbox me-2"></i>受信トレイ
            <?php if (isset($unreadCount) && $unreadCount > 0): ?>
                <span class="badge bg-danger float-end"><?php echo $unreadCount; ?></span>
            <?php endif; ?>
        </a>
        <a href="<?php echo BASE_PATH; ?>/messages/sent" class="list-group-item list-group-item-action <?php echo ($section === 'sent') ? 'active' : ''; ?>">
            <i class="fas fa-paper-plane me-2"></i>送信済み
        </a>
        <a href="<?php echo BASE_PATH; ?>/messages/starred" class="list-group-item list-group-item-action <?php echo ($section === 'starred') ? 'active' : ''; ?>">
            <i class="fas fa-star me-2"></i>スター付き
        </a>
    </div>
</div>