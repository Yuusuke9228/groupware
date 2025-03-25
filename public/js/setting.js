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
        smtpSettingsForm.addEventListener('submit', function (e) {
            e.preventDefault();

            // フォームデータの収集
            const formData = {};
            const formElements = smtpSettingsForm.elements;
            for (let i = 0; i < formElements.length; i++) {
                const element = formElements[i];
                if (element.name && element.name !== '') {
                    formData[element.name] = element.value;
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
});