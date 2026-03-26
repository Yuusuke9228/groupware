<div class="container-fluid mt-3">
    <h4 class="mb-3"><i class="fas fa-sync-alt me-2"></i>カレンダー連携</h4>
    <p class="text-muted mb-4"><?php echo htmlspecialchars($appName); ?>のスケジュールをiPhone、Googleカレンダー、Outlookなどと同期できます。公開範囲はユーザー単位で制御されます。</p>

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

    <div class="card mb-4">
        <div class="card-header bg-dark text-white">
            <strong><i class="fas fa-sliders-h me-2"></i>ユーザー別の同期設定</strong>
        </div>
        <div class="card-body">
            <form id="integration-settings-form" class="no-ajax">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="feed_enabled" name="feed_enabled" <?php echo !empty($settings['feed_enabled']) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="feed_enabled">外部カレンダー購読を有効にする</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="allow_ics_import" name="allow_ics_import" <?php echo !empty($settings['allow_ics_import']) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="allow_ics_import">外部ICS取り込みを許可する</label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="include_public" name="include_public" <?php echo !empty($settings['include_public']) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="include_public">全体公開予定</label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="include_participant" name="include_participant" <?php echo !empty($settings['include_participant']) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="include_participant">参加予定</label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="include_organization" name="include_organization" <?php echo !empty($settings['include_organization']) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="include_organization">組織共有予定</label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="include_private" name="include_private" <?php echo !empty($settings['include_private']) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="include_private">自分の限定予定</label>
                        </div>
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-2 mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>設定を保存
                    </button>
                    <button type="button" class="btn btn-outline-danger" id="regenerate-token-btn">
                        <i class="fas fa-key me-1"></i>購読トークンを再発行
                    </button>
                </div>
            </form>
            <div class="alert alert-secondary small mt-3 mb-0">
                <i class="fas fa-shield-alt me-1"></i>
                ユーザーごとに購読URLを持ちます。個人予定を出したくない場合は「自分の限定予定」をオフにしてください。トークン再発行後、旧URLは無効になります。
            </div>
        </div>
    </div>

    <!-- カレンダーURL -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <strong><i class="fas fa-link me-2"></i>あなたの同期用URL</strong>
        </div>
        <div class="card-body">
            <p class="mb-2">以下のトークン付きURLをカレンダーアプリに登録すると、<?php echo htmlspecialchars($appName); ?>の予定が自動で同期されます。</p>
            <div class="input-group mb-3">
                <input type="text" id="feed-url" class="form-control" readonly value="<?php echo htmlspecialchars($absoluteFeedUrl); ?>">
                <button class="btn btn-outline-primary copy-feed-url" type="button" data-target="#feed-url">
                    <i class="fas fa-copy me-1"></i>コピー
                </button>
            </div>
            <div class="input-group mb-3">
                <input type="text" id="auth-feed-url" class="form-control" readonly value="<?php echo htmlspecialchars($absoluteAuthFeedUrl); ?>">
                <button class="btn btn-outline-secondary copy-feed-url" type="button" data-target="#auth-feed-url">
                    <i class="fas fa-copy me-1"></i>ログイン中のURLをコピー
                </button>
            </div>
            <div class="alert alert-warning small mb-0">
                <i class="fas fa-info-circle me-1"></i>
                <strong>URLについて:</strong> 上段が外部購読向けのトークンURL、下段がログイン中の確認用URLです。
                外部カレンダーへ登録する場合は、必ず上段のトークンURLを使用してください。
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a class="btn btn-success" target="_blank" href="<?php echo htmlspecialchars($googleUrl); ?>">
                    <i class="fab fa-google me-1"></i>Googleカレンダーに追加
                </a>
                <a class="btn btn-primary" target="_blank" href="<?php echo htmlspecialchars($outlookUrl); ?>">
                    <i class="fab fa-microsoft me-1"></i>Outlookに追加
                </a>
                <a class="btn btn-outline-secondary" href="<?php echo htmlspecialchars($tokenFeedUrl); ?>" target="_blank">
                    <i class="fas fa-download me-1"></i>購読URLを開く
                </a>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <!-- iPhone設定手順 -->
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header">
                    <strong><i class="fab fa-apple me-2"></i>iPhone / iPad</strong>
                </div>
                <div class="card-body">
                    <ol class="mb-0" style="padding-left:20px;line-height:2;">
                        <li>上の同期用URLを<strong>コピー</strong></li>
                        <li>iPhoneの<strong>設定</strong>アプリを開く</li>
                        <li><strong>カレンダー</strong> をタップ</li>
                        <li><strong>アカウント</strong> をタップ</li>
                        <li><strong>アカウントを追加</strong> をタップ</li>
                        <li><strong>その他</strong> をタップ</li>
                        <li><strong>照会するカレンダーを追加</strong></li>
                        <li>コピーしたURLを<strong>サーバ</strong>欄に貼り付け</li>
                        <li><strong>次へ</strong> → <strong>保存</strong></li>
                    </ol>
                    <div class="alert alert-info mt-3 mb-0 small">
                        <i class="fas fa-info-circle me-1"></i>
                        登録後、iPhoneの標準カレンダーアプリに<?php echo htmlspecialchars($appName); ?>の予定が表示されます。自動で定期的に更新されます。
                    </div>
                </div>
            </div>
        </div>

        <!-- Googleカレンダー設定手順 -->
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header">
                    <strong><i class="fab fa-google me-2"></i>Googleカレンダー</strong>
                </div>
                <div class="card-body">
                    <h6>方法1: ワンクリック追加</h6>
                    <p>上の<strong>「Googleカレンダーに追加」</strong>ボタンをクリックするだけ！</p>
                    <hr>
                    <h6>方法2: 手動追加</h6>
                    <ol class="mb-0" style="padding-left:20px;line-height:2;">
                        <li>上の同期用URLを<strong>コピー</strong></li>
                        <li>PCで<a href="https://calendar.google.com" target="_blank">Googleカレンダー</a>を開く</li>
                        <li>左メニュー「他のカレンダー」横の<strong>+</strong>をクリック</li>
                        <li><strong>URLで追加</strong>を選択</li>
                        <li>コピーしたURLを貼り付けて<strong>カレンダーを追加</strong></li>
                    </ol>
                    <div class="alert alert-info mt-3 mb-0 small">
                        <i class="fas fa-info-circle me-1"></i>
                        Googleカレンダーの更新は数時間〜24時間かかることがあります。
                    </div>
                </div>
            </div>
        </div>

        <!-- Outlook設定手順 -->
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header">
                    <strong><i class="fab fa-microsoft me-2"></i>Outlook</strong>
                </div>
                <div class="card-body">
                    <h6>方法1: ワンクリック追加</h6>
                    <p>上の<strong>「Outlookに追加」</strong>ボタンをクリックするだけ！</p>
                    <hr>
                    <h6>方法2: デスクトップアプリ</h6>
                    <ol class="mb-0" style="padding-left:20px;line-height:2;">
                        <li>上の同期用URLを<strong>コピー</strong></li>
                        <li>Outlookを開き<strong>予定表</strong>を表示</li>
                        <li><strong>カレンダーの追加</strong>をクリック</li>
                        <li><strong>Web上のカレンダー</strong>を選択</li>
                        <li>URLを貼り付けて<strong>追加</strong></li>
                    </ol>
                    <div class="alert alert-info mt-3 mb-0 small">
                        <i class="fas fa-info-circle me-1"></i>
                        Outlook.comと同期されている場合、PCとスマホの両方に反映されます。
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ICS取り込み（双方向同期） -->
    <div class="card mb-4">
        <div class="card-header bg-info text-white">
            <strong><i class="fas fa-exchange-alt me-2"></i>外部カレンダーからの取り込み（双方向同期）</strong>
        </div>
        <div class="card-body">
            <p class="mb-3">GoogleカレンダーやiPhoneで入力した予定を<?php echo htmlspecialchars($appName); ?>に取り込むことができます。</p>

            <div class="row g-3 mb-3">
                <div class="col-lg-6">
                    <div class="card border">
                        <div class="card-header py-2"><strong><i class="fab fa-google me-2"></i>Googleカレンダーから取り込む</strong></div>
                        <div class="card-body small">
                            <ol class="mb-0" style="padding-left:18px;line-height:2;">
                                <li>PCで<a href="https://calendar.google.com/calendar/ical/" target="_blank">Googleカレンダー</a>を開く</li>
                                <li>左のカレンダー名の右「⋮」→「設定と共有」</li>
                                <li>「iCal形式の秘密のアドレス」をコピー</li>
                                <li>下の入力欄に貼り付けて「取り込み」をクリック</li>
                            </ol>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card border">
                        <div class="card-header py-2"><strong><i class="fab fa-apple me-2"></i>iCloudカレンダーから取り込む</strong></div>
                        <div class="card-body small">
                            <ol class="mb-0" style="padding-left:18px;line-height:2;">
                                <li>PCで<a href="https://www.icloud.com/calendar" target="_blank">iCloud.com</a>にサインイン</li>
                                <li>カレンダー名の右の共有アイコンをクリック</li>
                                <li>「公開カレンダー」にチェックを入れる</li>
                                <li>表示されたURLをコピーして下に貼り付け</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <form id="ics-import-form" class="no-ajax">
                <div class="input-group" style="max-width:700px;">
                    <input type="url" class="form-control" name="url" placeholder="外部カレンダーのICS URLを貼り付け" required <?php echo empty($settings['allow_ics_import']) ? 'disabled' : ''; ?>>
                    <button class="btn btn-primary" type="submit" <?php echo empty($settings['allow_ics_import']) ? 'disabled' : ''; ?>>
                        <i class="fas fa-download me-1"></i>取り込み
                    </button>
                </div>
            </form>
            <small class="text-muted mt-1 d-block">
                取り込まれた予定は公開予定として登録されます。<?php echo empty($settings['allow_ics_import']) ? '現在は設定で取り込みが無効です。' : '定期的に取り込むことで同期できます。'; ?>
            </small>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header bg-secondary text-white">
            <strong><i class="fas fa-repeat me-2"></i>定期取り込みジョブ</strong>
        </div>
        <div class="card-body">
            <p class="text-muted">外部カレンダーの ICS URL を登録すると、定期ジョブで取り込み更新・削除まで同期します。<?php echo htmlspecialchars($appName); ?> から外部への購読 URL と組み合わせることで、実運用上の双方向同期として使えます。</p>

            <form method="post" action="<?= BASE_PATH ?>/integrations/calendar-subscriptions" class="row g-3 mb-4 no-ajax">
                <input type="hidden" name="csrf_token" value="<?= $this->generateCsrfToken() ?>">
                <div class="col-md-3">
                    <label class="form-label">表示名</label>
                    <input type="text" class="form-control" name="name" placeholder="Google 個人予定">
                </div>
                <div class="col-md-5">
                    <label class="form-label">ICS URL</label>
                    <input type="url" class="form-control" name="source_url" required placeholder="https://.../calendar.ics">
                </div>
                <div class="col-md-2">
                    <label class="form-label">同期間隔(分)</label>
                    <input type="number" class="form-control" name="sync_interval_minutes" min="5" max="1440" value="30">
                </div>
                <div class="col-md-2">
                    <label class="form-label">登録時の公開範囲</label>
                    <select class="form-select" name="visibility">
                        <option value="public">全体公開</option>
                        <option value="private">自分のみ</option>
                        <option value="specific">参加者限定</option>
                    </select>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-dark">
                        <i class="fas fa-plus me-1"></i>購読を追加
                    </button>
                </div>
            </form>

            <?php if (empty($subscriptions)): ?>
                <div class="text-muted">登録済みの購読はありません。</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead>
                            <tr>
                                <th>名称</th>
                                <th>URL</th>
                                <th>間隔</th>
                                <th>公開範囲</th>
                                <th>最終同期</th>
                                <th>結果</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($subscriptions as $subscription): ?>
                                <tr>
                                    <td><?= htmlspecialchars($subscription['name']) ?></td>
                                    <td><code class="small"><?= htmlspecialchars(mb_strimwidth($subscription['source_url'], 0, 60, '...')) ?></code></td>
                                    <td><?= (int)$subscription['sync_interval_minutes'] ?>分</td>
                                    <td><?= htmlspecialchars($subscription['visibility']) ?></td>
                                    <td><?= !empty($subscription['last_synced_at']) ? date('Y/m/d H:i', strtotime($subscription['last_synced_at'])) : '未同期' ?></td>
                                    <td class="small">
                                        <?php if (!empty($subscription['last_error'])): ?>
                                            <span class="text-danger"><?= htmlspecialchars($subscription['last_error']) ?></span>
                                        <?php else: ?>
                                            <span class="text-muted"><?= htmlspecialchars($subscription['last_result'] ?? '') ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <form method="post" action="<?= BASE_PATH ?>/integrations/calendar-subscriptions/<?= (int)$subscription['id'] ?>/sync" class="no-ajax">
                                                <input type="hidden" name="csrf_token" value="<?= $this->generateCsrfToken() ?>">
                                                <button type="submit" class="btn btn-outline-primary btn-sm">今すぐ同期</button>
                                            </form>
                                            <form method="post" action="<?= BASE_PATH ?>/integrations/calendar-subscriptions/<?= (int)$subscription['id'] ?>/delete" class="no-ajax" onsubmit="return confirm('この購読設定を削除しますか？');">
                                                <input type="hidden" name="csrf_token" value="<?= $this->generateCsrfToken() ?>">
                                                <button type="submit" class="btn btn-outline-danger btn-sm">削除</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="7" class="bg-light">
                                        <form method="post" action="<?= BASE_PATH ?>/integrations/calendar-subscriptions/<?= (int)$subscription['id'] ?>/update" class="row g-2 no-ajax">
                                            <input type="hidden" name="csrf_token" value="<?= $this->generateCsrfToken() ?>">
                                            <div class="col-md-2">
                                                <input type="text" class="form-control form-control-sm" name="name" value="<?= htmlspecialchars($subscription['name']) ?>">
                                            </div>
                                            <div class="col-md-5">
                                                <input type="url" class="form-control form-control-sm" name="source_url" value="<?= htmlspecialchars($subscription['source_url']) ?>">
                                            </div>
                                            <div class="col-md-2">
                                                <input type="number" class="form-control form-control-sm" name="sync_interval_minutes" min="5" max="1440" value="<?= (int)$subscription['sync_interval_minutes'] ?>">
                                            </div>
                                            <div class="col-md-2">
                                                <select class="form-select form-select-sm" name="visibility">
                                                    <option value="public" <?= $subscription['visibility'] === 'public' ? 'selected' : '' ?>>全体公開</option>
                                                    <option value="private" <?= $subscription['visibility'] === 'private' ? 'selected' : '' ?>>自分のみ</option>
                                                    <option value="specific" <?= $subscription['visibility'] === 'specific' ? 'selected' : '' ?>>参加者限定</option>
                                                </select>
                                            </div>
                                            <div class="col-md-1 d-flex align-items-center">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="is_enabled" value="1" <?= !empty($subscription['is_enabled']) ? 'checked' : '' ?>>
                                                    <label class="form-check-label small">有効</label>
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <button type="submit" class="btn btn-outline-secondary btn-sm">設定更新</button>
                                            </div>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- CSV出力 -->
    <div class="card mb-4">
        <div class="card-header">
            <strong><i class="fas fa-file-export me-2"></i>CSVエクスポート</strong>
        </div>
        <div class="card-body">
            <div class="d-flex flex-wrap gap-2">
                <a href="<?php echo BASE_PATH; ?>/integrations/export-csv?module=schedule" class="btn btn-outline-secondary btn-sm">
                    <i class="far fa-calendar me-1"></i>予定CSV
                </a>
                <a href="<?php echo BASE_PATH; ?>/integrations/export-csv?module=workflow" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-project-diagram me-1"></i>ワークフローCSV
                </a>
                <a href="<?php echo BASE_PATH; ?>/integrations/export-csv?module=messages" class="btn btn-outline-secondary btn-sm">
                    <i class="far fa-envelope me-1"></i>メッセージCSV
                </a>
                <a href="<?php echo BASE_PATH; ?>/integrations/export-csv?module=task" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-tasks me-1"></i>タスクCSV
                </a>
            </div>
        </div>
    </div>
</div>
