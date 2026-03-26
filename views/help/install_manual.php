<?php
// views/help/install_manual.php
// インストールマニュアル
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
        <span class="text-dark fw-bold">インストールマニュアル</span>
    </div>

    <!-- ヒーローセクション -->
    <div class="manual-hero">
        <h1><i class="fas fa-download me-2"></i>インストールマニュアル</h1>
        <p><?php echo htmlspecialchars($appName); ?> の導入に必要なシステム要件とインストール手順をご案内いたします。</p>
    </div>

    <!-- 目次 -->
    <div class="manual-toc">
        <h5><i class="fas fa-list-ul me-1"></i> 目次</h5>
        <ul class="manual-toc-list">
            <li><a href="#sec-requirements"><i class="fas fa-check-circle"></i> 1. システム要件</a></li>
            <li><a href="#sec-install-steps"><i class="fas fa-list-ol"></i> 2. インストール手順</a></li>
            <li><a href="#sec-manual-install"><i class="fas fa-terminal"></i> 3. 手動インストール</a></li>
            <li><a href="#sec-permissions"><i class="fas fa-lock-open"></i> 4. ディレクトリ権限</a></li>
            <li><a href="#sec-troubleshooting"><i class="fas fa-wrench"></i> 5. トラブルシューティング</a></li>
        </ul>
    </div>

    <!-- ===== 1. システム要件 ===== -->
    <div class="manual-section" id="sec-requirements">
        <div class="manual-card">
            <div class="manual-card-head">
                <span class="sec-icon"><i class="fas fa-check-circle"></i></span>
                <h2>1. システム要件</h2>
            </div>
            <div class="manual-card-body">
                <p>本システムをご利用いただくには、以下の環境が必要です。インストールを開始される前に、サーバー環境をご確認ください。</p>

                <h4>必須要件</h4>
                <table class="table table-bordered req-table">
                    <thead>
                        <tr>
                            <th style="width: 180px;">項目</th>
                            <th>要件</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>PHP</strong></td>
                            <td>7.4 以上（PHP 8.0 以上を推奨）</td>
                        </tr>
                        <tr>
                            <td><strong>PHP 必須拡張</strong></td>
                            <td>
                                <code>pdo</code>, <code>pdo_mysql</code>, <code>mbstring</code>, <code>json</code>, <code>fileinfo</code>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>データベース</strong></td>
                            <td>MySQL 5.7 以上 / MariaDB 10.3 以上</td>
                        </tr>
                        <tr>
                            <td><strong>Web サーバー</strong></td>
                            <td>Apache 2.4 以上 または Nginx 1.14 以上</td>
                        </tr>
                    </tbody>
                </table>

                <h4>推奨設定</h4>
                <ul>
                    <li>PHP 8.0 以上のご利用を推奨いたします（パフォーマンスとセキュリティの観点から）</li>
                    <li>Apache をご利用の場合は <code>mod_rewrite</code> を有効にしてください</li>
                    <li>PHP の <code>memory_limit</code> は <strong>128M</strong> 以上を推奨いたします</li>
                    <li><code>upload_max_filesize</code> と <code>post_max_size</code> は、ファイルアップロード機能をご利用になる場合、適切なサイズに設定してください</li>
                </ul>

                <div class="manual-tip">
                    <strong><i class="fas fa-lightbulb me-1"></i>ヒント：</strong>
                    PHP の拡張モジュールが有効かどうかは、<code>php -m</code> コマンドまたは <code>phpinfo()</code> でご確認いただけます。
                </div>
            </div>
        </div>
    </div>

    <!-- ===== 2. インストール手順 ===== -->
    <div class="manual-section" id="sec-install-steps">
        <div class="manual-card">
            <div class="manual-card-head">
                <span class="sec-icon"><i class="fas fa-list-ol"></i></span>
                <h2>2. インストール手順</h2>
            </div>
            <div class="manual-card-body">
                <p>以下の手順に沿って、インストールを進めてください。Web インストーラーをご利用いただくことで、簡単にセットアップが完了いたします。</p>

                <!-- Step 1 -->
                <h4>Step 1: ファイルの配置</h4>
                <p>Git を使用する場合は、以下のコマンドでファイルを取得してください。</p>
                <div class="code-block">
                    <button class="copy-btn" onclick="copyCode(this)"><i class="fas fa-copy me-1"></i>コピー</button>
                    <pre style="margin:0;white-space:pre-wrap;">git clone https://github.com/your-repo/groupware.git /var/www/groupware</pre>
                </div>
                <p>Git をご利用にならない場合は、ダウンロードした ZIP ファイルを展開し、Web サーバーの公開ディレクトリに配置してください。</p>
                <div class="code-block">
                    <button class="copy-btn" onclick="copyCode(this)"><i class="fas fa-copy me-1"></i>コピー</button>
                    <pre style="margin:0;white-space:pre-wrap;">unzip groupware.zip -d /var/www/groupware</pre>
                </div>

                <!-- Step 2 -->
                <h4>Step 2: Web サーバーの設定</h4>
                <p><strong>Apache をご利用の場合：</strong></p>
                <p>ドキュメントルートを <code>public/</code> ディレクトリに設定してください。<code>.htaccess</code> ファイルが同梱されておりますので、<code>mod_rewrite</code> が有効であればそのままご利用いただけます。</p>
                <div class="code-block">
                    <button class="copy-btn" onclick="copyCode(this)"><i class="fas fa-copy me-1"></i>コピー</button>
                    <pre style="margin:0;white-space:pre-wrap;">&lt;VirtualHost *:80&gt;
    ServerName groupware.example.com
    DocumentRoot /var/www/groupware/public

    &lt;Directory /var/www/groupware/public&gt;
        AllowOverride All
        Require all granted
    &lt;/Directory&gt;
&lt;/VirtualHost&gt;</pre>
                </div>

                <p><strong>Nginx をご利用の場合：</strong></p>
                <div class="code-block">
                    <button class="copy-btn" onclick="copyCode(this)"><i class="fas fa-copy me-1"></i>コピー</button>
                    <pre style="margin:0;white-space:pre-wrap;">server {
    listen 80;
    server_name groupware.example.com;
    root /var/www/groupware/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\. {
        deny all;
    }
}</pre>
                </div>

                <!-- Step 3 -->
                <h4>Step 3: データベースの作成</h4>
                <p>MySQL（または MariaDB）にログインし、本システム用のデータベースとユーザーを作成してください。</p>
                <div class="code-block">
                    <button class="copy-btn" onclick="copyCode(this)"><i class="fas fa-copy me-1"></i>コピー</button>
                    <pre style="margin:0;white-space:pre-wrap;">CREATE DATABASE groupware_db CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
CREATE USER 'groupware_user'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON groupware_db.* TO 'groupware_user'@'localhost';
FLUSH PRIVILEGES;</pre>
                </div>
                <div class="manual-warn">
                    <strong><i class="fas fa-exclamation-triangle me-1"></i>注意：</strong>
                    パスワードは十分に強固なものをご設定ください。上記の <code>your_secure_password</code> はサンプルですので、必ず変更してください。
                </div>

                <!-- Step 4 -->
                <h4>Step 4: Web インストーラーの実行</h4>
                <p>ブラウザから本システムの URL にアクセスしてください。データベース設定ファイルが存在しない場合、自動的に Web インストーラーが起動いたします。</p>
                <div class="code-block">
                    <button class="copy-btn" onclick="copyCode(this)"><i class="fas fa-copy me-1"></i>コピー</button>
                    <pre style="margin:0;white-space:pre-wrap;">http://groupware.example.com/</pre>
                </div>
                <p>インストーラーは以下の 5 ステップで構成されております。</p>
                <div class="manual-step"><span class="manual-step-num">1</span><span class="manual-step-text"><strong>環境チェック</strong> — PHP バージョンや必須拡張モジュールの確認が行われます。</span></div>
                <div class="manual-step"><span class="manual-step-num">2</span><span class="manual-step-text"><strong>データベース設定</strong> — ホスト名、データベース名、ユーザー名、パスワードを入力してください。</span></div>
                <div class="manual-step"><span class="manual-step-num">3</span><span class="manual-step-text"><strong>テーブル作成</strong> — 必要なテーブルが自動的に作成されます。</span></div>
                <div class="manual-step"><span class="manual-step-num">4</span><span class="manual-step-text"><strong>管理者アカウントの作成</strong> — システム管理者のログイン ID、パスワード、氏名を設定してください。</span></div>
                <div class="manual-step"><span class="manual-step-num">5</span><span class="manual-step-text"><strong>完了</strong> — インストールが完了しますと、ログイン画面へのリンクが表示されます。</span></div>

                <div class="manual-info">
                    <strong><i class="fas fa-info-circle me-1"></i>情報：</strong>
                    インストール完了後、セキュリティのため <code>public/install.php</code> を削除するか、プロジェクトルートに <code>install.lock</code> ファイルを作成してください。これにより、インストーラーへの再アクセスが防止されます。
                </div>

                <!-- Step 5 -->
                <h4>Step 5: 初期設定</h4>
                <p>管理者アカウントでログイン後、以下の初期設定を行ってください。</p>
                <ul>
                    <li><strong>アプリケーション名</strong>の設定（管理者メニュー → システム設定）</li>
                    <li><strong>会社名</strong>の設定</li>
                    <li><strong>組織構造</strong>の登録（部署・課など）</li>
                    <li><strong>ユーザーアカウント</strong>の作成（個別登録または CSV 一括インポート）</li>
                    <li>必要に応じて<strong>施設</strong>（会議室など）の登録</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- ===== 3. 手動インストール ===== -->
    <div class="manual-section" id="sec-manual-install">
        <div class="manual-card">
            <div class="manual-card-head">
                <span class="sec-icon"><i class="fas fa-terminal"></i></span>
                <h2>3. 手動インストール（Web インストーラーを使わない場合）</h2>
            </div>
            <div class="manual-card-body">
                <p>Web インストーラーをご利用にならない場合は、以下の手順で手動インストールが可能です。</p>

                <h4>3-1. database.php の手動作成</h4>
                <p><code>config/</code> ディレクトリに <code>database.php</code> ファイルを作成してください。</p>
                <div class="code-block">
                    <button class="copy-btn" onclick="copyCode(this)"><i class="fas fa-copy me-1"></i>コピー</button>
                    <pre style="margin:0;white-space:pre-wrap;">&lt;?php
// config/database.php
return [
    'host'     =&gt; 'localhost',
    'dbname'   =&gt; 'groupware_db',
    'username' =&gt; 'groupware_user',
    'password' =&gt; 'your_secure_password',
    'charset'  =&gt; 'utf8mb4',
];</pre>
                </div>

                <h4>3-2. schema.sql のインポート</h4>
                <p><code>db/</code> ディレクトリに含まれている <code>schema.sql</code> をデータベースにインポートしてください。</p>
                <div class="code-block">
                    <button class="copy-btn" onclick="copyCode(this)"><i class="fas fa-copy me-1"></i>コピー</button>
                    <pre style="margin:0;white-space:pre-wrap;">mysql -u groupware_user -p groupware_db &lt; db/schema.sql</pre>
                </div>
                <div class="manual-tip">
                    <strong><i class="fas fa-lightbulb me-1"></i>ヒント：</strong>
                    <code>db/</code> ディレクトリに追加のアップグレード用 SQL ファイルが含まれている場合は、<code>schema.sql</code> の後に日付順でインポートしてください。
                </div>

                <h4>3-3. 管理者アカウントの作成</h4>
                <p>データベースに直接、管理者アカウントを登録してください。パスワードは PHP の <code>password_hash()</code> 関数でハッシュ化する必要があります。</p>
                <div class="code-block">
                    <button class="copy-btn" onclick="copyCode(this)"><i class="fas fa-copy me-1"></i>コピー</button>
                    <pre style="margin:0;white-space:pre-wrap;">php -r "echo password_hash('your_password', PASSWORD_DEFAULT);"</pre>
                </div>
                <p>出力されたハッシュ値を使用して、以下の SQL でユーザーを登録してください。</p>
                <div class="code-block">
                    <button class="copy-btn" onclick="copyCode(this)"><i class="fas fa-copy me-1"></i>コピー</button>
                    <pre style="margin:0;white-space:pre-wrap;">INSERT INTO users (login_id, password, display_name, email, role, status, created_at, updated_at)
VALUES ('admin', '上記のハッシュ値', '管理者', 'admin@example.com', 'admin', 'active', NOW(), NOW());</pre>
                </div>
                <div class="manual-warn">
                    <strong><i class="fas fa-exclamation-triangle me-1"></i>注意：</strong>
                    パスワードを平文のまま直接 SQL に記述しないでください。必ず <code>password_hash()</code> でハッシュ化した値をご使用ください。
                </div>
            </div>
        </div>
    </div>

    <!-- ===== 4. ディレクトリ権限 ===== -->
    <div class="manual-section" id="sec-permissions">
        <div class="manual-card">
            <div class="manual-card-head">
                <span class="sec-icon"><i class="fas fa-lock-open"></i></span>
                <h2>4. ディレクトリ権限</h2>
            </div>
            <div class="manual-card-body">
                <p>本システムが正しく動作するためには、以下のディレクトリに Web サーバーからの書き込み権限が必要です。</p>

                <table class="table table-bordered req-table">
                    <thead>
                        <tr>
                            <th style="width: 220px;">ディレクトリ</th>
                            <th>用途</th>
                            <th style="width: 100px;">権限</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>config/</code></td>
                            <td>データベース設定ファイル等の保存先（インストール時に書き込みが必要）</td>
                            <td><code>775</code></td>
                        </tr>
                        <tr>
                            <td><code>uploads/</code></td>
                            <td>ユーザーがアップロードしたファイルの保存先</td>
                            <td><code>775</code></td>
                        </tr>
                        <tr>
                            <td><code>public/uploads/</code></td>
                            <td>公開用アップロードファイルの保存先</td>
                            <td><code>775</code></td>
                        </tr>
                    </tbody>
                </table>

                <p>以下のコマンドで権限を設定できます。</p>
                <div class="code-block">
                    <button class="copy-btn" onclick="copyCode(this)"><i class="fas fa-copy me-1"></i>コピー</button>
                    <pre style="margin:0;white-space:pre-wrap;">chmod -R 775 config/ uploads/ public/uploads/
chown -R www-data:www-data config/ uploads/ public/uploads/</pre>
                </div>
                <div class="manual-tip">
                    <strong><i class="fas fa-lightbulb me-1"></i>ヒント：</strong>
                    Web サーバーの実行ユーザーは環境によって異なります。Apache の場合は <code>www-data</code>（Ubuntu）や <code>apache</code>（CentOS）、Nginx の場合は <code>nginx</code> または <code>www-data</code> が一般的です。
                </div>
            </div>
        </div>
    </div>

    <!-- ===== 5. トラブルシューティング ===== -->
    <div class="manual-section" id="sec-troubleshooting">
        <div class="manual-card">
            <div class="manual-card-head">
                <span class="sec-icon"><i class="fas fa-wrench"></i></span>
                <h2>5. トラブルシューティング</h2>
            </div>
            <div class="manual-card-body">
                <p>インストール中やご利用中に問題が発生した場合は、以下をご確認ください。</p>

                <h4>500 Internal Server Error が表示される</h4>
                <ul>
                    <li><code>.htaccess</code> ファイルが正しく配置されているかご確認ください</li>
                    <li>Apache の <code>mod_rewrite</code> が有効になっているかご確認ください</li>
                    <li>PHP のエラーログ（<code>/var/log/apache2/error.log</code> 等）をご確認ください</li>
                </ul>

                <h4>データベース接続エラーが表示される</h4>
                <ul>
                    <li><code>config/database.php</code> の接続情報（ホスト名、データベース名、ユーザー名、パスワード）が正しいかご確認ください</li>
                    <li>MySQL / MariaDB サービスが起動しているかご確認ください</li>
                    <li>指定したユーザーにデータベースへのアクセス権限が付与されているかご確認ください</li>
                </ul>

                <h4>ページが正しく表示されない（CSS / JavaScript が読み込まれない）</h4>
                <ul>
                    <li>Web サーバーのドキュメントルートが <code>public/</code> ディレクトリに設定されているかご確認ください</li>
                    <li>URL のベースパスが正しいかご確認ください</li>
                </ul>

                <h4>ファイルアップロードができない</h4>
                <ul>
                    <li><code>uploads/</code> および <code>public/uploads/</code> ディレクトリに書き込み権限があるかご確認ください</li>
                    <li>PHP の <code>upload_max_filesize</code> と <code>post_max_size</code> の値をご確認ください</li>
                </ul>

                <h4>インストーラーが表示されない</h4>
                <ul>
                    <li><code>config/database.php</code> が既に存在する場合、インストーラーは表示されません。再インストールする場合は、このファイルを削除してください</li>
                    <li><code>install.lock</code> ファイルが存在する場合も、インストーラーは無効になります</li>
                </ul>

                <h4>文字化けが発生する</h4>
                <ul>
                    <li>データベースの文字コードが <code>utf8mb4</code> であることをご確認ください</li>
                    <li>PHP の <code>mbstring</code> 拡張が有効であることをご確認ください</li>
                    <li><code>php.ini</code> で <code>default_charset = "UTF-8"</code> が設定されていることをご確認ください</li>
                </ul>

                <div class="manual-info">
                    <strong><i class="fas fa-info-circle me-1"></i>情報：</strong>
                    上記で解決しない場合は、PHP のエラーログと Web サーバーのアクセスログをご確認ください。多くの問題は、ログの情報から原因を特定することができます。
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
