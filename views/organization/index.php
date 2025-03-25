<?php
// views/organization/index.php
$pageTitle = '組織管理 - GroupWare';
?>
<div class="container-fluid" data-page-type="index">
    <div class="row mb-4">
        <div class="col">
            <h1 class="h3">組織管理</h1>
        </div>
        <!--
        <div class="col-auto">
            <button id="btn-create-organization" class="btn btn-primary">
                <i class="fas fa-plus"></i> 新規組織作成
            </button>
        </div>
-->
            <div class="col-auto">
                <?php if ($this->auth->isAdmin()): ?>
                    <button id="btn-create-organization" class="btn btn-primary">
                        <i class="fas fa-plus"></i> 新規組織作成
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <div class="input-group">
                        <input type="text" id="organization-search" class="form-control" placeholder="組織を検索...">
                        <button class="btn btn-outline-secondary" type="button" id="search-button">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div id="organization-tree"></div>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">組織情報</h5>
                </div>
                <div class="card-body p-0">
                    <div class="alert alert-info m-3">
                        左側のツリーから組織を選択すると、詳細情報が表示されます。
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 組織作成モーダル -->
<div class="modal fade" id="create-organization-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">新規組織作成</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?php echo BASE_PATH; ?>/api/organizations" method="post" class="modal-form">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="name" class="form-label">組織名 <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" required autofocus>
                        <div class="invalid-feedback"></div>
                    </div>

                    <div class="mb-3">
                        <label for="code" class="form-label">組織コード <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="code" name="code" required>
                        <div class="invalid-feedback"></div>
                        <small class="form-text text-muted">英数字とアンダースコアのみ使用可能です</small>
                    </div>

                    <div class="mb-3">
                        <label for="parent_id" class="form-label">親組織</label>
                        <select class="form-select" id="parent_id" name="parent_id">
                            <option value="">なし（トップレベル）</option>
                            <!-- 組織リストはJSで動的に生成 -->
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">説明</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
                    <button type="submit" class="btn btn-primary">作成</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 組織編集モーダル -->
<div class="modal fade" id="edit-organization-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">組織編集</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" method="post" class="modal-form">
                <input type="hidden" id="id" name="id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="name" class="form-label">組織名 <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" required>
                        <div class="invalid-feedback"></div>
                    </div>

                    <div class="mb-3">
                        <label for="code" class="form-label">組織コード <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="code" name="code" required>
                        <div class="invalid-feedback"></div>
                        <small class="form-text text-muted">英数字とアンダースコアのみ使用可能です</small>
                    </div>

                    <div class="mb-3">
                        <label for="parent_id" class="form-label">親組織</label>
                        <select class="form-select" id="parent_id" name="parent_id">
                            <option value="">なし（トップレベル）</option>
                            <!-- 組織リストはJSで動的に生成 -->
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">説明</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
                    <button type="submit" class="btn btn-primary">保存</button>
                </div>
            </form>
        </div>
    </div>
</div>