<!-- スケジュールモーダル -->
<div class="modal fade" id="schedule-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="schedule-modal-title">スケジュール</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- 編集モード -->
            <div class="edit-mode">
                <form id="schedule-form" method="post">
                    <input type="hidden" id="schedule-id" name="id">
                    <input type="hidden" id="start_time" name="start_time">
                    <input type="hidden" id="end_time" name="end_time">

                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="title" class="form-label">タイトル <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="title" name="title" required>
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="all_day" name="all_day" value="1">
                                <label class="form-check-label" for="all_day">終日</label>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label for="start_time_date" class="form-label">開始日 <span class="text-danger">*</span></label>
                                <input type="text" class="form-control date-picker" id="start_time_date" name="start_time_date" required>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-3 time-picker">
                                <label for="start_time_time" class="form-label">開始時間 <span class="text-danger">*</span></label>
                                <input type="text" class="form-control time-picker" id="start_time_time" name="start_time_time" required>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-3">
                                <label for="end_time_date" class="form-label">終了日 <span class="text-danger">*</span></label>
                                <input type="text" class="form-control date-picker" id="end_time_date" name="end_time_date" required>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-3 time-picker">
                                <label for="end_time_time" class="form-label">終了時間 <span class="text-danger">*</span></label>
                                <input type="text" class="form-control time-picker" id="end_time_time" name="end_time_time" required>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="location" class="form-label">場所</label>
                            <input type="text" class="form-control" id="location" name="location">
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">説明</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="visibility" class="form-label">公開範囲</label>
                                <select class="form-select" id="visibility" name="visibility">
                                    <option value="public">全体公開</option>
                                    <option value="private">非公開（自分のみ）</option>
                                    <option value="specific">特定ユーザー/組織</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="priority" class="form-label">優先度</label>
                                <select class="form-select" id="priority" name="priority">
                                    <option value="high">高</option>
                                    <option value="normal">通常</option>
                                    <option value="low">低</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="status" class="form-label">状態</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="scheduled">予定</option>
                                    <option value="tentative">仮予定</option>
                                    <option value="cancelled">取消</option>
                                </select>
                            </div>
                        </div>

                        <!-- views/schedule/modal.php のセレクタ部分 -->
                        <div class="mb-3">
                            <label for="participants" class="form-label">参加者</label>
                            <select class="form-select participant-select" id="participants" name="participants[]" multiple>
                            </select>
                            <small class="form-text text-muted">参加者を選択してください（複数選択可）</small>
                        </div>

                        <div class="mb-3 organization-select-container" style="display: none;">
                            <label for="organizations" class="form-label">共有組織</label>
                            <select class="form-select organization-select" id="organizations" name="organizations[]" multiple>
                            </select>
                            <small class="form-text text-muted">共有する組織を選択してください（複数選択可）</small>
                        </div>

                        <!--
                        <div class="mb-3">
                            <label for="participants" class="form-label">参加者</label>
                            <select class="form-select participant-select" id="participants" name="participants[]" multiple>
                            </select>
                            <small class="form-text text-muted">参加者を選択してください（複数選択可）</small>
                        </div>

                        <div class="mb-3 organization-select-container" style="display: none;">
                            <label for="organizations" class="form-label">共有組織</label>
                            <select class="form-select organization-select" id="organizations" name="organizations[]" multiple>
                            </select>
                            <small class="form-text text-muted">共有する組織を選択してください（複数選択可）</small>
                        </div>
-->
                        <div class="mb-3">
                            <label for="repeat_type" class="form-label">繰り返し</label>
                            <select class="form-select" id="repeat_type" name="repeat_type">
                                <option value="none">繰り返しなし</option>
                                <option value="daily">毎日</option>
                                <option value="weekly">毎週</option>
                                <option value="monthly">毎月</option>
                                <option value="yearly">毎年</option>
                            </select>
                        </div>

                        <div class="mb-3 repeat-options" style="display: none;">
                            <label for="repeat_end_date" class="form-label">繰り返し終了日</label>
                            <input type="text" class="form-control date-picker" id="repeat_end_date" name="repeat_end_date">
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger delete-btn me-auto" data-id="">
                            <i class="fas fa-trash"></i> 削除
                        </button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
                        <button type="submit" class="btn btn-primary">保存</button>
                    </div>
                </form>
            </div>

            <!-- 表示モード -->
            <div class="view-mode" style="display: none;">
                <div class="modal-body">
                    <h4 id="view-title" class="mb-3"></h4>

                    <div class="row mb-3">
                        <div class="col-md-3 fw-bold">日時</div>
                        <div class="col-md-9" id="view-datetime"></div>
                    </div>

                    <div class="row mb-3 location-section">
                        <div class="col-md-3 fw-bold">場所</div>
                        <div class="col-md-9" id="view-location"></div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-3 fw-bold">作成者</div>
                        <div class="col-md-9" id="view-creator"></div>
                    </div>

                    <div class="row mb-3 description-section">
                        <div class="col-md-3 fw-bold">説明</div>
                        <div class="col-md-9" id="view-description"></div>
                    </div>

                    <div class="participants-section">
                        <h5 class="mt-4 mb-3">参加者</h5>
                        <ul class="list-group" id="view-participants">
                        </ul>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-danger delete-btn me-auto" data-id="">
                        <i class="fas fa-trash"></i> 削除
                    </button>
                    <button type="button" id="edit-schedule-btn" class="btn btn-primary">
                        <i class="fas fa-edit"></i> 編集
                    </button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">閉じる</button>
                </div>
            </div>
        </div>
    </div>
</div>