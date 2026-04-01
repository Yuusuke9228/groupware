<?php
$chatReady = !empty($chatReady);
$rooms = $rooms ?? [];
$activeRoom = $activeRoom ?? null;
$messages = $messages ?? [];
$members = $members ?? [];
$activeMemberUserIds = $activeMemberUserIds ?? [];
$activeUsers = $activeUsers ?? [];
$currentUser = $currentUser ?? (\Core\Auth::getInstance()->user() ?? []);
$userId = (int)($currentUser['id'] ?? 0);
$csrfToken = (string)($csrf_token ?? '');
$migrationFile = (string)($migrationFile ?? '');
$activeMemberMap = [];
foreach ($activeMemberUserIds as $memberUserId) {
    $activeMemberMap[(int)$memberUserId] = true;
}

if (!function_exists('chatFormatDateTime')) {
    function chatFormatDateTime($value)
    {
        $ts = strtotime((string)$value);
        if ($ts === false) {
            return '';
        }
        return date('m/d H:i', $ts);
    }
}

if (!function_exists('chatInitial')) {
    function chatInitial($value)
    {
        $value = trim((string)$value);
        if ($value === '') {
            return 'C';
        }
        if (function_exists('mb_substr')) {
            return mb_substr($value, 0, 1, 'UTF-8');
        }
        return substr($value, 0, 1);
    }
}

$bootstrap = [
    'basePath' => BASE_PATH,
    'userId' => $userId,
    'roomId' => (int)($activeRoom['id'] ?? 0),
    'csrfToken' => $csrfToken,
    'rooms' => $rooms,
    'activeRoom' => $activeRoom,
    'messages' => $messages,
    'i18n' => [
        'read' => tr_text('既読', 'Read'),
        'readersTitle' => tr_text('既読ユーザー', 'Read users'),
        'readersEmpty' => tr_text('まだ既読ユーザーはいません。', 'No one has read yet.'),
        'readersLoadError' => tr_text('既読一覧の取得に失敗しました。', 'Failed to load read users.'),
    ],
];
?>

<style>
.chat-shell{height:calc(100vh - 190px);min-height:560px;display:flex;border:1px solid #dfe4ea;border-radius:16px;overflow:hidden;background:#fff}
.chat-sidebar{width:340px;max-width:42%;background:#fff;border-right:1px solid #e6e9ef;display:flex;flex-direction:column}
.chat-sidebar-head{padding:14px 16px;border-bottom:1px solid #18a851;background:#06c755;color:#fff}
.chat-sidebar-head .btn{border-color:rgba(255,255,255,0.7);color:#fff}
.chat-sidebar-head .btn:hover{background:rgba(255,255,255,0.14);border-color:#fff;color:#fff}
.chat-room-list{overflow:auto;flex:1;background:#fff}
.chat-room-item{display:block;padding:12px 14px;border-bottom:1px solid #f2f4f8;color:#29313d;text-decoration:none}
.chat-room-item:hover{background:#f7faf9;color:#29313d}
.chat-room-item.active{background:#eefaf2}
.chat-room-row{display:flex;align-items:center;gap:10px}
.chat-room-avatar{width:42px;height:42px;border-radius:50%;background:#dff4e6;color:#198754;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:16px;flex:0 0 42px}
.chat-room-main{min-width:0;flex:1}
.chat-room-title-row{display:flex;align-items:center;gap:8px;justify-content:space-between}
.chat-room-title-text{font-weight:700;font-size:14px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.chat-room-meta{font-size:12px;color:#748196;margin-top:4px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.chat-main{flex:1;display:flex;flex-direction:column;min-width:0;background:#dfe7ef}
.chat-main-head{padding:12px 16px;background:#fff;border-bottom:1px solid #edf0f4}
.chat-mobile-room-bar{display:none}
.chat-room-name{font-weight:700;font-size:16px;margin-bottom:2px}
.chat-room-headline{display:flex;align-items:center;justify-content:space-between;gap:8px}
.chat-room-sub{font-size:12px;color:#7b8794}
.chat-message-area{flex:1;overflow:auto;padding:16px 16px 20px;background:linear-gradient(180deg,#edf2f7 0%,#e4ebf3 100%)}
.chat-empty{height:100%;display:flex;align-items:center;justify-content:center;color:#8c95a3;font-size:14px;text-align:center;padding:24px}
.chat-message-row{display:flex;align-items:flex-end;margin-bottom:12px;gap:8px}
.chat-message-row.mine{justify-content:flex-end}
.chat-sender-avatar{width:32px;height:32px;border-radius:50%;background:#d6dde8;color:#506078;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:13px;flex:0 0 32px}
.chat-bubble-wrap{max-width:74%}
.chat-sender{font-size:11px;color:#6f7a89;margin:0 0 3px 2px}
.chat-message-row.mine .chat-sender{text-align:right;margin:0 2px 3px 0}
.chat-bubble{background:#fff;border-radius:16px;padding:10px 12px;box-shadow:0 1px 2px rgba(0,0,0,0.08);word-break:break-word}
.chat-message-row.mine .chat-bubble{background:#06c755;color:#fff;border-top-right-radius:6px}
.chat-message-row.other .chat-bubble{border-top-left-radius:6px}
.chat-attachment{margin-top:8px;padding:7px 9px;background:rgba(255,255,255,0.65);border:1px solid rgba(0,0,0,0.08);border-radius:10px;font-size:12px}
.chat-message-row.mine .chat-attachment{background:rgba(255,255,255,0.25);border-color:rgba(255,255,255,0.35)}
.chat-message-row.mine .chat-attachment a{color:#fff}
.chat-time{font-size:10px;color:#7b8794;margin-top:4px;text-align:right}
.chat-message-row.mine .chat-time{color:#5a676f}
.chat-read{font-size:10px;color:#06a94d;margin-top:2px;text-align:right}
.chat-read-btn{border:none;background:none;padding:0;color:#06a94d;font-size:10px;cursor:pointer;text-decoration:underline}
.chat-read-btn:hover{opacity:.75}
.chat-composer{padding:10px 12px;background:#fff;border-top:1px solid #edf0f4}
.chat-compose-row{display:flex;align-items:flex-end;gap:8px}
.chat-file-trigger{width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:#f2f5f8;color:#5f6d7f;cursor:pointer;flex:0 0 36px}
.chat-send-btn{width:38px;height:38px;border:none;border-radius:50%;background:#06c755;color:#fff;display:inline-flex;align-items:center;justify-content:center;flex:0 0 38px}
.chat-send-btn:hover{background:#04b14b}
.chat-message-input{resize:none;min-height:38px;max-height:110px;border:1px solid #dbe3ec;border-radius:20px;padding:8px 12px;background:#fff;line-height:1.35}
.chat-file-name{font-size:11px;color:#6f7a89;margin-top:4px;padding-left:44px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.chat-badge{display:inline-flex;min-width:20px;height:20px;padding:0 6px;background:#ff4d4f;color:#fff;border-radius:999px;align-items:center;justify-content:center;font-size:11px;font-weight:700}
.chat-member-chip{display:inline-block;padding:2px 8px;border-radius:999px;background:#eef4ff;color:#476282;font-size:11px;margin:2px 4px 2px 0}
.chat-mobile-back,.chat-mobile-edit{border:none;background:transparent;color:#fff}

@media (max-width: 767.98px){
  .container-fluid.mt-3.mb-3{padding:0;margin-top:0 !important;margin-bottom:0 !important}
  .chat-shell{height:calc(100dvh - 124px);min-height:0;border-radius:0;border-left:0;border-right:0;position:relative}
  .chat-sidebar,.chat-main{position:absolute;inset:0;width:100%;max-width:none;border-right:0}
  .chat-shell.mobile-list .chat-main{display:none}
  .chat-shell.mobile-room .chat-sidebar{display:none}
  .chat-sidebar-head{padding:12px 14px}
  .chat-sidebar-head .btn{padding:4px 10px}
  .chat-room-item{padding:12px 12px}
  .chat-main-head{padding:10px 12px;background:#06c755;color:#fff;border-bottom:0}
  .chat-mobile-room-bar{display:flex;align-items:center;justify-content:space-between;gap:8px}
  .chat-mobile-room-title{font-weight:700;font-size:15px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
  .chat-mobile-left{display:flex;align-items:center;gap:10px;min-width:0}
  .chat-mobile-back{width:32px;height:32px;border-radius:50%;background:rgba(255,255,255,0.16);display:inline-flex;align-items:center;justify-content:center}
  .chat-room-headline,.chat-room-sub{display:none}
  .chat-message-area{padding:12px 10px 84px}
  .chat-bubble-wrap{max-width:82%}
  .chat-composer{position:absolute;left:0;right:0;bottom:0;padding:8px 10px;border-top:1px solid #dfe6ef;box-shadow:0 -2px 8px rgba(10,20,30,0.08)}
  .chat-file-name{padding-left:42px}
}
</style>

<div class="container-fluid mt-3 mb-3">
    <?php if (!empty($_SESSION['flash_success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_SESSION['flash_success']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>
    <?php if (!empty($_SESSION['flash_error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_SESSION['flash_error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <?php if (!$chatReady): ?>
        <div class="alert alert-warning">
            <div class="fw-bold mb-1"><?= htmlspecialchars(tr_text('チャット機能の初期化が必要です。', 'Chat module initialization is required.')) ?></div>
            <div class="small"><?= htmlspecialchars(tr_text('以下のSQLを適用してください: ', 'Please apply migration SQL: ')) ?><code><?= htmlspecialchars($migrationFile) ?></code></div>
        </div>
    <?php else: ?>
        <div class="chat-shell" id="chatShell">
            <aside class="chat-sidebar">
                <div class="chat-sidebar-head">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-comments me-2 text-success"></i><?= htmlspecialchars(tr_text('チャット', 'Chat')) ?></h5>
                        <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#chatCreateRoomModal">
                            <i class="fas fa-user-friends me-1"></i><?= htmlspecialchars(tr_text('新規', 'New')) ?>
                        </button>
                    </div>
                </div>
                <div class="chat-room-list" id="chatRoomList">
                    <?php if (empty($rooms)): ?>
                        <div class="p-3 text-muted small"><?= htmlspecialchars(tr_text('チャットルームがありません。', 'No chat rooms yet.')) ?></div>
                    <?php else: ?>
                        <?php foreach ($rooms as $room): ?>
                            <?php
                            $roomId = (int)($room['id'] ?? 0);
                            $isActive = $roomId > 0 && $roomId === (int)($activeRoom['id'] ?? 0);
                            $unread = (int)($room['unread_count'] ?? 0);
                            $roomName = (string)($room['display_name'] ?? tr_text('チャット', 'Chat'));
                            ?>
                            <a class="chat-room-item <?= $isActive ? 'active' : '' ?>" href="<?= BASE_PATH ?>/chat?room_id=<?= $roomId ?>" data-room-id="<?= $roomId ?>">
                                <div class="chat-room-row">
                                    <div class="chat-room-avatar"><?= htmlspecialchars(chatInitial($roomName)) ?></div>
                                    <div class="chat-room-main">
                                        <div class="chat-room-title-row">
                                            <span class="chat-room-title-text"><?= htmlspecialchars($roomName) ?></span>
                                            <?php if ($unread > 0): ?><span class="chat-badge"><?= $unread ?></span><?php endif; ?>
                                        </div>
                                        <div class="chat-room-meta">
                                            <?= htmlspecialchars((string)($room['last_message_text'] ?? tr_text('メッセージはまだありません。', 'No messages yet.'))) ?>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </aside>

            <section class="chat-main">
                <?php if ($activeRoom): ?>
                    <div class="chat-main-head">
                        <div class="chat-mobile-room-bar">
                            <div class="chat-mobile-left">
                                <button type="button" class="chat-mobile-back" id="chatMobileBackBtn">
                                    <i class="fas fa-arrow-left"></i>
                                </button>
                                <div class="chat-mobile-room-title"><?= htmlspecialchars((string)($activeRoom['display_name'] ?? $activeRoom['name'] ?? '')) ?></div>
                            </div>
                            <?php if (($activeRoom['room_type'] ?? '') === 'group'): ?>
                                <button type="button" class="chat-mobile-edit" data-bs-toggle="modal" data-bs-target="#chatEditRoomModal">
                                    <i class="fas fa-cog"></i>
                                </button>
                            <?php endif; ?>
                        </div>
                        <div class="chat-room-headline">
                            <div class="chat-room-name"><?= htmlspecialchars((string)($activeRoom['display_name'] ?? $activeRoom['name'] ?? '')) ?></div>
                            <?php if (($activeRoom['room_type'] ?? '') === 'group'): ?>
                                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#chatEditRoomModal">
                                    <i class="fas fa-edit me-1"></i><?= htmlspecialchars(tr_text('ルーム編集', 'Edit room')) ?>
                                </button>
                            <?php endif; ?>
                        </div>
                        <div class="chat-room-sub">
                            <?= htmlspecialchars(tr_text('メンバー', 'Members')) ?>:
                            <?php foreach ($members as $member): ?>
                                <span class="chat-member-chip"><?= htmlspecialchars((string)($member['display_name'] ?? '')) ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="chat-message-area" id="chatMessageArea">
                        <div id="chatMessages">
                            <?php foreach ($messages as $message): ?>
                                <?php
                                $mine = (int)($message['user_id'] ?? 0) === $userId;
                                $readByOthers = max(0, (int)($message['read_count'] ?? 0) - 1);
                                ?>
                                <div class="chat-message-row <?= $mine ? 'mine' : 'other' ?>" data-message-id="<?= (int)$message['id'] ?>">
                                    <?php if (!$mine): ?>
                                        <div class="chat-sender-avatar"><?= htmlspecialchars(chatInitial((string)($message['sender_name'] ?? ''))) ?></div>
                                    <?php endif; ?>
                                    <div class="chat-bubble-wrap">
                                        <div class="chat-sender"><?= htmlspecialchars((string)($message['sender_name'] ?? '')) ?></div>
                                        <div class="chat-bubble">
                                            <?php if (!empty($message['message_text'])): ?>
                                                <div><?= nl2br(htmlspecialchars((string)$message['message_text'])) ?></div>
                                            <?php endif; ?>
                                            <?php if (!empty($message['attachment_path'])): ?>
                                                <div class="chat-attachment">
                                                    <a href="<?= BASE_PATH ?>/chat/files/<?= (int)$message['id'] ?>/download">
                                                        <i class="fas fa-paperclip me-1"></i><?= htmlspecialchars((string)($message['attachment_name'] ?? tr_text('添付ファイル', 'Attachment'))) ?>
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="chat-time"><?= htmlspecialchars(chatFormatDateTime((string)($message['created_at'] ?? ''))) ?></div>
                                        <?php if ($mine && $readByOthers > 0): ?>
                                            <div class="chat-read">
                                                <button type="button" class="chat-read-btn" data-message-id="<?= (int)$message['id'] ?>">
                                                    <?= htmlspecialchars(tr_text('既読', 'Read')) ?> <?= $readByOthers ?>
                                                </button>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="chat-composer">
                        <form id="chatMessageForm" class="no-ajax" method="post" enctype="multipart/form-data" action="<?= BASE_PATH ?>/chat/rooms/<?= (int)$activeRoom['id'] ?>/message">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                            <input type="hidden" name="room_id" value="<?= (int)$activeRoom['id'] ?>">
                            <div class="chat-compose-row">
                                <label class="chat-file-trigger" for="chatAttachmentInput" title="<?= htmlspecialchars(tr_text('ファイル', 'File')) ?>">
                                    <i class="fas fa-paperclip"></i>
                                </label>
                                <input type="file" name="attachment" id="chatAttachmentInput" class="d-none">
                                <textarea class="form-control chat-message-input" name="message_text" id="chatMessageInput" rows="1" placeholder="<?= htmlspecialchars(tr_text('メッセージを入力...', 'Type your message...')) ?>"></textarea>
                                <button type="submit" class="chat-send-btn" title="<?= htmlspecialchars(tr_text('送信', 'Send')) ?>">
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </div>
                            <div class="chat-file-name" id="chatAttachmentName"></div>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="chat-empty">
                        <div>
                            <div class="mb-2"><i class="far fa-comments" style="font-size:42px;color:#9ca8b8;"></i></div>
                            <div><?= htmlspecialchars(tr_text('チャットルームを選択してください。', 'Select a chat room.')) ?></div>
                        </div>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    <?php endif; ?>
</div>

<?php if ($chatReady): ?>
<div class="modal fade" id="chatCreateRoomModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form class="no-ajax" method="post" action="<?= BASE_PATH ?>/chat/rooms/create">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <div class="modal-header">
                    <h5 class="modal-title"><?= htmlspecialchars(tr_text('新規チャットグループ作成', 'Create New Chat Group')) ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label"><?= htmlspecialchars(tr_text('グループ名（任意）', 'Group name (optional)')) ?></label>
                        <input type="text" class="form-control" name="room_name" placeholder="<?= htmlspecialchars(tr_text('未入力で2名の場合はダイレクトチャット', 'For 2 users with blank name, creates direct chat')) ?>">
                    </div>
                    <div>
                        <label class="form-label"><?= htmlspecialchars(tr_text('メンバー選択（1名以上）', 'Select members (at least 1)')) ?></label>
                        <select class="form-select select2-multi" name="member_user_ids[]" multiple data-placeholder="<?= htmlspecialchars(tr_text('ユーザーを選択...', 'Select users...')) ?>" required>
                            <?php foreach ($activeUsers as $u): ?>
                                <option value="<?= (int)$u['id'] ?>"><?= htmlspecialchars((string)$u['display_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="small text-muted mt-1"><?= htmlspecialchars(tr_text('※ 作成者は自動でメンバーに追加されます。', 'Creator is automatically included.')) ?></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?= htmlspecialchars(tr_text('キャンセル', 'Cancel')) ?></button>
                    <button type="submit" class="btn btn-success"><?= htmlspecialchars(tr_text('作成', 'Create')) ?></button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php if ($activeRoom && ($activeRoom['room_type'] ?? '') === 'group'): ?>
<div class="modal fade" id="chatEditRoomModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form class="no-ajax" method="post" action="<?= BASE_PATH ?>/chat/rooms/<?= (int)$activeRoom['id'] ?>/update">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <div class="modal-header">
                    <h5 class="modal-title"><?= htmlspecialchars(tr_text('チャットルーム編集', 'Edit Chat Room')) ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label"><?= htmlspecialchars(tr_text('ルーム名', 'Room name')) ?></label>
                        <input type="text" class="form-control" name="room_name" value="<?= htmlspecialchars((string)($activeRoom['name'] ?? '')) ?>">
                    </div>
                    <div>
                        <label class="form-label"><?= htmlspecialchars(tr_text('メンバー選択（1名以上）', 'Select members (at least 1)')) ?></label>
                        <select class="form-select select2-multi" name="member_user_ids[]" multiple data-placeholder="<?= htmlspecialchars(tr_text('ユーザーを選択...', 'Select users...')) ?>" required>
                            <?php foreach ($activeUsers as $u): ?>
                                <?php $memberId = (int)($u['id'] ?? 0); ?>
                                <option value="<?= $memberId ?>" <?= !empty($activeMemberMap[$memberId]) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars((string)$u['display_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="small text-muted mt-1">
                            <?= htmlspecialchars(tr_text('※ 自分は自動でメンバーに含まれます。', 'You are always included automatically.')) ?>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?= htmlspecialchars(tr_text('キャンセル', 'Cancel')) ?></button>
                    <button type="submit" class="btn btn-success"><?= htmlspecialchars(tr_text('更新', 'Update')) ?></button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="modal fade" id="chatReadDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="chatReadDetailModalTitle"><?= htmlspecialchars(tr_text('既読ユーザー', 'Read users')) ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="chatReadDetailModalBody"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?= htmlspecialchars(tr_text('閉じる', 'Close')) ?></button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script id="chatBootstrapData" type="application/json"><?= json_encode($bootstrap, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
