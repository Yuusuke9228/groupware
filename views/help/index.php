<?php
// views/help/index.php
// ご利用ガイド - 全機能マニュアル
$settingModel = new \Models\Setting();
$appName = $settingModel->getAppName();
$companyName = $settingModel->getCompanyName();
$pageTitle = 'ご利用ガイド';
?>

<style>
    .help-container { max-width: 960px; margin: 0 auto; padding: 16px; }
    .help-hero {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: #fff; border-radius: 16px; padding: 40px 32px; margin-bottom: 28px; text-align: center;
        position: relative; overflow: hidden;
    }
    .help-hero::before {
        content: ''; position: absolute; top: -50%; right: -30%; width: 300px; height: 300px;
        background: rgba(255,255,255,0.08); border-radius: 50%;
    }
    .help-hero::after {
        content: ''; position: absolute; bottom: -40%; left: -20%; width: 250px; height: 250px;
        background: rgba(255,255,255,0.05); border-radius: 50%;
    }
    .help-hero h1 { font-size: 1.8rem; font-weight: 800; margin-bottom: 10px; position: relative; }
    .help-hero p { font-size: 1rem; opacity: 0.93; margin-bottom: 0; position: relative; }

    .help-toc {
        background: #f8f9ff; border: 1px solid #e0e4f5; border-radius: 12px;
        padding: 20px 24px; margin-bottom: 28px;
    }
    .help-toc h5 { font-weight: 700; margin-bottom: 14px; color: #555; font-size: 0.9rem; }
    .help-toc-grid {
        display: grid; grid-template-columns: repeat(auto-fill, minmax(210px, 1fr));
        gap: 6px 16px;
    }
    .help-toc-grid a {
        display: flex; align-items: center; gap: 6px; padding: 5px 8px;
        color: #4e73df; text-decoration: none; font-size: 0.88rem;
        border-radius: 6px; transition: background 0.15s;
    }
    .help-toc-grid a:hover { background: #eef1ff; text-decoration: none; }
    .help-toc-grid a i { width: 18px; text-align: center; font-size: 0.85rem; }

    .help-section { margin-bottom: 24px; }
    .help-card {
        border: 1px solid #e3e6f0; border-radius: 12px;
        box-shadow: 0 1px 4px rgba(0,0,0,0.04); overflow: hidden;
    }
    .help-card-head {
        padding: 14px 20px; display: flex; align-items: center; gap: 10px;
        cursor: pointer; user-select: none; background: #fff;
        border-bottom: 2px solid #667eea; transition: background 0.15s;
    }
    .help-card-head:hover { background: #f8f9ff; }
    .help-card-head .sec-icon { font-size: 1.15rem; color: #667eea; width: 26px; text-align: center; }
    .help-card-head h2 { font-size: 1.05rem; font-weight: 700; margin: 0; color: #333; flex: 1; }
    .help-card-head .toggle-icon { color: #aaa; transition: transform 0.3s; }
    .help-card-head.collapsed .toggle-icon { transform: rotate(-90deg); }

    .help-card-body {
        padding: 18px 22px; font-size: 0.9rem; line-height: 1.85; color: #555;
    }
    .help-card-body h4 {
        font-size: 0.95rem; font-weight: 700; color: #444;
        margin: 18px 0 8px; padding-left: 10px;
        border-left: 3px solid #667eea;
    }
    .help-card-body h4:first-child { margin-top: 0; }
    .help-card-body p { margin-bottom: 8px; }
    .help-card-body ul, .help-card-body ol { padding-left: 22px; margin-bottom: 10px; }
    .help-card-body li { margin-bottom: 4px; }

    .help-step {
        display: flex; gap: 10px; align-items: flex-start;
        padding: 8px 12px; margin: 4px 0; background: #f8f9fc;
        border-radius: 8px; font-size: 0.88rem;
    }
    .help-step-num {
        background: #667eea; color: #fff; width: 22px; height: 22px;
        border-radius: 50%; display: flex; align-items: center; justify-content: center;
        font-size: 0.75rem; font-weight: 700; flex-shrink: 0; margin-top: 1px;
    }
    .help-step-text { flex: 1; }

    .help-tip {
        background: #fff8e6; border-left: 3px solid #f0ad4e; padding: 10px 14px;
        border-radius: 0 8px 8px 0; margin: 10px 0; font-size: 0.87rem;
    }
    .help-tip strong { color: #c88a00; }

    .help-warn {
        background: #fef0f0; border-left: 3px solid #e74c3c; padding: 10px 14px;
        border-radius: 0 8px 8px 0; margin: 10px 0; font-size: 0.87rem;
    }
    .help-warn strong { color: #c0392b; }

    .help-info {
        background: #e8f4fd; border-left: 3px solid #2196F3; padding: 10px 14px;
        border-radius: 0 8px 8px 0; margin: 10px 0; font-size: 0.87rem;
    }
    .help-info strong { color: #1565C0; }

    .help-shortcut {
        display: inline-block; padding: 1px 7px; background: #eee;
        border: 1px solid #ccc; border-radius: 4px; font-size: 0.8rem;
        font-family: monospace; color: #333;
    }

    @media (max-width: 768px) {
        .help-container { padding: 10px; }
        .help-hero { padding: 24px 16px; }
        .help-hero h1 { font-size: 1.4rem; }
        .help-toc-grid { grid-template-columns: 1fr 1fr; }
        .help-card-body { padding: 14px 16px; }
    }
</style>

<div class="help-container">

    <!-- ヒーローセクション -->
    <div class="help-hero">
        <h1><i class="fas fa-book-open me-2"></i><?php echo htmlspecialchars($appName); ?> ご利用ガイド</h1>
        <p>本システムの全機能について、わかりやすくご説明いたします。<br>初めてお使いになる方も、どうぞご安心ください。</p>
    </div>

    <!-- マニュアルリンク -->
    <div class="row mb-4 g-3">
        <div class="col-md-6">
            <a href="<?php echo BASE_PATH; ?>/help/install-manual" class="text-decoration-none">
                <div class="card border-0 shadow-sm h-100" style="border-left: 4px solid #667eea !important;">
                    <div class="card-body d-flex align-items-center gap-3 py-3">
                        <div style="width:44px;height:44px;background:linear-gradient(135deg,#667eea,#764ba2);border-radius:10px;display:flex;align-items:center;justify-content:center;">
                            <i class="fas fa-download text-white"></i>
                        </div>
                        <div>
                            <h6 class="mb-0 fw-bold text-dark">インストールマニュアル</h6>
                            <small class="text-muted">システム要件・導入手順・トラブルシューティング</small>
                        </div>
                        <i class="fas fa-chevron-right ms-auto text-muted"></i>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-6">
            <a href="<?php echo BASE_PATH; ?>/help/admin-manual" class="text-decoration-none">
                <div class="card border-0 shadow-sm h-100" style="border-left: 4px solid #667eea !important;">
                    <div class="card-body d-flex align-items-center gap-3 py-3">
                        <div style="width:44px;height:44px;background:linear-gradient(135deg,#667eea,#764ba2);border-radius:10px;display:flex;align-items:center;justify-content:center;">
                            <i class="fas fa-user-shield text-white"></i>
                        </div>
                        <div>
                            <h6 class="mb-0 fw-bold text-dark">管理者マニュアル</h6>
                            <small class="text-muted">ユーザー管理・組織管理・システム設定</small>
                        </div>
                        <i class="fas fa-chevron-right ms-auto text-muted"></i>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <!-- 目次 -->
    <div class="help-toc">
        <h5><i class="fas fa-list-ul me-1"></i> 目次 ― ご覧になりたい項目をクリックしてください</h5>
        <div class="help-toc-grid">
            <a href="#sec-login"><i class="fas fa-sign-in-alt"></i> ログイン・ログアウト</a>
            <a href="#sec-home"><i class="fas fa-home"></i> トップページ（ポータル）</a>
            <a href="#sec-schedule"><i class="far fa-calendar-alt"></i> スケジュール管理</a>
            <a href="#sec-message"><i class="far fa-envelope"></i> メッセージ</a>
            <a href="#sec-bulletin"><i class="fas fa-bullhorn"></i> 掲示板</a>
            <a href="#sec-workflow"><i class="fas fa-project-diagram"></i> ワークフロー（申請・承認）</a>
            <a href="#sec-task"><i class="fas fa-tasks"></i> タスク管理</a>
            <a href="#sec-visual-boards"><i class="fas fa-project-diagram"></i> Visual Boards</a>
            <a href="#sec-daily"><i class="fas fa-file-alt"></i> 日報</a>
            <a href="#sec-file"><i class="fas fa-folder-open"></i> ファイル管理</a>
            <a href="#sec-webdb"><i class="fas fa-database"></i> WEBデータベース</a>
            <a href="#sec-address"><i class="fas fa-address-book"></i> アドレス帳</a>
            <a href="#sec-facility"><i class="fas fa-building"></i> 施設予約</a>
            <a href="#sec-notify"><i class="fas fa-bell"></i> 通知</a>
            <a href="#sec-search"><i class="fas fa-search"></i> 検索</a>
            <a href="#sec-calendar-sync"><i class="fas fa-sync"></i> カレンダー連携</a>
            <a href="#sec-profile"><i class="fas fa-user-cog"></i> プロフィール設定</a>
            <a href="#sec-admin"><i class="fas fa-cogs"></i> 管理者メニュー</a>
            <a href="#sec-mobile"><i class="fas fa-mobile-alt"></i> スマートフォンでのご利用</a>
            <a href="#sec-faq"><i class="fas fa-question-circle"></i> よくあるご質問（FAQ）</a>
            <a href="#sec-shortcut"><i class="fas fa-keyboard"></i> キーボードショートカット</a>
        </div>
    </div>

    <!-- ===== 1. ログイン・ログアウト ===== -->
    <div class="help-section" id="sec-login">
        <div class="help-card">
            <div class="help-card-head" onclick="toggleSection(this)">
                <span class="sec-icon"><i class="fas fa-sign-in-alt"></i></span>
                <h2>ログイン・ログアウト</h2>
                <i class="fas fa-chevron-down toggle-icon"></i>
            </div>
            <div class="help-card-body">
                <p><?php echo htmlspecialchars($appName); ?> をご利用いただくには、まずログインが必要です。管理者から発行されたログインIDとパスワードをご用意ください。</p>

                <h4>ログインの手順</h4>
                <div class="help-step"><span class="help-step-num">1</span><span class="help-step-text">ブラウザ（Google Chrome、Safari、Microsoft Edge など）で <?php echo htmlspecialchars($appName); ?> のURLを開きます。</span></div>
                <div class="help-step"><span class="help-step-num">2</span><span class="help-step-text"><strong>ログインID</strong> と <strong>パスワード</strong> を入力してください。</span></div>
                <div class="help-step"><span class="help-step-num">3</span><span class="help-step-text">「ログイン」ボタンをクリックします。</span></div>
                <div class="help-tip"><strong><i class="fas fa-lightbulb me-1"></i>ヒント：</strong>「ログイン状態を保持する」にチェックを入れていただくと、次回以降ブラウザを開いた際に自動的にログインされます。ただし、共用のパソコンをお使いの場合は、セキュリティのためチェックを入れないことをお勧めいたします。</div>

                <h4>ログアウトの手順</h4>
                <div class="help-step"><span class="help-step-num">1</span><span class="help-step-text">画面右上のご自身のお名前（またはアイコン）をクリックします。</span></div>
                <div class="help-step"><span class="help-step-num">2</span><span class="help-step-text">表示されるメニューから「ログアウト」を選択してください。</span></div>
                <div class="help-warn"><strong><i class="fas fa-exclamation-triangle me-1"></i>ご注意：</strong>共用のパソコンでご利用の場合は、離席時に必ずログアウトを行ってください。第三者にメッセージ等を閲覧される恐れがございます。</div>

                <h4>パスワードをお忘れの場合</h4>
                <p>パスワードをお忘れになった場合は、システム管理者にご連絡ください。管理者がパスワードのリセットを行います。セキュリティ上、ご自身での再発行はできない仕組みとなっております。</p>
            </div>
        </div>
    </div>

    <!-- ===== 2. トップページ（ポータル） ===== -->
    <div class="help-section" id="sec-home">
        <div class="help-card">
            <div class="help-card-head" onclick="toggleSection(this)">
                <span class="sec-icon"><i class="fas fa-home"></i></span>
                <h2>トップページ（ポータル）</h2>
                <i class="fas fa-chevron-down toggle-icon"></i>
            </div>
            <div class="help-card-body">
                <p>ログイン後に最初に表示されるのがトップページです。業務に必要な情報を一画面でご確認いただけます。</p>

                <h4>週間スケジュール</h4>
                <p>ご自身が所属する組織のメンバー全員の、1週間分の予定が一覧表で表示されます。</p>
                <ul>
                    <li><strong>縦軸</strong>：メンバーのお名前</li>
                    <li><strong>横軸</strong>：月曜日から日曜日までの各日付</li>
                    <li>予定をクリックすると、詳細な内容をご確認いただけます。</li>
                    <li>左右の矢印ボタン（<i class="fas fa-chevron-left"></i><i class="fas fa-chevron-right"></i>）で、前週・翌週に切り替えられます。</li>
                    <li>「今週」ボタンをクリックすると、今週の表示に戻ります。</li>
                </ul>

                <h4>本日のスケジュール</h4>
                <p>画面右側に、本日の予定がリスト形式で時間順に表示されます。次に控えている予定をすぐにご確認いただけます。</p>

                <h4>タスク概要</h4>
                <p>ご自身に割り当てられているタスクの件数や、期限切れとなっているタスクの有無を確認することができます。</p>

                <h4>未読メッセージ・通知</h4>
                <p>未読のメッセージや通知がある場合は、件数が表示されます。クリックすると該当の一覧画面に移動いたします。</p>
            </div>
        </div>
    </div>

    <!-- ===== 3. スケジュール管理 ===== -->
    <div class="help-section" id="sec-schedule">
        <div class="help-card">
            <div class="help-card-head" onclick="toggleSection(this)">
                <span class="sec-icon"><i class="far fa-calendar-alt"></i></span>
                <h2>スケジュール管理</h2>
                <i class="fas fa-chevron-down toggle-icon"></i>
            </div>
            <div class="help-card-body">
                <p>会議、打ち合わせ、外出予定などを登録・共有できるカレンダー機能です。個人の予定管理はもちろん、組織全体のスケジュール把握にもお使いいただけます。</p>

                <h4>表示モードの切り替え</h4>
                <p>画面上部のツールバーから、ご希望の表示形式をお選びいただけます。</p>
                <ul>
                    <li><strong>日表示</strong> ― 1日の予定を時間帯ごとに詳しくご確認いただけます。</li>
                    <li><strong>週表示</strong> ― 1週間分の予定を見渡すことができます。</li>
                    <li><strong>月表示</strong> ― 1か月分の予定をカレンダー形式でご覧いただけます。</li>
                </ul>
                <p>さらに「個人」と「組織」の2つのモードがございます。</p>
                <ul>
                    <li><strong>個人モード</strong> ― ご自身の予定のみが表示されます。</li>
                    <li><strong>組織モード</strong> ― 選択した組織（部署）に所属する全メンバーの予定が一覧で表示されます。メンバーの空き時間を把握するのに大変便利です。</li>
                </ul>

                <h4>予定の新規登録</h4>
                <div class="help-step"><span class="help-step-num">1</span><span class="help-step-text">「<i class="fas fa-plus"></i> 新規作成」ボタンをクリックします。</span></div>
                <div class="help-step"><span class="help-step-num">2</span><span class="help-step-text"><strong>タイトル</strong>を入力します（例：「○○社 定例打ち合わせ」）。</span></div>
                <div class="help-step"><span class="help-step-num">3</span><span class="help-step-text"><strong>開始日時</strong>と<strong>終了日時</strong>を設定します。1日を通した予定の場合は「終日」にチェックを入れてください。</span></div>
                <div class="help-step"><span class="help-step-num">4</span><span class="help-step-text">必要に応じて、<strong>場所</strong>や<strong>メモ</strong>をご入力ください。</span></div>
                <div class="help-step"><span class="help-step-num">5</span><span class="help-step-text"><strong>参加者</strong>を追加する場合は、名前を入力して検索し、候補から選択してください。</span></div>
                <div class="help-step"><span class="help-step-num">6</span><span class="help-step-text"><strong>組織</strong>を追加すると、該当組織の全メンバーに予定が共有されます。</span></div>
                <div class="help-step"><span class="help-step-num">7</span><span class="help-step-text"><strong>施設</strong>（会議室等）を利用する場合は、施設の予約もあわせて行うことができます。</span></div>
                <div class="help-step"><span class="help-step-num">8</span><span class="help-step-text">「保存」ボタンをクリックすると、予定が登録されます。</span></div>
                <div class="help-tip"><strong><i class="fas fa-lightbulb me-1"></i>ヒント：</strong>参加者を設定すると、相手の方にも通知が届きます。参加者は予定に対して「参加」「不参加」「未定」の回答をすることができます。</div>

                <h4>予定の編集・削除</h4>
                <p>予定をクリックすると詳細画面が表示されます。「編集」ボタンで内容を変更したり、「削除」ボタンで予定を取り消したりすることができます。</p>
                <div class="help-info"><strong><i class="fas fa-info-circle me-1"></i>補足：</strong>編集・削除が可能なのは、ご自身が作成した予定のみとなります。他のメンバーが作成した予定を変更することはできませんので、ご安心ください。</div>

                <h4>優先度による色分け</h4>
                <p>予定には優先度を設定でき、カレンダー上で色分けして表示されます。</p>
                <ul>
                    <li><span style="color:#ea4335;font-weight:700;">赤色</span> ― 優先度：高（重要な予定）</li>
                    <li><span style="color:#2b7de9;font-weight:700;">青色</span> ― 優先度：通常</li>
                    <li><span style="color:#34a853;font-weight:700;">緑色</span> ― 優先度：低</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- ===== 4. メッセージ ===== -->
    <div class="help-section" id="sec-message">
        <div class="help-card">
            <div class="help-card-head" onclick="toggleSection(this)">
                <span class="sec-icon"><i class="far fa-envelope"></i></span>
                <h2>メッセージ</h2>
                <i class="fas fa-chevron-down toggle-icon"></i>
            </div>
            <div class="help-card-body">
                <p><?php echo htmlspecialchars($appName); ?> 内で利用できる社内メール機能です。組織内のメンバーに対して、手軽にメッセージを送受信することができます。</p>

                <h4>受信トレイ</h4>
                <p>サイドメニューの「メッセージ」をクリックすると、受信トレイが表示されます。<strong>太字</strong>で表示されているメッセージは、まだお読みになっていない未読メッセージです。</p>

                <h4>メッセージの送信</h4>
                <div class="help-step"><span class="help-step-num">1</span><span class="help-step-text">「<i class="fas fa-pen"></i> 新規作成」ボタンをクリックします。</span></div>
                <div class="help-step"><span class="help-step-num">2</span><span class="help-step-text"><strong>宛先</strong>を指定します。お名前の一部を入力すると候補が表示されますので、該当の方を選択してください。複数名への同時送信も可能です。</span></div>
                <div class="help-step"><span class="help-step-num">3</span><span class="help-step-text"><strong>件名</strong>を入力します。内容が一目でわかるよう、簡潔にご記入ください。</span></div>
                <div class="help-step"><span class="help-step-num">4</span><span class="help-step-text"><strong>本文</strong>を入力します。</span></div>
                <div class="help-step"><span class="help-step-num">5</span><span class="help-step-text">ファイルを添付する場合は、「<i class="fas fa-paperclip"></i> ファイル添付」ボタンからファイルを選択してください。</span></div>
                <div class="help-step"><span class="help-step-num">6</span><span class="help-step-text">「送信」ボタンをクリックすると、メッセージが送信されます。</span></div>

                <h4>返信・全員に返信・転送</h4>
                <p>受信したメッセージの詳細画面には、以下のボタンが表示されます。</p>
                <ul>
                    <li><strong>返信</strong> ― 送信者のみに返信いたします。</li>
                    <li><strong>全員に返信</strong> ― 元のメッセージの宛先に含まれる全員に返信いたします。</li>
                    <li><strong>転送</strong> ― 別のメンバーにメッセージの内容を転送いたします。</li>
                </ul>

                <h4>下書き保存</h4>
                <p>作成途中のメッセージは、下書きとして保存しておくことができます。後ほど編集を再開し、送信することが可能です。</p>

                <h4>スター機能</h4>
                <p>重要なメッセージには <i class="fas fa-star" style="color:#f0ad4e;"></i> スターを付けることができます。「スター付き」タブからスターの付いたメッセージのみを一覧でご確認いただけます。</p>

                <h4>送信済み</h4>
                <p>「送信済み」タブから、ご自身が送信されたメッセージの履歴を確認することができます。</p>

                <h4>フォルダ管理</h4>
                <p>メッセージをフォルダに分類して整理することができます。プロジェクトごとやカテゴリごとにフォルダを作成すると、必要なメッセージを素早く見つけることができます。</p>
            </div>
        </div>
    </div>

    <!-- ===== 5. 掲示板 ===== -->
    <div class="help-section" id="sec-bulletin">
        <div class="help-card">
            <div class="help-card-head" onclick="toggleSection(this)">
                <span class="sec-icon"><i class="fas fa-bullhorn"></i></span>
                <h2>掲示板</h2>
                <i class="fas fa-chevron-down toggle-icon"></i>
            </div>
            <div class="help-card-body">
                <p>全社員への連絡事項や情報共有にご利用いただける掲示板機能です。会社からのお知らせ、各部署からの連絡など、組織内の情報伝達にお役立てください。</p>

                <h4>掲示板の閲覧</h4>
                <p>メニューの「掲示板」をクリックすると、投稿の一覧が表示されます。カテゴリで絞り込んで表示することも可能です。</p>
                <ul>
                    <li><strong>お知らせ</strong> ― 会社全体への通達・連絡事項</li>
                    <li><strong>総務・人事</strong> ― 人事関連のお知らせ</li>
                    <li><strong>IT・システム</strong> ― システムに関する情報・メンテナンス告知</li>
                    <li><strong>その他</strong> ― 上記以外のさまざまな情報</li>
                </ul>

                <h4>記事の投稿</h4>
                <div class="help-step"><span class="help-step-num">1</span><span class="help-step-text">「<i class="fas fa-plus"></i> 新規投稿」ボタンをクリックします。</span></div>
                <div class="help-step"><span class="help-step-num">2</span><span class="help-step-text"><strong>カテゴリ</strong>を選択します。</span></div>
                <div class="help-step"><span class="help-step-num">3</span><span class="help-step-text"><strong>タイトル</strong>と<strong>本文</strong>を入力します。</span></div>
                <div class="help-step"><span class="help-step-num">4</span><span class="help-step-text">資料等を添付する場合は、「ファイルを選択」からファイルを追加してください。</span></div>
                <div class="help-step"><span class="help-step-num">5</span><span class="help-step-text">必要に応じて<strong>公開範囲</strong>（閲覧可能な対象）を設定してください。</span></div>
                <div class="help-step"><span class="help-step-num">6</span><span class="help-step-text">「投稿する」ボタンをクリックすると、掲示板に公開されます。</span></div>

                <h4>コメント機能</h4>
                <p>各記事の下部にコメントを投稿することができます。質問や補足情報の共有などにご活用ください。</p>

                <h4>ピン留め機能</h4>
                <p>管理者が重要な記事を「ピン留め」すると、一覧の最上部に固定表示されます。重要なお知らせを見逃さないようご確認ください。</p>

                <h4>添付ファイル</h4>
                <p>投稿時にファイルを添付することができます。会議資料やマニュアルなどの配布にご利用ください。</p>
            </div>
        </div>
    </div>

    <!-- ===== 6. ワークフロー（申請・承認） ===== -->
    <div class="help-section" id="sec-workflow">
        <div class="help-card">
            <div class="help-card-head" onclick="toggleSection(this)">
                <span class="sec-icon"><i class="fas fa-project-diagram"></i></span>
                <h2>ワークフロー（申請・承認）</h2>
                <i class="fas fa-chevron-down toggle-icon"></i>
            </div>
            <div class="help-card-body">
                <p>有給休暇の申請、経費精算、稟議書など、承認が必要な各種申請をオンラインで行うための機能です。紙の申請書や押印に代わり、画面上で迅速に申請・承認の手続きを進めることができます。</p>

                <h4>新規申請の手順</h4>
                <div class="help-step"><span class="help-step-num">1</span><span class="help-step-text">メニューの「ワークフロー」を開きます。</span></div>
                <div class="help-step"><span class="help-step-num">2</span><span class="help-step-text">「<i class="fas fa-plus"></i> 新規申請」ボタンをクリックします。</span></div>
                <div class="help-step"><span class="help-step-num">3</span><span class="help-step-text">申請の種類（テンプレート）を選択します（例：「有給休暇申請」「経費精算」「備品購入申請」など）。</span></div>
                <div class="help-step"><span class="help-step-num">4</span><span class="help-step-text">フォームの各項目に必要な情報を入力します。</span></div>
                <div class="help-step"><span class="help-step-num">5</span><span class="help-step-text">入力内容を確認し、「申請する」ボタンをクリックして提出します。</span></div>
                <p>申請を提出すると、承認者に自動的に通知が送信されます。</p>

                <h4>申請状況の確認</h4>
                <p>「申請一覧」画面で、ご自身が提出された申請の進捗状況をご確認いただけます。</p>
                <ul>
                    <li><span style="color:#f0ad4e;">●</span> <strong>承認待ち</strong> ― 現在、承認者による審査中です。</li>
                    <li><span style="color:#28a745;">●</span> <strong>承認済み</strong> ― 承認が完了しました。</li>
                    <li><span style="color:#dc3545;">●</span> <strong>却下</strong> ― 承認されませんでした。理由をご確認のうえ、必要に応じて再申請してください。</li>
                    <li><span style="color:#6c757d;">●</span> <strong>取り下げ</strong> ― ご自身で申請を取り消されたものです。</li>
                    <li><span style="color:#17a2b8;">●</span> <strong>差し戻し</strong> ― 修正が必要なため、承認者から差し戻されたものです。</li>
                </ul>

                <h4>承認者としての操作</h4>
                <p>ご自身が承認者に設定されている場合、「承認依頼」タブに未処理の申請が表示されます。</p>
                <div class="help-step"><span class="help-step-num">1</span><span class="help-step-text">申請の内容を確認します。</span></div>
                <div class="help-step"><span class="help-step-num">2</span><span class="help-step-text">承認する場合は「承認」ボタン、承認しない場合は「却下」ボタンをクリックします。</span></div>
                <div class="help-step"><span class="help-step-num">3</span><span class="help-step-text">必要に応じてコメントをご記入ください。却下の場合は理由をお伝えいただくと、申請者にとって大変助かります。</span></div>

                <h4>代理承認の設定</h4>
                <p>ご出張や休暇などで承認が行えない期間がある場合、別のメンバーに承認権限を委任することができます。「代理設定」画面から設定してください。</p>

                <h4>PDF出力</h4>
                <p>承認が完了した申請は、PDF形式でダウンロードすることができます。印刷して保管する場合などにご利用ください。</p>

                <h4>CSV出力</h4>
                <p>申請データをCSV形式で書き出すことも可能です。集計や分析にご活用いただけます。</p>
            </div>
        </div>
    </div>

    <!-- ===== 7. タスク管理 ===== -->
    <div class="help-section" id="sec-task">
        <div class="help-card">
            <div class="help-card-head" onclick="toggleSection(this)">
                <span class="sec-icon"><i class="fas fa-tasks"></i></span>
                <h2>タスク管理</h2>
                <i class="fas fa-chevron-down toggle-icon"></i>
            </div>
            <div class="help-card-body">
                <p>個人やチームのタスク（やるべき作業）を管理するための機能です。カンバンボード形式で、タスクの進捗状況を視覚的に把握することができます。</p>

                <h4>ボードの作成</h4>
                <div class="help-step"><span class="help-step-num">1</span><span class="help-step-text">メニューの「タスク」を開きます。</span></div>
                <div class="help-step"><span class="help-step-num">2</span><span class="help-step-text">「<i class="fas fa-plus"></i> ボード作成」ボタンをクリックします。</span></div>
                <div class="help-step"><span class="help-step-num">3</span><span class="help-step-text">ボードの名前を入力します（例：「営業部 週次タスク」「プロジェクトA」）。</span></div>
                <p>ボード内には「リスト」（列）を作成し、タスクの状態ごとに分類します。一般的な構成は以下のとおりです。</p>
                <ul>
                    <li><strong>未着手</strong> ― まだ作業に取りかかっていないタスク</li>
                    <li><strong>進行中</strong> ― 現在作業中のタスク</li>
                    <li><strong>完了</strong> ― 作業が完了したタスク</li>
                </ul>

                <h4>チームメンバーの設定</h4>
                <p>ボードにチームメンバーを追加すると、メンバー間でタスクを共有・分担することができます。ボードの設定画面からメンバーを追加してください。</p>

                <h4>カード（タスク）の作成</h4>
                <div class="help-step"><span class="help-step-num">1</span><span class="help-step-text">リスト内の「<i class="fas fa-plus"></i> カード追加」ボタンをクリックします。</span></div>
                <div class="help-step"><span class="help-step-num">2</span><span class="help-step-text">タスクの<strong>タイトル</strong>を入力します。</span></div>
                <div class="help-step"><span class="help-step-num">3</span><span class="help-step-text">必要に応じて、<strong>詳細説明</strong>、<strong>期限</strong>、<strong>担当者</strong>を設定します。</span></div>

                <h4>ドラッグ＆ドロップによるステータス変更</h4>
                <p>タスクの進捗にあわせて、カードをマウスでつかんで別のリストへ移動させてください。例えば「未着手」から「進行中」に移動させるだけで、ステータスが更新されます。</p>

                <h4>チェックリスト</h4>
                <p>カードの中にチェックリストを作成することができます。ひとつのタスクに複数の小さな作業ステップがある場合に便利です。</p>

                <h4>ラベル（タグ）</h4>
                <p>色分けされたラベルをカードに付けることで、タスクを分類できます（例：「緊急」「バグ修正」「改善」「調査」など）。</p>

                <h4>コメント</h4>
                <p>各カードにコメントを投稿して、チームメンバーとのやり取りを行うことができます。進捗報告や質問などにご活用ください。</p>

                <h4>マイタスク</h4>
                <p>「マイタスク」タブでは、すべてのボードの中からご自身が担当しているタスクのみを一覧でご確認いただけます。</p>
            </div>
        </div>
    </div>

    <!-- ===== 8. Visual Boards ===== -->
    <div class="help-section" id="sec-visual-boards">
        <div class="help-card">
            <div class="help-card-head" onclick="toggleSection(this)">
                <span class="sec-icon"><i class="fas fa-project-diagram"></i></span>
                <h2>Visual Boards</h2>
                <i class="fas fa-chevron-down toggle-icon"></i>
            </div>
            <div class="help-card-body">
                <p>Visual Boards は、ノードと接続線で思考を整理するための機能です。既存のタスクカンバンとは用途とデータが分離されています。</p>

                <h4>新規作成（テンプレート）</h4>
                <div class="help-step"><span class="help-step-num">1</span><span class="help-step-text">メニューの「Visual Boards」を開き、「新規Visual Board」をクリックします。</span></div>
                <div class="help-step"><span class="help-step-num">2</span><span class="help-step-text">テンプレート（Blank / Mind Map / Flowchart / Brainstorm / Planning / Team Planning / Personal Thinking）を選択して作成します。</span></div>
                <div class="help-step"><span class="help-step-num">3</span><span class="help-step-text">必要に応じて「関連タスクプロジェクト」を選択すると、ノードのタスク連携候補を絞り込めます。</span></div>

                <h4>基本操作</h4>
                <ul>
                    <li><strong>ノード追加</strong>：「ルート追加」「子ノード」「兄弟ノード」</li>
                    <li><strong>親ノード変更</strong>：ノード詳細の「親ノード」で付け替え（循環参照は不可）</li>
                    <li><strong>ノード複製</strong>：ノード詳細の「複製」ボタンで内容を引き継いで複製</li>
                    <li><strong>接続線</strong>：「接続線」を押して、接続元ノード → 接続先ノードの順に選択</li>
                    <li><strong>自動レイアウト</strong>：ノード配置を自動で整理</li>
                    <li><strong>全体表示</strong>：キャンバス全体が見える位置・倍率に戻す</li>
                    <li><strong>保存</strong>：明示保存、または編集後の自動保存</li>
                </ul>

                <h4>ショートカット</h4>
                <ul>
                    <li><span class="help-shortcut">Tab</span>：子ノード追加</li>
                    <li><span class="help-shortcut">Enter</span>：兄弟ノード追加</li>
                    <li><span class="help-shortcut">Ctrl</span>+<span class="help-shortcut">Z</span>：Undo</li>
                    <li><span class="help-shortcut">Ctrl</span>+<span class="help-shortcut">Y</span>：Redo</li>
                    <li><span class="help-shortcut">Ctrl</span>+<span class="help-shortcut">S</span>：保存</li>
                </ul>

                <h4>スマートフォン操作</h4>
                <ul>
                    <li>1本指ドラッグでキャンバス移動</li>
                    <li>2本指ピンチでズーム</li>
                    <li>ノードタップで選択、接続モード時は順番にタップして線を接続</li>
                </ul>

                <div class="help-tip"><strong><i class="fas fa-lightbulb me-1"></i>ヒント：</strong>テンプレートは初期配置のひな型です。作成後にノード・接続線・色・内容を自由に編集できます。</div>
            </div>
        </div>
    </div>

    <!-- ===== 9. 日報 ===== -->
    <div class="help-section" id="sec-daily">
        <div class="help-card">
            <div class="help-card-head" onclick="toggleSection(this)">
                <span class="sec-icon"><i class="fas fa-file-alt"></i></span>
                <h2>日報</h2>
                <i class="fas fa-chevron-down toggle-icon"></i>
            </div>
            <div class="help-card-body">
                <p>日報は、日々の活動記録だけでなく、案件・業種・商品・プロセス単位の分析や予実管理まで行える報告機能です。</p>

                <h4>日報の作成（標準入力）</h4>
                <div class="help-step"><span class="help-step-num">1</span><span class="help-step-text">メニューの「日報」を開き、「<i class="fas fa-plus"></i> 新規作成」をクリックします。</span></div>
                <div class="help-step"><span class="help-step-num">2</span><span class="help-step-text">日付・タイトル・実働時間を入力します。</span></div>
                <div class="help-step"><span class="help-step-num">3</span><span class="help-step-text">「業務サマリー」に本日の成果、課題、明日の予定、所感を入力します。</span></div>
                <div class="help-step"><span class="help-step-num">4</span><span class="help-step-text">必要に応じて活動ログ（開始/終了時刻・件名・結果）と分析明細（案件/業種/商品/プロセス）を追加します。</span></div>
                <div class="help-step"><span class="help-step-num">5</span><span class="help-step-text">添付ファイルを追加し、「保存」または「下書き保存」を実行します。</span></div>

                <h4>テンプレートとリッチテキスト</h4>
                <p>テンプレートには、固定項目（必須/任意・入力タイプ）と本文のひな型を設定できます。本文はリッチテキスト入力に対応しており、読みやすいレポートを作成できます。</p>

                <h4>分析・予実管理</h4>
                <p>「分析」画面では、日付・ユーザー・案件・業種・商品・プロセスで絞り込み、月次推移、軸別集計、月目標と実績比較を確認できます。分析結果は CSV 出力できます。</p>

                <h4>一覧・タイムライン・コメント</h4>
                <p>日報は一覧、週間、月間、タイムラインで確認できます。検索・絞り込みのほか、<i class="fas fa-thumbs-up" style="color:#4e73df;"></i>いいね・コメントでチーム内コミュニケーションが可能です。</p>

                <h4>入力時の注意</h4>
                <ul>
                    <li>必須項目が未入力の場合は保存できません。</li>
                    <li>日時や数値の形式が不正な場合は、エラーメッセージが表示されます。</li>
                    <li>添付ファイルは編集時に差し替え・削除できます。</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- ===== 9. ファイル管理 ===== -->
    <div class="help-section" id="sec-file">
        <div class="help-card">
            <div class="help-card-head" onclick="toggleSection(this)">
                <span class="sec-icon"><i class="fas fa-folder-open"></i></span>
                <h2>ファイル管理</h2>
                <i class="fas fa-chevron-down toggle-icon"></i>
            </div>
            <div class="help-card-body">
                <p>社内の資料やドキュメントを一元管理し、メンバー間で共有するための機能です。フォルダ構成でファイルを整理し、必要な資料にすぐにアクセスすることができます。</p>

                <h4>フォルダの作成</h4>
                <div class="help-step"><span class="help-step-num">1</span><span class="help-step-text">メニューの「ファイル管理」を開きます。</span></div>
                <div class="help-step"><span class="help-step-num">2</span><span class="help-step-text">「<i class="fas fa-folder-plus"></i> フォルダ作成」ボタンをクリックします。</span></div>
                <div class="help-step"><span class="help-step-num">3</span><span class="help-step-text">フォルダ名と作成場所を指定します。</span></div>
                <p>フォルダの中にさらにフォルダを作成する（入れ子構造にする）ことも可能です。部署ごと、プロジェクトごとなど、用途にあわせて整理してください。</p>

                <h4>ファイルのアップロード</h4>
                <div class="help-step"><span class="help-step-num">1</span><span class="help-step-text">ファイルを保存したいフォルダを開きます。</span></div>
                <div class="help-step"><span class="help-step-num">2</span><span class="help-step-text">「<i class="fas fa-upload"></i> アップロード」ボタンをクリックします。</span></div>
                <div class="help-step"><span class="help-step-num">3</span><span class="help-step-text">アップロードするファイルを選択し、タイトルや説明を入力します。</span></div>
                <div class="help-step"><span class="help-step-num">4</span><span class="help-step-text">「アップロード」ボタンをクリックして完了です。</span></div>

                <h4>ファイルのダウンロード</h4>
                <p>ファイルの詳細画面で「ダウンロード」ボタンをクリックすると、お使いのパソコンにファイルを保存することができます。</p>

                <h4>バージョン管理</h4>
                <p>ファイルを更新した場合でも、以前のバージョンが保持されます。誤って上書きしてしまった場合でも、過去のバージョンに戻すことが可能です。</p>

                <h4>チェックアウト・チェックイン</h4>
                <p>ファイルを編集する際に「チェックアウト」を行うと、他のメンバーによる同時編集を防ぐことができます。編集完了後は「チェックイン」で更新内容を反映させてください。</p>

                <h4>アクセス権限</h4>
                <p>フォルダやファイルには、閲覧・編集が可能なメンバーや組織を設定することができます。機密資料の管理にご活用ください。</p>

                <h4>共有リンク（期限・パスワード・対象指定）</h4>
                <div class="help-step"><span class="help-step-num">1</span><span class="help-step-text">ファイル詳細画面の「共有リンク」から発行します。</span></div>
                <div class="help-step"><span class="help-step-num">2</span><span class="help-step-text">必要に応じて有効期限、ダウンロード回数上限、共有パスワードを設定します。</span></div>
                <div class="help-step"><span class="help-step-num">3</span><span class="help-step-text">共有先のユーザー/組織を選択すると、通知（メールキュー連携）でリンク配信できます。</span></div>
                <div class="help-step"><span class="help-step-num">4</span><span class="help-step-text">発行済みリンクは一覧から即時に無効化できます。</span></div>
                <div class="help-info"><strong><i class="fas fa-info-circle me-1"></i>補足：</strong>共有先を指定したリンクは、対象ユーザーのログインが必要です。対象未指定の場合は、リンク（必要ならパスワード）でアクセスできます。</div>

                <h4>容量上限</h4>
                <p>管理者は「システム設定 → 認証・PWA・SCIM → ファイル共有設定」で、1ファイル上限、全体容量、ユーザー容量、組織容量を設定できます。</p>
            </div>
        </div>
    </div>

    <!-- ===== 10. WEBデータベース ===== -->
    <div class="help-section" id="sec-webdb">
        <div class="help-card">
            <div class="help-card-head" onclick="toggleSection(this)">
                <span class="sec-icon"><i class="fas fa-database"></i></span>
                <h2>WEBデータベース</h2>
                <i class="fas fa-chevron-down toggle-icon"></i>
            </div>
            <div class="help-card-body">
                <p>WEBデータベースは、ノーコードで業務アプリを構築できる機能です。台帳管理だけでなく、フォーム設計、親子明細、集計ビュー、グラフ表示まで一つの画面群で運用できます。</p>

                <h4>1. アプリ（データベース）の作成</h4>
                <div class="help-step"><span class="help-step-num">1</span><span class="help-step-text">「WEBデータベース」→「<i class="fas fa-plus"></i> 新規データベース作成」をクリックします。</span></div>
                <div class="help-step"><span class="help-step-num">2</span><span class="help-step-text">名前・説明・表示アイコンなどを設定して保存します。</span></div>
                <div class="help-step"><span class="help-step-num">3</span><span class="help-step-text">「フィールド設定」へ進み、入力項目を追加します。</span></div>

                <h4>2. フォームビルダー（GUI設定）</h4>
                <p>フィールド設定画面では、ドラッグで並び替え、セクション分割、表示/非表示、必須、子テーブル指定を GUI で設定できます。</p>
                <ul>
                    <li>対応フィールド例：テキスト、数値、日付、選択、ユーザー、組織、ファイル</li>
                    <li>高度フィールド：リレーション、ルックアップ、計算、通貨、パーセント、自動採番</li>
                    <li>各項目に説明文・初期値・フィルタ可否を設定可能</li>
                </ul>

                <h4>3. リレーションと親子明細</h4>
                <p>他テーブル参照（リレーション）を設定すると、参照先名称を自然に表示できます。さらに「子テーブル入力」を有効化すると、ヘッダ + 明細の入力が可能です（例：売上ヘッダ + 売上明細）。</p>

                <h4>4. 一覧ビュー / 集計ビュー / グラフビュー</h4>
                <p>レコード一覧では、用途別に保存ビューを作成できます。表示カラム、フィルタ、並び順、共有範囲（ユーザー/組織/全体）を設定でき、集計・グラフ表示にも切り替えられます。</p>
                <ul>
                    <li>集計：件数 / 合計 / 平均</li>
                    <li>グループ化：任意項目 + 日付単位（日/月）</li>
                    <li>グラフ：棒 / 折れ線 / 円</li>
                </ul>

                <h4>5. CSV入出力とサンプル</h4>
                <p>CSV インポート/エクスポートで既存データを活用できます。管理者は「デモ業務サンプル投入」から、売上・売上明細を含むサンプルアプリを投入してすぐに検証できます。</p>
            </div>
        </div>
    </div>

    <!-- ===== 11. アドレス帳 ===== -->
    <div class="help-section" id="sec-address">
        <div class="help-card">
            <div class="help-card-head" onclick="toggleSection(this)">
                <span class="sec-icon"><i class="fas fa-address-book"></i></span>
                <h2>アドレス帳</h2>
                <i class="fas fa-chevron-down toggle-icon"></i>
            </div>
            <div class="help-card-body">
                <p>取引先やお客様の連絡先情報を登録・管理するための機能です。社内で連絡先を共有することで、業務の効率化を図ることができます。</p>

                <h4>連絡先の登録</h4>
                <div class="help-step"><span class="help-step-num">1</span><span class="help-step-text">メニューの「アドレス帳」を開きます。</span></div>
                <div class="help-step"><span class="help-step-num">2</span><span class="help-step-text">「<i class="fas fa-plus"></i> 新規登録」ボタンをクリックします。</span></div>
                <div class="help-step"><span class="help-step-num">3</span><span class="help-step-text">氏名、会社名、電話番号、メールアドレスなどの情報を入力します。</span></div>
                <div class="help-step"><span class="help-step-num">4</span><span class="help-step-text">カテゴリ（「取引先」「個人」など）を選択します。</span></div>
                <div class="help-step"><span class="help-step-num">5</span><span class="help-step-text">「保存」ボタンをクリックして登録を完了します。</span></div>

                <h4>グループ管理</h4>
                <p>連絡先をグループに分類して管理することができます。プロジェクト単位や業種ごとにグループを作成すると、目的の連絡先を素早く見つけることができます。</p>

                <h4>検索機能</h4>
                <p>氏名や会社名をキーワードに検索できます。カテゴリやグループでの絞り込みも可能です。</p>

                <h4>CSVインポート</h4>
                <p>Excel等で管理されている連絡先データを、CSVファイルとして一括で取り込むことができます。大量の連絡先を登録する際にご利用ください。</p>
            </div>
        </div>
    </div>

    <!-- ===== 12. 施設予約 ===== -->
    <div class="help-section" id="sec-facility">
        <div class="help-card">
            <div class="help-card-head" onclick="toggleSection(this)">
                <span class="sec-icon"><i class="fas fa-building"></i></span>
                <h2>施設予約</h2>
                <i class="fas fa-chevron-down toggle-icon"></i>
            </div>
            <div class="help-card-body">
                <p>会議室やプロジェクターなど、共用の施設・設備の予約を管理する機能です。予約の重複（ダブルブッキング）を防止し、施設を効率的にご利用いただけます。</p>

                <h4>施設の予約方法</h4>
                <div class="help-step"><span class="help-step-num">1</span><span class="help-step-text">メニューの「施設予約」を開きます。</span></div>
                <div class="help-step"><span class="help-step-num">2</span><span class="help-step-text">予約したい施設を選択します。</span></div>
                <div class="help-step"><span class="help-step-num">3</span><span class="help-step-text">カレンダー上で空き状況を確認します。</span></div>
                <div class="help-step"><span class="help-step-num">4</span><span class="help-step-text">利用日時を設定し、「予約する」ボタンをクリックします。</span></div>
                <div class="help-tip"><strong><i class="fas fa-lightbulb me-1"></i>ヒント：</strong>すでに予約が入っている時間帯は選択できないようになっております。空いている時間帯をお選びください。</div>

                <h4>スケジュールからの施設予約</h4>
                <p>スケジュールの新規作成画面からも、施設の予約を同時に行うことができます。会議の予定と会議室の予約をまとめて登録できるため、大変便利です。</p>

                <h4>空き状況の確認</h4>
                <p>施設一覧画面では、各施設の当日および今後の予約状況をカレンダー形式で確認することができます。</p>

                <h4>予約のキャンセル</h4>
                <p>ご自身で予約された施設は、「削除」ボタンからキャンセルすることができます。施設をご利用にならなくなった場合は、他の方がご利用できるよう、お早めにキャンセルしてください。</p>
            </div>
        </div>
    </div>

    <!-- ===== 13. 通知 ===== -->
    <div class="help-section" id="sec-notify">
        <div class="help-card">
            <div class="help-card-head" onclick="toggleSection(this)">
                <span class="sec-icon"><i class="fas fa-bell"></i></span>
                <h2>通知</h2>
                <i class="fas fa-chevron-down toggle-icon"></i>
            </div>
            <div class="help-card-body">
                <p>スケジュールの変更、新しいメッセージの受信、ワークフローの承認依頼など、重要な情報をお知らせする通知機能です。</p>

                <h4>通知の確認方法</h4>
                <p>画面右上の <i class="fas fa-bell"></i> ベルアイコンをクリックすると、最新の通知が一覧で表示されます。アイコン上に数字が表示されている場合は、未読の通知がある状態です。</p>

                <h4>通知の種類</h4>
                <ul>
                    <li><strong>メッセージ通知</strong> ― 新しいメッセージを受信した場合</li>
                    <li><strong>スケジュール通知</strong> ― 予定が追加・変更された場合</li>
                    <li><strong>ワークフロー通知</strong> ― 承認依頼、承認完了、却下などの場合</li>
                    <li><strong>タスク通知</strong> ― タスクの割り当て、期限の接近など</li>
                    <li><strong>掲示板通知</strong> ― 新しい記事やコメントの投稿</li>
                    <li><strong>日報通知</strong> ― コメントやいいねの受信</li>
                </ul>

                <h4>すべて既読にする</h4>
                <p>通知一覧画面の「すべて既読にする」ボタンをクリックすると、未読の通知をすべて既読状態にすることができます。</p>

                <h4>通知設定のカスタマイズ</h4>
                <p>「通知設定」画面から、受信する通知の種類をお選びいただけます。不要な通知をオフにすることで、重要な通知のみを受け取るよう設定できます。メール通知の有効・無効も設定可能です。</p>
            </div>
        </div>
    </div>

    <!-- ===== 14. 検索 ===== -->
    <div class="help-section" id="sec-search">
        <div class="help-card">
            <div class="help-card-head" onclick="toggleSection(this)">
                <span class="sec-icon"><i class="fas fa-search"></i></span>
                <h2>検索</h2>
                <i class="fas fa-chevron-down toggle-icon"></i>
            </div>
            <div class="help-card-body">
                <p><?php echo htmlspecialchars($appName); ?> 内のさまざまな情報を、キーワードで横断的に検索できる機能です。</p>

                <h4>検索の手順</h4>
                <div class="help-step"><span class="help-step-num">1</span><span class="help-step-text">画面上部の検索バーにキーワードを入力します。</span></div>
                <div class="help-step"><span class="help-step-num">2</span><span class="help-step-text">Enter キーを押すか、<i class="fas fa-search"></i> アイコンをクリックします。</span></div>
                <div class="help-step"><span class="help-step-num">3</span><span class="help-step-text">検索結果がカテゴリごとに分類されて表示されます。</span></div>

                <h4>検索対象</h4>
                <p>以下のすべてのモジュールを対象に、一括で検索を行います。</p>
                <ul>
                    <li>スケジュール</li>
                    <li>メッセージ</li>
                    <li>掲示板の記事</li>
                    <li>タスク</li>
                    <li>日報</li>
                    <li>アドレス帳の連絡先</li>
                    <li>ファイル管理のファイル</li>
                    <li>WEBデータベースのレコード</li>
                </ul>
                <div class="help-tip"><strong><i class="fas fa-lightbulb me-1"></i>ヒント：</strong>検索結果が多すぎる場合は、キーワードをより具体的にしてください。逆に見つからない場合は、キーワードを短くしたり、別の表現をお試しください。例えば「3月営業会議」で見つからない場合は「営業会議」のみでお試しください。</div>
            </div>
        </div>
    </div>

    <!-- ===== 15. カレンダー連携 ===== -->
    <div class="help-section" id="sec-calendar-sync">
        <div class="help-card">
            <div class="help-card-head" onclick="toggleSection(this)">
                <span class="sec-icon"><i class="fas fa-sync"></i></span>
                <h2>カレンダー連携</h2>
                <i class="fas fa-chevron-down toggle-icon"></i>
            </div>
            <div class="help-card-body">
                <p><?php echo htmlspecialchars($appName); ?> のスケジュールを、Google カレンダーや iPhone のカレンダーなど、外部のカレンダーアプリケーションと連携させることができます。</p>

                <h4>外部カレンダーに <?php echo htmlspecialchars($appName); ?> の予定を表示する</h4>
                <div class="help-step"><span class="help-step-num">1</span><span class="help-step-text">メニューの「カレンダー連携」を開きます。</span></div>
                <div class="help-step"><span class="help-step-num">2</span><span class="help-step-text">表示される「ICSフィードURL」をコピーします。</span></div>
                <div class="help-step"><span class="help-step-num">3</span><span class="help-step-text">Google カレンダー等の外部カレンダーアプリで「URLでカレンダーを追加」機能を使い、コピーしたURLを貼り付けます。</span></div>
                <p>この設定を行うことで、<?php echo htmlspecialchars($appName); ?> に登録された予定がスマートフォンのカレンダーアプリにも自動的に表示されるようになります。</p>

                <h4>外部カレンダーの予定を <?php echo htmlspecialchars($appName); ?> に取り込む</h4>
                <p>「カレンダー購読」機能で、外部カレンダーのURL（ICS形式）を登録すると、そのカレンダーの予定も <?php echo htmlspecialchars($appName); ?> の画面上に表示されます。</p>

                <h4>ICSファイルのインポート</h4>
                <p>.ics 形式のファイルをアップロードして、複数の予定を一括で取り込むことも可能です。他のシステムからの予定移行などにご活用ください。</p>
            </div>
        </div>
    </div>

    <!-- ===== 16. プロフィール設定 ===== -->
    <div class="help-section" id="sec-profile">
        <div class="help-card">
            <div class="help-card-head" onclick="toggleSection(this)">
                <span class="sec-icon"><i class="fas fa-user-cog"></i></span>
                <h2>プロフィール設定</h2>
                <i class="fas fa-chevron-down toggle-icon"></i>
            </div>
            <div class="help-card-body">
                <p>ご自身の表示名やパスワード、プロフィール画像などを変更するための機能です。</p>

                <h4>プロフィール画面の開き方</h4>
                <p>画面右上のご自身のお名前をクリックし、表示されるメニューから「プロフィール」を選択してください。</p>

                <h4>表示名の変更</h4>
                <p>プロフィール画面で表示名を変更することができます。表示名は他のメンバーに表示されるお名前です。わかりやすいお名前をご設定ください。</p>

                <h4>メールアドレスの変更</h4>
                <p>通知メールの送信先となるメールアドレスを変更できます。常にご確認可能なメールアドレスをご登録ください。</p>

                <h4>パスワードの変更</h4>
                <div class="help-step"><span class="help-step-num">1</span><span class="help-step-text">プロフィール画面の「パスワード変更」をクリックします。</span></div>
                <div class="help-step"><span class="help-step-num">2</span><span class="help-step-text">現在のパスワードを入力します。</span></div>
                <div class="help-step"><span class="help-step-num">3</span><span class="help-step-text">新しいパスワードを入力します。</span></div>
                <div class="help-step"><span class="help-step-num">4</span><span class="help-step-text">確認のため、新しいパスワードをもう一度入力します。</span></div>
                <div class="help-step"><span class="help-step-num">5</span><span class="help-step-text">「変更する」ボタンをクリックします。</span></div>
                <div class="help-warn"><strong><i class="fas fa-exclamation-triangle me-1"></i>ご注意：</strong>パスワードは他人に知られないよう厳重に管理してください。推測されやすいパスワード（「1234」「password」「自分の名前」など）は避け、英数字を組み合わせた安全なパスワードをご設定ください。</div>

                <h4>プロフィール画像（アバター）の設定</h4>
                <p>プロフィール画面からアバター画像をアップロードすることができます。お好みの画像を設定すると、メッセージやコメントの際にアイコンとして表示されます。</p>
            </div>
        </div>
    </div>

    <!-- ===== 17. 管理者メニュー ===== -->
    <div class="help-section" id="sec-admin">
        <div class="help-card">
            <div class="help-card-head" onclick="toggleSection(this)">
                <span class="sec-icon"><i class="fas fa-cogs"></i></span>
                <h2>管理者メニュー</h2>
                <i class="fas fa-chevron-down toggle-icon"></i>
            </div>
            <div class="help-card-body">
                <div class="help-info"><strong><i class="fas fa-info-circle me-1"></i>ご案内：</strong>この項目は<strong>管理者権限をお持ちの方</strong>のみが利用できる機能です。一般のユーザーの方には表示されません。</div>

                <h4>ユーザー管理</h4>
                <p>メンバーのアカウントの作成・編集・削除を行います。新しいメンバーが入社された際や、退社された際にご利用ください。</p>
                <ul>
                    <li>アカウントの新規作成（氏名、ログインID、パスワード、メールアドレス）</li>
                    <li>所属組織の設定</li>
                    <li>管理者権限の付与・取消</li>
                    <li>パスワードのリセット</li>
                    <li>アカウントの無効化・有効化</li>
                </ul>

                <h4>組織管理</h4>
                <p>会社の部署やチームの階層構造を設定する機能です。親子関係（例：「本社」の配下に「営業部」「総務部」）を設定することができます。</p>
                <ul>
                    <li>組織の追加・編集・削除</li>
                    <li>親子関係（階層構造）の設定</li>
                    <li>組織コードの設定</li>
                    <li>メンバーの所属設定</li>
                    <li>組織の表示順の変更</li>
                </ul>

                <h4>CSVインポート</h4>
                <p>大量のデータを一括登録する際にご利用いただけます。Excelで作成したリストをCSVファイルに変換し、取り込みます。</p>
                <ul>
                    <li><strong>ユーザーCSV</strong> ― ユーザーアカウントの一括登録</li>
                    <li><strong>組織CSV</strong> ― 組織の一括登録</li>
                    <li><strong>アドレス帳CSV</strong> ― 連絡先の一括登録</li>
                </ul>
                <div class="help-tip"><strong><i class="fas fa-lightbulb me-1"></i>ヒント：</strong>「サンプルCSVダウンロード」ボタンから、正しいフォーマットのサンプルファイルをダウンロードすることができます。こちらを参考にデータをご準備ください。</div>

                <h4>ワークフローテンプレート管理</h4>
                <p>ワークフローで使用する申請書のテンプレート（ひな型）を作成・管理します。</p>
                <ul>
                    <li>テンプレートの作成・編集・削除</li>
                    <li>フォーム項目の設計（テキスト、数値、日付、選択肢、計算フィールドなど）</li>
                    <li>承認ルートの設計（承認の段階数、各段階の承認者の設定）</li>
                </ul>

                <h4>施設管理</h4>
                <p>施設予約で利用する会議室やプロジェクターなどの施設情報を追加・編集・削除します。</p>

                <h4>システム設定</h4>
                <p>システム全体に関わる各種設定を行います。</p>
                <ul>
                    <li><strong>基本設定</strong> ― サイト名（アプリケーション名）、会社名の設定</li>
                    <li><strong>SMTP設定</strong> ― メール送信サーバーの設定（通知メール送信に必要です）</li>
                    <li><strong>通知設定</strong> ― 通知の初期設定</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- ===== 18. スマートフォンでのご利用 ===== -->
    <div class="help-section" id="sec-mobile">
        <div class="help-card">
            <div class="help-card-head" onclick="toggleSection(this)">
                <span class="sec-icon"><i class="fas fa-mobile-alt"></i></span>
                <h2>スマートフォンでのご利用</h2>
                <i class="fas fa-chevron-down toggle-icon"></i>
            </div>
            <div class="help-card-body">
                <p><?php echo htmlspecialchars($appName); ?> はレスポンシブデザインに対応し、PWA（ホーム画面追加）にも対応しています。スマートフォンやタブレットでも、アプリのように快適にご利用いただけます。</p>

                <h4>画面の操作方法</h4>
                <ul>
                    <li>メニューは画面左上の <i class="fas fa-bars"></i>（メニューアイコン）をタップすると開きます。</li>
                    <li>画面下部にもナビゲーションバーが表示されますので、よく使う機能にすぐにアクセスできます。</li>
                    <li>表（テーブル）は横方向にスワイプ（指でなぞる）して全体をご覧いただけます。</li>
                    <li>スケジュールの週表示も横スクロールに対応しております。</li>
                </ul>

                <h4>快適にご利用いただくためのコツ</h4>
                <ul>
                    <li>画面が小さい場合は、ピンチ操作（2本の指で広げる・縮める）で拡大・縮小を行ってください。</li>
                    <li>端末を横向き（横画面）にすると、表やカレンダーがより見やすくなります。</li>
                    <li>安定したWi-Fi環境でのご利用をお勧めいたします。モバイル回線では表示に時間がかかる場合がございます。</li>
                </ul>

                <h4>PWAインストール手順（iPhone / iPad）</h4>
                <ol>
                    <li>Safari で <?php echo htmlspecialchars($appName); ?> を開きます。</li>
                    <li>下部（または上部）の共有ボタンをタップします。</li>
                    <li>「ホーム画面に追加」を選択します。</li>
                    <li>名前を確認して「追加」を押します。</li>
                </ol>

                <h4>PWAインストール手順（Android / Chrome）</h4>
                <ol>
                    <li>Chrome で <?php echo htmlspecialchars($appName); ?> を開きます。</li>
                    <li>アドレスバーの「インストール」アイコン、またはメニューを開きます。</li>
                    <li>「ホーム画面に追加」または「アプリをインストール」を選択します。</li>
                    <li>確認ダイアログでインストールします。</li>
                </ol>

                <h4>PWAインストール手順（PCブラウザ）</h4>
                <ol>
                    <li>Chrome または Edge でログイン画面またはトップ画面を開きます。</li>
                    <li>アドレスバー右側のインストールアイコンをクリックします。</li>
                    <li>「インストール」を選択します。</li>
                </ol>

                <h4>通知を有効にする</h4>
                <ol>
                    <li>「設定 &gt; 認証・PWA・SCIM」で PWA Push通知が有効になっていることを確認します。</li>
                    <li>同画面の「このブラウザで購読」を押します。</li>
                    <li>ブラウザの通知許可ダイアログで「許可」を選択します。</li>
                </ol>

                <h4>通知が来ない場合の確認</h4>
                <ul>
                    <li>ブラウザ設定で通知がブロックされていないか</li>
                    <li>OS側（iOS/Android/Windows/Mac）の通知が無効になっていないか</li>
                    <li>「このブラウザで購読」を再実行し、必要なら「購読解除」後に再購読する</li>
                    <li>管理者に「テストPush送信」を依頼して疎通確認する</li>
                </ul>

                <div class="help-tip"><strong><i class="fas fa-lightbulb me-1"></i>ヒント：</strong>iOSではブラウザ通知が制限される場合があります。ホーム画面追加後に通知許可を行うと安定します。</div>
            </div>
        </div>
    </div>

    <!-- ===== 19. よくあるご質問（FAQ） ===== -->
    <div class="help-section" id="sec-faq">
        <div class="help-card">
            <div class="help-card-head" onclick="toggleSection(this)">
                <span class="sec-icon"><i class="fas fa-question-circle"></i></span>
                <h2>よくあるご質問（FAQ）</h2>
                <i class="fas fa-chevron-down toggle-icon"></i>
            </div>
            <div class="help-card-body">

                <h4>Q. ログインできません</h4>
                <ul>
                    <li>ログインIDとパスワードに誤りがないかご確認ください。大文字・小文字は区別されます。</li>
                    <li>キーボードの Caps Lock キーがオンになっていないかご確認ください。</li>
                    <li>上記を確認しても解決しない場合は、システム管理者にお問い合わせください。</li>
                </ul>

                <h4>Q. 画面が正しく表示されません（白い画面になる、レイアウトが崩れるなど）</h4>
                <ul>
                    <li>ブラウザの再読み込み（<span class="help-shortcut">F5</span> キー、または <span class="help-shortcut">Ctrl</span>+<span class="help-shortcut">R</span>）をお試しください。</li>
                    <li>ブラウザのキャッシュをクリアしてください（<span class="help-shortcut">Ctrl</span>+<span class="help-shortcut">Shift</span>+<span class="help-shortcut">Delete</span>）。</li>
                    <li>別のブラウザ（Google Chrome、Mozilla Firefox、Microsoft Edge など）でもお試しください。</li>
                </ul>

                <h4>Q. ファイルがアップロードできません</h4>
                <ul>
                    <li>ファイルサイズが上限を超えていないかご確認ください。</li>
                    <li>ファイル名に特殊な記号（半角カッコ、特殊文字など）が含まれていないかご確認ください。</li>
                    <li>ネットワーク接続が安定しているかご確認ください。</li>
                </ul>

                <h4>Q. 通知が届きません</h4>
                <ul>
                    <li>「通知設定」画面で、受信したい通知の種類が有効になっているかご確認ください。</li>
                    <li>メール通知の場合は、迷惑メールフォルダもあわせてご確認ください。</li>
                    <li>ブラウザの通知がブロックされていないかご確認ください。</li>
                </ul>

                <h4>Q. 予定がスケジュールに表示されません</h4>
                <ul>
                    <li>表示している日付・期間が正しいかご確認ください。意図せず別の週や月を表示している場合がございます。</li>
                    <li>組織モードの場合、正しい組織が選択されているかご確認ください。</li>
                    <li>予定の公開範囲が限定されている場合、対象外の方には表示されません。</li>
                </ul>

                <h4>Q. パスワードを変更したいのですが</h4>
                <p>画面右上のお名前をクリックし、「プロフィール」画面からパスワードの変更が可能です。詳しくは「<a href="#sec-profile" style="color:#4e73df;">プロフィール設定</a>」の項目をご覧ください。</p>

                <h4>Q. 他のメンバーの予定を編集したいのですが</h4>
                <p>セキュリティ上の理由から、他のメンバーが作成された予定を編集することはできません。変更が必要な場合は、作成者ご本人にご依頼ください。</p>

                <h4>Q. 削除したデータを復元できますか</h4>
                <p>一度削除されたデータは、原則として復元できません。削除の際は十分にご確認のうえ実行してください。ファイル管理については、バージョン管理機能により過去の版に戻すことが可能です。</p>

                <h4>上記で解決しない場合</h4>
                <p>システム管理者に以下の情報をお伝えください。迅速な解決に繋がります。</p>
                <ul>
                    <li><strong>どの画面で</strong>問題が発生したか</li>
                    <li><strong>どのような操作を行ったか</strong></li>
                    <li><strong>どのような症状が出ているか</strong>（エラーメッセージがあればその内容も）</li>
                    <li>可能であれば<strong>スクリーンショット</strong>（画面の画像）</li>
                </ul>
                <div class="help-tip"><strong><i class="fas fa-camera me-1"></i>スクリーンショットの撮り方：</strong><br>
                    Windows：<span class="help-shortcut">Win</span>+<span class="help-shortcut">Shift</span>+<span class="help-shortcut">S</span><br>
                    Mac：<span class="help-shortcut">Cmd</span>+<span class="help-shortcut">Shift</span>+<span class="help-shortcut">4</span><br>
                    スマートフォン：電源ボタン + 音量ダウンボタンの同時押し（機種により異なります）
                </div>
            </div>
        </div>
    </div>

    <!-- ===== 20. キーボードショートカット ===== -->
    <div class="help-section" id="sec-shortcut">
        <div class="help-card">
            <div class="help-card-head" onclick="toggleSection(this)">
                <span class="sec-icon"><i class="fas fa-keyboard"></i></span>
                <h2>キーボードショートカット</h2>
                <i class="fas fa-chevron-down toggle-icon"></i>
            </div>
            <div class="help-card-body">
                <p>ブラウザで利用できるキーボードショートカットです。覚えておくと操作がより素早く行えます。</p>

                <h4>ブラウザの基本操作</h4>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered" style="font-size:0.88rem;">
                        <thead class="table-light">
                            <tr><th style="width:40%;">操作内容</th><th>Windows</th><th>Mac</th></tr>
                        </thead>
                        <tbody>
                            <tr><td>ページを再読み込みする</td><td><span class="help-shortcut">F5</span> / <span class="help-shortcut">Ctrl</span>+<span class="help-shortcut">R</span></td><td><span class="help-shortcut">Cmd</span>+<span class="help-shortcut">R</span></td></tr>
                            <tr><td>キャッシュをクリアして再読み込み</td><td><span class="help-shortcut">Ctrl</span>+<span class="help-shortcut">Shift</span>+<span class="help-shortcut">R</span></td><td><span class="help-shortcut">Cmd</span>+<span class="help-shortcut">Shift</span>+<span class="help-shortcut">R</span></td></tr>
                            <tr><td>前のページに戻る</td><td><span class="help-shortcut">Alt</span>+<span class="help-shortcut">←</span></td><td><span class="help-shortcut">Cmd</span>+<span class="help-shortcut">←</span></td></tr>
                            <tr><td>次のページに進む</td><td><span class="help-shortcut">Alt</span>+<span class="help-shortcut">→</span></td><td><span class="help-shortcut">Cmd</span>+<span class="help-shortcut">→</span></td></tr>
                            <tr><td>ページ内検索</td><td><span class="help-shortcut">Ctrl</span>+<span class="help-shortcut">F</span></td><td><span class="help-shortcut">Cmd</span>+<span class="help-shortcut">F</span></td></tr>
                            <tr><td>印刷する</td><td><span class="help-shortcut">Ctrl</span>+<span class="help-shortcut">P</span></td><td><span class="help-shortcut">Cmd</span>+<span class="help-shortcut">P</span></td></tr>
                            <tr><td>画面を拡大する</td><td><span class="help-shortcut">Ctrl</span>+<span class="help-shortcut">+</span></td><td><span class="help-shortcut">Cmd</span>+<span class="help-shortcut">+</span></td></tr>
                            <tr><td>画面を縮小する</td><td><span class="help-shortcut">Ctrl</span>+<span class="help-shortcut">-</span></td><td><span class="help-shortcut">Cmd</span>+<span class="help-shortcut">-</span></td></tr>
                            <tr><td>画面の大きさを元に戻す</td><td><span class="help-shortcut">Ctrl</span>+<span class="help-shortcut">0</span></td><td><span class="help-shortcut">Cmd</span>+<span class="help-shortcut">0</span></td></tr>
                            <tr><td>新しいタブを開く</td><td><span class="help-shortcut">Ctrl</span>+<span class="help-shortcut">T</span></td><td><span class="help-shortcut">Cmd</span>+<span class="help-shortcut">T</span></td></tr>
                            <tr><td>現在のタブを閉じる</td><td><span class="help-shortcut">Ctrl</span>+<span class="help-shortcut">W</span></td><td><span class="help-shortcut">Cmd</span>+<span class="help-shortcut">W</span></td></tr>
                        </tbody>
                    </table>
                </div>

                <h4>テキスト入力時の操作</h4>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered" style="font-size:0.88rem;">
                        <thead class="table-light">
                            <tr><th style="width:40%;">操作内容</th><th>Windows</th><th>Mac</th></tr>
                        </thead>
                        <tbody>
                            <tr><td>すべて選択する</td><td><span class="help-shortcut">Ctrl</span>+<span class="help-shortcut">A</span></td><td><span class="help-shortcut">Cmd</span>+<span class="help-shortcut">A</span></td></tr>
                            <tr><td>コピーする</td><td><span class="help-shortcut">Ctrl</span>+<span class="help-shortcut">C</span></td><td><span class="help-shortcut">Cmd</span>+<span class="help-shortcut">C</span></td></tr>
                            <tr><td>切り取りする</td><td><span class="help-shortcut">Ctrl</span>+<span class="help-shortcut">X</span></td><td><span class="help-shortcut">Cmd</span>+<span class="help-shortcut">X</span></td></tr>
                            <tr><td>貼り付けする</td><td><span class="help-shortcut">Ctrl</span>+<span class="help-shortcut">V</span></td><td><span class="help-shortcut">Cmd</span>+<span class="help-shortcut">V</span></td></tr>
                            <tr><td>操作を元に戻す</td><td><span class="help-shortcut">Ctrl</span>+<span class="help-shortcut">Z</span></td><td><span class="help-shortcut">Cmd</span>+<span class="help-shortcut">Z</span></td></tr>
                            <tr><td>操作をやり直す</td><td><span class="help-shortcut">Ctrl</span>+<span class="help-shortcut">Y</span></td><td><span class="help-shortcut">Cmd</span>+<span class="help-shortcut">Shift</span>+<span class="help-shortcut">Z</span></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- フッター -->
    <div style="text-align:center; padding: 28px 0 16px; color: #999; font-size: 0.82rem;">
        <p>ご不明な点がございましたら、いつでもこのページをご参照ください。</p>
        <p><?php echo htmlspecialchars($appName); ?> ご利用ガイド &copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($companyName); ?></p>
    </div>

</div>

<script>
function toggleSection(header) {
    const body = header.nextElementSibling;
    if (body.style.display === 'none') {
        body.style.display = '';
        header.classList.remove('collapsed');
    } else {
        body.style.display = 'none';
        header.classList.add('collapsed');
    }
}

// 目次リンクでスムーズスクロール＆自動展開
document.querySelectorAll('.help-toc-grid a').forEach(link => {
    link.addEventListener('click', function(e) {
        e.preventDefault();
        const targetId = this.getAttribute('href').substring(1);
        const section = document.getElementById(targetId);
        if (section) {
            const body = section.querySelector('.help-card-body');
            const header = section.querySelector('.help-card-head');
            if (body && body.style.display === 'none') {
                body.style.display = '';
                header.classList.remove('collapsed');
            }
            section.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    });
});
</script>
