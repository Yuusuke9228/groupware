<?php
// views/workflow/request_view.php
$pageTitle = '申請詳細: ' . $request['title'];
?>
<div class="container-fluid" data-page-type="request_view">
    <input type="hidden" id="request-id" value="<?php echo $request['id']; ?>">

    <div class="row mb-4">
        <div class="col">
            <h1 class="h3">申請詳細</h1>
        </div>
        <div class="col-auto">
            <?php if ($request['status'] === 'draft' && $isRequester): ?>
                <a href="<?php echo BASE_PATH; ?>/workflow/requests/edit/<?php echo $request['id']; ?>" class="btn btn-primary me-2">
                    <i class="fas fa-edit"></i> 編集
                </a>
            <?php endif; ?>
            <a href="<?php echo BASE_PATH; ?>/workflow/requests" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> 申請一覧に戻る
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <!-- 申請情報カード -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between">
                    <h5 class="card-title mb-0"><?php echo htmlspecialchars($request['title']); ?></h5>
                    <span>
                        <?php
                        $statusLabels = [
                            'draft' => '<span class="badge bg-secondary">下書き</span>',
                            'pending' => '<span class="badge bg-warning">承認待ち</span>',
                            'approved' => '<span class="badge bg-success">承認済み</span>',
                            'rejected' => '<span class="badge bg-danger">却下</span>',
                            'cancelled' => '<span class="badge bg-info">キャンセル</span>'
                        ];
                        echo $statusLabels[$request['status']] ?? '<span class="badge bg-secondary">不明</span>';
                        ?>
                    </span>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="row mb-2">
                            <div class="col-md-3 fw-bold">テンプレート:</div>
                            <div class="col-md-9"><?php echo htmlspecialchars($template['name']); ?></div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-md-3 fw-bold">申請者:</div>
                            <div class="col-md-9"><?php echo htmlspecialchars($request['requester_name']); ?></div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-md-3 fw-bold">申請日時:</div>
                            <div class="col-md-9"><?php echo date('Y年m月d日 H:i', strtotime($request['created_at'])); ?></div>
                        </div>
                        <?php if ($request['updated_at'] !== $request['created_at']): ?>
                            <div class="row mb-2">
                                <div class="col-md-3 fw-bold">更新日時:</div>
                                <div class="col-md-9"><?php echo date('Y年m月d日 H:i', strtotime($request['updated_at'])); ?></div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <hr>

                    <!-- フォームデータ表示 -->
                    <?php foreach ($formDefinitions as $field):
                        $fieldId = $field['field_id'];
                        $fieldValue = isset($formData[$fieldId]) ? $formData[$fieldId] : '';

                        // 見出し、非表示フィールドはスキップ
                        if ($field['field_type'] === 'hidden') continue;
                    ?>
                        <?php if ($field['field_type'] === 'heading'): ?>
                            <h4 class="mt-4 mb-3"><?php echo htmlspecialchars($field['label']); ?></h4>
                            <?php if (!empty($field['help_text'])): ?>
                                <p class="text-muted mb-3"><?php echo htmlspecialchars($field['help_text']); ?></p>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="row mb-3">
                                <div class="col-md-3 fw-bold"><?php echo htmlspecialchars($field['label']); ?>:</div>
                                <div class="col-md-9">
                                    <?php
                                    switch ($field['field_type']) {
                                        case 'textarea':
                                            echo nl2br(htmlspecialchars($fieldValue));
                                            break;

                                        case 'select':
                                        case 'radio':
                                            $options = $field['options'] ? json_decode($field['options'], true) : [];
                                            $selectedOption = array_filter($options, function ($opt) use ($fieldValue) {
                                                return $opt['value'] === $fieldValue;
                                            });
                                            echo !empty($selectedOption) ? htmlspecialchars(reset($selectedOption)['label']) : htmlspecialchars($fieldValue);
                                            break;

                                        case 'checkbox':
                                            $options = $field['options'] ? json_decode($field['options'], true) : [];
                                            $checkedValues = is_array($fieldValue) ? $fieldValue : ($fieldValue ? [$fieldValue] : []);

                                            if (!empty($checkedValues)) {
                                                echo '<ul class="mb-0">';
                                                foreach ($checkedValues as $value) {
                                                    $selectedOption = array_filter($options, function ($opt) use ($value) {
                                                        return $opt['value'] === $value;
                                                    });
                                                    $label = !empty($selectedOption) ? reset($selectedOption)['label'] : $value;
                                                    echo '<li>' . htmlspecialchars($label) . '</li>';
                                                }
                                                echo '</ul>';
                                            } else {
                                                echo '<span class="text-muted">選択なし</span>';
                                            }
                                            break;

                                        case 'file':
                                            if (isset($attachments[$fieldId])) {
                                                $attachment = $attachments[$fieldId];
                                                echo '<a href="' . BASE_PATH . '/' . $attachment['file_path'] . '" target="_blank" class="btn btn-sm btn-outline-primary">';
                                                echo '<i class="fas fa-download"></i> ' . htmlspecialchars($attachment['file_name']);
                                                echo '</a>';
                                            } else {
                                                echo '<span class="text-muted">添付なし</span>';
                                            }
                                            break;

                                        default:
                                            echo htmlspecialchars($fieldValue ?: '未入力');
                                    }
                                    ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>

                    <hr>

                    <!-- 操作ボタン -->
                    <div class="d-flex justify-content-between mt-4">
                        <div>
                            <?php if ($isRequester && ($request['status'] === 'draft' || $request['status'] === 'pending')): ?>
                                <button type="button" class="btn btn-danger me-2" id="btn-cancel-request">
                                    <i class="fas fa-times"></i> 申請をキャンセル
                                </button>
                            <?php endif; ?>
                        </div>

                        <div>
                            <button type="button" class="btn btn-outline-secondary me-2" id="btn-export-pdf">
                                <i class="fas fa-file-pdf"></i> PDFエクスポート
                            </button>
                            <button type="button" class="btn btn-outline-secondary" id="btn-export-csv">
                                <i class="fas fa-file-csv"></i> CSVエクスポート
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- 承認状況カード -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">承認状況</h5>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($approvals)): ?>
                        <div class="p-3 text-center text-muted">
                            承認履歴はありません
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php
                            $currentStep = 0;
                            foreach ($approvals as $approval):
                                if ($currentStep != $approval['step_number']):
                                    $currentStep = $approval['step_number'];
                            ?>
                                    <div class="list-group-item bg-light">
                                        <strong>ステップ <?php echo $approval['step_number']; ?></strong>
                                    </div>
                                <?php endif; ?>
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <?php echo htmlspecialchars($approval['approver_name']); ?>
                                            <?php if ($approval['delegate_id']): ?>
                                                <span class="text-muted">（代理: <?php echo htmlspecialchars($approval['delegate_name']); ?>）</span>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <?php
                                            $statusLabels = [
                                                'pending' => '<span class="badge bg-warning">承認待ち</span>',
                                                'approved' => '<span class="badge bg-success">承認</span>',
                                                'rejected' => '<span class="badge bg-danger">却下</span>',
                                                'skipped' => '<span class="badge bg-secondary">スキップ</span>'
                                            ];
                                            echo $statusLabels[$approval['status']] ?? '';
                                            ?>
                                            <?php if ($approval['acted_at']): ?>
                                                <small class="ms-2 text-muted"><?php echo date('m/d H:i', strtotime($approval['acted_at'])); ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php if (!empty($approval['comment'])): ?>
                                        <div class="mt-2">
                                            <small class="text-muted"><?php echo nl2br(htmlspecialchars($approval['comment'])); ?></small>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <!-- 承認アクションフォーム -->
                    <?php if (isset($currentApproval) && $currentApproval): ?>
                        <div class="p-3 border-top">
                            <form id="approval-form" action="<?php echo BASE_PATH; ?>/api/workflow/requests/<?php echo $request['id']; ?>/approve" method="post">
                                <input type="hidden" name="action" id="action" value="">

                                <div class="mb-3">
                                    <label for="comment" class="form-label">コメント</label>
                                    <textarea class="form-control" id="comment" name="comment" rows="3"></textarea>
                                </div>

                                <div class="d-flex justify-content-between">
                                    <button type="button" class="btn btn-danger" id="btn-reject">
                                        <i class="fas fa-times"></i> 却下
                                    </button>
                                    <button type="button" class="btn btn-success" id="btn-approve">
                                        <i class="fas fa-check"></i> 承認
                                    </button>
                                </div>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- コメントカード -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">コメント</h5>
                </div>
                <div class="card-body">
                    <div id="comments-container">
                        <?php if (empty($comments)): ?>
                            <p class="text-muted">コメントはありません</p>
                        <?php else: ?>
                            <?php foreach ($comments as $comment): ?>
                                <div class="card mb-2">
                                    <div class="card-header d-flex justify-content-between">
                                        <span><?php echo htmlspecialchars($comment['user_name']); ?></span>
                                        <small><?php echo date('Y/m/d H:i', strtotime($comment['created_at'])); ?></small>
                                    </div>
                                    <div class="card-body">
                                        <p class="card-text"><?php echo nl2br(htmlspecialchars($comment['comment'])); ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <hr>

                    <div class="mt-3">
                        <label for="comment" class="form-label">新しいコメント</label>
                        <textarea class="form-control mb-2" id="comment" rows="2"></textarea>
                        <button type="button" class="btn btn-primary" id="btn-add-comment">
                            <i class="fas fa-paper-plane"></i> 送信
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>