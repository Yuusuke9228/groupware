<?php
// views/help/install_guide.php
// インストールガイド
$settingModel = new \Models\Setting();
$appName = $settingModel->getAppName();
$companyName = $settingModel->getCompanyName();
$pageTitle = 'インストールガイド';
?>

<style>
    .help-container { max-width: 960px; margin: 0 auto; padding: 16px; }
    .help-hero {
        background: linear-gradient(135deg, #2d6a4f 0%, #40916c 100%);
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
        background: #f4faf6; border: 1px solid #d0e8d8; border-radius: 12px;
        padding: 20px 24px; margin-bottom: 28px;
    }
    .help-toc h5 { font-weight: 700; margin-bottom: 14px; color: #555; font-size: 0.9rem; }
    .help-toc-grid {
        display: grid; grid-template-columns: repeat(auto-fill, minmax(210px, 1fr));
        gap: 6px 16px;
    }
    .help-toc-grid a {
        display: flex; align-items: center; gap: 6px; padding: 5px 8px;
        color: #2d6a4f; text-decoration: none; font-size: 0.88rem;
        border-radius: 6px; transition: background 0.15s;
    }
    .help-toc-grid a:hover { background: #e8f5ed; text-decoration: none; }
    .help-toc-grid a i { width: 18px; text-align: center; font-size: 0.85rem; }

    .help-section { margin-bottom: 24px; }
    .help-card {
        border: 1px solid #e3e6f0; border-radius: 12px;
        box-shadow: 0 1px 4px rgba(0,0,0,0.04); overflow: hidden;
    }
    .help-card-head {
        padding: 14px 20px; display: flex; align-items: center; gap: 10px;
        cursor: pointer; user-select: none; background: #fff;
        border-bottom: 2px solid #2d6a4f; transition: background 0.15s;
    }
    .help-card-head:hover { background: #f4faf6; }
    .help-card-head .sec-icon { font-size: 1.15rem; color: #2d6a4f; width: 26px; text-align: center; }
    .help-card-head h2 { font-size: 1.05rem; font-weight: 700; margin: 0; color: #333; flex: 1; }
    .help-card-head .toggle-icon { color: #aaa; transition: transform 0.3s; }
    .help-card-head.collapsed .toggle-icon { transform: rotate(-90deg); }

    .help-card-body {
        padding: 18px 22px; font-size: 0.9rem; line-height: 1.85; color: #555;
    }
    .help-card-body h4 {
        font-size: 0.95rem; font-weight: 700; color: #444;
        margin: 18px 0 8px; padding-left: 10px;
        border-left: 3px solid #2d6a4f;
    }
    .help-card-body h4:first-child { margin-top: 0; }
    .help-card-body p { margin-bottom: 8px; }
    .help-card-body ul, .help-card-body ol { padding-left: 22px; margin-bottom: 10px; }
    .help-card-body li { margin-bottom: 4px; }

    .help-step {
        display: flex; gap: 10px; align-items: flex-start;
        padding: 8px 12px; margin: 4px 0; background: #f4faf6;
        border-radius: 8px; font-size: 0.88rem;
    }
    .help-step-num {
        background: #2d6a4f; color: #fff; width: 22px; height: 22px;
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
        padding: 8px 16px; background: #fff; border: 1px solid #d0e8d8;
        border-radius: 8px; color: #2d6a4f; text-decoration: none; font-size: 0.88rem;
        transition: all 0.15s;
    }
    .help-nav-links a:hover { background: #f4faf6; border-color: #2d6a4f; }

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
        <a href="<?php echo BASE_PATH; ?>/help/admin"><i class="fas fa-user-shield"></i> 管理者マニュアル</a>
    </div>

    <!-- ヒーローセクション -->
    <div class="help-hero">
        <h1><i class="fas fa-download me-2"></i><?php echo htmlspecialchars($appName); ?> インストールガイド</h1>
        <p>本システムのインストールおよび初期設定の手順について、詳しくご説明いたします。</p>
    </div>

    <!-- 目次 -->
    <div class="help-toc">
        <h5><i class="fas fa-list-ul me-1"></i> 目次 ― ご覧になりたい項目をクリックしてください</h5>
        <div class="help-toc-grid">
            <a href="#sec-requirements"><i class="fas fa-check-circle"></i> システム要件</a>
            <a href="#sec-web-installer"><i class="fas fa-magic"></i> Webインストーラー（推奨）</a>
            <a href="#sec-manual-install"><i class="fas fa-terminal"></i> 手動インストール</a>
            <a href="#sec-webserver"><i class="fas fa-server"></i> Webサーバー設定</a>
            <a href="#sec-permissions"><i class="fas fa-lock-open"></i> ディレクトリパーミッション</a>
            <a href="#sec-env-config"><i class="fas fa-sliders-h"></i> 環境変数による設定</a>
            <a href="#sec-mail"><i class="fas fa-envelope"></i> メール送信設定</a>
            <a href="#sec-troubleshooting"><i class="fas fa-wrench"></i> トラブルシューティング</a>
            <a href="#sec-upgrade"><i class="fas fa-arrow-up"></i> アップグレード手順</a>
        </div>
    </div>

    <!-- ===== 1. システム要件 ===== -->
    <div class="help-section" id="sec-requirements">
        <div class="help-card">
            <div class="help-card-head" onclick="toggleSection(this)">
                <span class="sec-icon"><i class="fas fa-check-circle"></i></span>
                <h2>システム要件</h2>
                <i class="fas fa-chevron-down toggle-icon"></i>
            </div>
            <div class="help-card-body">
                <p><?php echo htmlspecialchars($appName); ?> をご利用いただくには、以下の環境が必要です。インストールを開始される前に、サーバー環境をご確認ください。</p>

                <h4>サーバー要件</h4>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered" style="font-size:0.88rem;">
                        <thead class="table-light">
                            <tr><th style="width:35%;">項目</th><th>要件</th></tr>
                        </thead>
                        <tbody>
                            <tr><td><strong>PHP</strong></td><td>7.4 以上（PHP 8.0 / 8.1 / 8.2 / 8.3 推奨）</td></tr>
                            <tr><td><strong>MySQL</strong></td><td>5.7 以上（MySQL 8.0 または MariaDB 10.3 以上推奨）</td></tr>
                            <tr><td><strong>Webサーバー</strong></td><td>Apache 2.4 以上（mod_rewrite 有効）または Nginx 1.18 以上</td></tr>
                            <tr><td><strong>メモリ</strong></td><td>PHP memory_limit 128M 以上（256M 推奨）</td></tr>
                            <tr><td><strong>ディスク容量</strong></td><td>アプリケーション本体：約 50MB + データ領域</td></tr>
                        </tbody>
                    </table>
                </div>

                <h4>必須 PHP 拡張モジュール</h4>
                <ul>
                    <li><strong>pdo_mysql</strong> ― MySQL データベース接続に使用いたします</li>
                    <li><strong>mbstring</strong> ― マルチバイト文字列（日本語）の処理に必要です</li>
                    <li><strong>json</strong> ― JSON データの処理に使用いたします</li>
                    <li><strong>session</strong> ― セッション管理に使用いたします</li>
                    <li><strong>fileinfo</strong> ― ファイルアップロード時の MIME タイプ判定に使用いたします</li>
                    <li><strong>openssl</strong> ― 暗号化処理およびメール送信（SMTP over TLS）に使用いたします</li>
                    <li><strong>gd</strong> または <strong>imagick</strong> ― 画像処理（サムネイル生成等）に使用いたします</li>
                </ul>

                <h4>推奨 PHP 拡張モジュール</h4>
                <ul>
                    <li><strong>curl</strong> ― 外部 API 連携に使用いたします</li>
                    <li><strong>zip</strong> ― ファイルの圧縮・解凍に使用いたします</li>
                    <li><strong>intl</strong> ― 国際化対応に使用いたします</li>
                </ul>

                <h4>推奨ブラウザ（クライアント側）</h4>
                <ul>
                    <li>Google Chrome（最新版）</li>
                    <li>Mozilla Firefox（最新版）</li>
                    <li>Microsoft Edge（最新版）</li>
                    <li>Safari（最新版）</li>
                </ul>

                <div class="help-tip"><strong><i class="fas fa-lightbulb me-1"></i>ヒント：</strong>PHP のバージョンおよび有効な拡張モジュールは、<code>php -v</code> および <code>php -m</code> コマンドでご確認いただけます。また、<code>phpinfo()</code> を使用すると、より詳細な情報をご確認いただけます。</div>
            </div>
        </div>
    </div>

    <!-- ===== 2. Webインストーラー ===== -->
    <div class="help-section" id="sec-web-installer">
        <div class="help-card">
            <div class="help-card-head" onclick="toggleSection(this)">
                <span class="sec-icon"><i class="fas fa-magic"></i></span>
                <h2>Webインストーラーを使用したセットアップ（推奨）</h2>
                <i class="fas fa-chevron-down toggle-icon"></i>
            </div>
            <div class="help-card-body">
                <p>最も簡単なインストール方法として、Webインストーラーのご利用を推奨いたします。ブラウザの画面に沿って操作するだけで、セットアップが完了いたします。</p>

                <h4>Step 1：ソースコードの配置</h4>
                <div class="help-step"><span class="help-step-num">1</span><span class="help-step-text"><?php echo htmlspecialchars($appName); ?> のソースコード一式を、Webサーバーの公開ディレクトリ（ドキュメントルート）に配置してください。</span></div>
                <div class="help-code">
<span class="code-comment"># 例：Apache の場合</span>
/var/www/html/groupware/

<span class="code-comment"># 例：Nginx の場合</span>
/usr/share/nginx/html/groupware/
                </div>

                <h4>Step 2：インストーラーへのアクセス</h4>
                <div class="help-step"><span class="help-step-num">2</span><span class="help-step-text">ブラウザで <?php echo htmlspecialchars($appName); ?> の URL にアクセスしてください。データベース設定ファイルが存在しない場合、自動的にインストーラーが起動いたします。</span></div>
                <div class="help-code">
<span class="code-comment"># アクセス例</span>
https://your-domain.com/groupware/
                </div>
                <div class="help-info"><strong><i class="fas fa-info-circle me-1"></i>補足：</strong>インストーラーが起動しない場合は、<code>config/database.php</code> が既に存在していないかご確認ください。既にこのファイルが存在する場合は、インストーラーは起動いたしません。</div>

                <h4>Step 3：環境チェック</h4>
                <div class="help-step"><span class="help-step-num">3</span><span class="help-step-text">インストーラーが PHP バージョン、必要な拡張モジュール、ディレクトリの書き込み権限などを自動的にチェックいたします。すべての項目が「OK」と表示されていることをご確認ください。</span></div>
                <div class="help-warn"><strong><i class="fas fa-exclamation-triangle me-1"></i>ご注意：</strong>チェック項目に「NG」がある場合は、該当する項目を修正してからお進みください。PHP 拡張モジュールの追加方法は、ご利用のサーバー環境によって異なります。</div>

                <h4>Step 4：データベース設定</h4>
                <div class="help-step"><span class="help-step-num">4</span><span class="help-step-text">データベースの接続情報を入力してください。事前に MySQL でデータベースを作成しておく必要がございます。</span></div>
                <ul>
                    <li><strong>ホスト名</strong> ― 通常は <code>localhost</code>（同一サーバーの場合）</li>
                    <li><strong>データベース名</strong> ― 作成済みのデータベース名</li>
                    <li><strong>ユーザー名</strong> ― データベースへのアクセス権を持つユーザー名</li>
                    <li><strong>パスワード</strong> ― 上記ユーザーのパスワード</li>
                </ul>
                <div class="help-code">
<span class="code-comment"># MySQL でデータベースを事前に作成する例</span>
<span class="code-keyword">CREATE DATABASE</span> groupware_db <span class="code-keyword">CHARACTER SET</span> utf8mb4 <span class="code-keyword">COLLATE</span> utf8mb4_general_ci;
<span class="code-keyword">CREATE USER</span> <span class="code-string">'groupware_user'</span>@<span class="code-string">'localhost'</span> <span class="code-keyword">IDENTIFIED BY</span> <span class="code-string">'your_secure_password'</span>;
<span class="code-keyword">GRANT ALL PRIVILEGES ON</span> groupware_db.* <span class="code-keyword">TO</span> <span class="code-string">'groupware_user'</span>@<span class="code-string">'localhost'</span>;
<span class="code-keyword">FLUSH PRIVILEGES</span>;
                </div>

                <h4>Step 5：管理者アカウントの作成</h4>
                <div class="help-step"><span class="help-step-num">5</span><span class="help-step-text">システム管理者のアカウント情報を入力してください。ここで作成したアカウントが、初期の管理者ユーザーとなります。</span></div>
                <ul>
                    <li><strong>管理者名</strong> ― 管理者として表示されるお名前</li>
                    <li><strong>ログインID</strong> ― ログインに使用する ID</li>
                    <li><strong>パスワード</strong> ― 十分に強度のあるパスワードを設定してください</li>
                    <li><strong>メールアドレス</strong> ― 通知の受信等に使用いたします</li>
                </ul>

                <h4>Step 6：基本設定</h4>
                <div class="help-step"><span class="help-step-num">6</span><span class="help-step-text">アプリケーション名や会社名などの基本情報を入力してください。これらの設定は、インストール後に管理画面から変更することも可能です。</span></div>

                <h4>Step 7：インストール完了</h4>
                <div class="help-step"><span class="help-step-num">7</span><span class="help-step-text">「インストール」ボタンをクリックすると、データベースのテーブル作成および初期データの投入が自動的に行われます。完了後、ログイン画面にリダイレクトされます。</span></div>
                <div class="help-info"><strong><i class="fas fa-info-circle me-1"></i>補足：</strong>インストール完了後、セキュリティのため <code>install.lock</code> ファイルが自動的に作成されます。これにより、インストーラーへの再アクセスが防止されます。</div>
            </div>
        </div>
    </div>

    <!-- ===== 3. 手動インストール ===== -->
    <div class="help-section" id="sec-manual-install">
        <div class="help-card">
            <div class="help-card-head" onclick="toggleSection(this)">
                <span class="sec-icon"><i class="fas fa-terminal"></i></span>
                <h2>手動インストール手順</h2>
                <i class="fas fa-chevron-down toggle-icon"></i>
            </div>
            <div class="help-card-body">
                <p>コマンドラインから手動でインストールを行う場合の手順です。Webインストーラーをご利用いただけない環境や、より詳細な制御が必要な場合にご利用ください。</p>

                <h4>1. ソースコードの取得</h4>
                <p>Git をご利用いただける場合は、リポジトリからクローンしてください。</p>
                <div class="help-code">
<span class="code-comment"># Git でクローンする場合</span>
git clone https://your-repository-url.git /var/www/html/groupware

<span class="code-comment"># ZIP ファイルからの場合</span>
unzip groupware.zip -d /var/www/html/groupware
                </div>

                <h4>2. 設定ファイルの準備</h4>
                <p>サンプル設定ファイルをコピーして、環境に合わせて編集してください。</p>
                <div class="help-code">
<span class="code-comment"># データベース設定ファイルの作成</span>
cp config/database.sample.php config/database.php

<span class="code-comment"># アプリケーション設定ファイルの作成</span>
cp config/config.sample.php config/config.php
                </div>

                <h4>3. データベース設定（config/database.php）</h4>
                <p>データベース設定ファイルを編集し、接続情報を設定してください。</p>
                <div class="help-code">
<span class="code-keyword">&lt;?php</span>
<span class="code-keyword">return</span> [
    <span class="code-string">'host'</span>     =&gt; <span class="code-string">'localhost'</span>,       <span class="code-comment">// データベースホスト</span>
    <span class="code-string">'dbname'</span>   =&gt; <span class="code-string">'groupware_db'</span>,    <span class="code-comment">// データベース名</span>
    <span class="code-string">'username'</span> =&gt; <span class="code-string">'groupware_user'</span>,  <span class="code-comment">// ユーザー名</span>
    <span class="code-string">'password'</span> =&gt; <span class="code-string">'your_password'</span>,   <span class="code-comment">// パスワード</span>
    <span class="code-string">'charset'</span>  =&gt; <span class="code-string">'utf8mb4'</span>,          <span class="code-comment">// 文字コード</span>
];
                </div>

                <h4>4. アプリケーション設定（config/config.php）</h4>
                <p>アプリケーションの基本設定を編集してください。</p>
                <div class="help-code">
<span class="code-keyword">&lt;?php</span>
<span class="code-keyword">return</span> [
    <span class="code-string">'app_name'</span>     =&gt; <span class="code-string">'グループウェア'</span>,
    <span class="code-string">'company_name'</span> =&gt; <span class="code-string">'株式会社サンプル'</span>,
    <span class="code-string">'timezone'</span>     =&gt; <span class="code-string">'Asia/Tokyo'</span>,
    <span class="code-string">'locale'</span>       =&gt; <span class="code-string">'ja_JP'</span>,
    <span class="code-string">'debug'</span>        =&gt; <span class="code-keyword">false</span>,
];
                </div>

                <h4>5. データベースの作成とスキーマインポート</h4>
                <p>MySQL にログインし、データベースの作成およびテーブル構造のインポートを行ってください。</p>
                <div class="help-code">
<span class="code-comment"># データベースの作成</span>
mysql -u root -p
<span class="code-keyword">CREATE DATABASE</span> groupware_db <span class="code-keyword">CHARACTER SET</span> utf8mb4 <span class="code-keyword">COLLATE</span> utf8mb4_general_ci;

<span class="code-comment"># スキーマのインポート</span>
mysql -u root -p groupware_db &lt; database/schema.sql

<span class="code-comment"># 初期データのインポート（必要な場合）</span>
mysql -u root -p groupware_db &lt; database/seed.sql
                </div>

                <h4>6. install.lock ファイルの作成</h4>
                <p>手動インストールの場合は、インストーラーの再実行を防止するため、<code>install.lock</code> ファイルを手動で作成してください。</p>
                <div class="help-code">
touch install.lock
                </div>

                <div class="help-warn"><strong><i class="fas fa-exclamation-triangle me-1"></i>ご注意：</strong><code>install.lock</code> ファイルを作成しない場合、第三者がインストーラーにアクセスし、データベースが上書きされる可能性がございます。必ず作成してください。</div>
            </div>
        </div>
    </div>

    <!-- ===== 4. Webサーバー設定 ===== -->
    <div class="help-section" id="sec-webserver">
        <div class="help-card">
            <div class="help-card-head" onclick="toggleSection(this)">
                <span class="sec-icon"><i class="fas fa-server"></i></span>
                <h2>Webサーバー設定</h2>
                <i class="fas fa-chevron-down toggle-icon"></i>
            </div>
            <div class="help-card-body">
                <p>Webサーバーの設定例をご紹介いたします。ご利用の環境に合わせて設定してください。</p>

                <h4>Apache の設定（.htaccess）</h4>
                <p>本システムにはルートディレクトリに <code>.htaccess</code> ファイルが含まれており、すべてのリクエストを <code>public/index.php</code> にルーティングいたします。Apache の <code>mod_rewrite</code> モジュールが有効である必要がございます。</p>
                <div class="help-code">
<span class="code-comment"># mod_rewrite の有効化（Ubuntu/Debian の場合）</span>
sudo a2enmod rewrite
sudo systemctl restart apache2

<span class="code-comment"># Apache の設定ファイルで AllowOverride を有効にする</span>
&lt;Directory /var/www/html/groupware&gt;
    AllowOverride All
    Require all granted
&lt;/Directory&gt;
                </div>

                <h4>Nginx の設定例</h4>
                <p>Nginx をご利用の場合は、以下のような設定を <code>server</code> ブロックに追加してください。</p>
                <div class="help-code">
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/html/groupware/public;
    index index.php;

    <span class="code-comment"># UTF-8 設定</span>
    charset utf-8;

    <span class="code-comment"># リクエストのルーティング</span>
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    <span class="code-comment"># PHP-FPM の設定</span>
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    <span class="code-comment"># アップロードサイズの上限</span>
    client_max_body_size 64M;

    <span class="code-comment"># セキュリティ：設定ファイルへのアクセス禁止</span>
    location ~ /config/ {
        deny all;
    }

    <span class="code-comment"># 静的ファイルのキャッシュ</span>
    location ~* \.(css|js|jpg|jpeg|png|gif|ico|svg|woff|woff2|ttf)$ {
        expires 30d;
        access_log off;
    }
}
                </div>

                <h4>php.ini の推奨設定</h4>
                <div class="help-code">
<span class="code-comment">; アップロードファイルサイズの上限</span>
upload_max_filesize = 64M
post_max_size = 64M

<span class="code-comment">; メモリ上限</span>
memory_limit = 256M

<span class="code-comment">; タイムゾーン</span>
date.timezone = Asia/Tokyo

<span class="code-comment">; セッション設定</span>
session.cookie_httponly = 1
session.cookie_secure = 1
session.use_strict_mode = 1
                </div>

                <div class="help-tip"><strong><i class="fas fa-lightbulb me-1"></i>ヒント：</strong>HTTPS（SSL/TLS）でのご利用を強く推奨いたします。Let's Encrypt を使用すると、無料で SSL 証明書を取得することができます。</div>
            </div>
        </div>
    </div>

    <!-- ===== 5. ディレクトリパーミッション ===== -->
    <div class="help-section" id="sec-permissions">
        <div class="help-card">
            <div class="help-card-head" onclick="toggleSection(this)">
                <span class="sec-icon"><i class="fas fa-lock-open"></i></span>
                <h2>ディレクトリパーミッション</h2>
                <i class="fas fa-chevron-down toggle-icon"></i>
            </div>
            <div class="help-card-body">
                <p>以下のディレクトリには、Webサーバー（Apache / Nginx）からの書き込み権限が必要です。</p>

                <div class="table-responsive">
                    <table class="table table-sm table-bordered" style="font-size:0.88rem;">
                        <thead class="table-light">
                            <tr><th style="width:35%;">ディレクトリ</th><th>用途</th><th style="width:15%;">推奨権限</th></tr>
                        </thead>
                        <tbody>
                            <tr><td><code>config/</code></td><td>設定ファイルの保存（インストール時に書き込み）</td><td>755</td></tr>
                            <tr><td><code>uploads/</code></td><td>アップロードファイルの保存</td><td>775</td></tr>
                            <tr><td><code>uploads/files/</code></td><td>ファイル管理のアップロード先</td><td>775</td></tr>
                            <tr><td><code>uploads/images/</code></td><td>画像ファイルのアップロード先</td><td>775</td></tr>
                            <tr><td><code>exports/</code></td><td>エクスポートファイルの一時保存</td><td>775</td></tr>
                            <tr><td><code>logs/</code></td><td>アプリケーションログの保存</td><td>775</td></tr>
                        </tbody>
                    </table>
                </div>

                <div class="help-code">
<span class="code-comment"># 所有者の変更（Apache の場合）</span>
sudo chown -R www-data:www-data /var/www/html/groupware
sudo chmod -R 755 /var/www/html/groupware

<span class="code-comment"># 書き込みが必要なディレクトリのパーミッション設定</span>
sudo chmod -R 775 /var/www/html/groupware/uploads
sudo chmod -R 775 /var/www/html/groupware/exports
sudo chmod -R 775 /var/www/html/groupware/logs
sudo chmod -R 755 /var/www/html/groupware/config
                </div>

                <div class="help-warn"><strong><i class="fas fa-exclamation-triangle me-1"></i>ご注意：</strong>セキュリティ上、<code>config/</code> ディレクトリの権限を 777 に設定することはお避けください。インストール完了後は、<code>config/database.php</code> のパーミッションを <code>640</code> に変更されることを推奨いたします。</div>
            </div>
        </div>
    </div>

    <!-- ===== 6. 環境変数による設定 ===== -->
    <div class="help-section" id="sec-env-config">
        <div class="help-card">
            <div class="help-card-head" onclick="toggleSection(this)">
                <span class="sec-icon"><i class="fas fa-sliders-h"></i></span>
                <h2>環境変数による設定</h2>
                <i class="fas fa-chevron-down toggle-icon"></i>
            </div>
            <div class="help-card-body">
                <p>設定ファイルの代わりに、環境変数を使用してアプリケーションを設定することも可能です。Docker 環境やクラウド環境でのデプロイに便利です。</p>

                <h4>対応している環境変数</h4>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered" style="font-size:0.88rem;">
                        <thead class="table-light">
                            <tr><th style="width:35%;">環境変数名</th><th>説明</th><th style="width:25%;">デフォルト値</th></tr>
                        </thead>
                        <tbody>
                            <tr><td><code>DB_HOST</code></td><td>データベースホスト名</td><td>localhost</td></tr>
                            <tr><td><code>DB_NAME</code></td><td>データベース名</td><td>（なし）</td></tr>
                            <tr><td><code>DB_USER</code></td><td>データベースユーザー名</td><td>（なし）</td></tr>
                            <tr><td><code>DB_PASS</code></td><td>データベースパスワード</td><td>（なし）</td></tr>
                            <tr><td><code>DB_CHARSET</code></td><td>データベース文字コード</td><td>utf8mb4</td></tr>
                            <tr><td><code>APP_DEBUG</code></td><td>デバッグモード</td><td>false</td></tr>
                            <tr><td><code>APP_TIMEZONE</code></td><td>タイムゾーン</td><td>Asia/Tokyo</td></tr>
                            <tr><td><code>SMTP_HOST</code></td><td>SMTP サーバーホスト</td><td>（なし）</td></tr>
                            <tr><td><code>SMTP_PORT</code></td><td>SMTP ポート番号</td><td>587</td></tr>
                            <tr><td><code>SMTP_USER</code></td><td>SMTP 認証ユーザー</td><td>（なし）</td></tr>
                            <tr><td><code>SMTP_PASS</code></td><td>SMTP 認証パスワード</td><td>（なし）</td></tr>
                        </tbody>
                    </table>
                </div>

                <h4>Apache での設定例</h4>
                <div class="help-code">
<span class="code-comment"># .htaccess または Apache 設定ファイルに追加</span>
SetEnv DB_HOST <span class="code-string">"localhost"</span>
SetEnv DB_NAME <span class="code-string">"groupware_db"</span>
SetEnv DB_USER <span class="code-string">"groupware_user"</span>
SetEnv DB_PASS <span class="code-string">"your_secure_password"</span>
                </div>

                <h4>Docker 環境での設定例</h4>
                <div class="help-code">
<span class="code-comment"># docker-compose.yml</span>
services:
  app:
    image: php:8.2-apache
    environment:
      DB_HOST: db
      DB_NAME: groupware_db
      DB_USER: groupware_user
      DB_PASS: your_secure_password
      SMTP_HOST: mailserver
      SMTP_PORT: 587
                </div>

                <div class="help-info"><strong><i class="fas fa-info-circle me-1"></i>補足：</strong>環境変数が設定されている場合、設定ファイル（<code>config/database.php</code>）の値よりも環境変数が優先されます。</div>
            </div>
        </div>
    </div>

    <!-- ===== 7. メール送信設定 ===== -->
    <div class="help-section" id="sec-mail">
        <div class="help-card">
            <div class="help-card-head" onclick="toggleSection(this)">
                <span class="sec-icon"><i class="fas fa-envelope"></i></span>
                <h2>メール送信設定（SMTP）</h2>
                <i class="fas fa-chevron-down toggle-icon"></i>
            </div>
            <div class="help-card-body">
                <p>通知メールや各種メール送信機能をご利用いただくには、SMTP サーバーの設定が必要です。管理画面の「システム設定」からも設定可能ですが、ここでは設定ファイルでの設定方法をご説明いたします。</p>

                <h4>主要メールサービスの設定例</h4>

                <p><strong>Gmail（Google Workspace）の場合：</strong></p>
                <div class="help-code">
SMTPホスト: smtp.gmail.com
SMTPポート: 587
暗号化方式: TLS
ユーザー名: your-email@gmail.com
パスワード: アプリパスワード（2段階認証が必要です）
                </div>

                <p><strong>Microsoft 365 / Outlook の場合：</strong></p>
                <div class="help-code">
SMTPホスト: smtp.office365.com
SMTPポート: 587
暗号化方式: TLS (STARTTLS)
ユーザー名: your-email@your-domain.com
パスワード: お使いのアカウントのパスワード
                </div>

                <p><strong>Amazon SES の場合：</strong></p>
                <div class="help-code">
SMTPホスト: email-smtp.ap-northeast-1.amazonaws.com
SMTPポート: 587
暗号化方式: TLS
ユーザー名: SMTP認証情報のアクセスキー
パスワード: SMTP認証情報のシークレットキー
                </div>

                <h4>送信テスト</h4>
                <p>SMTP の設定完了後は、管理画面の「システム設定」>「メール設定」にある「テストメール送信」機能をご利用いただき、メールが正しく送信されることをご確認ください。</p>

                <div class="help-warn"><strong><i class="fas fa-exclamation-triangle me-1"></i>ご注意：</strong>Gmail をご利用の場合は、Googleアカウントの2段階認証を有効にした上で「アプリパスワード」を生成する必要がございます。通常のパスワードではメールを送信できません。</div>
            </div>
        </div>
    </div>

    <!-- ===== 8. トラブルシューティング ===== -->
    <div class="help-section" id="sec-troubleshooting">
        <div class="help-card">
            <div class="help-card-head" onclick="toggleSection(this)">
                <span class="sec-icon"><i class="fas fa-wrench"></i></span>
                <h2>トラブルシューティング</h2>
                <i class="fas fa-chevron-down toggle-icon"></i>
            </div>
            <div class="help-card-body">
                <p>インストール時によくある問題と、その解決方法についてご説明いたします。</p>

                <h4>画面が真っ白になる（500 Internal Server Error）</h4>
                <ul>
                    <li>PHP のエラーログをご確認ください。多くの場合、エラーの原因が記録されています。</li>
                    <li><code>php.ini</code> で <code>display_errors = On</code> に設定すると、画面にエラーメッセージが表示されます（本番環境では Off にしてください）。</li>
                    <li>Apache の <code>mod_rewrite</code> が有効になっているかご確認ください。</li>
                    <li><code>.htaccess</code> ファイルが正しく配置されているかご確認ください。</li>
                </ul>

                <h4>データベース接続エラー</h4>
                <ul>
                    <li><code>config/database.php</code> のホスト名、データベース名、ユーザー名、パスワードが正しいかご確認ください。</li>
                    <li>MySQL サービスが起動しているかご確認ください：<code>sudo systemctl status mysql</code></li>
                    <li>データベースユーザーに適切な権限が付与されているかご確認ください。</li>
                    <li>PHP の <code>pdo_mysql</code> 拡張モジュールが有効になっているかご確認ください。</li>
                </ul>

                <h4>ファイルアップロードができない</h4>
                <ul>
                    <li><code>uploads/</code> ディレクトリの書き込み権限をご確認ください。</li>
                    <li><code>php.ini</code> の <code>upload_max_filesize</code> および <code>post_max_size</code> の値をご確認ください。</li>
                    <li>Nginx をご利用の場合は、<code>client_max_body_size</code> の設定もあわせてご確認ください。</li>
                </ul>

                <h4>URL が正しく動作しない（404 エラー）</h4>
                <ul>
                    <li>Apache の場合：<code>mod_rewrite</code> モジュールが有効であること、および <code>AllowOverride All</code> が設定されていることをご確認ください。</li>
                    <li>Nginx の場合：<code>try_files</code> ディレクティブが正しく設定されているかご確認ください。</li>
                    <li>サブディレクトリにインストールした場合、ベースパスの設定が正しいかご確認ください。</li>
                </ul>

                <h4>メールが送信されない</h4>
                <ul>
                    <li>SMTP 設定が正しいかご確認ください。特にポート番号と暗号化方式にご注意ください。</li>
                    <li>PHP の <code>openssl</code> 拡張モジュールが有効になっているかご確認ください。</li>
                    <li>ファイアウォールで SMTP ポート（25, 465, 587）がブロックされていないかご確認ください。</li>
                    <li>Gmail の場合は「アプリパスワード」をご利用ください。</li>
                </ul>

                <h4>文字化けが発生する</h4>
                <ul>
                    <li>データベースの文字コードが <code>utf8mb4</code> に設定されているかご確認ください。</li>
                    <li><code>config/database.php</code> の <code>charset</code> が <code>utf8mb4</code> になっているかご確認ください。</li>
                    <li>PHP の <code>mbstring</code> 拡張モジュールが有効になっているかご確認ください。</li>
                </ul>

                <h4>ログの確認方法</h4>
                <div class="help-code">
<span class="code-comment"># PHP エラーログの確認</span>
tail -f /var/log/php/error.log

<span class="code-comment"># Apache エラーログの確認</span>
tail -f /var/log/apache2/error.log

<span class="code-comment"># Nginx エラーログの確認</span>
tail -f /var/log/nginx/error.log

<span class="code-comment"># アプリケーションログの確認</span>
tail -f /var/www/html/groupware/logs/app.log
                </div>
            </div>
        </div>
    </div>

    <!-- ===== 9. アップグレード手順 ===== -->
    <div class="help-section" id="sec-upgrade">
        <div class="help-card">
            <div class="help-card-head" onclick="toggleSection(this)">
                <span class="sec-icon"><i class="fas fa-arrow-up"></i></span>
                <h2>アップグレード手順</h2>
                <i class="fas fa-chevron-down toggle-icon"></i>
            </div>
            <div class="help-card-body">
                <p>新しいバージョンへのアップグレード手順についてご説明いたします。アップグレード前には必ずバックアップを取得してください。</p>

                <h4>アップグレード前の準備</h4>
                <div class="help-step"><span class="help-step-num">1</span><span class="help-step-text"><strong>データベースのバックアップ</strong>を取得してください。</span></div>
                <div class="help-code">
mysqldump -u root -p groupware_db &gt; backup_$(date +%Y%m%d).sql
                </div>
                <div class="help-step"><span class="help-step-num">2</span><span class="help-step-text"><strong>設定ファイルのバックアップ</strong>を取得してください。</span></div>
                <div class="help-code">
cp config/database.php config/database.php.bak
cp config/config.php config/config.php.bak
                </div>
                <div class="help-step"><span class="help-step-num">3</span><span class="help-step-text"><strong>アップロードファイルのバックアップ</strong>を取得してください。</span></div>
                <div class="help-code">
tar czf uploads_backup_$(date +%Y%m%d).tar.gz uploads/
                </div>

                <h4>アップグレードの実行</h4>
                <div class="help-step"><span class="help-step-num">4</span><span class="help-step-text">新しいバージョンのソースコードを配置してください（設定ファイルと uploads ディレクトリを上書きしないようご注意ください）。</span></div>
                <div class="help-code">
<span class="code-comment"># Git の場合</span>
cd /var/www/html/groupware
git pull origin main

<span class="code-comment"># ZIP ファイルの場合（設定ファイルを退避してから展開）</span>
cp config/database.php /tmp/database.php.bak
cp config/config.php /tmp/config.php.bak
<span class="code-comment"># 新バージョンを展開後</span>
cp /tmp/database.php.bak config/database.php
cp /tmp/config.php.bak config/config.php
                </div>

                <div class="help-step"><span class="help-step-num">5</span><span class="help-step-text">データベースのマイグレーション（テーブル構造の更新）が必要な場合は、リリースノートの手順に従って実行してください。</span></div>

                <div class="help-step"><span class="help-step-num">6</span><span class="help-step-text">ブラウザのキャッシュをクリアし、正常に動作することをご確認ください。</span></div>

                <div class="help-warn"><strong><i class="fas fa-exclamation-triangle me-1"></i>ご注意：</strong>アップグレード作業は、利用者の少ない時間帯（深夜や早朝など）に実施されることを推奨いたします。万が一問題が発生した場合は、バックアップから復元してください。</div>

                <div class="help-tip"><strong><i class="fas fa-lightbulb me-1"></i>ヒント：</strong>アップグレード前に、テスト環境で新バージョンの動作確認を行うことを強く推奨いたします。</div>
            </div>
        </div>
    </div>

    <!-- フッター -->
    <div style="text-align:center; padding: 28px 0 16px; color: #999; font-size: 0.82rem;">
        <p>ご不明な点がございましたら、システム管理者にお問い合わせください。</p>
        <p><?php echo htmlspecialchars($appName); ?> インストールガイド &copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($companyName); ?></p>
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
