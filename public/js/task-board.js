/**
 * GroupWare - タスクボード管理用JavaScript
 */

// タスクボード管理
const TaskBoard = {
    boardId: null,
    lists: {},
    cards: {},
    labels: {},
    members: {},
    currentUser: null,
    canEdit: false,
    filterSettings: {
        status: ['not_started', 'in_progress', 'completed', 'deferred'],
        priority: ['highest', 'high', 'normal', 'low', 'lowest'],
        assignee: ['me', 'others', 'unassigned'],
        due: ['overdue', 'today', 'this_week', 'this_month', 'no_due'],
        label: []
    },
    sortSettings: {
        field: 'created_at',
        direction: 'asc'
    },
    charts: {},

    // 初期化
    init: function () {
        // ボードIDを取得
        this.boardId = $('#board-container').data('board-id');
        this.currentUser = $('body').data('user-id');
        this.canEdit = $('#canEdit').val() === '1' || $('body').data('is-admin') === true;

        // ラベル情報の取得
        $('.kanban-label').each((index, el) => {
            const labelId = $(el).data('label-id');
            if (labelId) {
                this.labels[labelId] = {
                    id: labelId,
                    name: $(el).attr('title'),
                    color: $(el).css('background-color')
                };
            }
        });

        // イベントハンドラの設定
        this.setupEventListeners();

        // ドラッグ&ドロップの設定
        this.setupDragAndDrop();

        // グラフ初期化
        this.initCharts();
    },

    // イベントリスナーの設定
    setupEventListeners: function () {
        // カード追加ボタン
        $(document).on('click', '.add-card', function (e) {
            e.preventDefault();
            const listId = $(this).data('list-id');
            TaskBoard.showAddCardModal(listId);
        });

        // カード保存ボタン
        $('#saveCardBtn').on('click', function () {
            TaskBoard.saveCard();
        });

        // リスト追加ボタン
        $('#addListBtn, #add-list-btn').on('click', function (e) {
            e.preventDefault();
            TaskBoard.showAddListModal();
        });

        // リスト保存ボタン
        $('#saveListBtn').on('click', function () {
            TaskBoard.saveList();
        });

        // リスト編集ボタン
        $(document).on('click', '.edit-list', function (e) {
            e.preventDefault();
            const listId = $(this).data('list-id');
            TaskBoard.showEditListModal(listId);
        });

        // リスト更新ボタン
        $('#updateListBtn').on('click', function () {
            TaskBoard.updateList();
        });

        // リスト削除ボタン
        $(document).on('click', '.delete-list', function (e) {
            e.preventDefault();
            const listId = $(this).data('list-id');
            if (confirm('このリストを削除しますか？含まれるカードもすべて削除されます。')) {
                TaskBoard.deleteList(listId);
            }
        });

        // カードクリック（詳細表示）
        $(document).on('click', '.kanban-card', function (e) {
            const cardId = $(this).data('card-id');
            TaskBoard.showCardDetail(cardId);
        });

        // ラベル追加ボタン
        $('#addLabelBtn').on('click', function (e) {
            e.preventDefault();
            TaskBoard.showAddLabelModal();
        });

        // ラベル保存ボタン
        $('#saveLabelBtn').on('click', function () {
            TaskBoard.saveLabel();
        });

        // メンバー追加ボタン
        $('#addMemberBtn').on('click', function (e) {
            e.preventDefault();
            TaskBoard.showAddMemberModal();
        });

        // メンバー保存ボタン
        $('#saveMemberBtn').on('click', function () {
            TaskBoard.saveMember();
        });

        // フィルターボタン
        $('#filterCardsBtn').on('click', function (e) {
            e.preventDefault();
            TaskBoard.showFilterModal();
        });

        // フィルターリセット
        $('#resetFilterBtn').on('click', function () {
            $('#filterForm input[type="checkbox"]').prop('checked', true);
        });

        // フィルター適用
        $('#applyFilterBtn').on('click', function () {
            TaskBoard.applyFilter();
        });

        // 並び替えボタン
        $('#sortCardsBtn').on('click', function (e) {
            e.preventDefault();
            TaskBoard.showSortModal();
        });

        // 並び替え適用
        $('#applySortBtn').on('click', function () {
            TaskBoard.applySort();
        });

        // 表示切替（カンバン/サマリー）
        $('#kanbanViewBtn').on('click', function (e) {
            e.preventDefault();
            $('.kanban-container').show();
            $('.summary-view').hide();
            $('#kanbanViewBtn').addClass('active');
            $('#summaryViewBtn').removeClass('active');
        });

        $('#summaryViewBtn').on('click', function (e) {
            e.preventDefault();
            $('.kanban-container').hide();
            $('.summary-view').show();
            $('#kanbanViewBtn').removeClass('active');
            $('#summaryViewBtn').addClass('active');

            // グラフ更新
            TaskBoard.updateCharts();
        });

        // コメント追加ボタン
        $(document).off('click', '#addCommentBtn').on('click', '#addCommentBtn', function () {
            const cardId = $(this).data('card-id');
            const comment = $('#commentText').val().trim();

            if (comment) {
                TaskBoard.addComment(cardId, comment);
            }
            return false;
        });

        // カード削除ボタン
        $(document).on('click', '.delete-card', function () {
            const cardId = $(this).data('card-id');

            if (confirm('このカードを削除しますか？')) {
                TaskBoard.deleteCard(cardId);
            }
        });

        // チェックリスト項目のチェック状態変更
        $(document).on('change', '.checklist-item', function () {
            const itemId = $(this).data('item-id');
            const isChecked = $(this).prop('checked');

            TaskBoard.updateChecklistItem(itemId, isChecked);
        });
    },

    // ドラッグ&ドロップの設定
    setupDragAndDrop: function () {
        // 編集権限がなければドラッグ無効
        if (!this.canEdit) return;

        // ドラッグ&ドロップライブラリの設定（Sortable.js使用）
        if (typeof Sortable !== 'undefined') {
            // リスト間の並び替え
            new Sortable(document.getElementById('kanban-board'), {
                animation: 150,
                handle: '.kanban-list-header',
                draggable: '.kanban-list',
                filter: '.kanban-add-list',
                onEnd: function (evt) {
                    if (evt.oldIndex !== evt.newIndex) {
                        TaskBoard.updateListOrder(evt.item.dataset.listId, evt.newIndex);
                    }
                }
            });

            // 各リスト内のカード並び替え
            document.querySelectorAll('.kanban-cards').forEach(list => {
                new Sortable(list, {
                    group: 'cards',
                    animation: 150,
                    draggable: '.kanban-card',
                    filter: '.kanban-empty-msg',
                    onEnd: function (evt) {
                        const cardId = evt.item.dataset.cardId;
                        const newListId = evt.to.id.replace('cards-', '');
                        const newIndex = Array.from(evt.to.children).indexOf(evt.item);

                        TaskBoard.updateCardOrder(cardId, newListId, newIndex);
                    }
                });
            });
        }
    },

    // カード追加モーダルを表示
    showAddCardModal: function (listId) {
        $('#listIdForCard').val(listId);
        $('#cardTitle').val('');
        $('#cardDescription').val('');
        $('#cardDueDate').val('');
        $('#cardPriority').val('normal');
        $('#cardStatus').val('not_started');
        $('#cardProgress').val(0);

        // 担当者に自分を設定
        $('#cardAssignees').val([this.currentUser]);

        // Select2の初期化（必ず$.fn.select2が存在していることを確認）
        setTimeout(function () {
            if ($.fn.select2) {
                $('#cardAssignees').select2({
                    placeholder: '担当者を選択',
                    width: '100%'
                });

                $('#cardLabels').select2({
                    placeholder: 'ラベルを選択',
                    width: '100%',
                    templateResult: function (label) {
                        if (!label.id) return label.text;

                        const $span = $('<span>').css({
                            'display': 'inline-block',
                            'width': '16px',
                            'height': '16px',
                            'background-color': $(label.element).data('color'),
                            'margin-right': '5px',
                            'vertical-align': 'middle',
                            'border-radius': '3px'
                        });

                        return $('<span>').append($span).append(label.text);
                    }
                });
            }
        }, 100);

        $('#addCardModal').modal('show');
    },

    // カードを保存
    saveCard: function () {
        const formData = {
            list_id: $('#listIdForCard').val(),
            title: $('#cardTitle').val(),
            description: $('#cardDescription').val(),
            due_date: $('#cardDueDate').val() || null,
            priority: $('#cardPriority').val(),
            status: $('#cardStatus').val(),
            progress: $('#cardProgress').val(),
            assignees: $('#cardAssignees').val(),
            labels: $('#cardLabels').val()
        };

        // バリデーション
        if (!formData.title.trim()) {
            alert('タイトルを入力してください');
            return;
        }

        // 送信ボタンを無効化して二重送信を防止
        const submitBtn = $('#saveCardBtn');
        submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> 処理中...');

        console.log('カード作成リクエスト:', formData);

        // APIリクエスト
        $.ajax({
            url: BASE_PATH + '/api/task/cards',
            type: 'POST',
            data: JSON.stringify(formData),
            contentType: 'application/json',
            success: function (response) {
                console.log('カード作成レスポンス:', response);
                submitBtn.prop('disabled', false).html('保存');

                if (response.success) {
                    // モーダルを閉じる
                    $('#addCardModal').modal('hide');

                    // カードを追加
                    const card = response.data;
                    TaskBoard.addCardToList(card);

                    // 通知
                    toastr.success('カードを追加しました');
                } else {
                    alert(response.error || 'カードの追加に失敗しました');
                }
            },
            error: function (xhr, status, error) {
                console.error('カード作成エラー:', xhr.responseText);
                submitBtn.prop('disabled', false).html('保存');
                alert('通信エラーが発生しました');
            }
        });
    },

    // リストにカードを追加
    addCardToList: function (card) {
        const listId = card.list_id;
        const cardHtml = this.renderCardHtml(card);

        // 空のメッセージを削除
        $(`#cards-${listId} .kanban-empty-msg`).remove();

        // カードを追加
        $(`#cards-${listId}`).append(cardHtml);

        // カード数を更新
        const cardCount = $(`#cards-${listId} .kanban-card`).length;
        $(`[data-list-id="${listId}"] .kanban-list-title .badge`).text(cardCount);
    },

    // カードのHTMLを生成
    renderCardHtml: function (card) {
        let labelsHtml = '';
        if (card.labels && card.labels.length > 0) {
            labelsHtml = '<div class="kanban-card-labels">';
            for (const label of card.labels) {
                labelsHtml += `<span class="kanban-label" style="background-color: ${label.color}" title="${label.name}"></span>`;
            }
            labelsHtml += '</div>';
        }

        let dueDateHtml = '';
        if (card.due_date) {
            const dueDate = new Date(card.due_date);
            const today = new Date();
            const isDue = dueDate < today && card.status != 'completed';
            dueDateHtml = `
                <div class="kanban-card-due${isDue ? ' overdue' : ''}">
                    <i class="far fa-calendar-alt"></i> 
                    ${dueDate.toLocaleDateString()}
                </div>
            `;
        }

        const priorityIcons = {
            'highest': '<i class="fas fa-arrow-up"></i><i class="fas fa-arrow-up"></i>',
            'high': '<i class="fas fa-arrow-up"></i>',
            'normal': '<i class="fas fa-minus"></i>',
            'low': '<i class="fas fa-arrow-down"></i>',
            'lowest': '<i class="fas fa-arrow-down"></i><i class="fas fa-arrow-down"></i>'
        };

        const colorStyle = card.color ? `<div class="kanban-card-color" style="background-color: ${card.color}"></div>` : '';

        return `
            <div class="kanban-card" data-card-id="${card.id}" data-status="${card.status}" data-priority="${card.priority}" data-progress="${card.progress}">
                ${colorStyle}
                ${labelsHtml}
                <h6 class="kanban-card-title">${card.title}</h6>
                ${dueDateHtml}
                <div class="kanban-card-footer">
                    <div class="kanban-card-info">
                        ${card.assignee_count ? `<span class="kanban-card-assignees" title="担当者: ${card.assignee_count}人">
                            <i class="fas fa-user"></i> ${card.assignee_count}
                        </span>` : ''}
                        
                        ${card.checklist_completion ? `<span class="kanban-card-checklist" title="チェックリスト: ${card.checklist_completion}% 完了">
                            <i class="fas fa-check-square"></i> ${card.checklist_completion}%
                        </span>` : ''}
                        
                        <span class="kanban-card-priority priority-${card.priority}" title="優先度: ${card.priority}">
                            ${priorityIcons[card.priority] || ''}
                        </span>
                    </div>
                    
                    <div class="kanban-card-progress">
                        <div class="progress" style="height: 5px;">
                            <div class="progress-bar bg-${card.status == 'completed' ? 'success' : 'primary'}" role="progressbar" 
                                 style="width: ${card.progress}%" 
                                 aria-valuenow="${card.progress}" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    },

    // リスト追加モーダルを表示
    showAddListModal: function () {
        $('#listName').val('');
        $('#listColor').val('#ffffff');
        $('#addListModal').modal('show');
    },

    // リストを保存
    saveList: function () {
        const formData = {
            board_id: this.boardId,
            name: $('#listName').val(),
            color: $('#listColor').val()
        };

        // バリデーション
        if (!formData.name.trim()) {
            alert('リスト名を入力してください');
            return;
        }

        // 送信ボタンを無効化して二重送信を防止
        const submitBtn = $('#saveListBtn');
        submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> 処理中...');

        console.log('リスト作成リクエスト:', formData);

        // APIリクエスト
        $.ajax({
            url: BASE_PATH + '/api/task/lists',
            type: 'POST',
            data: JSON.stringify(formData),
            contentType: 'application/json',
            success: function (response) {
                console.log('リスト作成レスポンス:', response);
                submitBtn.prop('disabled', false).html('保存');

                if (response.success) {
                    // モーダルを閉じる
                    $('#addListModal').modal('hide');

                    // リストを追加
                    const list = response.data;
                    TaskBoard.addList(list);

                    // 通知
                    toastr.success('リストを追加しました');
                } else {
                    alert(response.error || 'リストの追加に失敗しました');
                }
            },
            error: function (xhr, status, error) {
                console.error('リスト作成エラー:', xhr.responseText);
                submitBtn.prop('disabled', false).html('保存');
                alert('通信エラーが発生しました');
            }
        });
    },

    // リストを追加
    addList: function (list) {
        const listHtml = `
            <div class="kanban-list" data-list-id="${list.id}">
                <div class="kanban-list-header">
                    <h6 class="kanban-list-title">
                        ${list.name}
                        <span class="badge bg-secondary ms-1">0</span>
                    </h6>
                    <div class="dropdown kanban-list-menu">
                        <button class="btn btn-sm btn-link" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-ellipsis-v"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item edit-list" href="#" data-list-id="${list.id}">
                                <i class="fas fa-edit me-2"></i> リスト編集
                            </a></li>
                            <li><a class="dropdown-item add-card" href="#" data-list-id="${list.id}">
                                <i class="fas fa-plus me-2"></i> カード追加
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item delete-list text-danger" href="#" data-list-id="${list.id}">
                                <i class="fas fa-trash me-2"></i> リスト削除
                            </a></li>
                        </ul>
                    </div>
                </div>
                <div class="kanban-cards" id="cards-${list.id}">
                    <div class="kanban-empty-msg">カードがありません</div>
                </div>
                <div class="kanban-add-card">
                    <button class="btn btn-sm btn-light w-100 add-card" data-list-id="${list.id}">
                        <i class="fas fa-plus"></i> カード追加
                    </button>
                </div>
            </div>
        `;

        // リストを追加（追加ボタンの前に）
        $('.kanban-add-list').before(listHtml);

        // ドラッグ&ドロップの再設定
        if (typeof Sortable !== 'undefined') {
            new Sortable(document.getElementById(`cards-${list.id}`), {
                group: 'cards',
                animation: 150,
                draggable: '.kanban-card',
                filter: '.kanban-empty-msg',
                onEnd: function (evt) {
                    const cardId = evt.item.dataset.cardId;
                    const newListId = evt.to.id.replace('cards-', '');
                    const newIndex = Array.from(evt.to.children).indexOf(evt.item);

                    TaskBoard.updateCardOrder(cardId, newListId, newIndex);
                }
            });
        }
    },

    // リスト編集モーダルを表示
    showEditListModal: function (listId) {
        // APIでリスト情報を取得
        $.ajax({
            url: BASE_PATH + `/api/task/lists/${listId}`,
            type: 'GET',
            success: function (response) {
                if (response.success) {
                    const list = response.data;
                    $('#editListId').val(list.id);
                    $('#editListName').val(list.name);
                    $('#editListColor').val(list.color || '#ffffff');
                    $('#editListModal').modal('show');
                } else {
                    alert(response.error || 'リスト情報の取得に失敗しました');
                }
            },
            error: function () {
                alert('通信エラーが発生しました');
            }
        });
    },

    // リストを更新
    updateList: function () {
        const listId = $('#editListId').val();
        const formData = {
            name: $('#editListName').val(),
            color: $('#editListColor').val()
        };

        // バリデーション
        if (!formData.name.trim()) {
            alert('リスト名を入力してください');
            return;
        }

        // 送信ボタンを無効化して二重送信を防止
        const submitBtn = $('#updateListBtn');
        submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> 処理中...');

        // APIリクエスト
        $.ajax({
            url: BASE_PATH + `/api/task/lists/${listId}`,
            type: 'POST',
            data: JSON.stringify(formData),
            contentType: 'application/json',
            success: function (response) {
                submitBtn.prop('disabled', false).html('更新');

                if (response.success) {
                    // モーダルを閉じる
                    $('#editListModal').modal('hide');

                    // リスト名を更新
                    $(`.kanban-list[data-list-id="${listId}"] .kanban-list-title`).html(
                        formData.name + ' <span class="badge bg-secondary ms-1">' +
                        $(`.kanban-list[data-list-id="${listId}"] .kanban-list-title .badge`).text() + '</span>'
                    );

                    // 通知
                    toastr.success('リストを更新しました');
                } else {
                    alert(response.error || 'リストの更新に失敗しました');
                }
            },
            error: function () {
                submitBtn.prop('disabled', false).html('更新');
                alert('通信エラーが発生しました');
            }
        });
    },

    // その他のメソッドは変更なし...

    // カード詳細を表示
    showCardDetail: function (cardId) {
        // モーダルを表示
        $('#cardDetailModal').modal('show');
        $('#cardDetailBody').html(`
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">読み込み中...</span>
                </div>
            </div>
        `);

        // APIでカード情報を取得
        $.ajax({
            url: BASE_PATH + `/api/task/cards/${cardId}`,
            type: 'GET',
            success: function (response) {
                if (response.success) {
                    const card = response.data;

                    // モーダルタイトルを更新
                    $('#cardDetailTitle').text(card.title);

                    // カード詳細を表示
                    TaskBoard.renderCardDetail(card);
                } else {
                    $('#cardDetailBody').html(`
                        <div class="alert alert-danger">
                            ${response.error || 'カード情報の取得に失敗しました'}
                        </div>
                    `);
                }
            },
            error: function (xhr, status, error) {
                console.error('カード詳細取得エラー:', xhr.responseText);
                $('#cardDetailBody').html(`
                    <div class="alert alert-danger">
                        通信エラーが発生しました
                    </div>
                `);
            }
        });
    },

    // カード詳細を表示
    renderCardDetail: function (card) {
        // ラベル表示
        let labelsHtml = '';
        if (card.labels && card.labels.length > 0) {
            labelsHtml = '<div class="card-labels mb-3">';
            for (const label of card.labels) {
                labelsHtml += `
                    <span class="badge" style="background-color: ${label.color}; margin-right: 5px;">
                        ${label.name}
                    </span>
                `;
            }
            labelsHtml += '</div>';
        }

        // 担当者表示
        let assigneesHtml = '';
        if (card.assignees && card.assignees.length > 0) {
            assigneesHtml = '<div class="card-assignees mb-3"><h6>担当者</h6><div class="d-flex flex-wrap">';
            for (const assignee of card.assignees) {
                assigneesHtml += `
                    <div class="me-2 mb-2 d-flex align-items-center">
                        <span class="avatar">${assignee.display_name.charAt(0)}</span>
                        <span class="ms-1">${assignee.display_name}</span>
                    </div>
                `;
            }
            assigneesHtml += '</div></div>';
        }

        // 期限日表示
        let dueDateHtml = '';
        if (card.due_date) {
            const dueDate = new Date(card.due_date);
            const today = new Date();
            const isDue = dueDate < today && card.status != 'completed';

            dueDateHtml = `
                <div class="mb-3">
                    <h6>期限日</h6>
                    <span class="${isDue ? 'text-danger' : ''}">
                        <i class="far fa-calendar-alt"></i> 
                        ${dueDate.toLocaleDateString()}
                    </span>
                </div>
            `;
        }

        // ステータスと優先度
        const statusMap = {
            'not_started': '未対応',
            'in_progress': '処理中',
            'completed': '完了',
            'deferred': '保留'
        };

        const priorityMap = {
            'highest': '最高',
            'high': '高',
            'normal': '通常',
            'low': '低',
            'lowest': '最低'
        };

        const statusClass = {
            'not_started': 'secondary',
            'in_progress': 'primary',
            'completed': 'success',
            'deferred': 'warning'
        };

        const priorityClass = {
            'highest': 'danger',
            'high': 'warning',
            'normal': 'primary',
            'low': 'info',
            'lowest': 'secondary'
        };

        // チェックリスト表示
        let checklistsHtml = '';
        if (card.checklists && card.checklists.length > 0) {
            checklistsHtml = '<div class="card-checklists mb-3"><h6>チェックリスト</h6>';

            for (const checklist of card.checklists) {
                checklistsHtml += `<div class="checklist mb-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="mb-1">${checklist.title}</h6>
                        <span class="badge bg-info">${checklist.completion}%</span>
                    </div>
                    <div class="progress mb-2" style="height: 5px;">
                        <div class="progress-bar bg-info" role="progressbar" style="width: ${checklist.completion}%" 
                             aria-valuenow="${checklist.completion}" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    <ul class="list-group checklist-items">`;

                for (const item of checklist.items) {
                    checklistsHtml += `
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div class="form-check">
                                <input class="form-check-input checklist-item" type="checkbox" 
                                       ${item.is_checked ? 'checked' : ''}
                                       data-item-id="${item.id}" id="item-${item.id}">
                                <label class="form-check-label ${item.is_checked ? 'text-decoration-line-through' : ''}" 
                                       for="item-${item.id}">
                                    ${item.content}
                                </label>
                            </div>
                        </li>
                    `;
                }

                checklistsHtml += `</ul></div>`;
            }

            checklistsHtml += '</div>';
        }

        // 添付ファイル表示
        let attachmentsHtml = '';
        if (card.attachments && card.attachments.length > 0) {
            attachmentsHtml = '<div class="card-attachments mb-3"><h6>添付ファイル</h6><div class="list-group">';

            for (const attachment of card.attachments) {
                const fileIcon = TaskBoard.getFileIcon(attachment.mime_type);
                attachmentsHtml += `
                    <a href="${BASE_PATH}/uploads/tasks/${attachment.file_path}" class="list-group-item list-group-item-action" target="_blank">
                        <div class="d-flex align-items-center">
                            <i class="${fileIcon} fa-2x me-3"></i>
                            <div>
                                <div>${attachment.file_name}</div>
                                <small class="text-muted">
                                    ${attachment.uploader_name} - 
                                    ${new Date(attachment.created_at).toLocaleDateString()}
                                </small>
                            </div>
                        </div>
                    </a>
                `;
            }

            attachmentsHtml += '</div></div>';
        }

        // コメント表示
        let commentsHtml = '';
        if (card.comments && card.comments.length > 0) {
            commentsHtml = '<div class="card-comments mb-3"><h6>コメント</h6>';

            for (const comment of card.comments) {
                commentsHtml += `
                    <div class="comment mb-3">
                        <div class="d-flex">
                            <div class="avatar me-2">${comment.display_name.charAt(0)}</div>
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <strong>${comment.display_name}</strong>
                                        <small class="text-muted ms-2">
                                            ${new Date(comment.created_at).toLocaleString()}
                                        </small>
                                    </div>
                                </div>
                                <div>${comment.comment}</div>
                            </div>
                        </div>
                    </div>
                `;
            }

            commentsHtml += '</div>';
        }

        // 編集ボタン
        let editButtonsHtml = '';
        if (this.canEdit) {
            editButtonsHtml = `
                <div class="d-flex justify-content-end mt-3">
                    <button type="button" class="btn btn-outline-danger me-2 delete-card" data-card-id="${card.id}">
                        <i class="fas fa-trash"></i> 削除
                    </button>
                    <a href="${BASE_PATH}/task/edit-card/${card.id}" class="btn btn-primary">
                        <i class="fas fa-edit"></i> 編集
                    </a>
                </div>
            `;
        }

        // カード詳細を表示
        $('#cardDetailBody').html(`
            <div class="row">
                <div class="col-md-8">
                    ${labelsHtml}
                    
                    <div class="description mb-3">
                        <h6>説明</h6>
                        <div>${card.description ? card.description.replace(/\n/g, '<br>') : '<em>説明はありません</em>'}</div>
                    </div>
                    
                    ${checklistsHtml}
                    
                    <hr class="my-3">
                    
                    <!-- コメント入力 -->
                    <div class="add-comment mb-3">
                        <h6>コメントを追加</h6>
                        <div class="input-group">
                            <textarea class="form-control" id="commentText" rows="2" placeholder="コメントを入力..."></textarea>
                            <button class="btn btn-primary" type="button" id="addCommentBtn" data-card-id="${card.id}">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                    </div>
                    
                    ${commentsHtml}
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <h6>ステータス</h6>
                        <span class="badge bg-${statusClass[card.status] || 'secondary'}">
                            ${statusMap[card.status] || card.status}
                        </span>
                    </div>
                    
                    <div class="mb-3">
                        <h6>優先度</h6>
                        <span class="badge bg-${priorityClass[card.priority] || 'secondary'}">
                            ${priorityMap[card.priority] || card.priority}
                        </span>
                    </div>
                    
                    <div class="mb-3">
                        <h6>進捗率</h6>
                        <div class="progress mb-2" style="height: 8px;">
                            <div class="progress-bar bg-${card.status == 'completed' ? 'success' : 'primary'}" 
                                 role="progressbar" style="width: ${card.progress}%" 
                                 aria-valuenow="${card.progress}" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <span>${card.progress}%</span>
                    </div>
                    
                    ${dueDateHtml}
                    
                    ${assigneesHtml}
                    
                    ${attachmentsHtml}
                    
                    <div class="mb-3">
                        <h6>作成情報</h6>
                        <div>作成者: <strong>${card.creator_name || '不明'}</strong></div>
                        <div>作成日: ${new Date(card.created_at).toLocaleString()}</div>
                        <div>更新日: ${new Date(card.updated_at).toLocaleString()}</div>
                    </div>
                </div>
            </div>
            ${editButtonsHtml}
        `);

        // イベントハンドラの追加
        $('#addCommentBtn').off('click').on('click', function () {
            const cardId = $(this).data('card-id');
            const comment = $('#commentText').val().trim();

            if (!comment) return;

            TaskBoard.addComment(cardId, comment);
            return false;
        });

        $('.delete-card').on('click', function () {
            const cardId = $(this).data('card-id');

            if (confirm('このカードを削除しますか？')) {
                TaskBoard.deleteCard(cardId);
            }
        });

        $('.checklist-item').on('change', function () {
            const itemId = $(this).data('item-id');
            const isChecked = $(this).prop('checked');

            // ラベルの見た目を変更
            if (isChecked) {
                $(`label[for="item-${itemId}"]`).addClass('text-decoration-line-through');
            } else {
                $(`label[for="item-${itemId}"]`).removeClass('text-decoration-line-through');
            }

            TaskBoard.updateChecklistItem(itemId, isChecked);
        });
    },

    // ファイルアイコンの取得
    getFileIcon: function (mimeType) {
        if (!mimeType) return 'fas fa-file';

        if (mimeType.startsWith('image/')) return 'far fa-file-image';
        if (mimeType.startsWith('video/')) return 'far fa-file-video';
        if (mimeType.startsWith('audio/')) return 'far fa-file-audio';

        switch (mimeType) {
            case 'application/pdf':
                return 'far fa-file-pdf';
            case 'application/msword':
            case 'application/vnd.openxmlformats-officedocument.wordprocessingml.document':
                return 'far fa-file-word';
            case 'application/vnd.ms-excel':
            case 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet':
                return 'far fa-file-excel';
            case 'application/vnd.ms-powerpoint':
            case 'application/vnd.openxmlformats-officedocument.presentationml.presentation':
                return 'far fa-file-powerpoint';
            case 'application/zip':
            case 'application/x-rar-compressed':
            case 'application/x-7z-compressed':
                return 'far fa-file-archive';
            case 'text/plain':
                return 'far fa-file-alt';
            case 'text/html':
            case 'application/json':
            case 'application/xml':
                return 'far fa-file-code';
            default:
                return 'far fa-file';
        }
    },

    // コメントを追加
    addComment: function (cardId, comment) {
        const formData = {
            comment: comment
        };

        // APIリクエスト
        $.ajax({
            url: BASE_PATH + `/api/task/cards/${cardId}/comments`,
            type: 'POST',
            data: JSON.stringify(formData),
            contentType: 'application/json',
            success: function (response) {
                if (response.success) {
                    // コメント入力欄をクリア
                    $('#commentText').val('');

                    // コメントを追加表示
                    const newComment = response.data;
                    const commentHtml = `
                        <div class="comment mb-3">
                            <div class="d-flex">
                                <div class="avatar me-2">${newComment.display_name.charAt(0)}</div>
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <strong>${newComment.display_name}</strong>
                                            <small class="text-muted ms-2">
                                                ${new Date(newComment.created_at).toLocaleString()}
                                            </small>
                                        </div>
                                    </div>
                                    <div>${newComment.comment}</div>
                                </div>
                            </div>
                        </div>
                    `;

                    // 最初のコメントの場合
                    if ($('.card-comments').length === 0) {
                        $('.add-comment').after(`
                            <div class="card-comments mb-3">
                                <h6>コメント</h6>
                                ${commentHtml}
                            </div>
                        `);
                    } else {
                        $('.card-comments').append(commentHtml);
                    }

                    // 通知
                    toastr.success('コメントを追加しました');
                } else {
                    alert(response.error || 'コメントの追加に失敗しました');
                }
            },
            error: function () {
                alert('通信エラーが発生しました');
            }
        });
        return false;
    },

    // カードを削除
    deleteCard: function (cardId) {
        // APIリクエスト
        $.ajax({
            url: BASE_PATH + `/api/task/cards/${cardId}`,
            type: 'DELETE',
            success: function (response) {
                if (response.success) {
                    // モーダルを閉じる
                    $('#cardDetailModal').modal('hide');

                    // カードを削除
                    $(`.kanban-card[data-card-id="${cardId}"]`).remove();

                    // カード数を更新
                    TaskBoard.updateCardCounts();

                    // 通知
                    toastr.success('カードを削除しました');
                } else {
                    alert(response.error || 'カードの削除に失敗しました');
                }
            },
            error: function () {
                alert('通信エラーが発生しました');
            }
        });
    },

    // チェックリスト項目の状態を更新
    updateChecklistItem: function (itemId, isChecked) {
        const formData = {
            is_checked: isChecked
        };

        // APIリクエスト
        $.ajax({
            url: BASE_PATH + `/api/task/checklist-items/${itemId}`,
            type: 'POST',
            data: JSON.stringify(formData),
            contentType: 'application/json',
            success: function (response) {
                if (response.success) {
                    // チェック状態が変わったら通知
                    toastr.success('チェックリスト項目を更新しました');
                } else {
                    alert(response.error || 'チェックリスト項目の更新に失敗しました');
                    // 失敗したら元に戻す
                    $(`#item-${itemId}`).prop('checked', !isChecked);
                    if (isChecked) {
                        $(`label[for="item-${itemId}"]`).removeClass('text-decoration-line-through');
                    } else {
                        $(`label[for="item-${itemId}"]`).addClass('text-decoration-line-through');
                    }
                }
            },
            error: function () {
                alert('通信エラーが発生しました');
                // 失敗したら元に戻す
                $(`#item-${itemId}`).prop('checked', !isChecked);
                if (isChecked) {
                    $(`label[for="item-${itemId}"]`).removeClass('text-decoration-line-through');
                } else {
                    $(`label[for="item-${itemId}"]`).addClass('text-decoration-line-through');
                }
            }
        });
    },

    // ラベル追加モーダルを表示
    showAddLabelModal: function () {
        $('#labelName').val('');
        $('#labelColor').val('#cccccc');
        $('#addLabelModal').modal('show');
    },

    // ラベルを保存
    saveLabel: function () {
        const formData = {
            board_id: this.boardId,
            name: $('#labelName').val(),
            color: $('#labelColor').val()
        };

        // バリデーション
        if (!formData.name.trim()) {
            alert('ラベル名を入力してください');
            return;
        }

        // APIリクエスト
        $.ajax({
            url: BASE_PATH + '/api/task/labels',
            type: 'POST',
            data: JSON.stringify(formData),
            contentType: 'application/json',
            success: function (response) {
                if (response.success) {
                    // モーダルを閉じる
                    $('#addLabelModal').modal('hide');

                    // 通知
                    toastr.success('ラベルを追加しました');

                    // ページをリロード（ラベル選択肢を更新するため）
                    window.location.reload();
                } else {
                    alert(response.error || 'ラベルの追加に失敗しました');
                }
            },
            error: function () {
                alert('通信エラーが発生しました');
            }
        });
    },

    // メンバー追加モーダルを表示
    showAddMemberModal: function () {
        // アクティブユーザー一覧を取得
        $.ajax({
            url: BASE_PATH + '/api/active-users',
            type: 'GET',
            success: function (response) {
                if (response.success) {
                    const users = response.data;
                    let options = '<option value="">選択してください</option>';

                    // 既存メンバーを除外
                    const existingMembers = $('.board-member').map(function () {
                        return $(this).data('user-id').toString();
                    }).get();

                    for (const user of users) {
                        if (!existingMembers.includes(user.id.toString())) {
                            options += `<option value="${user.id}">${user.display_name} (${user.email})</option>`;
                        }
                    }

                    $('#memberUser').html(options);
                    $('#addMemberModal').modal('show');
                } else {
                    alert(response.error || 'ユーザー情報の取得に失敗しました');
                }
            },
            error: function () {
                alert('通信エラーが発生しました');
            }
        });
    },

    // メンバーを保存
    saveMember: function () {
        const formData = {
            board_id: this.boardId,
            user_id: $('#memberUser').val(),
            role: $('#memberRole').val()
        };

        // バリデーション
        if (!formData.user_id) {
            alert('ユーザーを選択してください');
            return;
        }

        // APIリクエスト
        $.ajax({
            url: BASE_PATH + '/api/task/board-members',
            type: 'POST',
            data: JSON.stringify(formData),
            contentType: 'application/json',
            success: function (response) {
                if (response.success) {
                    // モーダルを閉じる
                    $('#addMemberModal').modal('hide');

                    // 通知
                    toastr.success('メンバーを追加しました');

                    // ページをリロード（メンバー一覧を更新するため）
                    window.location.reload();
                } else {
                    alert(response.error || 'メンバーの追加に失敗しました');
                }
            },
            error: function () {
                alert('通信エラーが発生しました');
            }
        });
    },

    // フィルターモーダルを表示
    showFilterModal: function () {
        // 現在の設定を反映
        $('#filterForm input[type="checkbox"]').prop('checked', false);

        for (const status of this.filterSettings.status) {
            $(`#filterStatus${status.charAt(0).toUpperCase() + status.slice(1)}`).prop('checked', true);
        }

        for (const priority of this.filterSettings.priority) {
            $(`#filterPriority${priority.charAt(0).toUpperCase() + priority.slice(1)}`).prop('checked', true);
        }

        for (const assignee of this.filterSettings.assignee) {
            $(`#filterAssignee${assignee.charAt(0).toUpperCase() + assignee.slice(1)}`).prop('checked', true);
        }

        for (const due of this.filterSettings.due) {
            $(`#filterDue${due.charAt(0).toUpperCase() + due.slice(1)}`).prop('checked', true);
        }

        for (const label of this.filterSettings.label) {
            $(`#filterLabel${label}`).prop('checked', true);
        }

        $('#filterModal').modal('show');
    },

    // フィルターを適用
    applyFilter: function () {
        // フォームから設定を取得
        this.filterSettings.status = $('input[name="status[]"]:checked').map(function () {
            return $(this).val();
        }).get();

        this.filterSettings.priority = $('input[name="priority[]"]:checked').map(function () {
            return $(this).val();
        }).get();

        this.filterSettings.assignee = $('input[name="assignee[]"]:checked').map(function () {
            return $(this).val();
        }).get();

        this.filterSettings.due = $('input[name="due[]"]:checked').map(function () {
            return $(this).val();
        }).get();

        this.filterSettings.label = $('input[name="label[]"]:checked').map(function () {
            return $(this).val();
        }).get();

        // フィルターを適用
        this.filterCards();

        // モーダルを閉じる
        $('#filterModal').modal('hide');
    },

    // カードをフィルタリング
    filterCards: function () {
        const currentUser = this.currentUser;
        const today = new Date();
        today.setHours(0, 0, 0, 0);

        const weekDate = new Date(today);
        weekDate.setDate(today.getDate() + 7);

        const monthDate = new Date(today);
        monthDate.setMonth(today.getMonth() + 1);

        // 全てのカードを対象に
        $('.kanban-card').each(function () {
            const card = $(this);
            let show = true;

            // ステータスでフィルター
            const status = card.data('status');
            if (TaskBoard.filterSettings.status.length > 0 && !TaskBoard.filterSettings.status.includes(status)) {
                show = false;
            }

            // 優先度でフィルター
            const priority = card.data('priority');
            if (TaskBoard.filterSettings.priority.length > 0 && !TaskBoard.filterSettings.priority.includes(priority)) {
                show = false;
            }

            // 担当者でフィルター
            const assignees = card.data('assignees') ? card.data('assignees').split(',') : [];
            if (TaskBoard.filterSettings.assignee.length > 0) {
                let assigneeMatch = false;

                if (TaskBoard.filterSettings.assignee.includes('me') && assignees.includes(currentUser.toString())) {
                    assigneeMatch = true;
                }

                if (TaskBoard.filterSettings.assignee.includes('others') &&
                    assignees.length > 0 &&
                    !assignees.includes(currentUser.toString())) {
                    assigneeMatch = true;
                }

                if (TaskBoard.filterSettings.assignee.includes('unassigned') && assignees.length === 0) {
                    assigneeMatch = true;
                }

                if (!assigneeMatch) {
                    show = false;
                }
            }

            // 期限でフィルター
            const dueDate = card.data('due-date') ? new Date(card.data('due-date')) : null;
            if (TaskBoard.filterSettings.due.length > 0 && dueDate) {
                let dueMatch = false;

                if (TaskBoard.filterSettings.due.includes('overdue') &&
                    dueDate < today &&
                    status !== 'completed') {
                    dueMatch = true;
                }

                if (TaskBoard.filterSettings.due.includes('today') &&
                    dueDate.getFullYear() === today.getFullYear() &&
                    dueDate.getMonth() === today.getMonth() &&
                    dueDate.getDate() === today.getDate()) {
                    dueMatch = true;
                }

                if (TaskBoard.filterSettings.due.includes('this_week') &&
                    dueDate >= today && dueDate <= weekDate) {
                    dueMatch = true;
                }

                if (TaskBoard.filterSettings.due.includes('this_month') &&
                    dueDate >= today && dueDate <= monthDate) {
                    dueMatch = true;
                }

                if (!dueMatch && !TaskBoard.filterSettings.due.includes('no_due')) {
                    show = false;
                }
            } else if (TaskBoard.filterSettings.due.length > 0 && !dueDate && !TaskBoard.filterSettings.due.includes('no_due')) {
                show = false;
            }

            // ラベルでフィルター
            const labels = card.data('labels') ? card.data('labels').split(',') : [];
            if (TaskBoard.filterSettings.label.length > 0 && labels.length > 0) {
                let labelMatch = false;

                for (const label of labels) {
                    if (TaskBoard.filterSettings.label.includes(label)) {
                        labelMatch = true;
                        break;
                    }
                }

                if (!labelMatch) {
                    show = false;
                }
            }

            // 表示/非表示
            if (show) {
                card.show();
            } else {
                card.hide();
            }
        });

        // 空のメッセージを表示/非表示
        $('.kanban-cards').each(function () {
            const cards = $(this).find('.kanban-card:visible');
            if (cards.length === 0) {
                if ($(this).find('.kanban-empty-msg').length === 0) {
                    $(this).append('<div class="kanban-empty-msg">表示できるカードがありません</div>');
                } else {
                    $(this).find('.kanban-empty-msg').show();
                }
            } else {
                $(this).find('.kanban-empty-msg').hide();
            }
        });
    },

    // 並び替えモーダルを表示
    showSortModal: function () {
        $('#sortField').val(this.sortSettings.field);
        $('#sortDirection').val(this.sortSettings.direction);
        $('#sortModal').modal('show');
    },

    // 並び替えを適用
    applySort: function () {
        this.sortSettings.field = $('#sortField').val();
        this.sortSettings.direction = $('#sortDirection').val();

        // 並び替えを適用
        this.sortCards();

        // モーダルを閉じる
        $('#sortModal').modal('hide');
    },

    // カードを並び替え
    sortCards: function () {
        // 各リスト内でカードを並び替え
        $('.kanban-cards').each(function () {
            const cards = $(this).find('.kanban-card').get();

            // 並び替え関数
            cards.sort(function (a, b) {
                let aValue, bValue;

                switch (TaskBoard.sortSettings.field) {
                    case 'title':
                        aValue = $(a).find('.kanban-card-title').text();
                        bValue = $(b).find('.kanban-card-title').text();
                        break;
                    case 'due_date':
                        aValue = $(a).data('due-date') ? new Date($(a).data('due-date')).getTime() : Number.MAX_SAFE_INTEGER;
                        bValue = $(b).data('due-date') ? new Date($(b).data('due-date')).getTime() : Number.MAX_SAFE_INTEGER;
                        break;
                    case 'priority':
                        const priorityOrder = {
                            'highest': 0,
                            'high': 1,
                            'normal': 2,
                            'low': 3,
                            'lowest': 4
                        };
                        aValue = priorityOrder[$(a).data('priority')] || 2;
                        bValue = priorityOrder[$(b).data('priority')] || 2;
                        break;
                    case 'status':
                        const statusOrder = {
                            'not_started': 0,
                            'in_progress': 1,
                            'deferred': 2,
                            'completed': 3
                        };
                        aValue = statusOrder[$(a).data('status')] || 0;
                        bValue = statusOrder[$(b).data('status')] || 0;
                        break;
                    case 'progress':
                        aValue = parseInt($(a).data('progress')) || 0;
                        bValue = parseInt($(b).data('progress')) || 0;
                        break;
                    case 'created_at':
                        aValue = new Date($(a).data('created-at')).getTime();
                        bValue = new Date($(b).data('created-at')).getTime();
                        break;
                    case 'updated_at':
                        aValue = new Date($(a).data('updated-at')).getTime();
                        bValue = new Date($(b).data('updated-at')).getTime();
                        break;
                    default:
                        aValue = 0;
                        bValue = 0;
                }

                // 昇順/降順
                let result = aValue < bValue ? -1 : (aValue > bValue ? 1 : 0);
                if (TaskBoard.sortSettings.direction === 'desc') {
                    result = -result;
                }

                return result;
            });

            // 並び替えた要素を再挿入
            $.each(cards, function (index, card) {
                $(this).append(card);
            });
        });
    },

    // グラフを初期化
    initCharts: function () {
        // Chart.jsが利用可能かチェック
        if (typeof Chart === 'undefined') return;

        // コンテキストの取得
        const statusCtx = document.getElementById('statusChart');
        const priorityCtx = document.getElementById('priorityChart');
        const listCtx = document.getElementById('listChart');
        const assigneeCtx = document.getElementById('assigneeChart');
        const dueCtx = document.getElementById('dueChart');

        // コンテキストが存在するかチェック
        if (!statusCtx || !priorityCtx || !listCtx || !assigneeCtx || !dueCtx) return;

        // ステータス円グラフ
        this.charts.status = new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: ['未対応', '処理中', '完了', '保留'],
                datasets: [{
                    data: [0, 0, 0, 0],
                    backgroundColor: [
                        '#6c757d', // secondary
                        '#0d6efd', // primary
                        '#198754', // success
                        '#ffc107'  // warning
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right'
                    }
                }
            }
        });

        // 優先度円グラフ
        this.charts.priority = new Chart(priorityCtx, {
            type: 'doughnut',
            data: {
                labels: ['最高', '高', '通常', '低', '最低'],
                datasets: [{
                    data: [0, 0, 0, 0, 0],
                    backgroundColor: [
                        '#dc3545', // danger
                        '#ffc107', // warning
                        '#0d6efd', // primary
                        '#0dcaf0', // info
                        '#6c757d'  // secondary
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right'
                    }
                }
            }
        });

        // リスト棒グラフ
        this.charts.list = new Chart(listCtx, {
            type: 'bar',
            data: {
                labels: [],
                datasets: [{
                    label: 'タスク数',
                    data: [],
                    backgroundColor: '#0d6efd',
                    borderColor: '#0d6efd',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });

        // 担当者棒グラフ
        this.charts.assignee = new Chart(assigneeCtx, {
            type: 'bar',
            data: {
                labels: [],
                datasets: [{
                    label: 'タスク数',
                    data: [],
                    backgroundColor: '#20c997',
                    borderColor: '#20c997',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                },
                indexAxis: 'y' // 横向き棒グラフ
            }
        });

        // 期限日ライングラフ
        this.charts.due = new Chart(dueCtx, {
            type: 'line',
            data: {
                labels: [], // 日付
                datasets: [{
                    label: '期限切れタスク',
                    data: [],
                    backgroundColor: 'rgba(220, 53, 69, 0.2)',
                    borderColor: '#dc3545',
                    borderWidth: 1,
                    fill: true
                }, {
                    label: '完了済みタスク',
                    data: [],
                    backgroundColor: 'rgba(25, 135, 84, 0.2)',
                    borderColor: '#198754',
                    borderWidth: 1,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });

        // グラフデータの初期更新
        this.updateCharts();
    },

    // グラフデータを更新
    updateCharts: function () {
        // Chart.jsが利用可能かチェック
        if (typeof Chart === 'undefined' || !this.charts.status) return;

        // ボード概要情報を取得
        $.ajax({
            url: BASE_PATH + `/api/task/boards/${this.boardId}/summary`,
            type: 'GET',
            success: function (response) {
                if (response.success) {
                    const summary = response.data;

                    // ステータスチャート更新
                    if (summary.status && TaskBoard.charts.status) {
                        const statusData = [0, 0, 0, 0]; // 未対応、処理中、完了、保留
                        for (const status of summary.status) {
                            switch (status.status) {
                                case 'not_started':
                                    statusData[0] = status.count;
                                    break;
                                case 'in_progress':
                                    statusData[1] = status.count;
                                    break;
                                case 'completed':
                                    statusData[2] = status.count;
                                    break;
                                case 'deferred':
                                    statusData[3] = status.count;
                                    break;
                            }
                        }
                        TaskBoard.charts.status.data.datasets[0].data = statusData;
                        TaskBoard.charts.status.update();
                    }

                    // 優先度チャート更新
                    if (summary.priority && TaskBoard.charts.priority) {
                        const priorityData = [0, 0, 0, 0, 0]; // 最高、高、通常、低、最低
                        for (const priority of summary.priority) {
                            switch (priority.priority) {
                                case 'highest':
                                    priorityData[0] = priority.count;
                                    break;
                                case 'high':
                                    priorityData[1] = priority.count;
                                    break;
                                case 'normal':
                                    priorityData[2] = priority.count;
                                    break;
                                case 'low':
                                    priorityData[3] = priority.count;
                                    break;
                                case 'lowest':
                                    priorityData[4] = priority.count;
                                    break;
                            }
                        }
                        TaskBoard.charts.priority.data.datasets[0].data = priorityData;
                        TaskBoard.charts.priority.update();
                    }

                    // リストチャート更新
                    if (summary.lists && TaskBoard.charts.list) {
                        const listLabels = [];
                        const listData = [];
                        const listColors = [];

                        for (const list of summary.lists) {
                            listLabels.push(list.name);
                            listData.push(list.count);
                            // 色を取得（リストの背景色を使用）
                            const listElement = $(`.kanban-list[data-list-id="${list.id}"]`);
                            const color = listElement.length ? listElement.css('background-color') : '#0d6efd';
                            listColors.push(color);
                        }

                        TaskBoard.charts.list.data.labels = listLabels;
                        TaskBoard.charts.list.data.datasets[0].data = listData;
                        TaskBoard.charts.list.data.datasets[0].backgroundColor = listColors;
                        TaskBoard.charts.list.data.datasets[0].borderColor = listColors;
                        TaskBoard.charts.list.update();
                    }

                    // 担当者チャート更新
                    if (summary.assignees && TaskBoard.charts.assignee) {
                        const assigneeLabels = [];
                        const assigneeData = [];

                        // 上位5名までに制限
                        const topAssignees = summary.assignees.slice(0, 5);

                        for (const assignee of topAssignees) {
                            assigneeLabels.push(assignee.display_name);
                            assigneeData.push(assignee.count);
                        }

                        TaskBoard.charts.assignee.data.labels = assigneeLabels;
                        TaskBoard.charts.assignee.data.datasets[0].data = assigneeData;
                        TaskBoard.charts.assignee.update();
                    }

                    // 期限日チャート更新（簡易版 - 実際のデータはAPIから取得する必要あり）
                    if (TaskBoard.charts.due) {
                        // 期限切れタスク数
                        const overdueCount = summary.due_dates ? summary.due_dates.overdue : 0;
                        // 完了タスク数
                        let completedCount = 0;
                        if (summary.status) {
                            for (const status of summary.status) {
                                if (status.status === 'completed') {
                                    completedCount = status.count;
                                    break;
                                }
                            }
                        }

                        // 過去7日間のダミーデータ
                        const dueLabels = [];
                        const overdueData = [];
                        const completedData = [];

                        const today = new Date();
                        for (let i = 6; i >= 0; i--) {
                            const date = new Date(today);
                            date.setDate(today.getDate() - i);
                            dueLabels.push(date.toLocaleDateString());

                            // ダミーデータ（実際のデータはAPIから取得）
                            if (i === 0) {
                                overdueData.push(overdueCount);
                                completedData.push(completedCount);
                            } else {
                                // ランダムなダミーデータ
                                overdueData.push(Math.floor(Math.random() * overdueCount));
                                completedData.push(Math.floor(Math.random() * completedCount));
                            }
                        }

                        TaskBoard.charts.due.data.labels = dueLabels;
                        TaskBoard.charts.due.data.datasets[0].data = overdueData;
                        TaskBoard.charts.due.data.datasets[1].data = completedData;
                        TaskBoard.charts.due.update();
                    }

                } else {
                    console.error('ボード概要情報の取得に失敗しました。');
                }
            },
            error: function () {
                console.error('ボード概要情報の通信エラーが発生しました。');
            }
        });
    }
};

// DOMが読み込まれたら初期化
$(document).ready(function () {
    TaskBoard.init();
});