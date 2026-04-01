<?php
$chatReady = !empty($chatReady);
$rooms = $rooms ?? [];
$activeRoom = $activeRoom ?? null;
$messages = $messages ?? [];
$members = $members ?? [];
$activeUsers = $activeUsers ?? [];
$currentUser = $currentUser ?? (\Core\Auth::getInstance()->user() ?? []);
$userId = (int)($currentUser['id'] ?? 0);
$csrfToken = (string)($csrf_token ?? '');
$migrationFile = (string)($migrationFile ?? '');

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

$bootstrap = [
    'basePath' => BASE_PATH,
    'userId' => $userId,
    'roomId' => (int)($activeRoom['id'] ?? 0),
    'csrfToken' => $csrfToken,
    'rooms' => $rooms,
    'activeRoom' => $activeRoom,
    'messages' => $messages,
];
?>

<style>
.chat-shell{height:calc(100vh - 190px);min-height:560px;display:flex;border:1px solid #dfe4ea;border-radius:14px;overflow:hidden;background:#f7f8fa}
.chat-sidebar{width:340px;max-width:42%;background:#fff;border-right:1px solid #e6e9ef;display:flex;flex-direction:column}
.chat-sidebar-head{padding:14px;border-bottom:1px solid #edf0f4}
.chat-room-list{overflow:auto;flex:1}
.chat-room-item{display:block;padding:12px 14px;border-bottom:1px solid #f2f4f8;color:#333;text-decoration:none}
.chat-room-item:hover{background:#f8fafc;color:#333}
.chat-room-item.active{background:#e9f8ee;border-left:4px solid #06c755;padding-left:10px}
.chat-room-title{font-weight:700;font-size:14px;display:flex;justify-content:space-between;align-items:center;gap:8px}
.chat-room-meta{font-size:12px;color:#7b8794;margin-top:4px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.chat-main{flex:1;display:flex;flex-direction:column;min-width:0}
.chat-main-head{padding:14px 18px;background:#fff;border-bottom:1px solid #edf0f4}
.chat-room-name{font-weight:700;font-size:16px;margin-bottom:2px}
.chat-room-sub{font-size:12px;color:#7b8794}
.chat-message-area{flex:1;overflow:auto;padding:16px;background:linear-gradient(180deg,#f5f6f8 0%,#eef1f6 100%)}
.chat-empty{height:100%;display:flex;align-items:center;justify-content:center;color:#8c95a3;font-size:14px;text-align:center;padding:24px}
.chat-message-row{display:flex;margin-bottom:12px}
.chat-message-row.mine{justify-content:flex-end}
.chat-bubble-wrap{max-width:74%}
.chat-sender{font-size:11px;color:#6f7a89;margin:0 0 3px 2px}
.chat-message-row.mine .chat-sender{text-align:right;margin:0 2px 3px 0}
.chat-bubble{background:#fff;border-radius:14px;padding:9px 11px;box-shadow:0 1px 2px rgba(0,0,0,0.06);word-break:break-word}
.chat-message-row.mine .chat-bubble{background:#d8f7b9;border-top-right-radius:4px}
.chat-message-row.other .chat-bubble{border-top-left-radius:4px}
.chat-attachment{margin-top:8px;padding:7px 9px;background:rgba(255,255,255,0.6);border:1px solid rgba(0,0,0,0.08);border-radius:10px;font-size:12px}
.chat-time{font-size:10px;color:#7b8794;margin-top:4px;text-align:right}
.chat-read{font-size:10px;color:#06a94d;margin-top:2px;text-align:right}
.chat-composer{padding:12px;background:#fff;border-top:1px solid #edf0f4}
.chat-composer textarea{resize:none}
.chat-badge{display:inline-flex;min-width:20px;height:20px;padding:0 6px;background:#ff4d4f;color:#fff;border-radius:999px;align-items:center;justify-content:center;font-size:11px;font-weight:700}
.chat-member-chip{display:inline-block;padding:2px 8px;border-radius:999px;background:#eef4ff;color:#476282;font-size:11px;margin:2px 4px 2px 0}

@media (max-width: 991px){
  .chat-shell{height:auto;min-height:0;display:block}
  .chat-sidebar{width:100%;max-width:none;border-right:none;border-bottom:1px solid #e6e9ef;max-height:230px}
  .chat-main{min-height:62vh}
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
        <div class="chat-shell">
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
                            ?>
                            <a class="chat-room-item <?= $isActive ? 'active' : '' ?>" href="<?= BASE_PATH ?>/chat?room_id=<?= $roomId ?>" data-room-id="<?= $roomId ?>">
                                <div class="chat-room-title">
                                    <span><?= htmlspecialchars((string)($room['display_name'] ?? tr_text('チャット', 'Chat'))) ?></span>
                                    <?php if ($unread > 0): ?><span class="chat-badge"><?= $unread ?></span><?php endif; ?>
                                </div>
                                <div class="chat-room-meta">
                                    <?= htmlspecialchars((string)($room['last_message_text'] ?? tr_text('メッセージはまだありません。', 'No messages yet.'))) ?>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </aside>

            <section class="chat-main">
                <?php if ($activeRoom): ?>
                    <div class="chat-main-head">
                        <div class="chat-room-name"><?= htmlspecialchars((string)($activeRoom['display_name'] ?? $activeRoom['name'] ?? '')) ?></div>
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
                                            <div class="chat-read"><?= htmlspecialchars(tr_text('既読', 'Read')) ?> <?= $readByOthers ?></div>
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
                            <div class="row g-2 align-items-end">
                                <div class="col-12 col-md-8">
                                    <label class="form-label small mb-1"><?= htmlspecialchars(tr_text('メッセージ', 'Message')) ?></label>
                                    <textarea class="form-control" name="message_text" id="chatMessageInput" rows="2" placeholder="<?= htmlspecialchars(tr_text('メッセージを入力...', 'Type your message...')) ?>"></textarea>
                                </div>
                                <div class="col-12 col-md-2">
                                    <label class="form-label small mb-1"><?= htmlspecialchars(tr_text('ファイル', 'File')) ?></label>
                                    <input class="form-control form-control-sm" type="file" name="attachment" id="chatAttachmentInput">
                                </div>
                                <div class="col-12 col-md-2 d-grid">
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-paper-plane me-1"></i><?= htmlspecialchars(tr_text('送信', 'Send')) ?>
                                    </button>
                                </div>
                            </div>
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
<?php endif; ?>

<script id="chatBootstrapData" type="application/json"><?= json_encode($bootstrap, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
