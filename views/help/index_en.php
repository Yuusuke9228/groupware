<div class="container py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-2">Help Center</h1>
            <p class="text-muted mb-0">Operational guides for end users and administrators.</p>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <h2 class="h5">User Guide</h2>
                    <p class="text-muted">How to use core modules such as Schedule, Workflow, Tasks, Messages, and Daily Reports.</p>
                    <ul class="mb-0">
                        <li>Schedule and participation management</li>
                        <li>Workflow request and approval operations</li>
                        <li>LINE-style group chat with file sharing and read receipts</li>
                        <li>Task board and comments</li>
                        <li>File Sharing module with public/restricted links, address book recipients, and direct email recipients</li>
                        <li>Notifications and unread management</li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <h2 class="h5">Administrator Guide</h2>
                    <p class="text-muted">Configuration, security, and operation procedures for administrators.</p>
                    <div class="d-flex flex-wrap gap-2">
                        <a class="btn btn-primary" href="<?= BASE_PATH ?>/help/admin-manual">Open Admin Manual</a>
                        <a class="btn btn-outline-secondary" href="<?= BASE_PATH ?>/help/install-manual">Open Installation Manual</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-body">
            <h2 class="h5">Language</h2>
            <p class="mb-2">Use the language switcher in the header or footer to switch between Japanese and English.</p>
            <div class="d-flex gap-2">
                <a class="btn btn-sm btn-outline-secondary" href="<?= BASE_PATH ?>/locale/ja?redirect=<?= urlencode($_SERVER['REQUEST_URI'] ?? (BASE_PATH . '/help')) ?>">日本語</a>
                <a class="btn btn-sm btn-outline-secondary" href="<?= BASE_PATH ?>/locale/en?redirect=<?= urlencode($_SERVER['REQUEST_URI'] ?? (BASE_PATH . '/help')) ?>">English</a>
            </div>
        </div>
    </div>
</div>
