/**
 * GroupWare - 組織管理JS
 */

const Organization = {
    // データテーブル
    dataTable: null,

    // ツリービュー
    treeView: null,

    // 初期化
    init: function () {
        const page = $('[data-page-type]').data('page-type');

        // ページタイプに応じた初期化
        switch (page) {
            case 'index':
                this.initIndex();
                break;
            case 'create':
                this.initForm();
                break;
            case 'edit':
                this.initForm();
                break;
            case 'view':
                this.initView();
                break;
        }
    },

    // 一覧ページの初期化
    initIndex: function () {
        // 組織ツリーの初期化
        this.initOrganizationTree();

        // モーダルフォームのイベントハンドラ設定
        this.initModalForms();

        // 新規作成ボタンのイベントハンドラ
        $('#btn-create-organization').on('click', function () {
            $('#create-organization-modal').modal('show');
        });

        // 編集ボタンのイベントハンドラ
        $(document).on('click', '.btn-edit-organization', function () {
            const id = $(this).data('id');
            Organization.loadOrganizationData(id);
        });

        // 削除ボタンのイベントハンドラは app.js で共通処理
    },

    // フォームページの初期化
    initForm: function () {
        // 親組織セレクトボックスの初期化
        this.initParentOrgSelect();

        // コード入力フィールドの自動生成
        $('#name').on('input', function () {
            if ($('#code').val() === '') {
                const name = $(this).val();
                const code = Organization.generateCode(name);
                $('#code').val(code);
            }
        });

        // コード重複チェック
        $('#code').on('blur', function () {
            const code = $(this).val();
            const id = $('#id').val(); // 編集時はIDがある

            if (code !== '') {
                Organization.checkCodeUnique(code, id);
            }
        });
    },

    // 詳細ページの初期化
    initView: function () {
        // ユーザーリストの初期化
        this.initUserTable();
    },

    // 組織ツリーの初期化
    initOrganizationTree: function () {
        // 組織ツリーを取得
        App.apiGet('/organizations/tree')
            .then(response => {
                if (response.success) {
                    // ツリーデータを構築
                    const treeData = this.buildTreeData(response.data);

                    // ツリービューを初期化
                    $('#organization-tree').jstree({
                        'core': {
                            'data': treeData,
                            'themes': {
                                'name': 'proton',
                                'responsive': true
                            }
                        },
                        'plugins': ['types', 'contextmenu', 'dnd', 'search'],
                        'types': {
                            'default': {
                                'icon': 'far fa-building'
                            }
                        },
                        'contextmenu': {
                            'items': this.customMenu
                        },
                        'dnd': {
                            'is_draggable': function (node) {
                                return true; // すべてのノードをドラッグ可能に
                            }
                        }
                    })
                        .on('loaded.jstree', function () {
                            // ツリーを展開
                            $(this).jstree('open_all');
                        })
                        .on('select_node.jstree', function (e, data) {
                            // ノード選択時のイベント処理
                            const id = data.node.id;
                            window.location.href = BASE_PATH + '/organizations/view/' + id;
                        })
                        .on('move_node.jstree', function (e, data) {
                            // ノード移動時のイベント処理
                            Organization.moveNode(data);
                        });

                    // 検索機能
                    $('#organization-search').on('keyup', function () {
                        const searchString = $(this).val();
                        $('#organization-tree').jstree('search', searchString);
                    });

                    // 参照として保存
                    this.treeView = $('#organization-tree').jstree(true);
                } else {
                    App.showNotification('組織ツリーの読み込みに失敗しました', 'error');
                }
            })
            .catch(error => {
                App.showNotification('組織ツリーの読み込みに失敗しました', 'error');
                console.error(error);
            });
    },

    // モーダルフォームの初期化
    initModalForms: function () {
        // 親組織選択のセレクトボックス初期化
        this.initParentOrgSelect();

        // 作成モーダルのコード自動生成
        $('#create-organization-modal #name').on('input', function () {
            if ($('#create-organization-modal #code').val() === '') {
                const name = $(this).val();
                const code = Organization.generateCode(name);
                $('#create-organization-modal #code').val(code);
            }
        });

        // 編集モーダルのコード自動生成
        $('#edit-organization-modal #name').on('input', function () {
            if ($('#edit-organization-modal #code').data('original') === $('#edit-organization-modal #code').val()) {
                const name = $(this).val();
                const code = Organization.generateCode(name);
                $('#edit-organization-modal #code').val(code);
            }
        });

        // コード重複チェック（作成モーダル）
        $('#create-organization-modal #code').on('blur', function () {
            const code = $(this).val();
            if (code !== '') {
                Organization.checkCodeUnique(code);
            }
        });

        // コード重複チェック（編集モーダル）
        $('#edit-organization-modal #code').on('blur', function () {
            const code = $(this).val();
            const id = $('#edit-organization-modal #id').val();
            if (code !== '') {
                Organization.checkCodeUnique(code, id);
            }
        });
    },

    // 親組織セレクトボックスの初期化
    initParentOrgSelect: function () {
        // 組織リストを取得
        App.apiGet('/organizations')
            .then(response => {
                if (response.success) {
                    const organizations = response.data;

                    // 作成フォームのセレクトボックスを更新
                    const createSelect = $('#create-organization-modal #parent_id, #parent_id');
                    if (createSelect.length) {
                        createSelect.empty();
                        createSelect.append('<option value="">なし（トップレベル）</option>');

                        organizations.forEach(org => {
                            createSelect.append(`<option value="${org.id}">${org.name}</option>`);
                        });
                    }

                    // 編集フォームのセレクトボックスを更新
                    const editSelect = $('#edit-organization-modal #parent_id');
                    if (editSelect.length) {
                        const currentId = $('#edit-organization-modal #id').val();
                        const excludeIds = this.getDescendantIds(organizations, currentId);
                        excludeIds.push(currentId); // 自分自身も除外

                        editSelect.empty();
                        editSelect.append('<option value="">なし（トップレベル）</option>');

                        organizations.forEach(org => {
                            if (!excludeIds.includes(org.id)) {
                                editSelect.append(`<option value="${org.id}">${org.name}</option>`);
                            }
                        });
                    }
                } else {
                    App.showNotification('組織リストの読み込みに失敗しました', 'error');
                }
            })
            .catch(error => {
                App.showNotification('組織リストの読み込みに失敗しました', 'error');
                console.error(error);
            });
    },

    // ユーザーテーブルの初期化
    initUserTable: function () {
        const orgId = $('#organization-id').val();

        // APIエンドポイントの修正
        // データテーブルを初期化
        this.dataTable = $('#users-table').DataTable({
            processing: true,
            serverSide: false, // サーバーサイド処理は実装しない
            ajax: {
                url: BASE_PATH + '/api/organizations/' + orgId + '/users',
                type: 'GET',
                error: function (xhr, error, thrown) {
                    console.error('DataTables error:', error, thrown);
                    console.log('Response:', xhr.responseText);
                },
                dataSrc: function (json) {
                    if (json && json.success === true && Array.isArray(json.data)) {
                        return json.data;
                    } else {
                        console.error('Invalid response format:', json);
                        return [];
                    }
                }
            },
            columns: [
                { data: 'id' },
                { data: 'username' },
                { data: 'display_name' },
                { data: 'email' },
                { data: 'status' },
                {
                    data: null,
                    render: function (data, type, row) {
                        // 現在のユーザー情報を取得
                        const isAdmin = $('body').attr('data-is-admin') === 'true';
                        const currentUserId = $('body').attr('data-user-id');

                        let html = `
                            <a href="${BASE_PATH}/users/view/${row.id}" class="btn btn-sm btn-info">
                                <i class="fas fa-eye"></i>
                            </a>`;

                        // 管理者または自分自身の場合のみ編集ボタンを表示
                        if (isAdmin || String(currentUserId) === String(row.id)) {
                            html += `
                            <a href="${BASE_PATH}/users/edit/${row.id}" class="btn btn-sm btn-primary">
                                <i class="fas fa-edit"></i>
                            </a>`;
                        }

                        return html;
                    }
                }
            ],
            order: [[2, 'asc']], // 表示名でソート
            language: {
                url: BASE_PATH + '/js/vendor/dataTables.japanese.json'
            }
        });
    },

    // 組織データを読み込む（編集モーダル用）
    loadOrganizationData: function (id) {
        App.apiGet('/organizations/' + id)
            .then(response => {
                if (response.success) {
                    const org = response.data;

                    // フォームに値をセット
                    $('#edit-organization-modal #id').val(org.id);
                    $('#edit-organization-modal #name').val(org.name);
                    $('#edit-organization-modal #code').val(org.code);
                    $('#edit-organization-modal #code').data('original', org.code);
                    $('#edit-organization-modal #parent_id').val(org.parent_id || '');
                    $('#edit-organization-modal #description').val(org.description || '');

                    // モーダルを表示
                    $('#edit-organization-modal').modal('show');
                } else {
                    App.showNotification('組織データの読み込みに失敗しました', 'error');
                }
            })
            .catch(error => {
                App.showNotification('組織データの読み込みに失敗しました', 'error');
                console.error(error);
            });
    },

    // ツリーデータの構築
    buildTreeData: function (organizations) {
        // IDでインデックス化
        const orgMap = {};
        organizations.forEach(org => {
            orgMap[org.id] = {
                id: org.id.toString(),
                text: org.name + ' (' + org.code + ')',
                parent: org.parent_id ? org.parent_id.toString() : '#',
                data: org
            };
        });

        // ツリーデータの配列を生成
        const treeData = [];
        for (const id in orgMap) {
            treeData.push(orgMap[id]);
        }

        return treeData;
    },

    // コードの自動生成
    generateCode: function (name) {
        if (!name) return '';

        // 英数字とアンダースコアのみ許可し、空白をアンダースコアに変換
        let code = name
            .replace(/[^\w\s]/g, '') // 英数字とアンダースコア以外を削除
            .replace(/\s+/g, '_')    // 空白をアンダースコアに変換
            .toUpperCase();          // 大文字に変換

        // 20文字に制限
        if (code.length > 20) {
            code = code.substring(0, 20);
        }

        return code;
    },

    // コードの重複チェック
    checkCodeUnique: function (code, id = null) {
        console.log("Checking code uniqueness:", code, id);

        // パラメータを設定
        const params = { code: code };
        if (id) {
            params.id = id;
        }

        // APIリクエストを直接作成
        const url = BASE_PATH + '/api/organizations/check-code?' + new URLSearchParams(params).toString();
        console.log("API URL:", url);

        // 標準のfetchを使用
        fetch(url, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(response => response.json())
            .then(data => {
                console.log("Code uniqueness response:", data);

                const codeInput = $('#code').length ? $('#code') :
                    $('#create-organization-modal #code').length ? $('#create-organization-modal #code') :
                        $('#edit-organization-modal #code');

                if (data.success && data.data.unique) {
                    // 重複なし
                    codeInput.removeClass('is-invalid').addClass('is-valid');
                    codeInput.next('.invalid-feedback').text('');
                } else {
                    // 重複あり
                    codeInput.removeClass('is-valid').addClass('is-invalid');
                    codeInput.next('.invalid-feedback').text('この組織コードは既に使用されています');
                }
            })
            .catch(error => {
                console.error('Code unique check error:', error);
            });
    },

    // コンテキストメニューのカスタマイズ
    customMenu: function (node) {
        const items = {
            create: {
                label: "新規作成",
                action: function (data) {
                    const inst = $.jstree.reference(data.reference);
                    const node = inst.get_node(data.reference);

                    // 作成モーダルを表示し、親組織として選択
                    $('#create-organization-modal #parent_id').val(node.id);
                    $('#create-organization-modal').modal('show');
                }
            },
            edit: {
                label: "編集",
                action: function (data) {
                    const inst = $.jstree.reference(data.reference);
                    const node = inst.get_node(data.reference);

                    // 編集モーダルにデータを読み込む
                    Organization.loadOrganizationData(node.id);
                }
            },
            delete: {
                label: "削除",
                action: function (data) {
                    const inst = $.jstree.reference(data.reference);
                    const node = inst.get_node(data.reference);

                    // 子ノードがある場合は削除不可
                    if (inst.is_parent(node)) {
                        App.showNotification('子組織がある組織は削除できません', 'warning');
                        return;
                    }

                    // 削除確認
                    if (confirm('本当に削除しますか？')) {
                        App.apiDelete('/organizations/' + node.id)
                            .then(response => {
                                if (response.success) {
                                    App.showNotification(response.message || '削除しました', 'success');
                                    inst.delete_node(node);
                                } else {
                                    App.showNotification(response.error || 'エラーが発生しました', 'error');
                                }
                            })
                            .catch(error => {
                                App.showNotification('エラーが発生しました', 'error');
                                console.error(error);
                            });
                    }
                }
            },
            view: {
                label: "詳細",
                action: function (data) {
                    const inst = $.jstree.reference(data.reference);
                    const node = inst.get_node(data.reference);

                    // 詳細ページに遷移
                    window.location.href = BASE_PATH + '/organizations/view/' + node.id;
                }
            }
        };

        return items;
    },

    // ノードの移動（親変更）
    moveNode: function (data) {
        const nodeId = data.node.id;
        const newParentId = data.parent === '#' ? '' : data.parent;

        // API経由で親組織を更新
        App.apiPost('/organizations/' + nodeId + '/move', {
            parent_id: newParentId
        })
            .then(response => {
                if (response.success) {
                    App.showNotification(response.message || '組織の位置を更新しました', 'success');
                } else {
                    App.showNotification(response.error || 'エラーが発生しました', 'error');
                    // 失敗したら元の位置に戻す
                    this.treeView.refresh();
                }
            })
            .catch(error => {
                App.showNotification('エラーが発生しました', 'error');
                console.error(error);
                // 失敗したら元の位置に戻す
                this.treeView.refresh();
            });
    },

    // 組織の子孫IDリストを取得
    getDescendantIds: function (organizations, parentId) {
        const result = [];

        const findDescendants = (pid) => {
            organizations.forEach(org => {
                if (org.parent_id == pid) {
                    result.push(org.id);
                    findDescendants(org.id);
                }
            });
        };

        findDescendants(parentId);
        return result;
    }
};