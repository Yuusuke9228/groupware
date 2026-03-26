<?php
// views/help/admin_manual.php
// 管理者マニュアル
$settingModel = new \Models\Setting();
$appName = $settingModel->getAppName();
?>

<style>
    .manual-container { max-width: 960px; margin: 0 auto; padding: 16px; }
    .manual-hero {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: #fff; border-radius: 16px; padding: 40px 32px; margin-bottom: 28px; text-align: center;
        position: relative; overflow: hidden;
    }
    .manual-hero::before {
        content: ''; position: absolute; top: -50%; right: -30%; width: 300px; height: 300px;
        background: rgba(255,255,255,0.08); border-radius: 50%;
    }
    .manual-hero::after {
        content: ''; position: absolute; bottom: -40%; left: -20%; width: 250px; height: 250px;
        background: rgba(255,255,255,0.05); border-radius: 50%;
    }
    .manual-hero h1 { font-size: 1.8rem; font-weight: 800; margin-bottom: 10px; position: relative; }
    .manual-hero p { font-size: 1rem; opacity: 0.93; margin-bottom: 0; position: relative; }

    .manual-toc {
        background: #f8f9ff; border: 1px solid #e0e4f5; border-radius: 12px;
        padding: 20px 24px; margin-bottom: 28px;
    }
    .manual-toc h5 { font-weight: 700; margin-bottom: 14px; color: #555; font-size: 0.9rem; }
    .manual-toc-list { list-style: none; padding: 0; margin: 0; }
    .manual-toc-list li { margin-bottom: 4px; }
    .manual-toc-list a {
        display: flex; align-items: center; gap: 6px; padding: 5px 8px;
        color: #4e73df; text-decoration: none; font-size: 0.88rem;
        border-radius: 6px; transition: background 0.15s;
    }
    .manual-toc-list a:hover { background: #eef1ff; text-decoration: none; }
    .manual-toc-list a i { width: 18px; text-align: center; font-size: 0.85rem; }

    .manual-section { margin-bottom: 28px; }
    .manual-card {
        border: 1px solid #e3e6f0; border-radius: 12px;
        box-shadow: 0 1px 4px rgba(0,0,0,0.04); overflow: hidden; background: #fff;
    }
    .manual-card-head {
        padding: 14px 20px; display: flex; align-items: center; gap: 10px;
        background: #fff; border-bottom: 2px solid #667eea;
    }
    .manual-card-head .sec-icon { font-size: 1.15rem; color: #667eea; width: 26px; text-align: center; }
    .manual-card-head h2 { font-size: 1.05rem; font-weight: 700; margin: 0; color: #333; flex: 1; }
    .manual-card-body {
        padding: 18px 22px; font-size: 0.9rem; line-height: 1.85; color: #555;
    }
    .manual-card-body h4 {
        font-size: 0.95rem; font-weight: 700; color: #444;
        margin: 18px 0 8px; padding-left: 10px;
        border-left: 3px solid #667eea;
    }
    .manual-card-body h4:first-child { margin-top: 0; }
    .manual-card-body p { margin-bottom: 8px; }
    .manual-card-body ul, .manual-card-body ol { padding-left: 22px; margin-bottom: 10px; }
    .manual-card-body li { margin-bottom: 4px; }

    .manual-step {
        display: flex; gap: 10px; align-items: flex-start;
        padding: 8px 12px; margin: 4px 0; background: #f8f9fc;
        border-radius: 8px; font-size: 0.88rem;
    }
    .manual-step-num {
        background: #667eea; color: #fff; width: 22px; height: 22px;
        border-radius: 50%; display: flex; align-items: center; justify-content: center;
        font-size: 0.75rem; font-weight: 700; flex-shrink: 0; margin-top: 1px;
    }
    .manual-step-text { flex: 1; }

    .manual-tip {
        background: #fff8e6; border-left: 3px solid #f0ad4e; padding: 10px 14px;
        border-radius: 0 8px 8px 0; margin: 10px 0; font-size: 0.87rem;
    }
    .manual-tip strong { color: #c88a00; }

    .manual-warn {
        background: #fef0f0; border-left: 3px solid #e74c3c; padding: 10px 14px;
        border-radius: 0 8px 8px 0; margin: 10px 0; font-size: 0.87rem;
    }
    .manual-warn strong { color: #c0392b; }

    .manual-info {
        background: #e8f4fd; border-left: 3px solid #2196F3; padding: 10px 14px;
        border-radius: 0 8px 8px 0; margin: 10px 0; font-size: 0.87rem;
    }
    .manual-info strong { color: #1565C0; }

    .code-block {
        position: relative; background: #2d2d2d; color: #f8f8f2; border-radius: 8px;
        padding: 14px 16px; margin: 8px 0 12px; font-size: 0.82rem; line-height: 1.6;
        font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace;
        overflow-x: auto;
    }
    .code-block .copy-btn {
        position: absolute; top: 6px; right: 6px; background: rgba(255,255,255,0.15);
        border: none; color: #ccc; padding: 3px 8px; border-radius: 4px; font-size: 0.72rem;
        cursor: pointer; transition: background 0.2s;
    }
    .code-block .copy-btn:hover { background: rgba(255,255,255,0.3); }

    .req-table { font-size: 0.87rem; }
    .req-table th { background: #f8f9ff; font-weight: 600; white-space: nowrap; }
    .req-table td, .req-table th { vertical-align: middle; }

    .breadcrumb-nav { margin-bottom: 16px; }
    .breadcrumb-nav a { color: #667eea; text-decoration: none; font-size: 0.88rem; }
    .breadcrumb-nav a:hover { text-decoration: underline; }
    .breadcrumb-nav span { color: #999; font-size: 0.88rem; }

    @media (max-width: 768px) {
        .manual-container { padding: 10px; }
        .manual-hero { padding: 24px 16px; }
        .manual-hero h1 { font-size: 1.4rem; }
        .manual-card-body { padding: 14px 16px; }
    }
</style>

<div class="manual-container">

    <!-- パンくずナビ -->
    <div class="breadcrumb-nav">
        <a href="<?php echo BASE_PATH; ?>/help"><i class="fas fa-book-open me-1"></i>ご利用ガイド</a>
        <span class="mx-1">/</span>
        <span class="text-dark fw-bold">管理者マニュアル</span>
    </div>

    <!-- ヒーローセクション -->
    <div class="manual-hero">
        <h1><i class="fas fa-user-shield me-2"></i>管理者マニュアル</h1>
        <p><?php echo htmlspecialchars($appName); ?> の管理者向け機能について詳しくご説明いたします。</p>
    </div>

    <!-- 目次 -->
    <div class="manual-toc">
        <h5><i class="fas fa-list-ul me-1"></i> 目次</h5>
        <ul class="manual-toc-list">
            <li><a href="#sec-dashboard"><i class="fas fa-tachometer-alt"></i> 1. 管理者ダッシュボード概要</a></li>
            <li><a href="#sec-users"><i class="fas fa-users"></i> 2. ユーザー管理</a></li>
            <li><a href="#sec-organizations"><i class="fas fa-sitemap"></i> 3. 組織管理</a></li>
            <li><a href="#sec-workflow"><i class="fas fa-project-diagram"></i> 4. ワークフローテンプレート管理</a></li>
            <li><a href="#sec-facilities"><i class="fas fa-building"></i> 5. 施設管理</a></li>
            <li><a href="#sec-bulletin"><i class="fas fa-bullhorn"></i> 6. 掲示板カテゴリ管理</a></li>
            <li><a href="#sec-settings"><i class="fas fa-cogs"></i> 7. システム設定</a></li>
            <li><a href="#sec-csv-import"><i class="fas fa-file-csv"></i> 8. CSV 一括インポート</a></li>
            <li><a href="#sec-backup"><i class="fas fa-database"></i> 9. バックアップと復元</a></li>
            <li><a href="#sec-security"><i class="fas fa-shield-alt"></i> 10. セキュリティ設定の推奨事項</a></li>
        </ul>
    </div>

    <!-- ===== 1. 管理者ダッシュボード概要 ===== -->
    <div class="manual-section" id="sec-dashboard">
        <div class="manual-card">
            <div class="manual-card-head">
                <span class="sec-icon"><i class="fas fa-tachometer-alt"></i></span>
                <h2>1. 管理者ダッシュボード概要</h2>
            </div>
            <div class="manual-card-body">
                <p>管理者としてログインされますと、左側メニューまたはヘッダーメニューに「管理者メニュー」が表示されます。こちらからシステム全体の管理機能にアクセスいただけます。</p>

                <h4>管理者メニューへのアクセス方法</h4>
                <div class="manual-step"><span class="manual-step-num">1</span><span class="manual-step-text">管理者アカウントでシステムにログインしてください。</span></div>
                <div class="manual-step"><span class="manual-step-num">2</span><span class="manual-step-text">画面右上のメニューから「管理者メニュー」をクリックしてください。</span></div>

                <h4>管理者メニューの主な機能</h4>
                <table class="table table-bordered req-table">
                    <thead>
                        <tr>
                            <th style="width: 180px;">メニュー項目</th>
                            <th>説明</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td><strong>ユーザー管理</strong></td><td>ユーザーの追加・編集・削除、CSV インポート</td></tr>
                        <tr><td><strong>組織管理</strong></td><td>組織（部署・課）の構造設定とユーザー所属の管理</td></tr>
                        <tr><td><strong>ワークフロー管理</strong></td><td>申請テンプレートの作成・編集、承認ルートの設定</td></tr>
                        <tr><td><strong>施設管理</strong></td><td>会議室等の施設の追加・編集・削除</td></tr>
                        <tr><td><strong>掲示板カテゴリ</strong></td><td>掲示板のカテゴリ管理</td></tr>
                        <tr><td><strong>システム設定</strong></td><td>アプリケーション名、会社名、メール設定など</td></tr>
                        <tr><td><strong>CSV インポート</strong></td><td>ユーザーや組織の一括登録</td></tr>
                    </tbody>
                </table>

                <div class="manual-info">
                    <strong><i class="fas fa-info-circle me-1"></i>情報：</strong>
                    管理者メニューは、管理者権限（<code>admin</code> ロール）を持つユーザーにのみ表示されます。一般ユーザーにはこれらのメニューは表示されません。
                </div>
            </div>
        </div>
    </div>

    <!-- ===== 2. ユーザー管理 ===== -->
    <div class="manual-section" id="sec-users">
        <div class="manual-card">
            <div class="manual-card-head">
                <span class="sec-icon"><i class="fas fa-users"></i></span>
                <h2>2. ユーザー管理</h2>
            </div>
            <div class="manual-card-body">
                <p>ユーザー管理画面では、システムを利用するユーザーの追加・編集・削除を行うことができます。</p>

                <h4>ユーザーの追加</h4>
                <div class="manual-step"><span class="manual-step-num">1</span><span class="manual-step-text">管理者メニューから「ユーザー管理」を選択してください。</span></div>
                <div class="manual-step"><span class="manual-step-num">2</span><span class="manual-step-text">「新規ユーザー追加」ボタンをクリックしてください。</span></div>
                <div class="manual-step"><span class="manual-step-num">3</span><span class="manual-step-text">必要な情報を入力してください。</span></div>
                <div class="manual-step"><span class="manual-step-num">4</span><span class="manual-step-text">「保存」ボタンをクリックして登録を完了してください。</span></div>

                <h4>入力項目</h4>
                <table class="table table-bordered req-table">
                    <thead>
                        <tr>
                            <th style="width: 160px;">項目</th>
                            <th>説明</th>
                            <th style="width: 80px;">必須</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td><strong>ログイン ID</strong></td><td>半角英数字。システム内で一意である必要があります。</td><td class="text-center">必須</td></tr>
                        <tr><td><strong>パスワード</strong></td><td>8 文字以上を推奨いたします。</td><td class="text-center">必須</td></tr>
                        <tr><td><strong>表示名</strong></td><td>システム上に表示されるお名前です。</td><td class="text-center">必須</td></tr>
                        <tr><td><strong>メールアドレス</strong></td><td>通知の送信先となります。</td><td class="text-center">任意</td></tr>
                        <tr><td><strong>権限</strong></td><td>「管理者」または「一般ユーザー」を選択してください。</td><td class="text-center">必須</td></tr>
                        <tr><td><strong>所属組織</strong></td><td>所属する部署・課を選択してください。</td><td class="text-center">任意</td></tr>
                    </tbody>
                </table>

                <h4>ユーザーの編集</h4>
                <p>ユーザー一覧画面で対象のユーザーの「編集」ボタンをクリックすると、登録情報を変更できます。パスワード欄を空欄にした場合、パスワードは変更されません。</p>

                <h4>ユーザーの削除</h4>
                <p>ユーザー一覧画面で対象のユーザーの「削除」ボタンをクリックしてください。確認ダイアログが表示されますので、問題なければ「削除」を選択してください。</p>

                <div class="manual-warn">
                    <strong><i class="fas fa-exclamation-triangle me-1"></i>注意：</strong>
                    ユーザーを削除すると、そのユーザーが作成したデータ（スケジュール、メッセージなど）にも影響が及ぶ場合がございます。削除前に十分ご確認ください。ステータスを「無効」に変更することで、データを保持したまま利用を停止させることも可能です。
                </div>

                <h4>CSV 一括インポートによるユーザー登録</h4>
                <p>多数のユーザーを一括で登録する場合は、CSV インポート機能をご利用ください。詳細は「<a href="#sec-csv-import">8. CSV 一括インポート</a>」をご参照ください。</p>
            </div>
        </div>
    </div>

    <!-- ===== 3. 組織管理 ===== -->
    <div class="manual-section" id="sec-organizations">
        <div class="manual-card">
            <div class="manual-card-head">
                <span class="sec-icon"><i class="fas fa-sitemap"></i></span>
                <h2>3. 組織管理</h2>
            </div>
            <div class="manual-card-body">
                <p>組織管理画面では、会社の部署や課などの組織構造を設定し、各ユーザーの所属を管理することができます。</p>

                <h4>組織の親子構造</h4>
                <p>組織は親子関係（ツリー構造）で管理されます。例えば、以下のような構造を作成できます。</p>
                <div class="code-block">
                    <pre style="margin:0;white-space:pre-wrap;color:#f8f8f2;">会社
├── 営業部
│   ├── 営業第一課
│   └── 営業第二課
├── 開発部
│   ├── システム開発課
│   └── インフラ課
└── 総務部
    ├── 人事課
    └── 経理課</pre>
                </div>

                <h4>組織の追加</h4>
                <div class="manual-step"><span class="manual-step-num">1</span><span class="manual-step-text">管理者メニューから「組織管理」を選択してください。</span></div>
                <div class="manual-step"><span class="manual-step-num">2</span><span class="manual-step-text">「新規組織追加」ボタンをクリックしてください。</span></div>
                <div class="manual-step"><span class="manual-step-num">3</span><span class="manual-step-text">組織名を入力し、親組織を選択してください（最上位の組織の場合は「なし」を選択）。</span></div>
                <div class="manual-step"><span class="manual-step-num">4</span><span class="manual-step-text">「保存」をクリックして登録を完了してください。</span></div>

                <h4>ユーザーの所属設定</h4>
                <p>ユーザーの所属組織は、以下の方法で設定できます。</p>
                <ul>
                    <li><strong>ユーザー編集画面</strong>から所属組織を選択する</li>
                    <li><strong>CSV インポート</strong>で所属組織を指定して一括登録する</li>
                </ul>

                <div class="manual-tip">
                    <strong><i class="fas fa-lightbulb me-1"></i>ヒント：</strong>
                    組織構造は、スケジュールの表示や掲示板の閲覧範囲などにも影響いたします。実際の組織体制に合わせて正確に設定してください。
                </div>
            </div>
        </div>
    </div>

    <!-- ===== 4. ワークフローテンプレート管理 ===== -->
    <div class="manual-section" id="sec-workflow">
        <div class="manual-card">
            <div class="manual-card-head">
                <span class="sec-icon"><i class="fas fa-project-diagram"></i></span>
                <h2>4. ワークフローテンプレート管理</h2>
            </div>
            <div class="manual-card-body">
                <p>ワークフローテンプレート管理では、各種申請書のフォーマットと承認ルートを設定することができます。</p>

                <h4>テンプレートの作成</h4>
                <div class="manual-step"><span class="manual-step-num">1</span><span class="manual-step-text">管理者メニューから「ワークフロー管理」を選択してください。</span></div>
                <div class="manual-step"><span class="manual-step-num">2</span><span class="manual-step-text">「新規テンプレート作成」ボタンをクリックしてください。</span></div>
                <div class="manual-step"><span class="manual-step-num">3</span><span class="manual-step-text">テンプレート名と説明を入力してください。</span></div>
                <div class="manual-step"><span class="manual-step-num">4</span><span class="manual-step-text">申請フォームの項目（テキスト入力、日付、金額、テキストエリアなど）を設定してください。</span></div>
                <div class="manual-step"><span class="manual-step-num">5</span><span class="manual-step-text">「保存」をクリックして登録を完了してください。</span></div>

                <h4>承認ルートの設定</h4>
                <p>各テンプレートに対して、承認ルート（承認者の順序）を設定できます。</p>
                <ul>
                    <li><strong>承認ステップ</strong>：承認者を順番に設定します。第 1 承認者、第 2 承認者...のように複数段階を設定できます。</li>
                    <li><strong>承認者の指定方法</strong>：特定のユーザーを指定するか、「申請者の上長」などの動的な指定が可能です。</li>
                </ul>

                <h4>テンプレートの編集・無効化</h4>
                <p>既存のテンプレートは編集が可能です。既に使用されているテンプレートを削除するのではなく、ステータスを「無効」に変更していただくことを推奨いたします。これにより、過去の申請データは保持されます。</p>

                <div class="manual-tip">
                    <strong><i class="fas fa-lightbulb me-1"></i>ヒント：</strong>
                    よく使用される申請書（休暇届、経費精算、出張申請など）のテンプレートをあらかじめ作成しておくと、ユーザーがスムーズに申請を行えます。
                </div>
            </div>
        </div>
    </div>

    <!-- ===== 5. 施設管理 ===== -->
    <div class="manual-section" id="sec-facilities">
        <div class="manual-card">
            <div class="manual-card-head">
                <span class="sec-icon"><i class="fas fa-building"></i></span>
                <h2>5. 施設管理</h2>
            </div>
            <div class="manual-card-body">
                <p>施設管理では、会議室やプロジェクター等の共有設備を登録し、ユーザーが予約できるように設定します。</p>

                <h4>施設の追加</h4>
                <div class="manual-step"><span class="manual-step-num">1</span><span class="manual-step-text">管理者メニューから「施設管理」を選択してください。</span></div>
                <div class="manual-step"><span class="manual-step-num">2</span><span class="manual-step-text">「新規施設追加」ボタンをクリックしてください。</span></div>
                <div class="manual-step"><span class="manual-step-num">3</span><span class="manual-step-text">施設名、説明（定員数や設備情報など）を入力してください。</span></div>
                <div class="manual-step"><span class="manual-step-num">4</span><span class="manual-step-text">「保存」をクリックして登録を完了してください。</span></div>

                <h4>施設の編集・削除</h4>
                <p>施設一覧から対象の施設の「編集」ボタンで情報を更新できます。「削除」ボタンで施設を削除できますが、予約済みのデータがある場合はご注意ください。</p>

                <div class="manual-info">
                    <strong><i class="fas fa-info-circle me-1"></i>情報：</strong>
                    登録された施設は、スケジュール登録時に「施設予約」として選択可能になります。施設の重複予約は自動的に防止されます。
                </div>
            </div>
        </div>
    </div>

    <!-- ===== 6. 掲示板カテゴリ管理 ===== -->
    <div class="manual-section" id="sec-bulletin">
        <div class="manual-card">
            <div class="manual-card-head">
                <span class="sec-icon"><i class="fas fa-bullhorn"></i></span>
                <h2>6. 掲示板カテゴリ管理</h2>
            </div>
            <div class="manual-card-body">
                <p>掲示板のカテゴリを管理することで、投稿を分類し、情報を整理できます。</p>

                <h4>カテゴリの追加</h4>
                <div class="manual-step"><span class="manual-step-num">1</span><span class="manual-step-text">管理者メニューから「掲示板カテゴリ管理」を選択してください。</span></div>
                <div class="manual-step"><span class="manual-step-num">2</span><span class="manual-step-text">「新規カテゴリ追加」ボタンをクリックしてください。</span></div>
                <div class="manual-step"><span class="manual-step-num">3</span><span class="manual-step-text">カテゴリ名と表示順を設定してください。</span></div>
                <div class="manual-step"><span class="manual-step-num">4</span><span class="manual-step-text">「保存」をクリックして登録を完了してください。</span></div>

                <h4>カテゴリの運用例</h4>
                <table class="table table-bordered req-table">
                    <thead>
                        <tr>
                            <th style="width: 180px;">カテゴリ名</th>
                            <th>用途の例</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td><strong>お知らせ</strong></td><td>全社向けの公式なお知らせ・通達</td></tr>
                        <tr><td><strong>総務からの連絡</strong></td><td>総務部門からの事務連絡</td></tr>
                        <tr><td><strong>IT サポート</strong></td><td>システムメンテナンス情報や IT 関連の案内</td></tr>
                        <tr><td><strong>社内イベント</strong></td><td>懇親会・研修などのイベント告知</td></tr>
                        <tr><td><strong>自由掲示板</strong></td><td>社員間の自由なコミュニケーション</td></tr>
                    </tbody>
                </table>

                <div class="manual-tip">
                    <strong><i class="fas fa-lightbulb me-1"></i>ヒント：</strong>
                    カテゴリは利用開始前にあらかじめ設定しておくと、ユーザーがすぐに掲示板を活用できます。
                </div>
            </div>
        </div>
    </div>

    <!-- ===== 7. システム設定 ===== -->
    <div class="manual-section" id="sec-settings">
        <div class="manual-card">
            <div class="manual-card-head">
                <span class="sec-icon"><i class="fas fa-cogs"></i></span>
                <h2>7. システム設定</h2>
            </div>
            <div class="manual-card-body">
                <p>システム設定画面では、アプリケーション全体に関わる設定を行うことができます。</p>

                <h4>アプリケーション名</h4>
                <p>画面上部のヘッダーやログイン画面に表示されるアプリケーション名を設定できます。会社名やサービス名など、ご自由に変更いただけます。</p>

                <h4>会社名</h4>
                <p>各種帳票やフッターに表示される会社名を設定できます。</p>

                <h4>メール設定（SMTP）</h4>
                <p>通知メールを送信するためのメールサーバー設定を行います。</p>
                <table class="table table-bordered req-table">
                    <thead>
                        <tr>
                            <th style="width: 180px;">設定項目</th>
                            <th>説明</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td><strong>SMTP ホスト</strong></td><td>メールサーバーのホスト名（例：<code>smtp.gmail.com</code>）</td></tr>
                        <tr><td><strong>SMTP ポート</strong></td><td>ポート番号（例：<code>587</code> for TLS, <code>465</code> for SSL）</td></tr>
                        <tr><td><strong>SMTP ユーザー名</strong></td><td>メールサーバーの認証ユーザー名</td></tr>
                        <tr><td><strong>SMTP パスワード</strong></td><td>メールサーバーの認証パスワード</td></tr>
                        <tr><td><strong>送信元アドレス</strong></td><td>通知メールの送信元として表示されるアドレス</td></tr>
                        <tr><td><strong>送信元名</strong></td><td>通知メールの送信者名</td></tr>
                        <tr><td><strong>暗号化方式</strong></td><td><code>TLS</code>（推奨）または <code>SSL</code></td></tr>
                    </tbody>
                </table>

                <div class="manual-tip">
                    <strong><i class="fas fa-lightbulb me-1"></i>ヒント：</strong>
                    Gmail を SMTP サーバーとしてご利用になる場合は、Google アカウントの「アプリパスワード」を生成してご使用ください。通常のアカウントパスワードでは認証できない場合がございます。
                </div>

                <h4>その他の設定</h4>
                <ul>
                    <li><strong>タイムゾーン</strong>：システムの標準タイムゾーンを設定できます（デフォルト：<code>Asia/Tokyo</code>）</li>
                    <li><strong>1ページあたりの表示件数</strong>：一覧画面のページネーション設定</li>
                    <li><strong>ファイルアップロード上限</strong>：アップロード可能なファイルサイズの上限</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- ===== 8. CSV一括インポート ===== -->
    <div class="manual-section" id="sec-csv-import">
        <div class="manual-card">
            <div class="manual-card-head">
                <span class="sec-icon"><i class="fas fa-file-csv"></i></span>
                <h2>8. CSV 一括インポート</h2>
            </div>
            <div class="manual-card-body">
                <p>CSV ファイルを使用して、ユーザーや組織を一括登録することができます。大量のデータを効率的に登録する際にご利用ください。</p>

                <h4>ユーザーの CSV インポート</h4>
                <div class="manual-step"><span class="manual-step-num">1</span><span class="manual-step-text">管理者メニューから「CSV インポート」を選択してください。</span></div>
                <div class="manual-step"><span class="manual-step-num">2</span><span class="manual-step-text">「ユーザーインポート」タブを選択してください。</span></div>
                <div class="manual-step"><span class="manual-step-num">3</span><span class="manual-step-text">サンプル CSV をダウンロードし、フォーマットに従ってデータを入力してください。</span></div>
                <div class="manual-step"><span class="manual-step-num">4</span><span class="manual-step-text">CSV ファイルを選択し、「インポート」ボタンをクリックしてください。</span></div>

                <h4>ユーザー CSV のフォーマット</h4>
                <div class="code-block">
                    <button class="copy-btn" onclick="copyCode(this)"><i class="fas fa-copy me-1"></i>コピー</button>
                    <pre style="margin:0;white-space:pre-wrap;">login_id,password,display_name,email,role
user001,password123,山田太郎,yamada@example.com,user
user002,password456,鈴木花子,suzuki@example.com,user
admin002,adminpass,佐藤管理者,sato@example.com,admin</pre>
                </div>

                <h4>組織の CSV インポート</h4>
                <div class="manual-step"><span class="manual-step-num">1</span><span class="manual-step-text">「組織インポート」タブを選択してください。</span></div>
                <div class="manual-step"><span class="manual-step-num">2</span><span class="manual-step-text">サンプル CSV をダウンロードし、フォーマットに従ってデータを入力してください。</span></div>
                <div class="manual-step"><span class="manual-step-num">3</span><span class="manual-step-text">CSV ファイルを選択し、「インポート」ボタンをクリックしてください。</span></div>

                <h4>組織 CSV のフォーマット</h4>
                <div class="code-block">
                    <button class="copy-btn" onclick="copyCode(this)"><i class="fas fa-copy me-1"></i>コピー</button>
                    <pre style="margin:0;white-space:pre-wrap;">name,parent_name,sort_order
営業部,,1
営業第一課,営業部,1
営業第二課,営業部,2
開発部,,2
システム開発課,開発部,1</pre>
                </div>

                <div class="manual-warn">
                    <strong><i class="fas fa-exclamation-triangle me-1"></i>注意：</strong>
                    CSV ファイルの文字コードは <strong>UTF-8</strong> をご使用ください。Excel で編集された場合、Shift_JIS で保存されることがありますのでご注意ください。「名前を付けて保存」から「CSV UTF-8」形式を選択してください。
                </div>

                <div class="manual-tip">
                    <strong><i class="fas fa-lightbulb me-1"></i>ヒント：</strong>
                    インポート前にサンプル CSV をダウンロードし、そのフォーマットに合わせてデータを作成すると、エラーを防ぐことができます。
                </div>
            </div>
        </div>
    </div>

    <!-- ===== 9. バックアップと復元 ===== -->
    <div class="manual-section" id="sec-backup">
        <div class="manual-card">
            <div class="manual-card-head">
                <span class="sec-icon"><i class="fas fa-database"></i></span>
                <h2>9. バックアップと復元</h2>
            </div>
            <div class="manual-card-body">
                <p>データの安全性を確保するため、定期的なバックアップを行ってください。</p>

                <h4>データベースのバックアップ</h4>
                <p><code>mysqldump</code> コマンドを使用して、データベース全体をバックアップできます。</p>
                <div class="code-block">
                    <button class="copy-btn" onclick="copyCode(this)"><i class="fas fa-copy me-1"></i>コピー</button>
                    <pre style="margin:0;white-space:pre-wrap;">mysqldump -u groupware_user -p groupware_db > backup_$(date +%Y%m%d_%H%M%S).sql</pre>
                </div>

                <h4>アップロードファイルのバックアップ</h4>
                <p>ユーザーがアップロードしたファイルもバックアップ対象に含めてください。</p>
                <div class="code-block">
                    <button class="copy-btn" onclick="copyCode(this)"><i class="fas fa-copy me-1"></i>コピー</button>
                    <pre style="margin:0;white-space:pre-wrap;">tar -czf uploads_backup_$(date +%Y%m%d).tar.gz uploads/ public/uploads/</pre>
                </div>

                <h4>設定ファイルのバックアップ</h4>
                <div class="code-block">
                    <button class="copy-btn" onclick="copyCode(this)"><i class="fas fa-copy me-1"></i>コピー</button>
                    <pre style="margin:0;white-space:pre-wrap;">cp config/database.php config/database.php.bak</pre>
                </div>

                <h4>復元手順</h4>
                <div class="manual-step"><span class="manual-step-num">1</span><span class="manual-step-text">アプリケーションファイルを再配置してください（Git clone またはバックアップから復元）。</span></div>
                <div class="manual-step"><span class="manual-step-num">2</span><span class="manual-step-text"><code>config/database.php</code> を復元してください。</span></div>
                <div class="manual-step"><span class="manual-step-num">3</span><span class="manual-step-text">データベースを復元してください。</span></div>
                <div class="manual-step"><span class="manual-step-num">4</span><span class="manual-step-text">アップロードファイルを復元してください。</span></div>

                <div class="code-block">
                    <button class="copy-btn" onclick="copyCode(this)"><i class="fas fa-copy me-1"></i>コピー</button>
                    <pre style="margin:0;white-space:pre-wrap;">mysql -u groupware_user -p groupware_db < backup_20260326.sql
tar -xzf uploads_backup_20260326.tar.gz</pre>
                </div>

                <div class="manual-info">
                    <strong><i class="fas fa-info-circle me-1"></i>情報：</strong>
                    定期的な自動バックアップには、<code>cron</code> の設定をお勧めいたします。例えば、毎日深夜にバックアップを実行するスケジュールを設定できます。
                </div>

                <h4>自動バックアップの cron 設定例</h4>
                <div class="code-block">
                    <button class="copy-btn" onclick="copyCode(this)"><i class="fas fa-copy me-1"></i>コピー</button>
                    <pre style="margin:0;white-space:pre-wrap;"># 毎日午前2時にデータベースバックアップを実行
0 2 * * * mysqldump -u groupware_user -pYOUR_PASSWORD groupware_db > /var/backups/groupware/db_$(date +\%Y\%m\%d).sql

# 7日以上前のバックアップを自動削除
0 3 * * * find /var/backups/groupware/ -name "*.sql" -mtime +7 -delete</pre>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== 10. セキュリティ設定の推奨事項 ===== -->
    <div class="manual-section" id="sec-security">
        <div class="manual-card">
            <div class="manual-card-head">
                <span class="sec-icon"><i class="fas fa-shield-alt"></i></span>
                <h2>10. セキュリティ設定の推奨事項</h2>
            </div>
            <div class="manual-card-body">
                <p>システムの安全な運用のため、以下のセキュリティ対策を実施されることを強くお勧めいたします。</p>

                <h4>サーバー・ネットワーク</h4>
                <ul>
                    <li><strong>HTTPS の導入</strong>：SSL/TLS 証明書を導入し、通信を暗号化してください。Let's Encrypt などの無料 SSL もご利用いただけます。</li>
                    <li><strong>ファイアウォールの設定</strong>：不要なポートへのアクセスを制限してください。</li>
                    <li><strong>OS・ミドルウェアのアップデート</strong>：PHP、MySQL、Web サーバーは常に最新の安定版をご利用ください。</li>
                </ul>

                <h4>アプリケーション</h4>
                <ul>
                    <li><strong>インストーラーの無効化</strong>：インストール完了後、<code>public/install.php</code> を削除するか、<code>install.lock</code> を作成してください。</li>
                    <li><strong>デバッグモードの無効化</strong>：本番環境では <code>display_errors</code> を <code>Off</code> に設定してください。</li>
                    <li><strong>config ディレクトリのアクセス制限</strong>：Web からの直接アクセスができないようにしてください（<code>.htaccess</code> で制限）。</li>
                </ul>

                <h4>アカウント管理</h4>
                <ul>
                    <li><strong>強固なパスワードポリシー</strong>：パスワードは 8 文字以上、英大文字・小文字・数字・記号を含むものを推奨してください。</li>
                    <li><strong>管理者アカウントの最小化</strong>：管理者権限は必要最小限のユーザーにのみ付与してください。</li>
                    <li><strong>退職者のアカウント無効化</strong>：退職されたユーザーのアカウントは速やかに無効化してください。</li>
                    <li><strong>定期的なパスワード変更</strong>：ユーザーに定期的なパスワード変更を促してください。</li>
                </ul>

                <h4>データ保護</h4>
                <ul>
                    <li><strong>定期バックアップ</strong>：前述のバックアップ手順に従い、定期的にデータをバックアップしてください。</li>
                    <li><strong>バックアップの保管</strong>：バックアップデータは本番サーバーとは異なる場所に保管してください。</li>
                    <li><strong>アクセスログの監視</strong>：不正なアクセスがないか、定期的にログを確認してください。</li>
                </ul>

                <div class="manual-warn">
                    <strong><i class="fas fa-exclamation-triangle me-1"></i>重要：</strong>
                    本番環境へのデプロイ時には、必ず以下を確認してください。
                    <ul class="mb-0 mt-1">
                        <li><code>display_errors = Off</code> に設定されていること</li>
                        <li><code>public/install.php</code> が削除されている、または <code>install.lock</code> が作成されていること</li>
                        <li>HTTPS が有効であること</li>
                        <li><code>config/database.php</code> が Web から直接アクセスできないこと</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- 戻るリンク -->
    <div class="text-center mt-4 mb-4">
        <a href="<?php echo BASE_PATH; ?>/help" class="btn btn-outline-primary">
            <i class="fas fa-arrow-left me-1"></i> ご利用ガイドに戻る
        </a>
    </div>

</div>

<script>
function copyCode(btn) {
    const pre = btn.parentElement.querySelector('pre');
    const text = pre.textContent;
    navigator.clipboard.writeText(text).then(function() {
        const orig = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check me-1"></i>コピーしました';
        setTimeout(function() { btn.innerHTML = orig; }, 2000);
    });
}
</script>
