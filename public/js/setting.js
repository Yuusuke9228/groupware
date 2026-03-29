/**
 * 設定管理用JavaScript
 */
document.addEventListener('DOMContentLoaded', function () {
    // 基本設定フォーム
    const settingsForm = document.getElementById('settingsForm');
    if (settingsForm) {
        settingsForm.addEventListener('submit', function (e) {
            e.preventDefault();

            // フォームデータの収集
            const formData = {};
            const formElements = settingsForm.elements;
            for (let i = 0; i < formElements.length; i++) {
                const element = formElements[i];
                if (element.name && element.name !== '') {
                    if (element.type === 'checkbox') {
                        formData[element.name] = element.checked ? '1' : '0';
                    } else {
                        formData[element.name] = element.value;
                    }
                }
            }

            // API呼び出し
            fetch(`${BASE_PATH}/api/settings`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(formData)
            })
                .then(response => response.json())
                .then(data => {
                    const successAlert = document.getElementById('settingsSuccessAlert');
                    const errorAlert = document.getElementById('settingsErrorAlert');

                    if (data.success) {
                        successAlert.classList.remove('d-none');
                        errorAlert.classList.add('d-none');

                        // 3秒後に成功メッセージを非表示
                        setTimeout(() => {
                            successAlert.classList.add('d-none');
                        }, 3000);
                    } else {
                        errorAlert.textContent = data.error || '設定の保存に失敗しました。';
                        errorAlert.classList.remove('d-none');
                        successAlert.classList.add('d-none');
                    }
                })
                .catch(error => {
                    const errorAlert = document.getElementById('settingsErrorAlert');
                    errorAlert.textContent = '通信エラーが発生しました。';
                    errorAlert.classList.remove('d-none');
                    document.getElementById('settingsSuccessAlert').classList.add('d-none');
                });
        });
    }

    // SMTP設定フォーム
    const smtpSettingsForm = document.getElementById('smtpSettingsForm');
    if (smtpSettingsForm) {
        const transportSelect = document.getElementById('mail_transport');
        const smtpFields = document.getElementById('smtpFields');
        const sendmailFields = document.getElementById('sendmailFields');
        const smtpAuthCheckbox = document.getElementById('smtp_auth');

        const toggleMailTransportFields = () => {
            const transport = transportSelect ? transportSelect.value : 'smtp';

            if (smtpFields) {
                smtpFields.classList.toggle('d-none', transport !== 'smtp');
            }
            if (sendmailFields) {
                sendmailFields.classList.toggle('d-none', transport !== 'sendmail');
            }
        };

        const toggleSmtpAuthFields = () => {
            const useAuth = smtpAuthCheckbox ? smtpAuthCheckbox.checked : true;
            document.querySelectorAll('.smtp-auth-field').forEach(el => {
                el.classList.toggle('d-none', !useAuth);
            });
        };

        if (transportSelect) {
            transportSelect.addEventListener('change', toggleMailTransportFields);
        }
        if (smtpAuthCheckbox) {
            smtpAuthCheckbox.addEventListener('change', toggleSmtpAuthFields);
        }
        toggleMailTransportFields();
        toggleSmtpAuthFields();

        smtpSettingsForm.addEventListener('submit', function (e) {
            e.preventDefault();

            // フォームデータの収集
            const formData = {};
            const formElements = smtpSettingsForm.elements;
            for (let i = 0; i < formElements.length; i++) {
                const element = formElements[i];
                if (element.name && element.name !== '') {
                    if (element.type === 'checkbox') {
                        formData[element.name] = element.checked ? '1' : '0';
                    } else {
                        formData[element.name] = element.value;
                    }
                }
            }

            // API呼び出し
            fetch(`${BASE_PATH}/api/settings`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(formData)
            })
                .then(response => response.json())
                .then(data => {
                    const successAlert = document.getElementById('smtpSuccessAlert');
                    const errorAlert = document.getElementById('smtpErrorAlert');

                    if (data.success) {
                        successAlert.classList.remove('d-none');
                        errorAlert.classList.add('d-none');

                        // 3秒後に成功メッセージを非表示
                        setTimeout(() => {
                            successAlert.classList.add('d-none');
                        }, 3000);
                    } else {
                        errorAlert.textContent = data.error || '設定の保存に失敗しました。';
                        errorAlert.classList.remove('d-none');
                        successAlert.classList.add('d-none');
                    }
                })
                .catch(error => {
                    const errorAlert = document.getElementById('smtpErrorAlert');
                    errorAlert.textContent = '通信エラーが発生しました。';
                    errorAlert.classList.remove('d-none');
                    document.getElementById('smtpSuccessAlert').classList.add('d-none');
                });
        });
    }

    // メール送信テストボタン
    const testEmailBtn = document.getElementById('testEmailBtn');
    if (testEmailBtn) {
        testEmailBtn.addEventListener('click', function () {
            const testEmail = document.getElementById('test_email').value;

            if (!testEmail) {
                alert('テスト送信先メールアドレスを入力してください。');
                return;
            }

            testEmailBtn.disabled = true;
            testEmailBtn.textContent = '送信中...';

            // API呼び出し
            fetch(`${BASE_PATH}/api/settings/test-smtp`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ test_email: testEmail })
            })
                .then(response => response.json())
                .then(data => {
                    testEmailBtn.disabled = false;
                    testEmailBtn.textContent = 'テスト送信';

                    if (data.success) {
                        alert(`テストメールを送信しました。\n送信先: ${testEmail}`);
                    } else {
                        alert(`メール送信に失敗しました。\nエラー: ${data.error}`);
                    }
                })
                .catch(error => {
                    testEmailBtn.disabled = false;
                    testEmailBtn.textContent = 'テスト送信';
                    alert('通信エラーが発生しました。');
                });
        });
    }

    // 通知設定フォーム
    const notificationSettingsForm = document.getElementById('notificationSettingsForm');
    if (notificationSettingsForm) {
        notificationSettingsForm.addEventListener('submit', function (e) {
            e.preventDefault();

            // フォームデータの収集
            const formData = {};
            const formElements = notificationSettingsForm.elements;
            for (let i = 0; i < formElements.length; i++) {
                const element = formElements[i];
                if (element.name && element.name !== '') {
                    if (element.type === 'checkbox') {
                        formData[element.name] = element.checked ? '1' : '0';
                    } else {
                        formData[element.name] = element.value;
                    }
                }
            }

            // API呼び出し
            fetch(`${BASE_PATH}/api/settings`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(formData)
            })
                .then(response => response.json())
                .then(data => {
                    const successAlert = document.getElementById('notificationSuccessAlert');
                    const errorAlert = document.getElementById('notificationErrorAlert');

                    if (data.success) {
                        successAlert.classList.remove('d-none');
                        errorAlert.classList.add('d-none');

                        // 3秒後に成功メッセージを非表示
                        setTimeout(() => {
                            successAlert.classList.add('d-none');
                        }, 3000);
                    } else {
                        errorAlert.textContent = data.error || '設定の保存に失敗しました。';
                        errorAlert.classList.remove('d-none');
                        successAlert.classList.add('d-none');
                    }
                })
                .catch(error => {
                    const errorAlert = document.getElementById('notificationErrorAlert');
                    errorAlert.textContent = '通信エラーが発生しました。';
                    errorAlert.classList.remove('d-none');
                    document.getElementById('notificationSuccessAlert').classList.add('d-none');
                });
        });
    }

    // 手動でメール送信処理を実行するボタン
    const processCronManually = document.getElementById('processCronManually');
    if (processCronManually) {
        processCronManually.addEventListener('click', function () {
            processCronManually.disabled = true;
            processCronManually.textContent = '処理中...';

            // API呼び出し
            fetch(`${BASE_PATH}/api/settings/process-email-queue`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                }
            })
                .then(response => response.json())
                .then(data => {
                    processCronManually.disabled = false;
                    processCronManually.textContent = '手動でメール送信処理を実行';

                    if (data.success) {
                        alert(`メール送信処理を実行しました。\n処理件数: ${data.data.processed}\n成功: ${data.data.success_count}\n失敗: ${data.data.failed_count}`);
                    } else {
                        alert(`処理に失敗しました。\nエラー: ${data.error}`);
                    }
                })
                .catch(error => {
                    processCronManually.disabled = false;
                    processCronManually.textContent = '手動でメール送信処理を実行';
                    alert('通信エラーが発生しました。');
                });
        });
    }

    // デモデータ管理
    const demoDataYearsEl = document.getElementById('demoDataYears');
    const btnRefreshDemoData = document.getElementById('btnRefreshDemoData');
    const btnRebuildDemoData = document.getElementById('btnRebuildDemoData');

    const runDemoDataAction = (action) => {
        const years = demoDataYearsEl ? parseInt(demoDataYearsEl.value, 10) || 3 : 3;
        const isRebuild = action === 'rebuild';
        const message = isRebuild
            ? '全再構築は破壊的処理です。業務データを削除してデモデータに作り直します。実行しますか？'
            : `本日から${years}年分のデモデータを生成します。実行しますか？`;

        if (!window.confirm(message)) {
            return;
        }

        const successAlert = document.getElementById('demoDataSuccessAlert');
        const errorAlert = document.getElementById('demoDataErrorAlert');
        const activeBtn = isRebuild ? btnRebuildDemoData : btnRefreshDemoData;
        const inactiveBtn = isRebuild ? btnRefreshDemoData : btnRebuildDemoData;
        const originalLabel = activeBtn ? activeBtn.textContent : '';

        if (activeBtn) {
            activeBtn.disabled = true;
            activeBtn.textContent = '処理中...';
        }
        if (inactiveBtn) {
            inactiveBtn.disabled = true;
        }

        fetch(`${BASE_PATH}/api/settings/demo-data`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action,
                years
            })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const msg = data.message || 'デモデータ処理が完了しました。';
                    successAlert.textContent = msg;
                    successAlert.classList.remove('d-none');
                    errorAlert.classList.add('d-none');
                } else {
                    errorAlert.textContent = data.error || 'デモデータ処理に失敗しました。';
                    errorAlert.classList.remove('d-none');
                    successAlert.classList.add('d-none');
                }
            })
            .catch(() => {
                errorAlert.textContent = '通信エラーが発生しました。';
                errorAlert.classList.remove('d-none');
                successAlert.classList.add('d-none');
            })
            .finally(() => {
                if (activeBtn) {
                    activeBtn.disabled = false;
                    activeBtn.textContent = originalLabel;
                }
                if (inactiveBtn) {
                    inactiveBtn.disabled = false;
                }
            });
    };

    if (btnRefreshDemoData) {
        btnRefreshDemoData.addEventListener('click', () => runDemoDataAction('refresh'));
    }
    if (btnRebuildDemoData) {
        btnRebuildDemoData.addEventListener('click', () => runDemoDataAction('rebuild'));
    }

    const collectFormData = (form) => {
        const data = {};
        Array.from(form.elements).forEach((el) => {
            if (!el.name) {
                return;
            }
            if (el.type === 'checkbox') {
                data[el.name] = el.checked ? '1' : '0';
            } else {
                data[el.name] = el.value;
            }
        });
        return data;
    };

    const saveSettingsForm = (form, successEl, errorEl) => {
        const payload = collectFormData(form);
        fetch(`${BASE_PATH}/api/settings`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    successEl.classList.remove('d-none');
                    errorEl.classList.add('d-none');
                    setTimeout(() => successEl.classList.add('d-none'), 3000);
                } else {
                    errorEl.textContent = data.error || '設定保存に失敗しました。';
                    errorEl.classList.remove('d-none');
                    successEl.classList.add('d-none');
                }
            })
            .catch(() => {
                errorEl.textContent = '通信エラーが発生しました。';
                errorEl.classList.remove('d-none');
                successEl.classList.add('d-none');
            });
    };

    const securitySettingsForm = document.getElementById('securitySettingsForm');
    if (securitySettingsForm) {
        securitySettingsForm.addEventListener('submit', function (e) {
            e.preventDefault();
            saveSettingsForm(
                securitySettingsForm,
                document.getElementById('securitySuccessAlert'),
                document.getElementById('securityErrorAlert')
            );
        });
    }

    const ssoSettingsForm = document.getElementById('ssoSettingsForm');
    if (ssoSettingsForm) {
        ssoSettingsForm.addEventListener('submit', function (e) {
            e.preventDefault();
            saveSettingsForm(
                ssoSettingsForm,
                document.getElementById('ssoSuccessAlert'),
                document.getElementById('ssoErrorAlert')
            );
        });
    }

    const scimSettingsForm = document.getElementById('scimSettingsForm');
    if (scimSettingsForm) {
        scimSettingsForm.addEventListener('submit', function (e) {
            e.preventDefault();
            saveSettingsForm(
                scimSettingsForm,
                document.getElementById('scimSuccessAlert'),
                document.getElementById('scimErrorAlert')
            );
        });
    }

    const btnSendTestPush = document.getElementById('btnSendTestPush');
    if (btnSendTestPush) {
        btnSendTestPush.addEventListener('click', function () {
            btnSendTestPush.disabled = true;
            btnSendTestPush.textContent = '送信中...';
            fetch(`${BASE_PATH}/api/pwa/test-push`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({})
            })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message || 'Push通知を送信しました。');
                    } else {
                        alert(data.error || 'Push通知の送信に失敗しました。');
                    }
                })
                .catch(() => {
                    alert('通信エラーが発生しました。');
                })
                .finally(() => {
                    btnSendTestPush.disabled = false;
                    btnSendTestPush.textContent = 'テストPush送信';
                });
        });
    }

    const scimTokenCreateForm = document.getElementById('scimTokenCreateForm');
    if (scimTokenCreateForm) {
        scimTokenCreateForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const payload = collectFormData(scimTokenCreateForm);
            fetch(`${BASE_PATH}/api/settings/scim-token`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            })
                .then(r => r.json())
                .then(data => {
                    if (!data.success) {
                        alert(data.error || 'SCIMトークン発行に失敗しました。');
                        return;
                    }
                    const alertEl = document.getElementById('scimTokenPlainAlert');
                    if (alertEl) {
                        alertEl.classList.remove('d-none');
                        alertEl.textContent = `発行トークン（この表示のみ）: ${data.data?.token || ''}`;
                    }
                    window.location.reload();
                })
                .catch(() => {
                    alert('通信エラーが発生しました。');
                });
        });
    }

    document.querySelectorAll('.scim-token-active').forEach((checkbox) => {
        checkbox.addEventListener('change', function () {
            const id = this.dataset.id;
            fetch(`${BASE_PATH}/api/settings/scim-token/${encodeURIComponent(id)}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ is_active: this.checked ? '1' : '0' })
            })
                .then(r => r.json())
                .then(data => {
                    if (!data.success) {
                        alert(data.error || 'SCIMトークン更新に失敗しました。');
                        this.checked = !this.checked;
                    }
                })
                .catch(() => {
                    alert('通信エラーが発生しました。');
                    this.checked = !this.checked;
                });
        });
    });
});
