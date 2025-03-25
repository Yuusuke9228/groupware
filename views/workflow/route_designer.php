<?php
// views/workflow/route_designer.php
$pageTitle = '承認経路設定 - ' . $template['name'];

// JavaScriptのデータ属性用にユーザーと組織のデータを準備
$usersData = json_encode($users);
$organizationsData = json_encode($organizations);
?>
<div class="container-fluid" data-page-type="route_designer">
    <input type="hidden" id="template-id" value="<?php echo $template['id']; ?>">
    <div id="users-data" data-users='<?php echo htmlspecialchars($usersData); ?>'></div>
    <div id="organizations-data" data-organizations='<?php echo htmlspecialchars($organizationsData); ?>'></div>

    <div class="row mb-4">
        <div class="col">
            <h1 class="h3"><?php echo $pageTitle; ?></h1>
        </div>
        <div class="col-auto">
            <button id="btn-add-step" class="btn btn-success me-2">
                <i class="fas fa-plus"></i> 新規ステップ追加
            </button>
            <button id="btn-save-route" class="btn btn-primary me-2">
                <i class="fas fa-save"></i> 保存
            </button>
            <a href="<?php echo BASE_PATH; ?>/workflow/templates" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> 一覧に戻る
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">承認経路設定</h5>
                </div>
                <div class="card-body">
                    <div id="route-container">
                        <!-- 承認経路がここに表示される -->
                        <div class="text-center p-5 text-muted">新規ステップを追加してください</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">承認経路の使い方</h5>
        </div>
        <div class="card-body">
            <ul>
                <li><strong>ステップ</strong>: 承認フローの段階を表します。複数の承認者を持つことができます。</li>
                <li><strong>平行承認</strong>: 有効にすると、すべての承認者が承認する必要があります。無効の場合は1人でも承認すれば次のステップに進みます。</li>
                <li><strong>代理承認</strong>: 有効にすると、指定された代理人による承認が可能になります。</li>
                <li><strong>自己承認</strong>: 有効にすると、申請者自身が承認者である場合でも承認が必要になります。無効の場合は自動的にスキップされます。</li>
                <li><strong>ドラッグ＆ドロップ</strong>: ステップの順序やステップ内の承認者の順序は、ドラッグ＆ドロップで変更できます。</li>
            </ul>
        </div>
    </div>
</div>

<!-- ステップ編集モーダル -->
<div class="modal fade" id="step-edit-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">承認者設定</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="step-edit-form">
                <input type="hidden" id="step-id" name="step-id">
                <input type="hidden" id="step-number" name="step-number">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="step-name" class="form-label">承認者名 <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="step-name" name="step-name" required>
                        <small class="form-text text-muted">例: 部門長承認、経理部承認、最終承認 など</small>
                    </div>

                    <div class="mb-3">
                        <label for="approver-type" class="form-label">承認者タイプ <span class="text-danger">*</span></label>
                        <select class="form-select" id="approver-type" name="approver-type" required>
                            <option value="user">特定のユーザー</option>
                            <option value="role">役割（権限グループ）</option>
                            <option value="organization">特定の組織</option>
                            <option value="dynamic">動的承認者（フォーム項目から取得）</option>
                        </select>
                    </div>

                    <!-- 承認者タイプ別のオプション -->
                    <div id="user-options" class="approver-type-options mb-3">
                        <label for="user-id" class="form-label">承認ユーザー <span class="text-danger">*</span></label>
                        <select class="form-select" id="user-id" name="user-id">
                            <option value="">選択してください</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['display_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div id="role-options" class="approver-type-options mb-3" style="display: none;">
                        <label for="role-id" class="form-label">役割 <span class="text-danger">*</span></label>
                        <select class="form-select" id="role-id" name="role-id">
                            <option value="">選択してください</option>
                            <option value="admin">管理者</option>
                            <option value="manager">マネージャー</option>
                            <option value="user">一般ユーザー</option>
                        </select>
                    </div>

                    <div id="organization-options" class="approver-type-options mb-3" style="display: none;">
                        <label for="organization-id" class="form-label">組織 <span class="text-danger">*</span></label>
                        <select class="form-select" id="organization-id" name="organization-id">
                            <option value="">選択してください</option>
                            <?php foreach ($organizations as $org): ?>
                                <option value="<?php echo $org['id']; ?>"><?php echo htmlspecialchars($org['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div id="dynamic-options" class="approver-type-options mb-3" style="display: none;">
                        <label for="dynamic-field-id" class="form-label">フィールドID <span class="text-danger">*</span></label>
                        <select class="form-select" id="dynamic-field-id" name="dynamic-field-id">
                            <option value="">選択してください</option>
                            <?php foreach ($formDefinitions as $field): ?>
                                <option value="<?php echo $field['field_id']; ?>"><?php echo htmlspecialchars($field['label']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-text text-muted">フォームのユーザー選択フィールドから承認者を取得します</small>
                    </div>

                    <hr>
                    <h6>承認オプション</h6>

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="allow-delegation" name="allow-delegation">
                        <label class="form-check-label" for="allow-delegation">
                            代理承認を許可する
                        </label>
                        <small class="form-text text-muted d-block">承認者が代理人を設定している場合、代理人による承認を許可します</small>
                    </div>

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="allow-self-approval" name="allow-self-approval">
                        <label class="form-check-label" for="allow-self-approval">
                            自己承認を許可する
                        </label>
                        <small class="form-text text-muted d-block">申請者自身が承認者である場合でも承認が必要になります</small>
                    </div>

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="parallel-approval" name="parallel-approval">
                        <label class="form-check-label" for="parallel-approval">
                            平行承認
                        </label>
                        <small class="form-text text-muted d-block">同じステップの全ての承認者が承認する必要があります</small>
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