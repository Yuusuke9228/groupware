/**
 * GroupWare - ワークフロー承認経路デザイナーJS
 */

const RouteDesigner = {
    // 承認経路定義リスト
    routeDefinitions: [],

    // ステップカウンター
    stepCounter: 0,

    // 初期化
    init: function () {
        this.templateId = $('#template-id').val();

        // 既存の承認経路定義を読み込む
        this.loadRouteDefinitions();

        // イベントハンドラの設定
        this.setupEventHandlers();
    },

    // 承認経路定義の読み込み
    loadRouteDefinitions: function () {
        // APIから承認経路定義を取得
        App.apiGet(`/workflow/templates/${this.templateId}/route`)
            .then(response => {
                if (response.success) {
                    this.routeDefinitions = response.data.route_definitions || [];

                    // ステップカウンターを初期化（既存の最大値より大きい値に）
                    this.updateStepCounter();

                    // 既存のステップを表示
                    this.renderExistingSteps();
                } else {
                    App.showNotification('承認経路定義の読み込みに失敗しました', 'error');
                }
            })
            .catch(error => {
                console.error('Failed to load route definitions:', error);
                App.showNotification('承認経路定義の読み込みに失敗しました', 'error');
            });
    },

    // ステップカウンターの更新
    updateStepCounter: function () {
        if (this.routeDefinitions.length > 0) {
            // 最大のステップ番号を取得
            let maxStepNumber = 0;

            this.routeDefinitions.forEach(step => {
                if (step.step_number > maxStepNumber) {
                    maxStepNumber = step.step_number;
                }
            });

            this.stepCounter = maxStepNumber + 1;
        } else {
            this.stepCounter = 1;
        }
    },

    // 既存のステップを表示
    renderExistingSteps: function () {
        const container = $('#route-container');
        container.empty();

        if (this.routeDefinitions.length > 0) {
            // ステップ番号でグループ化
            const stepGroups = {};

            this.routeDefinitions.forEach(step => {
                const stepNumber = step.step_number;

                if (!stepGroups[stepNumber]) {
                    stepGroups[stepNumber] = [];
                }

                stepGroups[stepNumber].push(step);
            });

            // ステップ番号順に並べる
            const sortedStepNumbers = Object.keys(stepGroups).map(Number).sort((a, b) => a - b);

            sortedStepNumbers.forEach(stepNumber => {
                const steps = stepGroups[stepNumber];

                // ステップのHTMLを生成
                const stepHtml = this.createStepHtml(stepNumber, steps);
                container.append(stepHtml);
            });

            // ステップのイベントを再設定
            this.setupStepEvents();
        } else {
            container.html('<div class="text-center p-5 text-muted">新規ステップを追加してください</div>');
        }
    },

    // イベントハンドラの設定
    setupEventHandlers: function () {
        // 承認経路保存ボタン
        $('#btn-save-route').on('click', () => {
            this.saveRouteDefinitions();
        });

        // 新規ステップ追加ボタン
        $('#btn-add-step').on('click', () => {
            this.addNewStep();
        });

        // ステップのイベント設定
        this.setupStepEvents();
    },

    // ステップのイベント設定
    setupStepEvents: function () {
        // 編集ボタンクリック
        $('.btn-edit-step').off('click').on('click', function (e) {
            e.preventDefault();
            e.stopPropagation();

            const stepId = $(this).closest('.route-step').data('step-id');
            RouteDesigner.showStepEditModal(stepId);
        });

        // 削除ボタンクリック
        $('.btn-delete-step').off('click').on('click', function (e) {
            e.preventDefault();
            e.stopPropagation();

            const stepId = $(this).closest('.route-step').data('step-id');
            RouteDesigner.deleteStep(stepId);
        });

        // 承認者追加ボタンクリック
        $('.btn-add-approver').off('click').on('click', function (e) {
            e.preventDefault();
            e.stopPropagation();

            const stepNumber = $(this).closest('.route-step-group').data('step-number');
            RouteDesigner.showApproverAddModal(stepNumber);
        });

        // 承認グループの並び替えを有効化
        $('#route-container').sortable({
            items: '.route-step-group',
            handle: '.step-group-header',
            placeholder: 'route-step-group-placeholder',
            update: function (event, ui) {
                RouteDesigner.updateStepOrder();
            }
        });

        // 承認者の並び替えを有効化
        $('.approver-list').sortable({
            items: '.route-step',
            handle: '.step-drag-handle',
            placeholder: 'route-step-placeholder',
            update: function (event, ui) {
                RouteDesigner.updateApproverOrder($(this).closest('.route-step-group').data('step-number'));
            }
        });
    },

    // ステップのHTMLを生成
    createStepHtml: function (stepNumber, steps) {
        // ステップグループのヘッダー
        let html = `
            <div class="route-step-group card mb-4" data-step-number="${stepNumber}">
                <div class="card-header d-flex justify-content-between align-items-center step-group-header">
                    <h5 class="mb-0">ステップ ${stepNumber}</h5>
                    <div class="btn-group">
                        <button type="button" class="btn btn-sm btn-success btn-add-approver">
                            <i class="fas fa-plus"></i> 承認者追加
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="approver-list">
        `;

        // 各承認者のHTMLを追加
        steps.forEach(step => {
            html += this.createApproverHtml(step);
        });

        // ステップグループのフッター
        html += `
                    </div>
                </div>
            </div>
        `;

        return html;
    },

    // 承認者のHTMLを生成
    createApproverHtml: function (step) {
        const approverTypeLabels = {
            'user': 'ユーザー',
            'role': '役割',
            'organization': '組織',
            'dynamic': '動的承認者'
        };

        const approverTypeText = approverTypeLabels[step.approver_type] || step.approver_type;

        // 承認者の詳細情報を取得
        let approverDetail = '';
        switch (step.approver_type) {
            case 'user':
                approverDetail = this.getUserName(step.approver_id);
                break;
            case 'role':
                approverDetail = this.getRoleName(step.approver_id);
                break;
            case 'organization':
                approverDetail = this.getOrganizationName(step.approver_id);
                break;
            case 'dynamic':
                approverDetail = 'フィールド: ' + step.dynamic_approver_field_id;
                break;
        }

        // 承認オプションの表示
        const options = [];
        if (step.allow_delegation) options.push('代理承認許可');
        if (step.allow_self_approval) options.push('自己承認許可');
        if (step.parallel_approval) options.push('平行承認');

        const optionsText = options.length > 0 ? `<small class="text-muted">${options.join(', ')}</small>` : '';

        // 承認者のHTMLを生成
        return `
            <div class="route-step card mb-2" data-step-id="${step.id}">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <i class="fas fa-grip-vertical step-drag-handle me-2"></i>
                        <span class="badge bg-primary">${approverTypeText}</span>
                        <span>${step.step_name}</span>
                    </div>
                    <div class="btn-group">
                        <button type="button" class="btn btn-sm btn-primary btn-edit-step">
                            <i class="fas fa-edit"></i> 編集
                        </button>
                        <button type="button" class="btn btn-sm btn-danger btn-delete-step">
                            <i class="fas fa-trash"></i> 削除
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <p>${approverDetail}</p>
                    ${optionsText}
                </div>
            </div>
        `;
    },

    // ユーザー名を取得
    getUserName: function (userId) {
        const user = $('#users-data').data('users').find(u => u.id == userId);
        return user ? user.display_name : `ユーザーID: ${userId}`;
    },

    // 役割名を取得
    getRoleName: function (roleId) {
        const roles = {
            'admin': '管理者',
            'manager': 'マネージャー',
            'user': '一般ユーザー'
        };
        return roles[roleId] || `役割ID: ${roleId}`;
    },

    // 組織名を取得
    getOrganizationName: function (orgId) {
        const org = $('#organizations-data').data('organizations').find(o => o.id == orgId);
        return org ? org.name : `組織ID: ${orgId}`;
    },

    // 新規ステップを追加
    addNewStep: function () {
        // 新規ステップグループを追加
        const stepNumber = this.stepCounter++;

        // 新規承認者を追加
        this.showApproverAddModal(stepNumber);
    },

    // 承認者追加モーダルを表示
    showApproverAddModal: function (stepNumber) {
        // ステップ番号を保存
        $('#step-number').val(stepNumber);

        // フォームをリセット
        $('#step-id').val('');
        $('#step-name').val('');
        $('#approver-type').val('user');
        $('#user-id').val('');
        $('#role-id').val('');
        $('#organization-id').val('');
        $('#dynamic-field-id').val('');
        $('#allow-delegation').prop('checked', false);
        $('#allow-self-approval').prop('checked', false);
        $('#parallel-approval').prop('checked', false);

        // 承認者タイプに応じたオプションを表示
        this.toggleApproverTypeOptions('user');

        // モーダルのタイトルを設定
        $('#step-edit-modal .modal-title').text('承認者追加');

        // モーダルを表示
        $('#step-edit-modal').modal('show');
    },

    // ステップ編集モーダルを表示
    showStepEditModal: function (stepId) {
        // ステップ定義を取得
        const step = this.routeDefinitions.find(s => s.id == stepId);
        if (!step) return;

        // フォームに値を設定
        $('#step-id').val(step.id);
        $('#step-number').val(step.step_number);
        $('#step-name').val(step.step_name);
        $('#approver-type').val(step.approver_type);

        // 承認者タイプに応じた値を設定
        switch (step.approver_type) {
            case 'user':
                $('#user-id').val(step.approver_id);
                break;
            case 'role':
                $('#role-id').val(step.approver_id);
                break;
            case 'organization':
                $('#organization-id').val(step.approver_id);
                break;
            case 'dynamic':
                $('#dynamic-field-id').val(step.dynamic_approver_field_id);
                break;
        }

        $('#allow-delegation').prop('checked', step.allow_delegation);
        $('#allow-self-approval').prop('checked', step.allow_self_approval);
        $('#parallel-approval').prop('checked', step.parallel_approval);

        // 承認者タイプに応じたオプションを表示
        this.toggleApproverTypeOptions(step.approver_type);

        // モーダルのタイトルを設定
        $('#step-edit-modal .modal-title').text('承認者編集');

        // モーダルを表示
        $('#step-edit-modal').modal('show');
    },

    // 承認者タイプに応じたオプション表示切替
    toggleApproverTypeOptions: function (approverType) {
        // 全てのオプションを非表示
        $('.approver-type-options').hide();

        // 選択されたタイプのオプションを表示
        switch (approverType) {
            case 'user':
                $('#user-options').show();
                break;
            case 'role':
                $('#role-options').show();
                break;
            case 'organization':
                $('#organization-options').show();
                break;
            case 'dynamic':
                $('#dynamic-options').show();
                break;
        }
    },

    // ステップを削除
    deleteStep: function (stepId) {
        if (confirm('この承認者を削除してもよろしいですか？')) {
            // ステップ定義からステップを削除
            const index = this.routeDefinitions.findIndex(s => s.id == stepId);
            if (index !== -1) {
                const step = this.routeDefinitions[index];

                // APIに保存済みの場合は削除APIを呼び出し
                if (step.id) {
                    App.apiDelete(`/workflow/templates/${this.templateId}/route-steps/${step.id}`)
                        .then(response => {
                            if (response.success) {
                                // 成功したらステップを削除
                                this.routeDefinitions.splice(index, 1);

                                // 画面からステップを削除
                                $(`.route-step[data-step-id="${stepId}"]`).remove();

                                // 空のステップグループを削除
                                this.cleanupEmptyStepGroups();

                                App.showNotification('承認者を削除しました', 'success');
                            } else {
                                App.showNotification(response.error || '承認者の削除に失敗しました', 'error');
                            }
                        })
                        .catch(error => {
                            console.error('Failed to delete step:', error);
                            App.showNotification('承認者の削除に失敗しました', 'error');
                        });
                } else {
                    // APIに保存されていない場合は、ローカルのみ削除
                    this.routeDefinitions.splice(index, 1);

                    // 画面からステップを削除
                    $(`.route-step[data-step-id="${stepId}"]`).remove();

                    // 空のステップグループを削除
                    this.cleanupEmptyStepGroups();

                    App.showNotification('承認者を削除しました', 'success');
                }
            }
        }
    },

    // 空のステップグループを削除
    cleanupEmptyStepGroups: function () {
        $('.route-step-group').each(function () {
            if ($(this).find('.route-step').length === 0) {
                $(this).remove();
            }
        });
    },

    // ステップの並び順を更新
    updateStepOrder: function () {
        // ステップグループの並び順を取得
        const stepGroupOrder = $('.route-step-group').map(function (index) {
            return {
                oldStepNumber: $(this).data('step-number'),
                newStepNumber: index + 1
            };
        }).get();

        // ルート定義の並び順を更新
        stepGroupOrder.forEach(group => {
            if (group.oldStepNumber !== group.newStepNumber) {
                // 該当するステップのステップ番号を更新
                this.routeDefinitions.forEach(step => {
                    if (step.step_number === group.oldStepNumber) {
                        step.step_number = group.newStepNumber;
                    }
                });

                // 画面上のステップグループのデータ属性を更新
                $(`.route-step-group[data-step-number="${group.oldStepNumber}"]`).attr('data-step-number', group.newStepNumber);

                // ステップグループのヘッダーテキストを更新
                $(`.route-step-group[data-step-number="${group.newStepNumber}"] .step-group-header h5`).text(`ステップ ${group.newStepNumber}`);
            }
        });
    },

    // 承認者の並び順を更新
    updateApproverOrder: function (stepNumber) {
        // 特定のステップグループ内の承認者の並び順を取得
        const approverOrder = $(`.route-step-group[data-step-number="${stepNumber}"] .route-step`).map(function (index) {
            return {
                stepId: $(this).data('step-id'),
                sortOrder: index
            };
        }).get();

        // ルート定義の並び順を更新
        approverOrder.forEach(item => {
            const step = this.routeDefinitions.find(s => s.id == item.stepId);
            if (step) {
                step.sort_order = item.sortOrder;
            }
        });
    },

    // 承認経路定義を保存
    saveRouteDefinitions: function () {
        // API呼び出しで承認経路定義を保存
        App.apiPost(`/workflow/templates/${this.templateId}/route`, {
            route_definitions: this.routeDefinitions
        })
            .then(response => {
                if (response.success) {
                    // 保存成功時は新しい承認経路定義を設定
                    this.routeDefinitions = response.data.route_definitions || [];

                    // 画面を更新
                    this.renderExistingSteps();

                    App.showNotification(response.message || '承認経路定義を保存しました', 'success');
                } else {
                    App.showNotification(response.error || '承認経路定義の保存に失敗しました', 'error');
                }
            })
            .catch(error => {
                console.error('Failed to save route definitions:', error);
                App.showNotification('承認経路定義の保存に失敗しました', 'error');
            });
    }
};

// DOMが読み込まれたら初期化
$(document).ready(function () {
    RouteDesigner.init();

    // 承認者タイプのセレクトボックスが変更されたらオプション表示を切り替え
    $('#approver-type').on('change', function () {
        const approverType = $(this).val();
        RouteDesigner.toggleApproverTypeOptions(approverType);
    });

    // ステップ編集フォームの送信
    $('#step-edit-form').on('submit', function (e) {
        e.preventDefault();

        // フォームからステップデータを取得
        const stepId = $('#step-id').val();
        const stepNumber = $('#step-number').val();
        const stepName = $('#step-name').val();
        const approverType = $('#approver-type').val();

        // 承認者ID/フィールドIDを取得
        let approverId = null;
        let dynamicApproverFieldId = null;

        switch (approverType) {
            case 'user':
                approverId = $('#user-id').val();
                break;
            case 'role':
                approverId = $('#role-id').val();
                break;
            case 'organization':
                approverId = $('#organization-id').val();
                break;
            case 'dynamic':
                dynamicApproverFieldId = $('#dynamic-field-id').val();
                break;
        }

        const allowDelegation = $('#allow-delegation').is(':checked');
        const allowSelfApproval = $('#allow-self-approval').is(':checked');
        const parallelApproval = $('#parallel-approval').is(':checked');

        // バリデーション
        if (!stepName) {
            App.showNotification('承認者名は必須です', 'error');
            return;
        }

        if (approverType !== 'dynamic' && !approverId) {
            App.showNotification('承認者を選択してください', 'error');
            return;
        }

        if (approverType === 'dynamic' && !dynamicApproverFieldId) {
            App.showNotification('動的承認者フィールドを選択してください', 'error');
            return;
        }

        // 新規ステップまたは既存ステップを更新
        if (stepId) {
            // 既存ステップを更新
            const index = RouteDesigner.routeDefinitions.findIndex(s => s.id == stepId);
            if (index !== -1) {
                const step = RouteDesigner.routeDefinitions[index];

                step.step_name = stepName;
                step.approver_type = approverType;
                step.approver_id = approverId;
                step.dynamic_approver_field_id = dynamicApproverFieldId;
                step.allow_delegation = allowDelegation;
                step.allow_self_approval = allowSelfApproval;
                step.parallel_approval = parallelApproval;

                // APIに保存
                App.apiPost(`/workflow/templates/${RouteDesigner.templateId}/route-steps/${step.id}`, step)
                    .then(response => {
                        if (response.success) {
                            // 画面を更新
                            RouteDesigner.renderExistingSteps();

                            // モーダルを閉じる
                            $('#step-edit-modal').modal('hide');

                            App.showNotification(response.message || '承認者を更新しました', 'success');
                        } else {
                            App.showNotification(response.error || '承認者の更新に失敗しました', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Failed to update step:', error);
                        App.showNotification('承認者の更新に失敗しました', 'error');
                    });
            }
        } else {
            // 新規ステップを作成
            const newStep = {
                id: null, // API保存時に割り当てられる
                template_id: RouteDesigner.templateId,
                step_number: parseInt(stepNumber),
                step_type: 'approval',
                step_name: stepName,
                approver_type: approverType,
                approver_id: approverId,
                dynamic_approver_field_id: dynamicApproverFieldId,
                allow_delegation: allowDelegation,
                allow_self_approval: allowSelfApproval,
                parallel_approval: parallelApproval,
                sort_order: 0 // APIで自動設定
            };

            // 一時的にIDを割り当て
            const tempId = 'temp_' + Date.now();
            newStep.id = tempId;

            // ルート定義リストに追加
            RouteDesigner.routeDefinitions.push(newStep);

            // APIに保存
            App.apiPost(`/workflow/templates/${RouteDesigner.templateId}/route-steps`, newStep)
                .then(response => {
                    if (response.success) {
                        // 一時IDを実際のIDに置き換え
                        const index = RouteDesigner.routeDefinitions.findIndex(s => s.id === tempId);
                        if (index !== -1) {
                            RouteDesigner.routeDefinitions[index].id = response.data.id;
                        }

                        // 画面を更新
                        RouteDesigner.renderExistingSteps();

                        // モーダルを閉じる
                        $('#step-edit-modal').modal('hide');

                        App.showNotification(response.message || '承認者を追加しました', 'success');
                    } else {
                        // エラー時は一時追加したステップを削除
                        const index = RouteDesigner.routeDefinitions.findIndex(s => s.id === tempId);
                        if (index !== -1) {
                            RouteDesigner.routeDefinitions.splice(index, 1);
                        }

                        App.showNotification(response.error || '承認者の追加に失敗しました', 'error');
                    }
                })
                .catch(error => {
                    console.error('Failed to add step:', error);

                    // エラー時は一時追加したステップを削除
                    const index = RouteDesigner.routeDefinitions.findIndex(s => s.id === tempId);
                    if (index !== -1) {
                        RouteDesigner.routeDefinitions.splice(index, 1);
                    }

                    App.showNotification('承認者の追加に失敗しました', 'error');
                });
        }
    });
});