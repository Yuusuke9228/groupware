<div class="container py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-2">Administrator Manual</h1>
            <p class="text-muted mb-0">Operational handbook for secure management of TeamSpace in production.</p>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-3">
            <div class="card sticky-top" style="top: 90px;">
                <div class="card-header">Contents</div>
                <div class="list-group list-group-flush">
                    <a class="list-group-item list-group-item-action" href="#sec-users">1. User & Organization</a>
                    <a class="list-group-item list-group-item-action" href="#sec-security">2. Security Settings</a>
                    <a class="list-group-item list-group-item-action" href="#sec-notification">3. Notification & Mail</a>
                    <a class="list-group-item list-group-item-action" href="#sec-backup">4. Backup Operations</a>
                    <a class="list-group-item list-group-item-action" href="#sec-recovery">5. Recovery Procedure</a>
                    <a class="list-group-item list-group-item-action" href="#sec-checklist">6. Daily Checklist</a>
                </div>
            </div>
        </div>

        <div class="col-lg-9 d-grid gap-3">
            <section class="card" id="sec-users">
                <div class="card-body">
                    <h2 class="h5">1. User & Organization Management</h2>
                    <ul class="mb-0">
                        <li>Create and disable users from <code>System Settings &gt; User Management</code>.</li>
                        <li>Assign organization hierarchy before enabling workflow routes.</li>
                        <li>Use CSV import only with tested templates and always verify duplicates.</li>
                    </ul>
                </div>
            </section>

            <section class="card" id="sec-security">
                <div class="card-body">
                    <h2 class="h5">2. Security Settings</h2>
                    <ul>
                        <li>Manage SSO (OIDC/SAML), local admin emergency login, and SCIM from <code>System Settings &gt; Auth / PWA / SCIM</code>.</li>
                        <li>Restrict admin accounts and enforce strong password policy at identity provider side.</li>
                        <li>Keep <code>/login/local-admin</code> available for break-glass recovery when SSO is misconfigured.</li>
                    </ul>
                    <div class="alert alert-warning mb-0">
                        Never disable all administrative access paths at the same time.
                    </div>
                </div>
            </section>

            <section class="card" id="sec-notification">
                <div class="card-body">
                    <h2 class="h5">3. Notification & Mail</h2>
                    <ul>
                        <li>Configure SMTP/sendmail in <code>System Settings &gt; Mail</code>.</li>
                        <li>Use the test mail button before enabling production notifications.</li>
                        <li>Register cron for queue processing: <code>* * * * * php scripts/process_email_queue.php</code>.</li>
                    </ul>
                </div>
            </section>

            <section class="card" id="sec-backup">
                <div class="card-body">
                    <h2 class="h5">4. Backup Operations</h2>
                    <p>Run backups from <code>System Settings &gt; Auth / PWA / SCIM</code> using the <strong>Backup Management</strong> panel.</p>
                    <ul>
                        <li>One-click execution (admin only)</li>
                        <li>ZIP archive includes database dump + <code>uploads/</code> + <code>public/uploads/</code></li>
                        <li>History stores executor, timestamp, size, status, and error details</li>
                        <li>Download is served only through authenticated controller endpoint (no public direct URL)</li>
                    </ul>
                    <div class="alert alert-info mb-0">
                        Backup files are stored in a non-public directory and cannot be downloaded by path guessing.
                    </div>
                </div>
            </section>

            <section class="card" id="sec-recovery">
                <div class="card-body">
                    <h2 class="h5">5. Recovery Procedure</h2>
                    <ol class="mb-0">
                        <li>Prepare clean application files and configuration.</li>
                        <li>Restore database from SQL dump contained in backup ZIP.</li>
                        <li>Restore uploaded files to <code>uploads/</code> and <code>public/uploads/</code>.</li>
                        <li>Verify login, schedule, workflow, messages, and web database modules.</li>
                    </ol>
                </div>
            </section>

            <section class="card" id="sec-checklist">
                <div class="card-body">
                    <h2 class="h5">6. Daily Operations Checklist</h2>
                    <ul class="mb-0">
                        <li>Confirm backup status is <strong>Success</strong>.</li>
                        <li>Monitor failed email queue items and SCIM provisioning errors.</li>
                        <li>Check unresolved approvals and system notification volume.</li>
                        <li>Keep at least 7 daily backups and 3 monthly long-term backups.</li>
                    </ul>
                </div>
            </section>
        </div>
    </div>
</div>
