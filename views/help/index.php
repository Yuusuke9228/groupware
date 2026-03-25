<?php
// views/help/index.php
// TeamSpace ヘルプページ
?>

<style>
    .help-container {
        max-width: 960px;
        margin: 0 auto;
        padding: 20px;
    }
    .help-hero {
        background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
        color: #fff;
        border-radius: 12px;
        padding: 40px 32px;
        margin-bottom: 32px;
        text-align: center;
    }
    .help-hero h1 {
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 8px;
    }
    .help-hero p {
        font-size: 1.05rem;
        opacity: 0.9;
        margin-bottom: 0;
    }
    .help-toc {
        background: #f8f9fc;
        border: 1px solid #e3e6f0;
        border-radius: 8px;
        padding: 24px 28px;
        margin-bottom: 32px;
    }
    .help-toc h5 {
        font-weight: 700;
        margin-bottom: 16px;
        color: #5a5c69;
        font-size: 0.95rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    .help-toc ol {
        padding-left: 20px;
        margin-bottom: 0;
        columns: 2;
        column-gap: 32px;
    }
    .help-toc ol li {
        margin-bottom: 6px;
        break-inside: avoid;
    }
    .help-toc ol li a {
        color: #4e73df;
        text-decoration: none;
        font-size: 0.93rem;
    }
    .help-toc ol li a:hover {
        text-decoration: underline;
    }
    .help-section {
        margin-bottom: 28px;
    }
    .help-section .card {
        border: 1px solid #e3e6f0;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04);
    }
    .help-section .card-header {
        background: #fff;
        border-bottom: 2px solid #4e73df;
        padding: 16px 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .help-section .card-header h2 {
        font-size: 1.15rem;
        font-weight: 700;
        margin: 0;
        color: #3a3b45;
    }
    .help-section .card-header .section-icon {
        font-size: 1.2rem;
        color: #4e73df;
        width: 28px;
        text-align: center;
    }
    .help-section .card-body {
        padding: 20px 24px;
        font-size: 0.93rem;
        line-height: 1.75;
        color: #5a5c69;
    }
    .help-section .card-body h4 {
        font-size: 1rem;
        font-weight: 700;
        color: #3a3b45;
        margin-top: 20px;
        margin-bottom: 10px;
        padding-bottom: 4px;
        border-bottom: 1px solid #eaecf4;
    }
    .help-section .card-body h4:first-child {
        margin-top: 0;
    }
    .help-section .card-body ul,
    .help-section .card-body ol {
        padding-left: 20px;
        margin-bottom: 12px;
    }
    .help-section .card-body li {
        margin-bottom: 4px;
    }
    .help-section .card-body .alert {
        font-size: 0.9rem;
    }
    .help-kbd {
        display: inline-block;
        background: #eaecf4;
        border: 1px solid #d1d3e2;
        border-radius: 4px;
        padding: 1px 7px;
        font-size: 0.82rem;
        font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace;
        color: #3a3b45;
        box-shadow: 0 1px 0 rgba(0,0,0,0.08);
    }
    .help-step {
        display: flex;
        gap: 14px;
        margin-bottom: 14px;
        align-items: flex-start;
    }
    .help-step-num {
        flex-shrink: 0;
        width: 28px;
        height: 28px;
        background: #4e73df;
        color: #fff;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.82rem;
        font-weight: 700;
    }
    .help-step-content {
        flex: 1;
        padding-top: 3px;
    }
    .faq-item {
        border-bottom: 1px solid #eaecf4;
        padding: 14px 0;
    }
    .faq-item:last-child {
        border-bottom: none;
        padding-bottom: 0;
    }
    .faq-q {
        font-weight: 700;
        color: #3a3b45;
        margin-bottom: 6px;
    }
    .faq-q::before {
        content: 'Q.';
        color: #4e73df;
        margin-right: 6px;
        font-weight: 800;
    }
    .faq-a {
        padding-left: 24px;
        color: #5a5c69;
    }
    .faq-a::before {
        content: 'A.';
        color: #e74a3b;
        margin-left: -24px;
        margin-right: 6px;
        font-weight: 800;
    }
    .help-back-top {
        text-align: right;
        margin-top: 8px;
    }
    .help-back-top a {
        font-size: 0.82rem;
        color: #b7b9cc;
        text-decoration: none;
    }
    .help-back-top a:hover {
        color: #4e73df;
    }
    .help-table th {
        background: #f8f9fc;
        font-weight: 600;
        font-size: 0.88rem;
        white-space: nowrap;
    }
    .help-table td {
        font-size: 0.9rem;
        vertical-align: top;
    }
    .code-block {
        background: #2d2d2d;
        color: #f8f8f2;
        border-radius: 6px;
        padding: 14px 18px;
        font-size: 0.82rem;
        font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace;
        overflow-x: auto;
        margin: 10px 0;
        line-height: 1.6;
    }
    @media (max-width: 768px) {
        .help-toc ol {
            columns: 1;
        }
        .help-container {
            padding: 12px;
        }
        .help-hero {
            padding: 28px 20px;
        }
        .help-hero h1 {
            font-size: 1.5rem;
        }
    }
</style>

<div class="help-container" id="help-top">

    <!-- ヒーロー -->
    <div class="help-hero">
        <h1><i class="fas fa-life-ring me-2"></i>TeamSpace ヘルプガイド</h1>
        <p>TeamSpaceの各機能の使い方をご案内します。目次から知りたい項目をお選びください。</p>
    </div>

    <!-- ========== 目次 ========== -->
    <div class="help-toc" id="toc">
        <h5><i class="fas fa-list me-2"></i>目次</h5>
        <ol>
            <li><a href="#sec-getting-started">はじめに</a></li>
            <li><a href="#sec-portal">トップページの使い方</a></li>
            <li><a href="#sec-schedule">スケジュール管理</a></li>
            <li><a href="#sec-messages">メッセージ</a></li>
            <li><a href="#sec-workflow">ワークフロー</a></li>
            <li><a href="#sec-tasks">タスク管理</a></li>
            <li><a href="#sec-daily-report">日報</a></li>
            <li><a href="#sec-webdatabase">WEBデータベース</a></li>
            <li><a href="#sec-addressbook">アドレス帳</a></li>
            <li><a href="#sec-facility">施設予約</a></li>
            <li><a href="#sec-calendar-sync">カレンダー連携</a></li>
            <li><a href="#sec-notifications">通知設定</a></li>
            <li><a href="#sec-mobile">モバイルでの利用</a></li>
            <li><a href="#sec-faq">よくある質問（FAQ）</a></li>
            <li><a href="#sec-contact">お問い合わせ</a></li>
        </ol>
    </div>

    <!-- ========== 1. はじめに ========== -->
    <div class="help-section" id="sec-getting-started">
        <div class="card">
            <div class="card-header">
                <span class="section-icon"><i class="fas fa-rocket"></i></span>
                <h2>1. はじめに</h2>
            </div>
            <div class="card-body">
                <p>TeamSpaceは、チームのコミュニケーションと業務効率化を支援する統合グループウェアです。スケジュール管理、メッセージ、ワークフロー申請、タスク管理、日報など、日々の業務に必要な機能をひとつのプラットフォームでご利用いただけます。</p>

                <h4>ログイン方法</h4>
                <ol>
                    <li>ブラウザでTeamSpaceのURLにアクセスします。</li>
                    <li>ログイン画面で、管理者から発行された<strong>ログインID</strong>と<strong>パスワード</strong>を入力します。</li>
                    <li>「ログイン」ボタンをクリックするとトップページに移動します。</li>
                </ol>
                <div class="alert alert-info mb-3">
                    <i class="fas fa-info-circle me-1"></i>
                    パスワードを忘れた場合は、システム管理者にお問い合わせください。
                </div>

                <h4>画面構成</h4>
                <ul>
                    <li><strong>ヘッダーナビゲーション</strong> &mdash; 上部に表示されるメニューバーから各機能に素早くアクセスできます。</li>
                    <li><strong>通知アイコン</strong> &mdash; ヘッダー右側のベルアイコンで未読通知を確認できます。</li>
                    <li><strong>ユーザーメニュー</strong> &mdash; 右上のユーザー名をクリックすると、個人設定・ログアウトなどが表示されます。</li>
                </ul>

                <h4>推奨環境</h4>
                <div class="table-responsive"><table class="table table-bordered help-table">
                    <thead>
                        <tr>
                            <th>項目</th>
                            <th>推奨環境</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td>ブラウザ</td><td>Google Chrome（最新版）、Microsoft Edge（最新版）、Safari（最新版）、Firefox（最新版）</td></tr>
                        <tr><td>モバイル</td><td>iOS Safari、Android Chrome</td></tr>
                        <tr><td>画面解像度</td><td>1280 x 720 以上推奨</td></tr>
                    </tbody>
                </table></div>
            </div>
        </div>
        <div class="help-back-top"><a href="#help-top"><i class="fas fa-arrow-up me-1"></i>ページ上部へ戻る</a></div>
    </div>

    <!-- ========== 2. トップページ ========== -->
    <div class="help-section" id="sec-portal">
        <div class="card">
            <div class="card-header">
                <span class="section-icon"><i class="fas fa-home"></i></span>
                <h2>2. トップページの使い方</h2>
            </div>
            <div class="card-body">
                <p>トップページ（ポータル）は、ログイン後に最初に表示されるダッシュボード画面です。今日の予定やタスク、メッセージなど、重要な情報を一覧で確認できます。</p>

                <h4>表示される情報</h4>
                <ul>
                    <li><strong>今日の日付</strong> &mdash; 画面上部に本日の日付と曜日が表示されます。</li>
                    <li><strong>今日の予定</strong> &mdash; 本日登録されているスケジュールが時系列で表示されます。クリックすると詳細画面に遷移します。</li>
                    <li><strong>タスク</strong> &mdash; 自分に割り当てられた未完了のタスクが一覧表示されます。</li>
                    <li><strong>メッセージ</strong> &mdash; 最新の受信メッセージが表示されます。未読メッセージには目印が付きます。</li>
                    <li><strong>通知</strong> &mdash; ワークフローの承認依頼や各種お知らせが表示されます。</li>
                </ul>

                <h4>クイック操作</h4>
                <ul>
                    <li>「予定登録」ボタンから新しいスケジュールを素早く作成できます。</li>
                    <li>「組織1日表示」ボタンから所属組織のメンバー全員の予定を確認できます。</li>
                    <li>各ウィジェットのリンクから詳細画面へ移動できます。</li>
                </ul>
            </div>
        </div>
        <div class="help-back-top"><a href="#help-top"><i class="fas fa-arrow-up me-1"></i>ページ上部へ戻る</a></div>
    </div>

    <!-- ========== 3. スケジュール管理 ========== -->
    <div class="help-section" id="sec-schedule">
        <div class="card">
            <div class="card-header">
                <span class="section-icon"><i class="fas fa-calendar-alt"></i></span>
                <h2>3. スケジュール管理</h2>
            </div>
            <div class="card-body">
                <p>スケジュール機能では、個人やチームの予定を効率的に管理できます。日・週・月の3つの表示形式と、個人表示・組織表示を切り替えて利用できます。</p>

                <h4>表示モードの切り替え</h4>
                <div class="table-responsive"><table class="table table-bordered help-table">
                    <thead>
                        <tr>
                            <th>表示モード</th>
                            <th>説明</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td><strong>日表示</strong></td><td>1日の予定を時間軸で詳細に確認できます。</td></tr>
                        <tr><td><strong>週表示</strong></td><td>1週間分の予定を横並びで俯瞰できます。</td></tr>
                        <tr><td><strong>月表示</strong></td><td>1ヶ月分の予定をカレンダー形式で確認できます。</td></tr>
                        <tr><td><strong>組織日表示</strong></td><td>組織メンバー全員の1日の予定を並べて確認できます。空き時間の把握に便利です。</td></tr>
                        <tr><td><strong>組織週表示</strong></td><td>組織メンバー全員の1週間の予定を一覧で確認できます。</td></tr>
                        <tr><td><strong>組織月表示</strong></td><td>組織メンバー全員の1ヶ月の予定をカレンダー形式で確認できます。</td></tr>
                    </tbody>
                </table></div>

                <h4>予定の登録</h4>
                <div class="help-step">
                    <div class="help-step-num">1</div>
                    <div class="help-step-content">「予定登録」ボタンをクリックするか、カレンダー上の日付をクリックします。</div>
                </div>
                <div class="help-step">
                    <div class="help-step-num">2</div>
                    <div class="help-step-content">タイトル、日時、場所などの必要情報を入力します。「終日」チェックボックスをオンにすると時間指定なしの終日予定として登録できます。</div>
                </div>
                <div class="help-step">
                    <div class="help-step-num">3</div>
                    <div class="help-step-content">参加者を追加する場合は、参加者欄でメンバーを検索・選択します。</div>
                </div>
                <div class="help-step">
                    <div class="help-step-num">4</div>
                    <div class="help-step-content">「保存」ボタンをクリックして予定を登録します。参加者にはメール・アプリ内通知が送信されます。</div>
                </div>

                <h4>予定の編集・削除</h4>
                <ul>
                    <li>予定をクリックすると詳細画面が開きます。「編集」ボタンから内容を変更できます。</li>
                    <li>自分が作成した予定、または編集権限のある予定のみ変更・削除が可能です。</li>
                    <li>繰り返し予定の場合、「この予定のみ」または「以降すべて」を選択して変更できます。</li>
                </ul>

                <h4>予定の優先度・カテゴリ</h4>
                <p>予定には優先度（通常・重要）やカテゴリを設定できます。カレンダー上では色分けされて表示されるため、一目で重要な予定を識別できます。</p>

                <div class="alert alert-warning">
                    <i class="fas fa-lightbulb me-1"></i>
                    <strong>ヒント：</strong>組織表示を活用すると、会議のスケジュール調整がスムーズに行えます。メンバーの空き時間を確認してから予定を作成しましょう。
                </div>
            </div>
        </div>
        <div class="help-back-top"><a href="#help-top"><i class="fas fa-arrow-up me-1"></i>ページ上部へ戻る</a></div>
    </div>

    <!-- ========== 4. メッセージ ========== -->
    <div class="help-section" id="sec-messages">
        <div class="card">
            <div class="card-header">
                <span class="section-icon"><i class="fas fa-envelope"></i></span>
                <h2>4. メッセージ</h2>
            </div>
            <div class="card-body">
                <p>メッセージ機能は、TeamSpace内のユーザー間でやりとりを行うための社内メッセージシステムです。</p>

                <h4>メッセージの閲覧</h4>
                <ul>
                    <li><strong>受信箱</strong> &mdash; 受信したメッセージの一覧です。未読メッセージは太字で表示されます。</li>
                    <li><strong>送信済み</strong> &mdash; 送信済みメッセージの一覧です。</li>
                    <li><strong>下書き</strong> &mdash; 作成途中で保存したメッセージが表示されます。</li>
                </ul>

                <h4>メッセージの送信</h4>
                <div class="help-step">
                    <div class="help-step-num">1</div>
                    <div class="help-step-content">「新規作成」ボタンをクリックします。</div>
                </div>
                <div class="help-step">
                    <div class="help-step-num">2</div>
                    <div class="help-step-content">宛先を選択します。ユーザー名で検索するか、組織から選択できます。複数の宛先を指定することも可能です。</div>
                </div>
                <div class="help-step">
                    <div class="help-step-num">3</div>
                    <div class="help-step-content">件名と本文を入力します。</div>
                </div>
                <div class="help-step">
                    <div class="help-step-num">4</div>
                    <div class="help-step-content">必要に応じてファイルを添付します。添付ファイルは複数追加可能です。</div>
                </div>
                <div class="help-step">
                    <div class="help-step-num">5</div>
                    <div class="help-step-content">「送信」ボタンをクリックします。すぐに送信しない場合は「下書き保存」を選択してください。</div>
                </div>

                <h4>ファイル添付</h4>
                <p>メッセージにはファイルを添付することができます。「ファイルを添付」ボタンまたはドラッグ&amp;ドロップで添付できます。</p>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-1"></i>
                    添付ファイルのサイズ上限はシステム設定に依存します。上限を超えるファイルはアップロードできません。
                </div>
            </div>
        </div>
        <div class="help-back-top"><a href="#help-top"><i class="fas fa-arrow-up me-1"></i>ページ上部へ戻る</a></div>
    </div>

    <!-- ========== 5. ワークフロー ========== -->
    <div class="help-section" id="sec-workflow">
        <div class="card">
            <div class="card-header">
                <span class="section-icon"><i class="fas fa-project-diagram"></i></span>
                <h2>5. ワークフロー</h2>
            </div>
            <div class="card-body">
                <p>ワークフロー機能は、稟議書や各種申請の承認プロセスを電子化します。申請の提出から承認・差し戻しまで、すべてTeamSpace上で完結できます。</p>

                <h4>申請の作成</h4>
                <div class="help-step">
                    <div class="help-step-num">1</div>
                    <div class="help-step-content">ワークフローメニューから「新規申請」をクリックします。</div>
                </div>
                <div class="help-step">
                    <div class="help-step-num">2</div>
                    <div class="help-step-content">申請テンプレートを選択します。テンプレートに応じた入力フォームが表示されます。</div>
                </div>
                <div class="help-step">
                    <div class="help-step-num">3</div>
                    <div class="help-step-content">必要事項を入力し、承認経路（承認者）を確認します。</div>
                </div>
                <div class="help-step">
                    <div class="help-step-num">4</div>
                    <div class="help-step-content">「申請」ボタンをクリックすると、最初の承認者に通知が送信されます。</div>
                </div>

                <h4>申請の承認・差し戻し</h4>
                <ul>
                    <li>承認依頼がある場合、ヘッダーの通知やトップページに表示されます。</li>
                    <li>内容を確認し、「承認」「差し戻し」「却下」のいずれかを選択します。</li>
                    <li>コメントを添えることができます。差し戻しの場合は理由を記載してください。</li>
                </ul>

                <h4>承認経路とテンプレート</h4>
                <p>管理者はワークフローテンプレートを作成し、申請項目や承認経路を定義できます。テンプレートには入力フィールド（テキスト、数値、日付、選択肢など）を自由に設定可能です。</p>

                <h4>代理承認</h4>
                <p>承認者が不在の場合に備えて、代理承認者を設定できます。代理設定は「ワークフロー」メニューの「代理設定」から行います。</p>
                <ul>
                    <li>代理期間（開始日〜終了日）を指定できます。</li>
                    <li>代理承認者は、指定期間中に届いた承認依頼を代わりに処理できます。</li>
                </ul>

                <h4>申請一覧</h4>
                <div class="table-responsive"><table class="table table-bordered help-table">
                    <thead>
                        <tr>
                            <th>タブ</th>
                            <th>内容</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td><strong>自分の申請</strong></td><td>自分が提出した申請の一覧と進捗状況を確認できます。</td></tr>
                        <tr><td><strong>承認待ち</strong></td><td>自分が承認すべき申請の一覧です。</td></tr>
                        <tr><td><strong>承認済み</strong></td><td>自分が承認処理済みの申請履歴です。</td></tr>
                    </tbody>
                </table></div>
            </div>
        </div>
        <div class="help-back-top"><a href="#help-top"><i class="fas fa-arrow-up me-1"></i>ページ上部へ戻る</a></div>
    </div>

    <!-- ========== 6. タスク管理 ========== -->
    <div class="help-section" id="sec-tasks">
        <div class="card">
            <div class="card-header">
                <span class="section-icon"><i class="fas fa-tasks"></i></span>
                <h2>6. タスク管理</h2>
            </div>
            <div class="card-body">
                <p>タスク管理機能では、カンバンボード形式で業務の進捗を視覚的に管理できます。個人タスクだけでなく、チームメンバーへのタスク割り当ても可能です。</p>

                <h4>カンバンボード</h4>
                <p>タスクは以下のステータスカラムに分類されて表示されます。</p>
                <ul>
                    <li><strong>未着手</strong> &mdash; まだ開始していないタスク</li>
                    <li><strong>進行中</strong> &mdash; 現在取り組んでいるタスク</li>
                    <li><strong>完了</strong> &mdash; 完了したタスク</li>
                </ul>
                <p>タスクカードをドラッグ&amp;ドロップで別のカラムに移動することで、ステータスを変更できます。</p>

                <h4>タスクの作成</h4>
                <div class="help-step">
                    <div class="help-step-num">1</div>
                    <div class="help-step-content">「タスク追加」ボタンをクリックします。</div>
                </div>
                <div class="help-step">
                    <div class="help-step-num">2</div>
                    <div class="help-step-content">タイトル、説明、担当者、期限を入力します。</div>
                </div>
                <div class="help-step">
                    <div class="help-step-num">3</div>
                    <div class="help-step-content">優先度（低・中・高）を設定します。</div>
                </div>
                <div class="help-step">
                    <div class="help-step-num">4</div>
                    <div class="help-step-content">「保存」をクリックすると、タスクがカンバンボードに追加されます。</div>
                </div>

                <h4>タスクの管理</h4>
                <ul>
                    <li>タスクカードをクリックすると詳細を確認・編集できます。</li>
                    <li>担当者を変更して別のメンバーにタスクを引き継ぐことができます。</li>
                    <li>期限が近いタスクや期限超過のタスクは色付きで強調表示されます。</li>
                </ul>

                <div class="alert alert-warning">
                    <i class="fas fa-lightbulb me-1"></i>
                    <strong>ヒント：</strong>期限超過のタスクはトップページにも警告表示されます。定期的にタスクボードを確認し、進捗を更新しましょう。
                </div>
            </div>
        </div>
        <div class="help-back-top"><a href="#help-top"><i class="fas fa-arrow-up me-1"></i>ページ上部へ戻る</a></div>
    </div>

    <!-- ========== 7. 日報 ========== -->
    <div class="help-section" id="sec-daily-report">
        <div class="card">
            <div class="card-header">
                <span class="section-icon"><i class="fas fa-file-alt"></i></span>
                <h2>7. 日報</h2>
            </div>
            <div class="card-body">
                <p>日報機能では、日々の業務報告を作成・提出できます。テンプレートを利用して統一されたフォーマットで報告を行えます。</p>

                <h4>日報の作成</h4>
                <div class="help-step">
                    <div class="help-step-num">1</div>
                    <div class="help-step-content">日報メニューから「日報作成」をクリックします。</div>
                </div>
                <div class="help-step">
                    <div class="help-step-num">2</div>
                    <div class="help-step-content">テンプレートを選択します。テンプレートに沿った入力フォームが表示されます。</div>
                </div>
                <div class="help-step">
                    <div class="help-step-num">3</div>
                    <div class="help-step-content">業務内容、成果、課題などを入力します。</div>
                </div>
                <div class="help-step">
                    <div class="help-step-num">4</div>
                    <div class="help-step-content">「提出」ボタンで上長に送信します。下書き保存も可能です。</div>
                </div>

                <h4>テンプレート管理</h4>
                <p>管理者またはテンプレート作成権限のあるユーザーは、日報テンプレートを作成・編集できます。テンプレートにはセクション（見出し）やテキスト入力欄を自由に配置できます。</p>

                <h4>日報の閲覧</h4>
                <ul>
                    <li>自分の過去の日報を一覧から確認できます。</li>
                    <li>上長は部下の日報を閲覧し、コメントを付けることができます。</li>
                    <li>日付やユーザーで絞り込み検索が可能です。</li>
                </ul>
            </div>
        </div>
        <div class="help-back-top"><a href="#help-top"><i class="fas fa-arrow-up me-1"></i>ページ上部へ戻る</a></div>
    </div>

    <!-- ========== 8. WEBデータベース ========== -->
    <div class="help-section" id="sec-webdatabase">
        <div class="card">
            <div class="card-header">
                <span class="section-icon"><i class="fas fa-database"></i></span>
                <h2>8. WEBデータベース</h2>
            </div>
            <div class="card-body">
                <p>WEBデータベースは、業務に合わせたカスタムデータベースを作成・管理できる機能です。顧客管理、在庫管理、案件管理など、用途に応じたデータベースを自由に構築できます。</p>

                <h4>データベースの作成</h4>
                <div class="help-step">
                    <div class="help-step-num">1</div>
                    <div class="help-step-content">WEBデータベースメニューから「新規データベース作成」をクリックします。</div>
                </div>
                <div class="help-step">
                    <div class="help-step-num">2</div>
                    <div class="help-step-content">データベース名と説明を入力します。</div>
                </div>
                <div class="help-step">
                    <div class="help-step-num">3</div>
                    <div class="help-step-content">フィールド（項目）を追加します。以下のフィールドタイプが利用可能です。</div>
                </div>

                <div class="table-responsive"><table class="table table-bordered help-table mt-2 mb-3">
                    <thead>
                        <tr>
                            <th>フィールドタイプ</th>
                            <th>説明</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td>テキスト（1行）</td><td>短いテキスト入力欄（名前、タイトルなど）</td></tr>
                        <tr><td>テキストエリア（複数行）</td><td>長文入力欄（備考、説明など）</td></tr>
                        <tr><td>数値</td><td>数値データの入力欄</td></tr>
                        <tr><td>日付</td><td>日付選択入力欄</td></tr>
                        <tr><td>選択肢（プルダウン）</td><td>あらかじめ定義した選択肢から選択</td></tr>
                        <tr><td>チェックボックス</td><td>はい/いいえの選択</td></tr>
                        <tr><td>ラジオボタン</td><td>複数の選択肢から1つを選択</td></tr>
                        <tr><td>ファイル添付</td><td>ファイルのアップロード</td></tr>
                        <tr><td>ユーザー選択</td><td>TeamSpaceユーザーの選択</td></tr>
                    </tbody>
                </table></div>

                <h4>レコードの管理</h4>
                <ul>
                    <li>データベースにレコード（データ行）を追加・編集・削除できます。</li>
                    <li>一覧画面ではフィルタやソートを使ってデータを絞り込めます。</li>
                    <li>レコードの詳細画面から各項目の閲覧・編集が可能です。</li>
                </ul>

                <h4>アクセス権限</h4>
                <p>データベースごとに閲覧・編集権限を設定できます。特定の組織やユーザーにのみアクセスを許可することが可能です。</p>
            </div>
        </div>
        <div class="help-back-top"><a href="#help-top"><i class="fas fa-arrow-up me-1"></i>ページ上部へ戻る</a></div>
    </div>

    <!-- ========== 9. アドレス帳 ========== -->
    <div class="help-section" id="sec-addressbook">
        <div class="card">
            <div class="card-header">
                <span class="section-icon"><i class="fas fa-address-book"></i></span>
                <h2>9. アドレス帳</h2>
            </div>
            <div class="card-body">
                <p>アドレス帳では、社外の取引先や顧客の連絡先情報を管理できます。</p>

                <h4>連絡先の管理</h4>
                <ul>
                    <li><strong>連絡先の追加</strong> &mdash; 「新規登録」ボタンから、氏名・会社名・部署・電話番号・メールアドレスなどを登録します。</li>
                    <li><strong>検索</strong> &mdash; 氏名、会社名、電話番号などで連絡先を検索できます。</li>
                    <li><strong>グループ管理</strong> &mdash; 連絡先をグループ（カテゴリ）に分類して管理できます。</li>
                    <li><strong>編集・削除</strong> &mdash; 連絡先の詳細画面から情報の更新や削除が可能です。</li>
                </ul>

                <h4>共有アドレス帳</h4>
                <p>管理者が設定した共有アドレス帳は、所属する組織のメンバー全員が閲覧可能です。共有の連絡先に対する編集権限は管理者が設定します。</p>
            </div>
        </div>
        <div class="help-back-top"><a href="#help-top"><i class="fas fa-arrow-up me-1"></i>ページ上部へ戻る</a></div>
    </div>

    <!-- ========== 10. 施設予約 ========== -->
    <div class="help-section" id="sec-facility">
        <div class="card">
            <div class="card-header">
                <span class="section-icon"><i class="fas fa-building"></i></span>
                <h2>10. 施設予約</h2>
            </div>
            <div class="card-body">
                <p>施設予約機能では、会議室やプロジェクター、社用車などの共有リソースの予約管理を行えます。</p>

                <h4>施設の予約方法</h4>
                <div class="help-step">
                    <div class="help-step-num">1</div>
                    <div class="help-step-content">施設予約メニューから予約したい施設カテゴリ（会議室、備品など）を選択します。</div>
                </div>
                <div class="help-step">
                    <div class="help-step-num">2</div>
                    <div class="help-step-content">施設の空き状況をカレンダーで確認します。</div>
                </div>
                <div class="help-step">
                    <div class="help-step-num">3</div>
                    <div class="help-step-content">利用日時と利用目的を入力し、「予約」をクリックします。</div>
                </div>

                <h4>予約の確認・変更・取消</h4>
                <ul>
                    <li>自分の予約一覧から予約状況を確認できます。</li>
                    <li>予約の変更・取消は、利用開始時刻前であれば可能です。</li>
                    <li>他のユーザーと予約時間が重複している場合はエラーが表示されます。</li>
                </ul>

                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-1"></i>
                    スケジュール登録画面から施設を同時に予約することもできます。予定と施設予約を連動させると管理が容易になります。
                </div>
            </div>
        </div>
        <div class="help-back-top"><a href="#help-top"><i class="fas fa-arrow-up me-1"></i>ページ上部へ戻る</a></div>
    </div>

    <!-- ========== 11. カレンダー連携 ========== -->
    <div class="help-section" id="sec-calendar-sync">
        <div class="card">
            <div class="card-header">
                <span class="section-icon"><i class="fas fa-sync-alt"></i></span>
                <h2>11. カレンダー連携</h2>
            </div>
            <div class="card-body">
                <p>TeamSpaceのスケジュールを外部カレンダーアプリケーション（iPhone標準カレンダー、Googleカレンダー、Microsoft Outlook）と同期できます。ICS形式の購読URLを利用します。</p>

                <h4>連携URLの取得</h4>
                <div class="help-step">
                    <div class="help-step-num">1</div>
                    <div class="help-step-content">TeamSpaceの設定画面（右上ユーザーメニュー → 個人設定）を開きます。</div>
                </div>
                <div class="help-step">
                    <div class="help-step-num">2</div>
                    <div class="help-step-content">「カレンダー連携」セクションで「ICS URLを生成」ボタンをクリックします。</div>
                </div>
                <div class="help-step">
                    <div class="help-step-num">3</div>
                    <div class="help-step-content">表示されたURLをコピーします。このURLは個人専用のため、他のユーザーには共有しないでください。</div>
                </div>

                <h4><i class="fab fa-apple me-1"></i>iPhone（iOS標準カレンダー）との連携</h4>
                <ol>
                    <li>iPhoneの「設定」アプリを開きます。</li>
                    <li>「カレンダー」→「アカウント」→「アカウントを追加」をタップします。</li>
                    <li>「その他」→「照会するカレンダーを追加」をタップします。</li>
                    <li>「サーバー」欄にTeamSpaceで取得したICS URLを貼り付けます。</li>
                    <li>「次へ」をタップし、設定を保存します。</li>
                    <li>標準カレンダーアプリにTeamSpaceの予定が表示されます。</li>
                </ol>
                <div class="alert alert-info mb-3">
                    <i class="fas fa-info-circle me-1"></i>
                    iOSの照会カレンダーは自動更新の間隔が長い場合があります。手動で更新するには、カレンダーアプリを開いて画面を下にスワイプしてください。
                </div>

                <h4><i class="fab fa-google me-1"></i>Googleカレンダーとの連携</h4>
                <ol>
                    <li>パソコンのブラウザでGoogleカレンダー（<code>calendar.google.com</code>）を開きます。</li>
                    <li>左サイドバーの「他のカレンダー」の「＋」アイコンをクリックします。</li>
                    <li>「URLで追加」を選択します。</li>
                    <li>「カレンダーのURL」欄にTeamSpaceで取得したICS URLを貼り付けます。</li>
                    <li>「カレンダーを追加」をクリックします。</li>
                    <li>数分後にTeamSpaceの予定がGoogleカレンダーに表示されます。</li>
                </ol>
                <div class="alert alert-warning mb-3">
                    <i class="fas fa-exclamation-triangle me-1"></i>
                    <strong>注意：</strong>Googleカレンダーの照会カレンダーは、更新に最大12〜24時間かかる場合があります。これはGoogle側の仕様です。
                </div>

                <h4><i class="fab fa-microsoft me-1"></i>Microsoft Outlookとの連携</h4>
                <p><strong>Outlook デスクトップアプリ（Windows）の場合：</strong></p>
                <ol>
                    <li>Outlookを開き、「ファイル」→「アカウント設定」→「アカウント設定」を選択します。</li>
                    <li>「インターネット予定表」タブを選択し、「新規」をクリックします。</li>
                    <li>TeamSpaceで取得したICS URLを貼り付け、「追加」をクリックします。</li>
                    <li>フォルダ名を「TeamSpace」などわかりやすい名前に設定し「OK」をクリックします。</li>
                </ol>
                <p><strong>Outlook on the web（Microsoft 365）の場合：</strong></p>
                <ol>
                    <li>Outlook on the webのカレンダー画面を開きます。</li>
                    <li>左サイドバーの「予定表を追加」をクリックします。</li>
                    <li>「Webから購読」を選択します。</li>
                    <li>URLを貼り付け、名前を入力して「インポート」をクリックします。</li>
                </ol>

                <div class="alert alert-secondary">
                    <i class="fas fa-shield-alt me-1"></i>
                    <strong>セキュリティについて：</strong>ICS URLにはアクセストークンが含まれています。このURLを知っている人は予定を閲覧できるため、URLの取り扱いにご注意ください。URLが漏洩した場合は、設定画面から「URLを再生成」して古いURLを無効にしてください。
                </div>
            </div>
        </div>
        <div class="help-back-top"><a href="#help-top"><i class="fas fa-arrow-up me-1"></i>ページ上部へ戻る</a></div>
    </div>

    <!-- ========== 12. 通知設定 ========== -->
    <div class="help-section" id="sec-notifications">
        <div class="card">
            <div class="card-header">
                <span class="section-icon"><i class="fas fa-bell"></i></span>
                <h2>12. 通知設定</h2>
            </div>
            <div class="card-body">
                <p>TeamSpaceでは、各種イベント発生時にアプリ内通知およびメール通知をお知らせします。通知の受信設定をカスタマイズできます。</p>

                <h4>通知の種類</h4>
                <div class="table-responsive"><table class="table table-bordered help-table">
                    <thead>
                        <tr>
                            <th>通知イベント</th>
                            <th>アプリ内通知</th>
                            <th>メール通知</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td>スケジュールへの招待</td><td>常時</td><td>設定可能</td></tr>
                        <tr><td>メッセージの受信</td><td>常時</td><td>設定可能</td></tr>
                        <tr><td>ワークフロー承認依頼</td><td>常時</td><td>設定可能</td></tr>
                        <tr><td>ワークフロー承認・差し戻し</td><td>常時</td><td>設定可能</td></tr>
                        <tr><td>タスクの割り当て</td><td>常時</td><td>設定可能</td></tr>
                        <tr><td>タスクの期限通知</td><td>常時</td><td>設定可能</td></tr>
                    </tbody>
                </table></div>

                <h4>通知設定の変更</h4>
                <ol>
                    <li>右上のユーザーメニューから「個人設定」を開きます。</li>
                    <li>「通知設定」セクションで、各イベントのメール通知のオン/オフを切り替えます。</li>
                    <li>「保存」をクリックして設定を反映します。</li>
                </ol>

                <h4>通知の確認方法</h4>
                <ul>
                    <li><strong>アプリ内通知</strong> &mdash; ヘッダーのベルアイコンをクリックすると、最新の通知一覧がドロップダウン表示されます。</li>
                    <li><strong>メール通知</strong> &mdash; 登録されたメールアドレスに通知メールが送信されます。</li>
                    <li><strong>トップページ</strong> &mdash; トップページの通知ウィジェットにも最新の通知が表示されます。</li>
                </ul>
            </div>
        </div>
        <div class="help-back-top"><a href="#help-top"><i class="fas fa-arrow-up me-1"></i>ページ上部へ戻る</a></div>
    </div>

    <!-- ========== 13. モバイルでの利用 ========== -->
    <div class="help-section" id="sec-mobile">
        <div class="card">
            <div class="card-header">
                <span class="section-icon"><i class="fas fa-mobile-alt"></i></span>
                <h2>13. モバイルでの利用</h2>
            </div>
            <div class="card-body">
                <p>TeamSpaceはレスポンシブデザインに対応しており、スマートフォンやタブレットのブラウザからも快適にご利用いただけます。</p>

                <h4>対応端末</h4>
                <ul>
                    <li><strong>iPhone / iPad</strong> &mdash; Safari（最新版）</li>
                    <li><strong>Android</strong> &mdash; Google Chrome（最新版）</li>
                </ul>

                <h4>モバイル利用のポイント</h4>
                <ul>
                    <li><strong>ホーム画面に追加</strong> &mdash; ブラウザの「ホーム画面に追加」機能を利用すると、アプリのようにワンタップでTeamSpaceにアクセスできます。</li>
                    <li><strong>ナビゲーション</strong> &mdash; モバイル画面ではハンバーガーメニュー（三本線アイコン）をタップしてナビゲーションを展開します。</li>
                    <li><strong>スケジュール</strong> &mdash; モバイルでは日表示が初期表示となります。左右スワイプで前後の日に移動できます。</li>
                    <li><strong>タスクボード</strong> &mdash; カンバンボードは横スクロールで各カラムを表示します。</li>
                    <li><strong>通知</strong> &mdash; カレンダー連携を設定しておくと、端末の標準通知機能でスケジュールのリマインダーを受け取れます。</li>
                </ul>

                <h4>iPhoneのホーム画面に追加する方法</h4>
                <ol>
                    <li>SafariでTeamSpaceにアクセスします。</li>
                    <li>画面下部の共有ボタン（四角に上向き矢印）をタップします。</li>
                    <li>「ホーム画面に追加」をタップします。</li>
                    <li>名前を確認して「追加」をタップします。</li>
                </ol>

                <h4>Androidのホーム画面に追加する方法</h4>
                <ol>
                    <li>ChromeでTeamSpaceにアクセスします。</li>
                    <li>画面右上のメニュー（三点アイコン）をタップします。</li>
                    <li>「ホーム画面に追加」をタップします。</li>
                    <li>名前を確認して「追加」をタップします。</li>
                </ol>

                <div class="alert alert-warning">
                    <i class="fas fa-lightbulb me-1"></i>
                    <strong>ヒント：</strong>モバイルでファイルを添付する際は、カメラで撮影した写真を直接アップロードすることも可能です。
                </div>
            </div>
        </div>
        <div class="help-back-top"><a href="#help-top"><i class="fas fa-arrow-up me-1"></i>ページ上部へ戻る</a></div>
    </div>

    <!-- ========== 14. よくある質問（FAQ） ========== -->
    <div class="help-section" id="sec-faq">
        <div class="card">
            <div class="card-header">
                <span class="section-icon"><i class="fas fa-question-circle"></i></span>
                <h2>14. よくある質問（FAQ）</h2>
            </div>
            <div class="card-body">

                <h4>ログイン・アカウント</h4>

                <div class="faq-item">
                    <div class="faq-q">パスワードを忘れてしまいました。</div>
                    <div class="faq-a">システム管理者にご連絡ください。管理者がパスワードをリセットいたします。</div>
                </div>
                <div class="faq-item">
                    <div class="faq-q">パスワードを変更したいです。</div>
                    <div class="faq-a">右上のユーザーメニューから「個人設定」を開き、パスワード変更セクションで現在のパスワードと新しいパスワードを入力して変更できます。</div>
                </div>
                <div class="faq-item">
                    <div class="faq-q">ログインできません。</div>
                    <div class="faq-a">以下をご確認ください。(1) ログインIDとパスワードが正しいか (2) Caps Lockがオフになっているか (3) ブラウザのCookieが有効になっているか。解決しない場合は管理者にお問い合わせください。</div>
                </div>

                <h4>スケジュール</h4>

                <div class="faq-item">
                    <div class="faq-q">他のメンバーの予定が見えません。</div>
                    <div class="faq-a">組織表示（組織日表示、組織週表示、組織月表示）に切り替えると、同じ組織のメンバーの予定を確認できます。他の組織のメンバーは、ドロップダウンから組織を変更してください。</div>
                </div>
                <div class="faq-item">
                    <div class="faq-q">予定を繰り返し登録できますか？</div>
                    <div class="faq-a">はい、予定登録画面で「繰り返し」設定を利用すると、毎日・毎週・毎月などの繰り返し予定を一括で登録できます。</div>
                </div>
                <div class="faq-item">
                    <div class="faq-q">外部カレンダーに同期した予定が更新されません。</div>
                    <div class="faq-a">外部カレンダーの更新間隔はアプリケーション側の設定に依存します。Googleカレンダーは最大12〜24時間、iOSカレンダーは数分〜数時間かかる場合があります。手動更新をお試しください。</div>
                </div>

                <h4>ワークフロー</h4>

                <div class="faq-item">
                    <div class="faq-q">申請を取り下げたいです。</div>
                    <div class="faq-a">「自分の申請」一覧から該当の申請を開き、「取り下げ」ボタンをクリックしてください。ただし、すでに最終承認済みの申請は取り下げできません。</div>
                </div>
                <div class="faq-item">
                    <div class="faq-q">承認者が休暇中で申請が止まっています。</div>
                    <div class="faq-a">承認者が代理承認を設定していれば、代理者が承認できます。設定がない場合は、システム管理者にご相談ください。</div>
                </div>

                <h4>メッセージ</h4>

                <div class="faq-item">
                    <div class="faq-q">送信したメッセージを取り消せますか？</div>
                    <div class="faq-a">送信済みのメッセージは取り消しできません。送信前に内容をよくご確認ください。</div>
                </div>
                <div class="faq-item">
                    <div class="faq-q">添付ファイルがアップロードできません。</div>
                    <div class="faq-a">ファイルサイズの上限を超えている可能性があります。ファイルサイズをご確認ください。また、システムで許可されていないファイル形式の場合もアップロードできないことがあります。</div>
                </div>

                <h4>タスク</h4>

                <div class="faq-item">
                    <div class="faq-q">完了したタスクを非表示にできますか？</div>
                    <div class="faq-a">タスクボードのフィルタ機能を使って、完了済みタスクの表示/非表示を切り替えることができます。</div>
                </div>

                <h4>WEBデータベース</h4>

                <div class="faq-item">
                    <div class="faq-q">データベースのフィールドを後から変更できますか？</div>
                    <div class="faq-a">はい、データベースの管理画面からフィールドの追加・編集・削除が可能です。ただし、フィールドを削除すると既存レコードの該当データも失われますのでご注意ください。</div>
                </div>
                <div class="faq-item">
                    <div class="faq-q">データをCSVでエクスポートできますか？</div>
                    <div class="faq-a">データベースの一覧画面にエクスポート機能がある場合は、CSV形式でダウンロードできます。詳細はシステム管理者にお問い合わせください。</div>
                </div>

                <h4>その他</h4>

                <div class="faq-item">
                    <div class="faq-q">画面の表示が崩れています。</div>
                    <div class="faq-a">ブラウザのキャッシュをクリアしてから再読み込みしてください。<span class="help-kbd">Ctrl</span> + <span class="help-kbd">Shift</span> + <span class="help-kbd">R</span>（Macの場合は <span class="help-kbd">Cmd</span> + <span class="help-kbd">Shift</span> + <span class="help-kbd">R</span>）でスーパーリロードできます。</div>
                </div>
                <div class="faq-item">
                    <div class="faq-q">動作が遅いです。</div>
                    <div class="faq-a">以下をお試しください。(1) ブラウザのキャッシュをクリア (2) ブラウザの拡張機能を一時的に無効化 (3) 他のタブを閉じてメモリを確保 (4) ネットワーク接続を確認。改善しない場合は管理者にお知らせください。</div>
                </div>
            </div>
        </div>
        <div class="help-back-top"><a href="#help-top"><i class="fas fa-arrow-up me-1"></i>ページ上部へ戻る</a></div>
    </div>

    <!-- ========== 15. お問い合わせ ========== -->
    <div class="help-section" id="sec-contact">
        <div class="card">
            <div class="card-header">
                <span class="section-icon"><i class="fas fa-headset"></i></span>
                <h2>15. お問い合わせ</h2>
            </div>
            <div class="card-body">
                <p>TeamSpaceの操作方法やトラブルについてご不明な点がございましたら、以下の方法でお問い合わせください。</p>

                <h4>社内サポート窓口</h4>
                <div class="table-responsive"><table class="table table-bordered help-table">
                    <tbody>
                        <tr><th style="width: 140px;">担当</th><td>システム管理者（情報システム部門）</td></tr>
                        <tr><th>受付時間</th><td>平日 9:00 〜 18:00</td></tr>
                    </tbody>
                </table></div>

                <h4>お問い合わせの際にお伝えいただきたい情報</h4>
                <ul>
                    <li>お名前（ログインID）</li>
                    <li>発生日時</li>
                    <li>使用しているブラウザ・OS</li>
                    <li>画面のスクリーンショット（取得可能な場合）</li>
                    <li>操作の手順と発生した問題の内容</li>
                </ul>

                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-1"></i>
                    スクリーンショットの取得方法：<span class="help-kbd">PrintScreen</span>キー（Windowsの場合）または <span class="help-kbd">Cmd</span> + <span class="help-kbd">Shift</span> + <span class="help-kbd">3</span>（Macの場合）で画面全体をキャプチャできます。
                </div>
            </div>
        </div>
        <div class="help-back-top"><a href="#help-top"><i class="fas fa-arrow-up me-1"></i>ページ上部へ戻る</a></div>
    </div>

    <!-- ========== キーボードショートカット ========== -->
    <div class="help-section" id="sec-shortcuts">
        <div class="card">
            <div class="card-header">
                <span class="section-icon"><i class="fas fa-keyboard"></i></span>
                <h2>付録：キーボードショートカット</h2>
            </div>
            <div class="card-body">
                <p>以下のキーボードショートカットで、より素早く操作を行えます。</p>
                <div class="table-responsive"><table class="table table-bordered help-table">
                    <thead>
                        <tr>
                            <th>ショートカット</th>
                            <th>動作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><span class="help-kbd">Ctrl</span> + <span class="help-kbd">Shift</span> + <span class="help-kbd">R</span></td>
                            <td>ページの強制再読み込み（キャッシュクリア）</td>
                        </tr>
                        <tr>
                            <td><span class="help-kbd">Ctrl</span> + <span class="help-kbd">F</span></td>
                            <td>ページ内検索</td>
                        </tr>
                        <tr>
                            <td><span class="help-kbd">Esc</span></td>
                            <td>モーダル（ダイアログ）を閉じる</td>
                        </tr>
                        <tr>
                            <td><span class="help-kbd">Tab</span></td>
                            <td>次の入力欄へ移動</td>
                        </tr>
                        <tr>
                            <td><span class="help-kbd">Shift</span> + <span class="help-kbd">Tab</span></td>
                            <td>前の入力欄へ移動</td>
                        </tr>
                        <tr>
                            <td><span class="help-kbd">Enter</span></td>
                            <td>フォームの送信 / ボタンのクリック</td>
                        </tr>
                    </tbody>
                </table></div>
                <p class="text-muted mb-0" style="font-size: 0.85rem;">※ Macの場合は <span class="help-kbd">Ctrl</span> を <span class="help-kbd">Cmd</span> に読み替えてください。</p>
            </div>
        </div>
        <div class="help-back-top"><a href="#help-top"><i class="fas fa-arrow-up me-1"></i>ページ上部へ戻る</a></div>
    </div>

    <!-- フッター -->
    <div class="text-center text-muted py-4" style="font-size: 0.85rem; border-top: 1px solid #eaecf4; margin-top: 16px;">
        TeamSpace ヘルプガイド &mdash; 最終更新日: 2026年3月25日
    </div>

</div>
