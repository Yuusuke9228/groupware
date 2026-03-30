<div class="container py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-2">Installation Manual</h1>
            <p class="text-muted mb-0">Quick setup for PHP-based TeamSpace Groupware.</p>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <h2 class="h5">Requirements</h2>
            <ul class="mb-0">
                <li>PHP 8.1+</li>
                <li>MySQL 5.7+</li>
                <li>Apache with rewrite or Nginx</li>
                <li>Composer 2.x</li>
            </ul>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <h2 class="h5">Install Steps</h2>
<pre class="mb-0"><code>git clone https://github.com/Yuusuke9228/groupware.git
cd groupware
composer install
cp config/database_sample.php config/database.php
cp config/config_sample.php config/config.php
mysql -u root -p -e "CREATE DATABASE groupware CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"
mysql -u root -p groupware &lt; db/schema.sql</code></pre>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <h2 class="h5">Post-Install</h2>
            <ul class="mb-0">
                <li>Set document root to <code>public/</code>.</li>
                <li>Log in with initial admin account and update password immediately.</li>
                <li>Configure mail, notification, and security settings from System Settings.</li>
                <li>Set cron jobs for queue and automation scripts.</li>
            </ul>
        </div>
    </div>

    <div class="alert alert-warning">
        Before any upgrade, take a full backup from <strong>System Settings &gt; Backup Management</strong>.
    </div>
</div>
