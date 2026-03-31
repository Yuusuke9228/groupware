/**
 * 設定管理用JavaScript
 */
document.addEventListener('DOMContentLoaded', function () {
    const tr = (key, replace = {}) => {
        if (typeof window.tJs === 'function') {
            return window.tJs(key, replace);
        }
        return key;
    };
    const tl = (ja, en) => {
        if (typeof window.tLiteral === 'function') {
            return window.tLiteral(ja, en);
        }
        return ja;
    };

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
                        errorAlert.textContent = data.error || tr('js.settings.save_failed');
                        errorAlert.classList.remove('d-none');
                        successAlert.classList.add('d-none');
                    }
                })
                .catch(error => {
                    const errorAlert = document.getElementById('settingsErrorAlert');
                    errorAlert.textContent = tr('js.error.communication');
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
                        errorAlert.textContent = data.error || tr('js.settings.save_failed');
                        errorAlert.classList.remove('d-none');
                        successAlert.classList.add('d-none');
                    }
                })
                .catch(error => {
                    const errorAlert = document.getElementById('smtpErrorAlert');
                    errorAlert.textContent = tr('js.error.communication');
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
                alert(tr('js.settings.test_email_required'));
                return;
            }

            testEmailBtn.disabled = true;
            testEmailBtn.textContent = tr('js.settings.sending');

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
                    testEmailBtn.textContent = tr('js.settings.send_test');

                    if (data.success) {
                        alert(tr('js.settings.test_email_success', { email: testEmail }));
                    } else {
                        alert(tr('js.settings.test_email_failed', { error: data.error || '-' }));
                    }
                })
                .catch(error => {
                    testEmailBtn.disabled = false;
                    testEmailBtn.textContent = tr('js.settings.send_test');
                    alert(tr('js.error.communication'));
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
                        errorAlert.textContent = data.error || tr('js.settings.save_failed');
                        errorAlert.classList.remove('d-none');
                        successAlert.classList.add('d-none');
                    }
                })
                .catch(error => {
                    const errorAlert = document.getElementById('notificationErrorAlert');
                    errorAlert.textContent = tr('js.error.communication');
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
            processCronManually.textContent = tr('js.settings.processing');

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
                    processCronManually.textContent = tr('js.settings.process_email_now');

                    if (data.success) {
                        alert(tr('js.settings.process_email_success', {
                            processed: data.data.processed,
                            success: data.data.success_count,
                            failed: data.data.failed_count
                        }));
                    } else {
                        alert(tr('js.settings.process_email_failed', { error: data.error || '-' }));
                    }
                })
                .catch(error => {
                    processCronManually.disabled = false;
                    processCronManually.textContent = tr('js.settings.process_email_now');
                    alert(tr('js.error.communication'));
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
            ? tl('全再構築は破壊的処理です。業務データを削除してデモデータに作り直します。実行しますか？', 'Rebuild is destructive. Business data will be removed and replaced with demo data. Continue?')
            : tl(`本日から${years}年分のデモデータを生成します。実行しますか？`, `Generate ${years} year(s) of demo data from today. Continue?`);

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
            activeBtn.textContent = tr('js.settings.processing');
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
                    const msg = data.message || tl('デモデータ処理が完了しました。', 'Demo data processing completed.');
                    successAlert.textContent = msg;
                    successAlert.classList.remove('d-none');
                    errorAlert.classList.add('d-none');
                } else {
                    errorAlert.textContent = data.error || tl('デモデータ処理に失敗しました。', 'Demo data processing failed.');
                    errorAlert.classList.remove('d-none');
                    successAlert.classList.add('d-none');
                }
            })
            .catch(() => {
                errorAlert.textContent = tr('js.error.communication');
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
                    errorEl.textContent = data.error || tr('js.settings.save_failed');
                    errorEl.classList.remove('d-none');
                    successEl.classList.add('d-none');
                }
            })
            .catch(() => {
                errorEl.textContent = tr('js.error.communication');
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

    const authSecuritySettingsForm = document.getElementById('authSecuritySettingsForm');
    if (authSecuritySettingsForm) {
        authSecuritySettingsForm.addEventListener('submit', function (e) {
            e.preventDefault();
            saveSettingsForm(
                authSecuritySettingsForm,
                document.getElementById('authSecuritySuccessAlert'),
                document.getElementById('authSecurityErrorAlert')
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
            btnSendTestPush.textContent = tr('js.settings.sending');
            fetch(`${BASE_PATH}/api/pwa/test-push`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({})
            })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message || tl('Push通知を送信しました。', 'Push notification sent.'));
                    } else {
                        alert(data.error || tl('Push通知の送信に失敗しました。', 'Failed to send push notification.'));
                    }
                })
                .catch(() => {
                    alert(tr('js.error.communication'));
                })
                .finally(() => {
                    btnSendTestPush.disabled = false;
                    btnSendTestPush.textContent = tl('テストPush送信', 'Send test push');
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
                        alert(data.error || tl('SCIMトークン発行に失敗しました。', 'Failed to issue SCIM token.'));
                        return;
                    }
                    const alertEl = document.getElementById('scimTokenPlainAlert');
                    if (alertEl) {
                        alertEl.classList.remove('d-none');
                        alertEl.textContent = tl('発行トークン（この表示のみ）: ', 'Issued token (shown only once): ') + (data.data?.token || '');
                    }
                    window.location.reload();
                })
                .catch(() => {
                    alert(tr('js.error.communication'));
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
                        alert(data.error || tl('SCIMトークン更新に失敗しました。', 'Failed to update SCIM token.'));
                        this.checked = !this.checked;
                    }
                })
                .catch(() => {
                    alert(tr('js.error.communication'));
                    this.checked = !this.checked;
                });
        });
    });

    const runBackupBtn = document.getElementById('runBackupBtn');
    const backupCsrfToken = document.getElementById('backupCsrfToken');
    const backupHistoryBody = document.getElementById('backupHistoryBody');
    const backupSuccessAlert = document.getElementById('backupSuccessAlert');
    const backupErrorAlert = document.getElementById('backupErrorAlert');

    const formatBytes = (size) => {
        const value = Number(size || 0);
        if (!Number.isFinite(value) || value <= 0) return '0 B';
        const units = ['B', 'KB', 'MB', 'GB', 'TB'];
        let idx = 0;
        let num = value;
        while (num >= 1024 && idx < units.length - 1) {
            num /= 1024;
            idx += 1;
        }
        return `${num.toFixed(idx === 0 ? 0 : 2)} ${units[idx]}`;
    };

    const statusLabel = (status) => {
        if (status === 'success') return tr('settings.backup.status_success');
        if (status === 'failed') return tr('settings.backup.status_failed');
        if (status === 'running') return tr('settings.backup.status_running');
        return String(status || '-');
    };

    const renderBackupHistory = (rows) => {
        if (!backupHistoryBody) return;
        const list = Array.isArray(rows) ? rows : [];
        if (list.length === 0) {
            backupHistoryBody.innerHTML = `<tr><td colspan=\"8\" class=\"text-center text-muted\">${tr('settings.backup.none') || 'No backup history yet.'}</td></tr>`;
            return;
        }

        backupHistoryBody.innerHTML = list.map((row) => {
            const id = Number(row.id || 0);
            const createdAt = row.created_at || row.started_at || '';
            const actor = row.executed_by_name || row.executed_by || '-';
            const fileName = row.file_name || '-';
            const size = formatBytes(row.file_size || 0);
            const status = statusLabel(row.status);
            const error = row.error_message || '-';
            const canDownload = row.status === 'success' && id > 0;
            const downloadLink = canDownload
                ? `<a href=\"${BASE_PATH}/settings/backup/download/${id}\" class=\"btn btn-sm btn-outline-primary\">${tr('js.backup.download')}</a>`
                : '<span class=\"text-muted\">-</span>';
            return `
                <tr>
                    <td>${id}</td>
                    <td>${actor}</td>
                    <td>${createdAt}</td>
                    <td>${fileName}</td>
                    <td>${size}</td>
                    <td>${status}</td>
                    <td>${error}</td>
                    <td>${downloadLink}</td>
                </tr>
            `;
        }).join('');
    };

    const loadBackupHistory = () => {
        if (!backupHistoryBody) return;
        fetch(`${BASE_PATH}/api/settings/backup/history`, {
            method: 'GET',
            headers: { 'Content-Type': 'application/json' }
        })
            .then((r) => r.json())
            .then((data) => {
                if (!data.success) {
                    throw new Error(data.error || tr('js.backup.run_failed'));
                }
                renderBackupHistory(data.data);
            })
            .catch((error) => {
                if (backupErrorAlert) {
                    backupErrorAlert.textContent = error.message || tr('js.error.communication');
                    backupErrorAlert.classList.remove('d-none');
                }
            });
    };

    if (runBackupBtn) {
        runBackupBtn.addEventListener('click', () => {
            if (!window.confirm(tr('js.backup.run_confirm'))) {
                return;
            }

            if (backupSuccessAlert) backupSuccessAlert.classList.add('d-none');
            if (backupErrorAlert) backupErrorAlert.classList.add('d-none');

            runBackupBtn.disabled = true;
            const originalLabel = runBackupBtn.textContent;
            runBackupBtn.textContent = tr('js.settings.processing');

            fetch(`${BASE_PATH}/api/settings/backup/run`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    csrf_token: backupCsrfToken ? backupCsrfToken.value : ''
                })
            })
                .then((r) => r.json())
                .then((data) => {
                    if (!data.success) {
                        throw new Error(data.error || tr('js.backup.run_failed'));
                    }
                    if (backupSuccessAlert) {
                        backupSuccessAlert.textContent = data.message || tr('js.backup.run_success');
                        backupSuccessAlert.classList.remove('d-none');
                    }
                    loadBackupHistory();
                })
                .catch((error) => {
                    if (backupErrorAlert) {
                        backupErrorAlert.textContent = error.message || tr('js.backup.run_failed');
                        backupErrorAlert.classList.remove('d-none');
                    }
                })
                .finally(() => {
                    runBackupBtn.disabled = false;
                    runBackupBtn.textContent = originalLabel;
                });
        });

        loadBackupHistory();
    }
});
