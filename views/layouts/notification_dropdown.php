<!-- views/layouts/notification_dropdown.php -->
<?php
// 通知モデルのインスタンス化
$notificationModel = new \Models\Notification();
// 現在のユーザーID
$userId = $this->auth->id();
// 未読通知の件数を取得
$unreadCount = $notificationModel->getUnreadCount($userId);
// 最新の通知10件を取得
$recentNotifications = $notificationModel->getUnread($userId, 10);
?>

<li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle" href="#" id="notificationDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="far fa-bell"></i>
        <?php if ($unreadCount > 0): ?>
            <span class="badge bg-danger"><?= $unreadCount ?></span>
        <?php endif; ?>
    </a>
    <div class="dropdown-menu dropdown-menu-end notification-dropdown" style="width: 400px; max-height: 500px; overflow-y: auto;" aria-labelledby="notificationDropdown">
        <div class="d-flex justify-content-between align-items-center p-3 border-bottom">
            <h6 class="mb-0">通知</h6>
            <?php if ($unreadCount > 0): ?>
                <button class="btn btn-sm btn-outline-primary" id="markAllReadButton">すべて既読</button>
            <?php endif; ?>
        </div>

        <?php if (empty($recentNotifications)): ?>
            <div class="text-center p-4">
                <i class="far fa-bell fa-2x text-muted mb-2"></i>
                <p class="mb-0">新しい通知はありません</p>
            </div>
        <?php else: ?>
            <div class="list-group list-group-flush">
                <?php foreach ($recentNotifications as $notification): ?>
                    <a href="<?= !empty($notification['link']) ? BASE_PATH . $notification['link'] : BASE_PATH . '/notifications?id=' . $notification['id'] ?>"
                        class="list-group-item list-group-item-action notification-item"
                        data-id="<?= $notification['id'] ?>">
                        <div class="d-flex">
                            <div class="me-3">
                                <?php if ($notification['type'] === 'schedule'): ?>
                                    <i class="far fa-calendar-alt fa-lg text-primary"></i>
                                <?php elseif ($notification['type'] === 'workflow'): ?>
                                    <i class="fas fa-tasks fa-lg text-success"></i>
                                <?php elseif ($notification['type'] === 'message'): ?>
                                    <i class="far fa-envelope fa-lg text-info"></i>
                                <?php else: ?>
                                    <i class="fas fa-bell fa-lg text-warning"></i>
                                <?php endif; ?>
                            </div>
                            <div>
                                <h6 class="mb-1"><?= htmlspecialchars($notification['title']) ?></h6>
                                <p class="mb-1 text-truncate" style="max-width: 300px;"><?= htmlspecialchars($notification['content']) ?></p>
                                <small class="text-muted"><?= date('Y/m/d H:i', strtotime($notification['created_at'])) ?></small>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="dropdown-divider"></div>
        <a class="dropdown-item text-center" href="<?= BASE_PATH ?>/notifications">
            すべての通知を見る
        </a>
    </div>
</li>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // 通知アイテムのクリックイベント - 既読にする
        document.querySelectorAll('.notification-item').forEach(item => {
            item.addEventListener('click', function() {
                const notificationId = this.getAttribute('data-id');
                fetch(`${BASE_PATH}/api/notifications/${notificationId}/read`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    }
                });
            });
        });

        // すべて既読ボタンのクリックイベント
        const markAllReadButton = document.getElementById('markAllReadButton');
        if (markAllReadButton) {
            markAllReadButton.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();

                fetch(`${BASE_PATH}/api/notifications/read-all`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // バッジを非表示
                            const badge = document.querySelector('#notificationDropdown .badge');
                            if (badge) {
                                badge.style.display = 'none';
                            }

                            // ボタンを非表示
                            markAllReadButton.style.display = 'none';

                            // 通知アイテムの見た目を更新
                            document.querySelectorAll('.notification-item').forEach(item => {
                                item.classList.remove('bg-light');
                            });

                            // ドロップダウンを閉じる
                            bootstrap.Dropdown.getInstance(document.getElementById('notificationDropdown')).hide();

                            // 必要に応じてページをリロード
                            if (window.location.pathname.includes('/notifications')) {
                                window.location.reload();
                            }
                        }
                    });
            });
        }
    });
</script>