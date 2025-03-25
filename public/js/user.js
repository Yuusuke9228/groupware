/**
 * GroupWare - ユーザー管理JS
 */

const User = {
    // データテーブル
    dataTable: null,

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
            case 'change-password':
                this.initChangePassword();
                break;
        }
    },

    // 一覧ページの初期化
    initIndex: function () {
        // データテーブルの初期化
        this.initUserTable();

        // 新規作成ボタンのイベントハンドラ
        $('#btn-create-user').on('click', function () {
            window.location.href = BASE_PATH + '/users/create';
        });

        // 検索フォームのイベントハンドラ
        $('#search-form').on('submit', function (e) {
            e.preventDefault();
            const searchTerm = $('#search-input').val();
            User.dataTable.search(searchTerm).draw();
        });

        // 検索クリアボタン
        $('#search-clear').on('click', function () {
            $('#search-input').val('');
            User.dataTable.search('').draw();
        });
    },

    // フォームページの初期化
    initForm: function () {
        // 組織選択の初期化
        this.initOrganizationSelect();

        // ディスプレイ名の自動生成
        $('#last_name, #first_name').on('input', function () {
            if ($('#display_name').data('auto-generated') !== false) {
                const lastName = $('#last_name').val();
                const firstName = $('#first_name').val();

                if (lastName || firstName) {
                    $('#display_name').val(lastName + ' ' + firstName);
                }
            }
        });

        // ディスプレイ名が手動で変更された場合、自動生成フラグをオフ
        $('#display_name').on('input', function () {
            $(this).data('auto-generated', false);
        });

        // ユーザー名の自動生成（新規作成時のみ）
        if ($('#id').length === 0) {
            $('#email').on('input', function () {
                if ($('#username').val() === '') {
                    const email = $(this).val();
                    const username = email.split('@')[0];
                    $('#username').val(username);
                }
            });
        }

        // パスワード強度チェック
        $('#password').on('input', function () {
            User.checkPasswordStrength($(this).val());
        });

        // パスワード確認一致チェック
        $('#password_confirm').on('input', function () {
            const password = $('#password').val();
            const confirm = $(this).val();

            if (confirm === '') {
                $(this).removeClass('is-valid is-invalid');
            } else if (password === confirm) {
                $(this).removeClass('is-invalid').addClass('is-valid');
                $(this).next('.invalid-feedback').text('');
            } else {
                $(this).removeClass('is-valid').addClass('is-invalid');
                $(this).next('.invalid-feedback').text('パスワードが一致しません');
            }
        });

        // フォーム送信前のバリデーション
        $('form').on('submit', function (e) {
            if (!User.validateForm()) {
                e.preventDefault();
            }
        });

        // 追加組織の追加/削除ボタン
        $('#add-organization').on('click', function () {
            User.addOrganizationRow();
        });

        $(document).on('click', '.remove-organization', function () {
            $(this).closest('.organization-row').remove();
        });

        // 編集時には既に追加組織があるかもしれないので、hidden入力から取得して追加
        if ($('#id').length > 0) {
            const additionalOrgs = $('#additional_organizations').val();
            if (additionalOrgs) {
                const orgIds = additionalOrgs.split(',');
                for (const orgId of orgIds) {
                    if (orgId.trim()) {
                        // 少し遅延させて、組織選択データが読み込まれた後に実行
                        setTimeout(() => User.addOrganizationRow(orgId), 1000);
                    }
                }
            }
        }
    },

    // 組織選択の初期化
    initOrganizationSelect: function () {
        // 組織リストを取得
        App.apiGet('organizations')
            .then(response => {
                if (response.success) {
                    const organizations = response.data;

                    // 主組織のセレクトボックスを更新
                    const orgSelect = $('#organization_id');
                    const selectedOrgId = orgSelect.data('selected');

                    orgSelect.empty();
                    orgSelect.append('<option value="">選択してください</option>');

                    organizations.forEach(org => {
                        const selected = (selectedOrgId == org.id) ? 'selected' : '';
                        orgSelect.append(`<option value="${org.id}" ${selected}>${org.name}</option>`);
                    });

                    // 追加組織のセレクトボックスも構築
                    this.buildAdditionalOrgSelect(organizations);
                } else {
                    App.showNotification('組織リストの読み込みに失敗しました', 'error');
                }
            })
            .catch(error => {
                App.showNotification('組織リストの読み込みに失敗しました', 'error');
                console.error(error);
            });
    },

    // 詳細ページの初期化
    initView: function () {
        // 所属組織リストの初期化
        this.initOrganizationList();
    },

    // パスワード変更ページの初期化
    initChangePassword: function () {
        // パスワード強度チェック
        $('#new_password').on('input', function () {
            User.checkPasswordStrength($(this).val());
        });

        // パスワード確認一致チェック
        $('#new_password_confirm').on('input', function () {
            const password = $('#new_password').val();
            const confirm = $(this).val();

            if (confirm === '') {
                $(this).removeClass('is-valid is-invalid');
            } else if (password === confirm) {
                $(this).removeClass('is-invalid').addClass('is-valid');
                $(this).next('.invalid-feedback').text('');
            } else {
                $(this).removeClass('is-valid').addClass('is-invalid');
                $(this).next('.invalid-feedback').text('パスワードが一致しません');
            }
        });

        // フォーム送信前のバリデーション
        $('form').on('submit', function (e) {
            if (!User.validatePasswordForm()) {
                e.preventDefault();
            }
        });
    },

    // ユーザーテーブルの初期化
    initUserTable: function () {
        this.dataTable = $('#users-table').DataTable({
            processing: true,
            serverSide: false, // サーバーサイド処理は実装しない
            ajax: {
                url: App.config.apiEndpoint + '/users',
                data: function (d) {
                    // 検索条件を追加
                    d.search = $('#search-input').val();
                },
                dataSrc: function (json) {
                    return json.data.users || [];
                }
            },
            columns: [
                { data: 'id' },
                { data: 'username' },
                { data: 'display_name' },
                { data: 'email' },
                {
                    data: 'organization_name',
                    defaultContent: '-'
                },
                {
                    data: 'status',
                    render: function (data) {
                        switch (data) {
                            case 'active':
                                return '<span class="badge bg-success">有効</span>';
                            case 'inactive':
                                return '<span class="badge bg-warning">無効</span>';
                            case 'suspended':
                                return '<span class="badge bg-danger">停止</span>';
                            default:
                                return data;
                        }
                    }
                },
                {
                    data: null,
                    render: function (data, type, row) {
                        // console.log を追加してデバッグ
                        console.log("Body data:", $('body').data('is-admin'), $('body').data('user-id'));

                        // 文字列として明示的に取得
                        const isAdmin = $('body').attr('data-is-admin') === 'true';
                        const currentUserId = $('body').attr('data-user-id');

                        // デバッグログ
                        console.log("Is admin:", isAdmin, "Current user ID:", currentUserId, "Row ID:", row.id);

                        let html = `
                            <div class="btn-group" role="group">
                                <a href="${BASE_PATH}/users/view/${row.id}" class="btn btn-sm btn-info">
                                    <i class="fas fa-eye"></i> 詳細
                                </a>`;

                        // 数値比較ではなく文字列比較に変更
                        if (isAdmin || String(currentUserId) === String(row.id)) {
                            html += `
                                <a href="${BASE_PATH}/users/edit/${row.id}" class="btn btn-sm btn-primary">
                                    <i class="fas fa-edit"></i> 編集
                                </a>`;
                        }

                        // 数値比較ではなく文字列比較に変更
                        if (isAdmin && String(currentUserId) !== String(row.id)) {
                            html += `
                                <button type="button" class="btn btn-sm btn-danger btn-delete" data-url="${BASE_PATH}/api/users/${row.id}" data-confirm="ユーザー「${row.display_name}」を削除しますか？">
                                    <i class="fas fa-trash"></i> 削除
                                </button>`;
                        }

                        html += `</div>`;
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

    // 組織選択の初期化
    // initOrganizationSelect: function () {
    //     // 組織リストを取得
    //     App.apiGet('/organizations')
    //         .then(response => {
    //             if (response.success) {
    //                 const organizations = response.data;

    //                 // 主組織のセレクトボックスを更新
    //                 const orgSelect = $('#organization_id');
    //                 orgSelect.empty();
    //                 orgSelect.append('<option value="">選択してください</option>');

    //                 organizations.forEach(org => {
    //                     const selected = (orgSelect.data('selected') == org.id) ? 'selected' : '';
    //                     orgSelect.append(`<option value="${org.id}" ${selected}>${org.name}</option>`);
    //                 });

    //                 // 追加組織のセレクトボックスも構築
    //                 this.buildAdditionalOrgSelect(organizations);

    //                 // 既存の追加組織がある場合（編集時）
    //                 if ($('.organization-row').length === 0 && $('#additional_organizations').val()) {
    //                     const additionalOrgs = $('#additional_organizations').val().split(',');
    //                     additionalOrgs.forEach(orgId => {
    //                         this.addOrganizationRow(orgId);
    //                     });
    //                 }
    //             } else {
    //                 App.showNotification('組織リストの読み込みに失敗しました', 'error');
    //             }
    //         })
    //         .catch(error => {
    //             App.showNotification('組織リストの読み込みに失敗しました', 'error');
    //             console.error(error);
    //         });
    // },

    // 追加組織のセレクトボックス構築
    buildAdditionalOrgSelect: function (organizations) {
        // テンプレートを生成
        let orgOptions = '<option value="">選択してください</option>';
        organizations.forEach(org => {
            orgOptions += `<option value="${org.id}">${org.name}</option>`;
        });

        // テンプレートを保存
        this.organizationOptions = orgOptions;
    },

    // 追加組織の行を追加
    addOrganizationRow: function (selectedOrgId = '') {
        const primaryOrgId = $('#organization_id').val();
        const rowId = new Date().getTime();
        const rowHtml = `
            <div class="form-row organization-row mb-2">
                <div class="col-10">
                    <select name="additional_org_${rowId}" class="form-control additional-org-select" data-selected="${selectedOrgId}">
                        ${this.organizationOptions}
                    </select>
                </div>
                <div class="col-2">
                    <button type="button" class="btn btn-danger remove-organization">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        `;

        $('#organizations-container').append(rowHtml);

        // 選択値をセット
        if (selectedOrgId) {
            $(`select[name="additional_org_${rowId}"]`).val(selectedOrgId);
        }

        // 主組織と同じ組織は選択できないようにする
        this.updateOrganizationSelects();
    },

    // 組織セレクトボックスの選択肢を更新
    updateOrganizationSelects: function () {
        const primaryOrgId = $('#organization_id').val();

        // 追加組織のセレクトボックスから主組織を除外
        $('.additional-org-select').each(function () {
            const selectedVal = $(this).val();

            $(this).find('option').prop('disabled', false);

            if (primaryOrgId) {
                $(this).find(`option[value="${primaryOrgId}"]`).prop('disabled', true);
            }

            // 他の追加組織で選択されている値も除外
            $('.additional-org-select').not(this).each(function () {
                const otherVal = $(this).val();
                if (otherVal) {
                    $(this).find(`option[value="${otherVal}"]`).prop('disabled', true);
                }
            });

            // 選択値が無効になった場合はリセット
            if (selectedVal && $(this).find(`option[value="${selectedVal}"]`).prop('disabled')) {
                $(this).val('');
            }
        });
    },

    // 所属組織リストの初期化
    initOrganizationList: function () {
        const userId = $('#user-id').val();

        // ユーザーの組織を取得
        App.apiGet('/users/' + userId + '/organizations')
            .then(response => {
                if (response.success) {
                    const organizations = response.data;
                    const orgListContainer = $('#organization-list');

                    if (organizations.length > 0) {
                        let html = '<ul class="list-group">';

                        organizations.forEach(org => {
                            const primaryBadge = org.is_primary
                                ? '<span class="badge bg-primary ml-2">主組織</span>'
                                : '';

                            html += `
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <a href="${BASE_PATH}/organizations/view/${org.id}">${org.name}</a>
                                        ${primaryBadge}
                                    </div>
                                </li>
                            `;
                        });

                        html += '</ul>';
                        orgListContainer.html(html);
                    } else {
                        orgListContainer.html('<p>所属組織はありません</p>');
                    }
                } else {
                    App.showNotification('組織情報の読み込みに失敗しました', 'error');
                }
            })
            .catch(error => {
                App.showNotification('組織情報の読み込みに失敗しました', 'error');
                console.error(error);
            });
    },

    // パスワード強度チェック
    checkPasswordStrength: function (password) {
        const passwordInput = $('#password').length ? $('#password') : $('#new_password');
        const strengthMeter = $('#password-strength');
        const feedback = $('#password-feedback');

        if (!password) {
            passwordInput.removeClass('is-valid is-invalid');
            strengthMeter.css('width', '0%').removeClass('bg-danger bg-warning bg-info bg-success');
            feedback.text('');
            return;
        }

        // 強度チェック
        let strength = 0;
        const messages = [];

        // 文字数チェック
        if (password.length < 8) {
            messages.push('8文字以上にしてください');
        } else {
            strength += 1;
        }

        // 大文字を含むか
        if (!/[A-Z]/.test(password)) {
            messages.push('大文字を含めてください');
        } else {
            strength += 1;
        }

        // 小文字を含むか
        if (!/[a-z]/.test(password)) {
            messages.push('小文字を含めてください');
        } else {
            strength += 1;
        }

        // 数字を含むか
        if (!/[0-9]/.test(password)) {
            messages.push('数字を含めてください');
        } else {
            strength += 1;
        }

        // 記号を含むか
        if (!/[^A-Za-z0-9]/.test(password)) {
            messages.push('記号を含めてください');
        } else {
            strength += 1;
        }

        // 強度に応じて表示を変更
        let strengthPercent, strengthClass, strengthText;

        switch (strength) {
            case 0:
            case 1:
                strengthPercent = '20%';
                strengthClass = 'bg-danger';
                strengthText = '非常に弱い';
                passwordInput.removeClass('is-valid').addClass('is-invalid');
                break;
            case 2:
                strengthPercent = '40%';
                strengthClass = 'bg-warning';
                strengthText = '弱い';
                passwordInput.removeClass('is-valid').addClass('is-invalid');
                break;
            case 3:
                strengthPercent = '60%';
                strengthClass = 'bg-info';
                strengthText = '普通';
                passwordInput.removeClass('is-invalid').addClass('is-valid');
                break;
            case 4:
                strengthPercent = '80%';
                strengthClass = 'bg-success';
                strengthText = '強い';
                passwordInput.removeClass('is-invalid').addClass('is-valid');
                break;
            case 5:
                strengthPercent = '100%';
                strengthClass = 'bg-success';
                strengthText = '非常に強い';
                passwordInput.removeClass('is-invalid').addClass('is-valid');
                break;
        }

        strengthMeter.css('width', strengthPercent).removeClass('bg-danger bg-warning bg-info bg-success').addClass(strengthClass);

        if (messages.length > 0) {
            feedback.html(strengthText + ': ' + messages.join('、'));
        } else {
            feedback.html(strengthText);
        }
    },

    // フォームのバリデーション
    validateForm: function () {
        let isValid = true;

        // 必須フィールドのチェック
        const requiredFields = ['username', 'email', 'first_name', 'last_name', 'display_name'];

        // 新規作成時はパスワードも必須
        if (!$('#id').length) {
            requiredFields.push('password', 'password_confirm');
        }

        requiredFields.forEach(field => {
            const input = $('#' + field);

            if (!input.val().trim()) {
                input.addClass('is-invalid');
                input.next('.invalid-feedback').text('この項目は必須です');
                isValid = false;
            } else {
                input.removeClass('is-invalid');
            }
        });

        // メールアドレスの形式チェック
        const emailInput = $('#email');
        if (emailInput.val() && !this.isValidEmail(emailInput.val())) {
            emailInput.addClass('is-invalid');
            emailInput.next('.invalid-feedback').text('有効なメールアドレスを入力してください');
            isValid = false;
        }

        // パスワードの一致チェック
        if ($('#password').val() && $('#password').val() !== $('#password_confirm').val()) {
            $('#password_confirm').addClass('is-invalid');
            $('#password_confirm').next('.invalid-feedback').text('パスワードが一致しません');
            isValid = false;
        }

        // 追加組織の値を収集
        const additionalOrgs = [];
        $('.additional-org-select').each(function () {
            const value = $(this).val();
            if (value) {
                additionalOrgs.push(value);
            }
        });

        // 隠しフィールドに設定
        $('#additional_organizations').val(additionalOrgs.join(','));

        return isValid;
    },

    // パスワードフォームのバリデーション
    validatePasswordForm: function () {
        let isValid = true;

        // 現在のパスワードが入力されているか
        if ($('#current_password').length && !$('#current_password').val().trim()) {
            $('#current_password').addClass('is-invalid');
            $('#current_password').next('.invalid-feedback').text('現在のパスワードを入力してください');
            isValid = false;
        } else {
            $('#current_password').removeClass('is-invalid');
        }

        // 新しいパスワードが入力されているか
        if (!$('#new_password').val().trim()) {
            $('#new_password').addClass('is-invalid');
            $('#new_password').next('.invalid-feedback').text('新しいパスワードを入力してください');
            isValid = false;
        } else {
            $('#new_password').removeClass('is-invalid');
        }

        // パスワード確認が一致するか
        if ($('#new_password').val() !== $('#new_password_confirm').val()) {
            $('#new_password_confirm').addClass('is-invalid');
            $('#new_password_confirm').next('.invalid-feedback').text('パスワードが一致しません');
            isValid = false;
        } else {
            $('#new_password_confirm').removeClass('is-invalid');
        }

        return isValid;
    },

    // メールアドレスバリデーション
    isValidEmail: function (email) {
        const re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
        return re.test(email);
    }
};