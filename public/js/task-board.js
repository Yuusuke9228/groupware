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
    },

    // ドラッグ&ドロップの設定
    setupDragAndDrop: function () {
        // 編集権限がなければドラッグ無効
        if (!this.canEdit) return;

        // ドラッグ&ドロップライブラリの設定（ここではSortableJSを想定）
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

        // Select2の初期化
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

        // APIリクエスト
        $.ajax({
            url: BASE_PATH + '/api/task/cards',
            type: 'POST',
            data: JSON.stringify(formData),
            contentType: 'application/json',
            success: function (response) {
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
            error: function () {
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
            <div class="kanban-card" data-card-id="${card.id}">
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

        // APIリクエスト
        $.ajax({
            url: BASE_PATH + '/api/task/lists',
            type: 'POST',
            data: JSON.stringify(formData),
            contentType: 'application/json',
            success: function (response) {
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
            error: function () {
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

        // APIリクエスト
        $.ajax({
            url: BASE_PATH + `/api/task/lists/${listId}`,
            type: 'POST',
            data: JSON.stringify(formData),
            contentType: 'application/json',
            success: function (response) {
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
                alert('通信エラーが発生しました');
            }
        });
    },

    // リストを削除
    deleteList: function (listId) {
        // APIリクエスト
        $.ajax({
            url: BASE_PATH + `/api/task/lists/${listId}`,
            type: 'DELETE',
            success: function (response) {
                if (response.success) {
                    // リストを削除
                    $(`.kanban-list[data-list-id="${listId}"]`).remove();

                    // 通知
                    toastr.success('リストを削除しました');
                } else {
                    alert(response.error || 'リストの削除に失敗しました');
                }
            },
            error: function () {
                alert('通信エラーが発生しました');
            }
        });
    },

    // リストの順序を更新
    updateListOrder: function (listId, newIndex) {
        const formData = {
            position: newIndex
        };

        // APIリクエスト
        $.ajax({
            url: BASE_PATH + `/api/task/lists/${listId}/order`,
            type: 'POST',
            data: JSON.stringify(formData),
            contentType: 'application/json',
            success: function (response) {
                if (response.success) {
                    // 通知
                    toastr.success('リストの順序を更新しました');
                } else {
                    // 失敗した場合はリロード
                    alert(response.error || 'リストの順序更新に失敗しました');
                    window.location.reload();
                }
            },
            error: function () {
                alert('通信エラーが発生しました');
                window.location.reload();
            }
        });
    },

    // カードの順序/リストを更新
    updateCardOrder: function (cardId, listId, position) {
        const formData = {
            list_id: listId,
            position: position
        };

        // APIリクエスト
        $.ajax({
            url: BASE_PATH + `/api/task/cards/${cardId}/order`,
            type: 'POST',
            data: JSON.stringify(formData),
            contentType: 'application/json',
            success: function (response) {
                if (response.success) {
                    // カード数を更新
                    TaskBoard.updateCardCounts();

                    // 通知
                    toastr.success('カードを移動しました');
                } else {
                    // 失敗した場合はリロード
                    alert(response.error || 'カードの移動に失敗しました');
                    window.location.reload();
                }
            },
            error: function () {
                alert('通信エラーが発生しました');
                window.location.reload();
            }
        });
    },

    // カード数を更新
    updateCardCounts: function () {
        $('.kanban-list').each(function () {
            const listId = $(this).data('list-id');
            const cardCount = $(`#cards-${listId} .kanban-card`).length;
            $(`.kanban-list[data-list-id="${listId}"] .kanban-list-title .badge`).text(cardCount);

            // 空のメッセージを表示/非表示
            if (cardCount === 0) {
                if ($(`#cards-${listId} .kanban-empty-msg`).length === 0) {
                    $(`#cards-${listId}`).append('<div class="kanban-empty-msg">カードがありません</div>');
                }
            } else {
                $(`#cards-${listId} .kanban-empty-msg`).remove();
            }
        });
    },

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
            error: function () {
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
        $('#addCommentBtn').on('click', function () {
            const cardId = $(this).data('card-id');
            const comment = $('#commentText').val().trim();

            if (!comment) return;

            TaskBoard.addComment(cardId, comment);
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
                                    </div>/**
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
    init: function() {
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
    setupEventListeners: function() {
        // カード追加ボタン
        $(document).on('click', '.add-card', function(e) {
            e.preventDefault();
            const listId = $(this).data('list-id');
            TaskBoard.showAddCardModal(listId);
        });
        
        // カード保存ボタン
        $('#saveCardBtn').on('click', function() {
            TaskBoard.saveCard();
        });
        
        // リスト追加ボタン
        $('#addListBtn, #add-list-btn').on('click', function(e) {
            e.preventDefault();
            TaskBoard.showAddListModal();
        });
        
        // リスト保存ボタン
        $('#saveListBtn').on('click', function() {
            TaskBoard.saveList();
        });
        
        // リスト編集ボタン
        $(document).on('click', '.edit-list', function(e) {
            e.preventDefault();
            const listId = $(this).data('list-id');
            TaskBoard.showEditListModal(listId);
        });
        
        // リスト更新ボタン
        $('#updateListBtn').on('click', function() {
            TaskBoard.updateList();
        });
        
        // リスト削除ボタン
        $(document).on('click', '.delete-list', function(e) {
            e.preventDefault();
            const listId = $(this).data('list-id');
            if (confirm('このリストを削除しますか？含まれるカードもすべて削除されます。')) {
                TaskBoard.deleteList(listId);
            }
        });
        
        // カードクリック（詳細表示）
        $(document).on('click', '.kanban-card', function(e) {
            const cardId = $(this).data('card-id');
            TaskBoard.showCardDetail(cardId);
        });
        
        // ラベル追加ボタン
        $('#addLabelBtn').on('click', function(e) {
            e.preventDefault();
            TaskBoard.showAddLabelModal();
        });
        
        // ラベル保存ボタン
        $('#saveLabelBtn').on('click', function() {
            TaskBoard.saveLabel();
        });
        
        // メンバー追加ボタン
        $('#addMemberBtn').on('click', function(e) {
            e.preventDefault();
            TaskBoard.showAddMemberModal();
        });
        
        // メンバー保存ボタン
        $('#saveMemberBtn').on('click', function() {
            TaskBoard.saveMember();
        });
        
        // フィルターボタン
        $('#filterCardsBtn').on('click', function(e) {
            e.preventDefault();
            TaskBoard.showFilterModal();
        });
        
        // フィルターリセット
        $('#resetFilterBtn').on('click', function() {
            $('#filterForm input[type="checkbox"]').prop('checked', true);
        });
        
        // フィルター適用
        $('#applyFilterBtn').on('click', function() {
            TaskBoard.applyFilter();
        });
        
        // 並び替えボタン
        $('#sortCardsBtn').on('click', function(e) {
            e.preventDefault();
            TaskBoard.showSortModal();
        });
        
        // 並び替え適用
        $('#applySortBtn').on('click', function() {
            TaskBoard.applySort();
        });
        
        // 表示切替（カンバン/サマリー）
        $('#kanbanViewBtn').on('click', function(e) {
            e.preventDefault();
            $('.kanban-container').show();
            $('.summary-view').hide();
            $('#kanbanViewBtn').addClass('active');
            $('#summaryViewBtn').removeClass('active');
        });
        
        $('#summaryViewBtn').on('click', function(e) {
            e.preventDefault();
            $('.kanban-container').hide();
            $('.summary-view').show();
            $('#kanbanViewBtn').removeClass('active');
            $('#summaryViewBtn').addClass('active');
            
            // グラフ更新
            TaskBoard.updateCharts();
        });
    },
    
    // ドラッグ&ドロップの設定
    setupDragAndDrop: function() {
        // 編集権限がなければドラッグ無効
        if (!this.canEdit) return;
        
        // ドラッグ&ドロップライブラリの設定（ここではSortableJSを想定）
        if (typeof Sortable !== 'undefined') {
            // リスト間の並び替え
            new Sortable(document.getElementById('kanban-board'), {
                animation: 150,
                handle: '.kanban-list-header',
                draggable: '.kanban-list',
                filter: '.kanban-add-list',
                onEnd: function(evt) {
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
                    onEnd: function(evt) {
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
    showAddCardModal: function(listId) {
        $('#listIdForCard').val(listId);
        $('#cardTitle').val('');
        $('#cardDescription').val('');
        $('#cardDueDate').val('');
        $('#cardPriority').val('normal');
        $('#cardStatus').val('not_started');
        $('#cardProgress').val(0);
        
        // 担当者に自分を設定
        $('#cardAssignees').val([this.currentUser]);
        
        // Select2の初期化
        if ($.fn.select2) {
            $('#cardAssignees').select2({
                placeholder: '担当者を選択',
                width: '100%'
            });
            
            $('#cardLabels').select2({
                placeholder: 'ラベルを選択',
                width: '100%',
                templateResult: function(label) {
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
        
        $('#addCardModal').modal('show');
    },
    
    // カードを保存
    saveCard: function() {
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
        
        // APIリクエスト
        $.ajax({
            url: BASE_PATH + '/api/task/cards',
            type: 'POST',
            data: JSON.stringify(formData),
            contentType: 'application/json',
            success: function(response) {
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
            error: function() {
                alert('通信エラーが発生しました');
            }
        });
    },
    
    // リストにカードを追加
    addCardToList: function(card) {
        const listId = card.list_id;
        const cardHtml = this.renderCardHtml(card);
        
        // 空のメッセージを削除
        $(`#cards-${ listId } .kanban - empty - msg`).remove();
        
        // カードを追加
        $(`#cards-${ listId }`).append(cardHtml);
        
        // カード数を更新
        const cardCount = $(`#cards - ${ listId }.kanban - card`).length;
        $(`[data - list - id="${listId}"] .kanban - list - title.badge`).text(cardCount);
    },
    
    // カードのHTMLを生成
    renderCardHtml: function(card) {
        let labelsHtml = '';
        if (card.labels && card.labels.length > 0) {
            labelsHtml = '<div class="kanban-card-labels">';
            for (const label of card.labels) {
                labelsHtml += `< span class="kanban-label" style = "background-color: ${label.color}" title = "${label.name}" ></ > `;
            }
            labelsHtml += '</div>';
        }