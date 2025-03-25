<?php
// views/workflow/request_form.php
$pageTitle = isset($request) ? '申請編集 - ' . $request['title'] : '新規申請 - ' . $template['name'];
$isEdit = isset($request);
?>
<div class="container-fluid" data-page-type="request_form">
    <div class="row mb-4">
        <div class="col">
            <h1 class="h3"><?php echo $isEdit ? '申請編集' : '新規申請作成'; ?></h1>
        </div>
        <div class="col-auto">
            <a href="<?php echo BASE_PATH; ?>/workflow/requests" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> 申請一覧に戻る
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="card-title"><?php echo htmlspecialchars($template['name']); ?></h5>
        </div>
        <div class="card-body">
            <form action="<?php echo BASE_PATH; ?>/api/workflow/requests<?php echo $isEdit ? '/' . $request['id'] : ''; ?>" method="post" enctype="multipart/form-data">
                <?php if ($isEdit): ?>
                    <input type="hidden" name="id" value="<?php echo $request['id']; ?>">
                <?php endif; ?>
                <input type="hidden" name="template_id" value="<?php echo $template['id']; ?>">
                <input type="hidden" name="status" id="status" value="draft">

                <div class="mb-3">
                    <label for="title" class="form-label">タイトル <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="title" name="title" value="<?php echo $isEdit ? htmlspecialchars($request['title']) : ''; ?>" required>
                    <div class="invalid-feedback">タイトルを入力してください</div>
                </div>

                <hr>

                <!-- フォームフィールド -->
                <?php foreach ($formDefinitions as $field):
                    $fieldId = $field['field_id'];
                    $fieldValue = $isEdit && isset($formData[$fieldId]) ? $formData[$fieldId] : '';
                    $requiredMark = $field['is_required'] ? '<span class="text-danger">*</span>' : '';
                    $requiredClass = $field['is_required'] ? 'required-field' : '';
                    $requiredAttr = $field['is_required'] ? 'required' : '';

                    switch ($field['field_type']):
                        case 'text': ?>
                            <div class="mb-3">
                                <label for="<?php echo $fieldId; ?>" class="form-label"><?php echo htmlspecialchars($field['label']); ?> <?php echo $requiredMark; ?></label>
                                <input type="text" class="form-control <?php echo $requiredClass; ?>" id="<?php echo $fieldId; ?>" name="form_data[<?php echo $fieldId; ?>]" value="<?php echo htmlspecialchars($fieldValue); ?>" placeholder="<?php echo htmlspecialchars($field['placeholder']); ?>" <?php echo $requiredAttr; ?>>
                                <?php if (!empty($field['help_text'])): ?>
                                    <small class="form-text text-muted"><?php echo htmlspecialchars($field['help_text']); ?></small>
                                <?php endif; ?>
                            </div>
                        <?php break;
                        case 'textarea': ?>
                            <div class="mb-3">
                                <label for="<?php echo $fieldId; ?>" class="form-label"><?php echo htmlspecialchars($field['label']); ?> <?php echo $requiredMark; ?></label>
                                <textarea class="form-control <?php echo $requiredClass; ?>" id="<?php echo $fieldId; ?>" name="form_data[<?php echo $fieldId; ?>]" placeholder="<?php echo htmlspecialchars($field['placeholder']); ?>" rows="3" <?php echo $requiredAttr; ?>><?php echo htmlspecialchars($fieldValue); ?></textarea>
                                <?php if (!empty($field['help_text'])): ?>
                                    <small class="form-text text-muted"><?php echo htmlspecialchars($field['help_text']); ?></small>
                                <?php endif; ?>
                            </div>
                        <?php break;
                        case 'select':
                            $options = $field['options'] ? json_decode($field['options'], true) : [];
                        ?>
                            <div class="mb-3">
                                <label for="<?php echo $fieldId; ?>" class="form-label"><?php echo htmlspecialchars($field['label']); ?> <?php echo $requiredMark; ?></label>
                                <select class="form-select <?php echo $requiredClass; ?>" id="<?php echo $fieldId; ?>" name="form_data[<?php echo $fieldId; ?>]" <?php echo $requiredAttr; ?>>
                                    <option value=""><?php echo $field['placeholder'] ?: '選択してください'; ?></option>
                                    <?php foreach ($options as $option): ?>
                                        <option value="<?php echo htmlspecialchars($option['value']); ?>" <?php echo $fieldValue === $option['value'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($option['label']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (!empty($field['help_text'])): ?>
                                    <small class="form-text text-muted"><?php echo htmlspecialchars($field['help_text']); ?></small>
                                <?php endif; ?>
                            </div>
                        <?php break;
                        case 'radio':
                            $options = $field['options'] ? json_decode($field['options'], true) : [];
                        ?>
                            <div class="mb-3">
                                <label class="form-label"><?php echo htmlspecialchars($field['label']); ?> <?php echo $requiredMark; ?></label>
                                <?php foreach ($options as $index => $option): ?>
                                    <div class="form-check">
                                        <input class="form-check-input <?php echo $requiredClass; ?>" type="radio" name="form_data[<?php echo $fieldId; ?>]" id="<?php echo $fieldId . '_' . $index; ?>" value="<?php echo htmlspecialchars($option['value']); ?>" <?php echo $fieldValue === $option['value'] ? 'checked' : ''; ?> <?php echo $requiredAttr; ?>>
                                        <label class="form-check-label" for="<?php echo $fieldId . '_' . $index; ?>"><?php echo htmlspecialchars($option['label']); ?></label>
                                    </div>
                                <?php endforeach; ?>
                                <?php if (!empty($field['help_text'])): ?>
                                    <small class="form-text text-muted"><?php echo htmlspecialchars($field['help_text']); ?></small>
                                <?php endif; ?>
                            </div>
                        <?php break;
                        case 'checkbox':
                            $options = $field['options'] ? json_decode($field['options'], true) : [];
                            $checkedValues = is_array($fieldValue) ? $fieldValue : ($fieldValue ? [$fieldValue] : []);
                        ?>
                            <div class="mb-3">
                                <label class="form-label"><?php echo htmlspecialchars($field['label']); ?> <?php echo $requiredMark; ?></label>
                                <?php foreach ($options as $index => $option): ?>
                                    <div class="form-check">
                                        <input class="form-check-input <?php echo $requiredClass; ?>" type="checkbox" name="form_data[<?php echo $fieldId; ?>][]" id="<?php echo $fieldId . '_' . $index; ?>" value="<?php echo htmlspecialchars($option['value']); ?>" <?php echo in_array($option['value'], $checkedValues) ? 'checked' : ''; ?> <?php echo $requiredAttr; ?>>
                                        <label class="form-check-label" for="<?php echo $fieldId . '_' . $index; ?>"><?php echo htmlspecialchars($option['label']); ?></label>
                                    </div>
                                <?php endforeach; ?>
                                <?php if (!empty($field['help_text'])): ?>
                                    <small class="form-text text-muted"><?php echo htmlspecialchars($field['help_text']); ?></small>
                                <?php endif; ?>
                            </div>
                        <?php break;
                        case 'date': ?>
                            <div class="mb-3">
                                <label for="<?php echo $fieldId; ?>" class="form-label"><?php echo htmlspecialchars($field['label']); ?> <?php echo $requiredMark; ?></label>
                                <input type="date" class="form-control date-picker <?php echo $requiredClass; ?>" id="<?php echo $fieldId; ?>" name="form_data[<?php echo $fieldId; ?>]" value="<?php echo htmlspecialchars($fieldValue); ?>" <?php echo $requiredAttr; ?>>
                                <?php if (!empty($field['help_text'])): ?>
                                    <small class="form-text text-muted"><?php echo htmlspecialchars($field['help_text']); ?></small>
                                <?php endif; ?>
                            </div>
                        <?php break;
                        case 'number': ?>
                            <div class="mb-3">
                                <label for="<?php echo $fieldId; ?>" class="form-label"><?php echo htmlspecialchars($field['label']); ?> <?php echo $requiredMark; ?></label>
                                <input type="number" class="form-control <?php echo $requiredClass; ?>" id="<?php echo $fieldId; ?>" name="form_data[<?php echo $fieldId; ?>]" value="<?php echo htmlspecialchars($fieldValue); ?>" placeholder="<?php echo htmlspecialchars($field['placeholder']); ?>" <?php echo $requiredAttr; ?>>
                                <?php if (!empty($field['help_text'])): ?>
                                    <small class="form-text text-muted"><?php echo htmlspecialchars($field['help_text']); ?></small>
                                <?php endif; ?>
                            </div>
                        <?php break;
                        case 'file': ?>
                            <div class="mb-3">
                                <label for="<?php echo $fieldId; ?>" class="form-label"><?php echo htmlspecialchars($field['label']); ?> <?php echo $requiredMark; ?></label>
                                <input type="file" class="form-control <?php echo $requiredClass; ?>" id="<?php echo $fieldId; ?>" name="<?php echo $fieldId; ?>" <?php echo $requiredAttr; ?>>
                                <?php if (!empty($field['help_text'])): ?>
                                    <small class="form-text text-muted"><?php echo htmlspecialchars($field['help_text']); ?></small>
                                <?php endif; ?>
                                <?php if ($isEdit && isset($attachments[$fieldId])): ?>
                                    <div class="mt-2">
                                        <strong>現在のファイル:</strong> <?php echo htmlspecialchars($attachments[$fieldId]['file_name']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php break;
                        case 'heading': ?>
                            <h4 class="mt-4 mb-3"><?php echo htmlspecialchars($field['label']); ?></h4>
                            <?php if (!empty($field['help_text'])): ?>
                                <p class="text-muted mb-3"><?php echo htmlspecialchars($field['help_text']); ?></p>
                            <?php endif; ?>
                        <?php break;
                        case 'hidden': ?>
                            <input type="hidden" id="<?php echo $fieldId; ?>" name="form_data[<?php echo $fieldId; ?>]" value="<?php echo htmlspecialchars($fieldValue); ?>">
                    <?php break;
                    endswitch; ?>
                <?php endforeach; ?>

                <hr class="mt-5">
                <div class="d-flex justify-content-between">
                    <button type="button" class="btn btn-secondary" onclick="history.back()">キャンセル</button>
                    <div class="btn-group">
                        <button type="submit" class="btn btn-outline-primary" id="btn-save-draft">下書き保存</button>
                        <button type="submit" class="btn btn-primary" id="btn-submit-request">申請する</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>