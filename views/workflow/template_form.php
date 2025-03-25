<?php
// views/workflow/template_form.php
$pageTitle = isset($template) ? 'テンプレート編集 - ' . $template['name'] : '新規テンプレート作成';
$isEdit = isset($template);
?>
<div class="container-fluid" data-page-type="template_form">
    <div class="row mb-4">
        <div class="col">
            <h1 class="h3"><?php echo $pageTitle; ?></h1>
        </div>
        <div class="col-auto">
            <a href="<?php echo BASE_PATH; ?>/workflow/templates" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> 一覧に戻る
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form id="template-form" action="<?php echo BASE_PATH; ?>/api/workflow/templates<?php echo $isEdit ? '/' . $template['id'] : ''; ?>" method="post">
                <?php if ($isEdit): ?>
                    <input type="hidden" name="id" value="<?php echo $template['id']; ?>">
                <?php endif; ?>

                <div class="mb-3">
                    <label for="name" class="form-label">テンプレート名 <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="name" name="name" value="<?php echo $isEdit ? htmlspecialchars($template['name']) : ''; ?>" required>
                    <div class="invalid-feedback">テンプレート名を入力してください。</div>
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">説明</label>
                    <textarea class="form-control" id="description" name="description" rows="3"><?php echo $isEdit ? htmlspecialchars($template['description'] ?? '') : ''; ?></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">ステータス</label>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="status" id="status-active" value="active" <?php echo $isEdit && $template['status'] === 'inactive' ? '' : 'checked'; ?>>
                        <label class="form-check-label" for="status-active">
                            有効
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="status" id="status-inactive" value="inactive" <?php echo $isEdit && $template['status'] === 'inactive' ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="status-inactive">
                            無効
                        </label>
                    </div>
                </div>

                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <a href="<?php echo BASE_PATH; ?>/workflow/templates" class="btn btn-outline-secondary me-md-2">キャンセル</a>
                    <button type="submit" class="btn btn-primary">保存</button>
                </div>
            </form>
        </div>
    </div>

    <?php if ($isEdit): ?>
        <div class="row mt-4">
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">フォーム設計</h5>
                        <a href="<?php echo BASE_PATH; ?>/workflow/design-form/<?php echo $template['id']; ?>" class="btn btn-sm btn-primary">
                            <i class="fas fa-edit"></i> フォームデザイン
                        </a>
                    </div>
                    <div class="card-body">
                        <?php if (isset($formDefinitions) && count($formDefinitions) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>フィールドID</th>
                                            <th>タイプ</th>
                                            <th>ラベル</th>
                                            <th>必須</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($formDefinitions as $field): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($field['field_id']); ?></td>
                                                <td><?php echo htmlspecialchars($field['field_type']); ?></td>
                                                <td><?php echo htmlspecialchars($field['label']); ?></td>
                                                <td>
                                                    <?php if ($field['is_required']): ?>
                                                        <span class="badge bg-danger">必須</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">任意</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">フォーム定義がありません。フォームデザインボタンからフォームを作成してください。</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">承認経路設定</h5>
                        <a href="<?php echo BASE_PATH; ?>/workflow/design-route/<?php echo $template['id']; ?>" class="btn btn-sm btn-primary">
                            <i class="fas fa-route"></i> 承認経路設定
                        </a>
                    </div>
                    <div class="card-body">
                        <?php if (isset($routeDefinitions) && count($routeDefinitions) > 0): ?>
                            <?php
                            // ステップ番号でグループ化
                            $stepGroups = [];
                            foreach ($routeDefinitions as $step) {
                                $stepNumber = $step['step_number'];
                                if (!isset($stepGroups[$stepNumber])) {
                                    $stepGroups[$stepNumber] = [];
                                }
                                $stepGroups[$stepNumber][] = $step;
                            }
                            ksort($stepGroups);
                            ?>

                            <div class="list-group">
                                <?php foreach ($stepGroups as $stepNumber => $steps): ?>
                                    <div class="list-group-item">
                                        <h6>ステップ <?php echo $stepNumber; ?></h6>
                                        <ul class="list-unstyled">
                                            <?php foreach ($steps as $step): ?>
                                                <li>
                                                    <span class="badge bg-primary"><?php echo htmlspecialchars($step['approver_type']); ?></span>
                                                    <?php echo htmlspecialchars($step['step_name']); ?>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">承認経路が設定されていません。承認経路設定ボタンから経路を設定してください。</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>