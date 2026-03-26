<?php
// views/help/admin_guide.php
// 管理者マニュアル
$settingModel = new \Models\Setting();
$appName = $settingModel->getAppName();
$companyName = $settingModel->getCompanyName();
$pageTitle = '管理者マニュアル';
?>

<style>
    .help-container { max-width: 960px; margin: 0 auto; padding: 16px; }
    .help-hero {
        background: linear-gradient(135deg, #c0392b 0%, #e74c3c 100%);
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
        background: #fef5f5; border: 1px solid #f0d0d0; border-radius: 12px;
        padding: 20px 24px; margin-bottom: 28px;
    }
    .help-toc h5 { font-weight: 700; margin-bottom: 14px; color: #555; font-size: 0.9rem; }
    .help-toc-grid {
        display: grid; grid-template-columns: repeat(auto-fill, minmax(210px, 1fr));
        gap: 6px 16px;
    }
    .help-toc-grid a {
        display: flex; align-items: center; gap: 6px; padding: 5px 8px;
        color: #c0392b; text-decoration: none; font-size: 0.88rem;
        border-radius: 6px; transition: background 0.15s;
    }
    .help-toc-grid a:hover { background: #fde8e8; text-decoration: none; }
    .help-toc-grid a i { width: 18px; text-align: center; font-size: 0.85rem; }

    .help-section { margin-bottom: 24px; }
    .help-card {
        border: 1px solid #e3e6f0; border-radius: 12px;
        box-shadow: 0 1px 4px rgba(0,0,0,0.04); overflow: hidden;
    }
    .help-card-head {
        padding: 14px 20px; display: flex; align-items: center; gap: 10px;
        cursor: pointer; user-select: none; background: #fff;
        border-bottom: 2px solid #c0392b; transition: background 0.15s;
    }
    .help-card-head:hover { background: #fef5f5; }
    .help-card-head .sec-icon { font-size: 1.15rem; color: #c0392b; width: 26px; text-align: center; }
    .help-card-head h2 { font-size: 1.05rem; font-weight: 700; margin: 0; color: #333; flex: 1; }
    .help-card-head .toggle-icon { color: #aaa; transition: transform 0.3s; }
    .help-card-head.collapsed .toggle-icon { transform: rotate(-90deg); }

    .help-card-body {
        padding: 18px 22px; font-size: 0.9rem; line-height: 1.85; color: #555;
    }
    .help-card-body h4 {
        font-size: 0.95rem; font-weight: 700; color: #444;
        margin: 18px 0 8px; padding-left: 10px;
        border-left: 3px solid #c0392b;
    }
    .help-card-body h4:first-child { margin-top: 0; }
    .help-card-body p { margin-bottom: 8px; }
    .help-card-body ul, .help-card-body ol { padding-left: 22px; margin-bottom: 10px; }
    .help-card-body li { margin-bottom: 4px; }

    .help-step {
        display: flex; gap: 10px; align-items: flex-start;
        padding: 8px 12px; margin: 4px 0; background: #fef8f8;
        border-radius: 8px; font-size: 0.88rem;
    }
    .help-step-num {
        background: #c0392b; color: #fff; width: 22px; height: 22px;
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

    .help-code {
        background: #1e1e1e; color: #d4d4d4; padding: 14px 18px; border-radius: 8px;
        font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace;
        font-size: 0.82rem; line-height: 1.7; overflow-x: auto; margin: 10px 0;
        white-space: pre-wrap; word-break: break-all;
    }
    .help-code .code-comment { color: #6a9955; }
    .help-code .code-keyword { color: #569cd6; }
    .help-code .code-string { color: #ce9178; }

    .help-nav-links {
        display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 24px;
    }
    .help-nav-links a {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 8px 16px; background: #fff; border: 1px solid #f0d0d0;
        border-radius: 8px; color: #c0392b; text-decoration: none; font-size: 0.88rem;
        transition: all 0.15s;
    }
    .help-nav-links a:hover { background: #fef5f5; border-color: #c0392b; }

    @media (max-width: 768px) {
        .help-container { padding: 10px; }
        .help-hero { padding: 24px 16px; }
        .help-hero h1 { font-size: 1.4rem; }
        .help-toc-grid { grid-template-columns: 1fr 1fr; }
        .help-card-body { padding: 14px 16px; }
        .help-code { font-size: 0.75rem; padding: 10px 12px; }
    }
</style>

<div class="help-container">

    <!-- ナビゲーションリンク -->
    <div class="help-nav-links">
        <a href="<?php echo BASE_PATH; ?>/help"><i class="fas fa-book-open"></i> ご利用ガイド</a>
        <a href="<?php echo BASE_PATH; ?>/help/install"><i class="fas fa-download"></i> インストールガイド</a>
    </div>

    <!-- ヒーローセクション -->
    <div class="help-hero">
        <h1><i class="fas fa-user-shield me-2"></i><?php echo htmlspecialchars($appName); ?> 管理者マニュアル</h1>
        <p>システム管理者向けの設定・運用マニュアルです。<br>ユーザー管理、組織管理、システム設定などの管理業務についてご説明いたします。</p>
    </div>

    <!-- 目次 -->
    <div class="help-toc">
        <h5><i class="fas fa-list-ul me-1"></i> 目次 ― ご覧になりたい項目をクリックしてください</h5>
        <div class="help-toc-grid">
            <a href="#sec-overview"><i class="fas fa-info-circle"></i> 管理者向け概要</a>
            <a href="#sec-users"><i class="fas fa-users"></i> ユーザー管理</a>
            <a href="#sec-organizations"><i class="fas fa-sitemap"></i> 組織管理</a>
            <a href="#sec-workflow"><i class="fas fa-project-diagram"></i> ワークフローテンプレート管理</a>
            <a href="#sec-facilities"><i class="fas fa-building"></i> 施設管理</a>
            <a href="#sec-settings"><i class="fas fa-cogs"></i> システム設定</a>
            <a href="#sec-csv-import"><i class="fas fa-file-csv"></i> CSV一括インポート</a>
            <a href="#sec-security"><i class="fas fa-shield-alt"></i> セキュリティ設定</a>
            <a href="#sec-backup"><i class="fas fa-database"></i> バックアップと復元</a>
            <a href="#sec-faq"><i class="fas fa-question-circle"></i> 管理者向けFAQ</a>
        </div>
    </div>

    <!-- ===== 1. 管理者向け概要 ===== -->
    <div class="help-section" id="sec-overview">
        <div class="help-card">
            <div class="help-card-head" onclick="toggleSection(this)">
                <span class="sec-icon"><i class="fas fa-info-circle"></i></span>
                <h2>管理者向け概要</h2>
                <i class="fas fa-chevron-down toggle-icon"></i>
            </div>
            <div class="help-card-body">
                <p><?php echo htmlspecialchars($appName); ?> の管理者には、通常のユーザー機能に加えて、システム全体の設定やユーザー管理などの管理機能が付与されております。</p>

                <h4>管理者ができること</h4>
                <ul>
                    <li><strong>ユーザー管理</strong> ― ユーザーの作成・編集・削除、パスワードリセット</li>
                    <li><strong>組織管理</strong> ― 部署・チームの作成と階層構造の設定、ユーザーの配属</li>
                    <li><strong>ワークフローテンプレート管理</strong> ― 申請フォームの設計、承認経路の設定</li>
                    <li><strong>施設管理</strong> ― 会議室・備品などの施設情報の管理</li>
                    <li><strong>システム設定</strong> ― アプリケーション名、メール設定、通知設定等</li>
                    <li><strong>CSV一括操作</strong> ― ユーザー・組織・アドレス帳のCSVインポート</li>
                </ul>

                <h4>管理画面へのアクセス</h4>
                <div class="help-step"><span class="help-step-num">1</span><span class="help-step-text">管理者権限を持つアカウントでログインしてください。</span></div>
                <div class="help-step"><span class="help-step-num">2</span><span class="help-step-text">左側のサイドメニュー下部にある「管理者メニュー」セクション、または画面上部のナビゲーションバーから各管理機能にアクセスできます。</span></div>

                <div class="help-warn"><strong><i class="fas fa-exclamation-triangle me-1"></i>ご注意：</strong>管理者権限は、システム全体に影響を及ぼす操作が可能です。設定変更の際は、影響範囲を十分にご確認のうえ実施してください。</div>
            </div>
        </div>
    </div>

    <!-- ===== 2. ユーザー管理 ===== -->
    <div class="help-section" id="sec-users">
        <div class="help-card">
            <div class="help-card-head" onclick="toggleSection(this)">
                <span class="sec-icon"><i class="fas fa-users"></i></span>
                <h2>ユーザー管理</h2>
                <i class="fas fa-chevron-down toggle-icon"></i>
            </div>
            <div class="help-card-body">
                <p>システムを利用するユーザーの追加・編集・削除を行います。管理メニューの「ユーザー管理」からアクセスしてください。</p>

                <h4>ユーザーの新規作成</h4>
                <div class="help-step"><span class="help-step-num">1</span><span class="help-step-text">管理メニューから「ユーザー管理」を開き、「新規ユーザー作成」ボタンをクリックします。</span></div>
                <div class="help-step"><span class="help-step-num">2</span><span class="help-step-text">以下の情報を入力してください。</span></div>
                <ul>
                    <li><strong>ログインID</strong> ― 半角英数字で入力してください（重複不可）</li>
                    <li><strong>氏名</strong> ― 姓・名を入力してください</li>
                    <li><strong>メールアドレス</strong> ― 通知の送信先として使用いたします</li>
                    <li><strong>パスワード</strong> ― 初期パスワードを設定してください</li>
                    <li><strong>所属組織</strong> ― 所属する部署を選択してください</li>
                    <li><strong>権限</strong> ― 「一般ユーザー」または「管理者」を選択してください</li>
                </ul>
                <div class="help-step"><span class="help-step-num">3</span><span class="help-step-text">「登録」ボタンをクリックすると、ユーザーが作成されます。</span></div>

                <h4>ユーザーの編集</h4>
                <p>ユーザー一覧から対象のユーザーの「編集」ボタンをクリックすると、ユーザー情報の変更が可能です。パスワードのリセットもこの画面から行えます。</p>

                <h4>ユーザーの削除</h4>
                <p>ユーザー一覧から対象のユーザーの「削除」ボタンをクリックしてください。削除の確認ダイアログが表示されます。</p>
                <div class="help-warn"><strong><i class="fas fa-exclamation-triangle me-1"></i>ご注意：</strong>ユーザーを削除すると、そのユーザーが作成したデータ（スケジュール、メッセージ等）の一部が参照できなくなる場合がございます。退職者のアカウントは、削除ではなく「無効化」にされることを推奨いたします。</div>

                <h4>CSV一括登録</h4>
                <p>多数のユーザーを一度に登録する場合は、CSVファイルによる一括登録が便利です。詳細は「<a href="#sec-csv-import" style="color:#c0392b;">CSV一括インポート</a>」の項目をご覧ください。</p>

                <h4>パスワードリセット</h4>
                <p>ユーザーがパスワードを忘れた場合は、管理者がユーザー編集画面からパスワードをリセットすることができます。リセット後は、新しいパスワードを対象のユーザーにお伝えください。</p>

                <div class="help-tip"><strong><i class="fas fa-lightbulb me-1"></i>ヒント：</strong>ユーザーに最初にログインしていただいた際に、パスワードを変更するようご案内されることを推奨いたします。</div>
            </div>
        </div>
    </div>

    <!-- ===== 3. 組織管理 ===== -->
    <div class="help-section" id="sec-organizations">
        <div class="help-card">
            <div class="help-card-head" onclick="toggleSection(this)">
                <span class="sec-icon"><i class="fas fa-sitemap"></i></span>
                <h2>組織管理</h2>
                <i class="fas fa-chevron-down toggle-icon"></i>
            </div>
            <div class="help-card-body">
                <p>会社の部署・チーム・課などの組織構造を管理します。組織は階層構造（ツリー構造）で管理でき、スケジュールの組織表示やアクセス権限の制御に使用されます。</p>

                <h4>組織の作成</h4>
                <div class="help-step"><span class="help-step-num">1</span><span class="help-step-text">管理メニューから「組織管理」を開き、「新規組織作成」ボタンをクリックします。</span></div>
                <div class="help-step"><span class="help-step-num">2</span><span class="help-step-text">以下の情報を入力してください。</span></div>
                <ul>
                    <li><strong>組織名</strong> ― 部署名やチーム名を入力してください</li>
                    <li><strong>親組織</strong> ― 上位の組織を選択してください（最上位の場合は「なし」）</li>
                    <li><strong>表示順</strong> ― 一覧での表示順序を指定してください</li>
                </ul>
                <div class="help-step"><span class="help-step-num">3</span><span class="help-step-text">「登録」ボタンをクリックします。</span></div>

                <h4>階層構造の例</h4>
                <div class="help-code">
株式会社サンプル
  ├── 営業部
  │     ├── 営業1課
  │     └── 営業2課
  ├── 開発部
  │     ├── フロントエンドチーム
  │     └── バックエンドチーム
  ├── 人事部
  └── 総務部
                </div>

                <h4>ユーザーの配属</h4>
                <p>ユーザーの組織への配属は、以下の2つの方法で行えます。</p>
                <ul>
                    <li><strong>ユーザー編集画面から</strong> ― ユーザーの編集画面で所属組織を選択してください</li>
                    <li><strong>組織管理画面から</strong> ― 組織の詳細画面で「メンバー追加」からユーザーを選択してください</li>
                </ul>

                <h4>組織の編集・削除</h4>
                <p>組織一覧から対象の組織の「編集」または「削除」ボタンをクリックしてください。</p>
                <div class="help-warn"><strong><i class="fas fa-exclamation-triangle me-1"></i>ご注意：</strong>組織を削除する場合、その組織に所属しているユーザーの配属が解除されます。また、下位組織がある場合は、先に下位組織を削除または移動してください。</div>
            </div>
        </div>
    </div>

    <!-- ===== 4. ワークフローテンプレート管理 ===== -->
    <div class="help-section" id="sec-workflow">
        <div class="help-card">
            <div class="help-card-head" onclick="toggleSection(this)">
                <span class="sec-icon"><i class="fas fa-project-diagram"></i></span>
                <h2>ワークフローテンプレート管理</h2>
                <i class="fas fa-chevron-down toggle-icon"></i>
            </div>
            <div class="help-card-body">
                <p>ワークフロー（申請・承認）で使用するテンプレートの作成・管理を行います。テンプレートには申請フォームの項目と承認経路を定義します。</p>

                <h4>テンプレートの作成</h4>
                <div class="help-step"><span class="help-step-num">1</span><span class="help-step-text">管理メニューから「ワークフローテンプレート」を開き、「新規テンプレート作成」ボタンをクリックします。</span></div>
                <div class="help-step"><span class="help-step-num">2</span><span class="help-step-text">テンプレート名と説明を入力してください（例：「休暇申請」「経費精算」「出張申請」など）。</span></div>

                <h4>フォーム設計</h4>
                <p>申請フォームに配置する入力項目を設計します。以下の種類の項目をご利用いただけます。</p>
                <ul>
                    <li><strong>テキスト入力</strong> ― 1行のテキスト入力欄</li>
                    <li><strong>テキストエリア</strong> ― 複数行のテキスト入力欄</li>
                    <li><strong>数値入力</strong> ― 数値の入力欄</li>
                    <li><strong>日付選択</strong> ― カレンダーから日付を選択</li>
                    <li><strong>選択肢（プルダウン）</strong> ― あらかじめ定義した選択肢から選択</li>
                    <li><strong>選択肢（ラジオボタン）</strong> ― 単一選択</li>
                    <li><strong>選択肢（チェックボックス）</strong> ― 複数選択</li>
                    <li><strong>ファイル添付</strong> ― ファイルを添付する欄</li>
                </ul>
                <p>各項目には「必須」の設定や、プレースホルダーテキストの設定が可能です。</p>

                <h4>承認経路の設定</h4>
                <p>申請が提出された後の承認の流れ（経路）を設定します。</p>
                <div class="help-step"><span class="help-step-num">1</span><span class="help-step-text">承認ステップを追加します。ステップごとに承認者を指定してください。</span></div>
                <div class="help-step"><span class="help-step-num">2</span><span class="help-step-text">承認者は「特定のユーザー」または「申請者の上長」など、柔軟に設定可能です。</span></div>
                <div class="help-step"><span class="help-step-num">3</span><span class="help-step-text">複数のステップを設定することで、段階的な承認フロー（例：課長 → 部長 → 経理部）を構築できます。</span></div>

                <h4>承認経路の例</h4>
                <div class="help-code">
【休暇申請の承認経路】
  Step 1: 直属の上長（課長）
  Step 2: 部門長（部長）

【経費精算の承認経路】
  Step 1: 直属の上長
  Step 2: 経理部担当者
  Step 3: 経理部長
                </div>

                <h4>テンプレートの有効化・無効化</h4>
                <p>作成したテンプレートは「有効」または「無効」の状態を切り替えることができます。無効化されたテンプレートは、ユーザーの申請画面に表示されません。</p>

                <div class="help-tip"><strong><i class="fas fa-lightbulb me-1"></i>ヒント：</strong>テンプレートを変更しても、既に提出済みの申請には影響しません。変更は新しく作成される申請から適用されます。</div>
            </div>
        </div>
    </div>

    <!-- ===== 5. 施設管理 ===== -->
    <div class="help-section" id="sec-facilities">
        <div class="help-card">
            <div class="help-card-head" onclick="toggleSection(this)">
                <span class="sec-icon"><i class="fas fa-building"></i></span>
                <h2>施設管理</h2>
                <i class="fas fa-chevron-down toggle-icon"></i>
            </div>
            <div class="help-card-body">
                <p>会議室やプロジェクター、社用車などの共有施設・備品の管理を行います。登録された施設は、ユーザーがスケジュールと連動して予約できるようになります。</p>

                <h4>施設の新規登録</h4>
                <div class="help-step"><span class="help-step-num">1</span><span class="help-step-text">管理メニューから「施設管理」を開き、「新規施設登録」ボタンをクリックします。</span></div>
                <div class="help-step"><span class="help-step-num">2</span><span class="help-step-text">以下の情報を入力してください。</span></div>
                <ul>
                    <li><strong>施設名</strong> ― 「第1会議室」「プロジェクターA」など</li>
                    <li><strong>説明</strong> ― 施設の詳細情報（収容人数、場所、備品など）</li>
                </ul>
                <div class="help-step"><span class="help-step-num">3</span><span class="help-step-text">「登録」ボタンをクリックします。</span></div>

                <h4>施設の編集</h4>
                <p>施設一覧から対象の施設の「編集」ボタンをクリックすると、施設情報を変更できます。</p>

                <h4>施設の削除</h4>
                <p>使用しなくなった施設は、「削除」ボタンから削除できます。</p>
                <div class="help-warn"><strong><i class="fas fa-exclamation-triangle me-1"></i>ご注意：</strong>施設を削除すると、その施設に関連する今後の予約もすべて削除されます。削除する前に、影響のある予約がないかご確認ください。</div>

                <div class="help-tip"><strong><i class="fas fa-lightbulb me-1"></i>ヒント：</strong>施設の予約状況は、施設管理画面のカレンダービューからも確認できます。重複予約を防ぐため、定期的に予約状況をご確認ください。</div>
            </div>
        </div>
    </div>

    <!-- ===== 6. システム設定 ===== -->
    <div class="help-section" id="sec-settings">
        <div class="help-card">
            <div class="help-card-head" onclick="toggleSection(this)">
                <span class="sec-icon"><i class="fas fa-cogs"></i></span>
                <h2>システム設定</h2>
                <i class="fas fa-chevron-down toggle-icon"></i>
            </div>
            <div class="help-card-body">
                <p>システム全体の基本設定を管理します。管理メニューの「システム設定」からアクセスしてください。</p>

                <h4>基本設定</h4>
                <ul>
                    <li><strong>アプリケーション名</strong> ― システムの表示名を設定します。ヘッダーやメール件名などに使用されます。</li>
                    <li><strong>会社名</strong> ― 会社名またはご所属の組織名を設定します。</li>
                    <li><strong>タイムゾーン</strong> ― システムで使用するタイムゾーンを設定します（デフォルト：Asia/Tokyo）。</li>
                </ul>

                <h4>メール設定（SMTP）</h4>
                <p>通知メールの送信に使用するSMTPサーバーの設定です。</p>
                <ul>
                    <li><strong>SMTPホスト</strong> ― メールサーバーのホスト名（例：smtp.gmail.com）</li>
                    <li><strong>SMTPポート</strong> ― ポート番号（一般的に 587 または 465）</li>
                    <li><strong>暗号化方式</strong> ― TLS または SSL</li>
                    <li><strong>SMTPユーザー名</strong> ― 認証に使用するユーザー名</li>
                    <li><strong>SMTPパスワード</strong> ― 認証に使用するパスワード</li>
                    <li><strong>送信元アドレス</strong> ― メールの送信元として表示されるアドレス</li>
                    <li><strong>送信元名</strong> ― メールの送信元として表示される名前</li>
                </ul>
                <p>設定後は「テストメール送信」ボタンで正しくメールが送信されることをご確認ください。</p>

                <h4>通知設定</h4>
                <p>システム全体の通知に関する設定です。</p>
                <ul>
                    <li><strong>メール通知</strong> ― メールによる通知の有効・無効を切り替えます</li>
                    <li><strong>ブラウザ通知</strong> ― ブラウザのプッシュ通知の有効・無効を切り替えます</li>
                    <li><strong>通知の種類</strong> ― どのイベントで通知を送信するかを設定します</li>
                </ul>

                <div class="help-info"><strong><i class="fas fa-info-circle me-1"></i>補足：</strong>ここでの設定はシステム全体のデフォルト値です。個々のユーザーは、自身のプロフィール設定で通知設定をカスタマイズすることが可能です。</div>
            </div>
        </div>
    </div>

    <!-- ===== 7. CSV一括インポート ===== -->
    <div class="help-section" id="sec-csv-import">
        <div class="help-card">
            <div class="help-card-head" onclick="toggleSection(this)">
                <span class="sec-icon"><i class="fas fa-file-csv"></i></span>
                <h2>CSV一括インポート</h2>
                <i class="fas fa-chevron-down toggle-icon"></i>
            </div>
            <div class="help-card-body">
                <p>CSVファイルを使用して、ユーザー・組織・アドレス帳のデータを一括でインポートすることができます。大量のデータを効率的に登録する際にご利用ください。</p>

                <h4>対応しているインポート種別</h4>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered" style="font-size:0.88rem;">
                        <thead class="table-light">
                            <tr><th style="width:25%;">種別</th><th>概要</th></tr>
                        </thead>
                        <tbody>
                            <tr><td><strong>ユーザー</strong></td><td>ユーザーアカウントの一括登録（ログインID、氏名、メールアドレス、パスワード等）</td></tr>
                            <tr><td><strong>組織</strong></td><td>組織情報の一括登録（組織名、親組織、表示順等）</td></tr>
                            <tr><td><strong>アドレス帳</strong></td><td>連絡先の一括登録（氏名、会社名、メールアドレス、電話番号等）</td></tr>
                        </tbody>
                    </table>
                </div>

                <h4>インポートの手順</h4>
                <div class="help-step"><span class="help-step-num">1</span><span class="help-step-text">管理メニューから「CSVインポート」を開きます。</span></div>
                <div class="help-step"><span class="help-step-num">2</span><span class="help-step-text">インポートする種別（ユーザー・組織・アドレス帳）を選択します。</span></div>
                <div class="help-step"><span class="help-step-num">3</span><span class="help-step-text">「サンプルCSVダウンロード」からテンプレートファイルをダウンロードし、フォーマットを確認してください。</span></div>
                <div class="help-step"><span class="help-step-num">4</span><span class="help-step-text">テンプレートに沿ってデータを入力し、CSVファイルを準備します。</span></div>
                <div class="help-step"><span class="help-step-num">5</span><span class="help-step-text">CSVファイルを選択し、「インポート」ボタンをクリックします。</span></div>
                <div class="help-step"><span class="help-step-num">6</span><span class="help-step-text">インポート結果が表示されます。エラーがあった行は、エラー内容とともに一覧で表示されます。</span></div>

                <h4>CSVファイルの注意事項</h4>
                <ul>
                    <li><strong>文字コード</strong> ― UTF-8（BOM付き推奨）またはShift-JISに対応しております</li>
                    <li><strong>区切り文字</strong> ― カンマ（,）区切り</li>
                    <li><strong>1行目</strong> ― ヘッダー行として扱われます（データは2行目から記載してください）</li>
                    <li><strong>必須項目</strong> ― サンプルCSVに記載されている必須項目は必ず入力してください</li>
                </ul>

                <div class="help-tip"><strong><i class="fas fa-lightbulb me-1"></i>ヒント：</strong>大量のデータをインポートする前に、少数のテストデータで動作確認を行うことをお勧めいたします。</div>
                <div class="help-warn"><strong><i class="fas fa-exclamation-triangle me-1"></i>ご注意：</strong>ユーザーのCSVインポートでは、既に同じログインIDが存在する場合、そのユーザーの情報が上書きされます。事前にバックアップを取得されることをお勧めいたします。</div>
            </div>
        </div>
    </div>

    <!-- ===== 8. セキュリティ設定 ===== -->
    <div class="help-section" id="sec-security">
        <div class="help-card">
            <div class="help-card-head" onclick="toggleSection(this)">
                <span class="sec-icon"><i class="fas fa-shield-alt"></i></span>
                <h2>セキュリティ設定と注意事項</h2>
                <i class="fas fa-chevron-down toggle-icon"></i>
            </div>
            <div class="help-card-body">
                <p>システムを安全にご運用いただくためのセキュリティ設定および注意事項についてご説明いたします。</p>

                <h4>HTTPS（SSL/TLS）の使用</h4>
                <p>通信の暗号化のため、必ず HTTPS でのご利用を推奨いたします。HTTP でのアクセスは、通信内容（ログインパスワードを含む）が平文で送信されるため、セキュリティ上のリスクがございます。</p>

                <h4>パスワードポリシー</h4>
                <p>ユーザーのパスワードについて、以下の運用を推奨いたします。</p>
                <ul>
                    <li>8文字以上の長さを設定すること</li>
                    <li>英大文字・英小文字・数字・記号を組み合わせること</li>
                    <li>定期的にパスワードを変更すること（3～6ヶ月ごとを推奨）</li>
                    <li>他のサービスと同じパスワードを使い回さないこと</li>
                </ul>

                <h4>管理者アカウントの管理</h4>
                <ul>
                    <li>管理者権限を持つアカウントは、必要最小限にしてください</li>
                    <li>管理者アカウントには、特に強度の高いパスワードを設定してください</li>
                    <li>不要になった管理者アカウントは速やかに権限を変更してください</li>
                </ul>

                <h4>設定ファイルの保護</h4>
                <ul>
                    <li><code>config/database.php</code> にはデータベースのパスワードが含まれています。ファイルのパーミッションを適切に設定してください（推奨：640）</li>
                    <li>Webサーバーの設定で、<code>config/</code> ディレクトリへの直接アクセスを禁止してください</li>
                    <li>設定ファイルをバージョン管理システム（Git 等）にコミットしないようご注意ください</li>
                </ul>

                <h4>定期的なアップデート</h4>
                <p>セキュリティパッチやバグ修正を含む最新バージョンへのアップデートを、定期的に実施してください。アップデートの手順は「<a href="<?php echo BASE_PATH; ?>/help/install#sec-upgrade" style="color:#c0392b;">インストールガイド - アップグレード手順</a>」をご参照ください。</p>

                <h4>不正アクセスの監視</h4>
                <p>以下の点を定期的にご確認いただくことを推奨いたします。</p>
                <ul>
                    <li>ログインの失敗が多発していないか（ブルートフォース攻撃の兆候）</li>
                    <li>不審なユーザーアカウントが作成されていないか</li>
                    <li>Webサーバーのアクセスログに不審なリクエストがないか</li>
                </ul>

                <div class="help-warn"><strong><i class="fas fa-exclamation-triangle me-1"></i>ご注意：</strong>セキュリティインシデントが疑われる場合は、速やかにシステムを停止し、専門家にご相談ください。</div>
            </div>
        </div>
    </div>

    <!-- ===== 9. バックアップと復元 ===== -->
    <div class="help-section" id="sec-backup">
        <div class="help-card">
            <div class="help-card-head" onclick="toggleSection(this)">
                <span class="sec-icon"><i class="fas fa-database"></i></span>
                <h2>バックアップと復元</h2>
                <i class="fas fa-chevron-down toggle-icon"></i>
            </div>
            <div class="help-card-body">
                <p>データの消失や障害に備えて、定期的なバックアップの取得を強く推奨いたします。バックアップ対象は、データベースとアップロードファイルの2つです。</p>

                <h4>データベースのバックアップ</h4>
                <p><code>mysqldump</code> コマンドを使用して、データベース全体のバックアップを取得します。</p>
                <div class="help-code">
<span class="code-comment"># データベースのバックアップ</span>
mysqldump -u root -p groupware_db &gt; backup_db_$(date +%Y%m%d_%H%M%S).sql

<span class="code-comment"># 圧縮してバックアップ</span>
mysqldump -u root -p groupware_db | gzip &gt; backup_db_$(date +%Y%m%d_%H%M%S).sql.gz
                </div>

                <h4>アップロードファイルのバックアップ</h4>
                <p><code>uploads/</code> ディレクトリにはユーザーがアップロードしたファイルが保存されています。</p>
                <div class="help-code">
<span class="code-comment"># アップロードファイルのバックアップ</span>
tar czf backup_uploads_$(date +%Y%m%d_%H%M%S).tar.gz uploads/

<span class="code-comment"># 設定ファイルもあわせてバックアップ</span>
tar czf backup_config_$(date +%Y%m%d_%H%M%S).tar.gz config/
                </div>

                <h4>自動バックアップの設定（cron）</h4>
                <p>cronジョブを設定して、定期的に自動バックアップを取得することを推奨いたします。</p>
                <div class="help-code">
<span class="code-comment"># crontab -e で以下を追加</span>
<span class="code-comment"># 毎日午前3時にデータベースをバックアップ</span>
0 3 * * * mysqldump -u groupware_user -p'password' groupware_db | gzip &gt; /backup/db_$(date +\%Y\%m\%d).sql.gz

<span class="code-comment"># 毎週日曜日の午前4時にファイルをバックアップ</span>
0 4 * * 0 tar czf /backup/uploads_$(date +\%Y\%m\%d).tar.gz /var/www/html/groupware/uploads/
                </div>

                <h4>復元手順</h4>
                <div class="help-step"><span class="help-step-num">1</span><span class="help-step-text">データベースの復元：バックアップファイルからデータベースをリストアします。</span></div>
                <div class="help-code">
<span class="code-comment"># SQL ファイルからの復元</span>
mysql -u root -p groupware_db &lt; backup_db_20260101.sql

<span class="code-comment"># 圧縮ファイルからの復元</span>
gunzip &lt; backup_db_20260101.sql.gz | mysql -u root -p groupware_db
                </div>
                <div class="help-step"><span class="help-step-num">2</span><span class="help-step-text">アップロードファイルの復元：バックアップからファイルを展開します。</span></div>
                <div class="help-code">
tar xzf backup_uploads_20260101.tar.gz -C /var/www/html/groupware/
                </div>

                <h4>バックアップの保管に関する推奨事項</h4>
                <ul>
                    <li>バックアップは、本番サーバーとは別の場所に保管してください</li>
                    <li>最低でも直近7日分のバックアップを保持することを推奨いたします</li>
                    <li>月次のバックアップは、長期保管（3ヶ月～1年）されることを推奨いたします</li>
                    <li>定期的にバックアップからの復元テストを実施し、バックアップが正常に取得できていることをご確認ください</li>
                </ul>

                <div class="help-warn"><strong><i class="fas fa-exclamation-triangle me-1"></i>ご注意：</strong>バックアップファイルにはデータベースのパスワードや個人情報が含まれている場合がございます。バックアップファイルの取り扱いには十分ご注意ください。</div>
            </div>
        </div>
    </div>

    <!-- ===== 10. 管理者向けFAQ ===== -->
    <div class="help-section" id="sec-faq">
        <div class="help-card">
            <div class="help-card-head" onclick="toggleSection(this)">
                <span class="sec-icon"><i class="fas fa-question-circle"></i></span>
                <h2>よくある管理者向けFAQ</h2>
                <i class="fas fa-chevron-down toggle-icon"></i>
            </div>
            <div class="help-card-body">

                <h4>Q. ユーザーがログインできないと報告を受けました</h4>
                <ul>
                    <li>対象ユーザーのアカウントが有効であるかご確認ください</li>
                    <li>ユーザー編集画面からパスワードをリセットし、新しいパスワードを対象者にお伝えください</li>
                    <li>ブラウザのCookieが有効になっているかご確認ください</li>
                </ul>

                <h4>Q. ユーザーの権限を変更したい</h4>
                <p>ユーザー管理画面から対象ユーザーの「編集」画面を開き、権限の項目を変更してください。変更は即時反映されます。次回のログイン時（またはページの再読み込み時）から新しい権限が適用されます。</p>

                <h4>Q. 組織を統合・分割したい</h4>
                <ul>
                    <li><strong>統合の場合</strong> ― 統合先の組織にメンバーを移動した後、不要になった組織を削除してください</li>
                    <li><strong>分割の場合</strong> ― 新しい組織を作成し、該当するメンバーの所属を変更してください</li>
                </ul>

                <h4>Q. ワークフローの承認経路を変更したい</h4>
                <p>ワークフローテンプレート管理から、対象のテンプレートの承認経路を編集してください。変更は新しく作成される申請から適用されます。処理中の申請には影響いたしません。</p>

                <h4>Q. システムの動作が遅くなった</h4>
                <ul>
                    <li>サーバーのCPU使用率やメモリ使用率をご確認ください</li>
                    <li>データベースのサイズが増大していないかご確認ください</li>
                    <li>不要なログファイルやエクスポートファイルを削除してディスク容量を確保してください</li>
                    <li>MySQL のスロークエリログを確認し、パフォーマンスの問題がないか調査してください</li>
                </ul>

                <h4>Q. メール通知が送信されない</h4>
                <ul>
                    <li>システム設定のSMTP設定が正しいかご確認ください</li>
                    <li>「テストメール送信」機能でメールが正常に送信されるかお試しください</li>
                    <li>受信側で迷惑メールとして分類されていないかご確認ください</li>
                    <li>サーバーのファイアウォールでSMTPポートがブロックされていないかご確認ください</li>
                </ul>

                <h4>Q. 退職したユーザーの扱いはどうすれば良いですか</h4>
                <p>退職者のアカウントは、即時削除するのではなく、まず「無効化」にされることを推奨いたします。これにより、過去のデータ（メッセージの送信者情報やワークフローの履歴など）の参照が引き続き可能です。一定期間経過後、問題がなければ削除を検討してください。</p>

                <h4>Q. データベースのサイズが大きくなってきました</h4>
                <ul>
                    <li>不要な通知データの削除を検討してください</li>
                    <li>古いエクスポートファイル（<code>exports/</code> ディレクトリ）を定期的に削除してください</li>
                    <li>MySQL の <code>OPTIMIZE TABLE</code> コマンドでテーブルの最適化を行ってください</li>
                </ul>

                <h4>上記で解決しない場合</h4>
                <p>問題が解決しない場合は、以下の情報をまとめて開発元にお問い合わせください。</p>
                <ul>
                    <li>PHP バージョンおよびサーバー環境</li>
                    <li>エラーメッセージの全文</li>
                    <li>問題が発生するまでの操作手順</li>
                    <li>PHP エラーログおよび Web サーバーのエラーログ</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- フッター -->
    <div style="text-align:center; padding: 28px 0 16px; color: #999; font-size: 0.82rem;">
        <p>管理についてご不明な点がございましたら、開発元にお問い合わせください。</p>
        <p><?php echo htmlspecialchars($appName); ?> 管理者マニュアル &copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($companyName); ?></p>
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
