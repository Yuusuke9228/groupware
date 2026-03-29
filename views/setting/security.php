<?php
$appUrlSetting = trim((string)($settings['app_url'] ?? ''));
$runtimeBase = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? 'localhost');
$appUrl = $appUrlSetting !== '' ? rtrim($appUrlSetting, '/') : rtrim($runtimeBase . BASE_PATH, '/');
$scimBasePath = (string)($settings['scim_base_path'] ?? '/api/scim/v2');
$scimBaseUrl = $appUrl . $scimBasePath;
?>
<div class="container">
    <div class="row mb-4">
        <div class="col-md-12">
            <h1 class="h3 mb-2">認証・PWA・SCIM設定</h1>
            <p class="text-muted">PWA、SSO（OIDC/SAML）、SCIMプロビジョニングの運用設定を行います。</p>
        </div>
    </div>

    <div class="row">
        <div class="col-md-3">
            <div class="card">
                <div class="card-header">設定メニュー</div>
                <div class="list-group list-group-flush">
                    <a href="<?= BASE_PATH ?>/settings" class="list-group-item list-group-item-action">基本設定</a>
                    <a href="<?= BASE_PATH ?>/settings/smtp" class="list-group-item list-group-item-action">メール設定</a>
                    <a href="<?= BASE_PATH ?>/settings/notification" class="list-group-item list-group-item-action">通知設定</a>
                    <a href="<?= BASE_PATH ?>/settings/security" class="list-group-item list-group-item-action active">認証・PWA・SCIM</a>
                </div>
            </div>
        </div>

        <div class="col-md-9">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">PWA設定</h5>
                </div>
                <div class="card-body">
                    <form id="securitySettingsForm">
                        <div class="alert alert-success d-none" id="securitySuccessAlert">設定を保存しました。</div>
                        <div class="alert alert-danger d-none" id="securityErrorAlert"></div>

                        <div class="mb-3 form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="pwa_enabled" name="pwa_enabled" <?= ($settings['pwa_enabled'] ?? '0') === '1' ? 'checked' : '' ?>>
                            <label class="form-check-label" for="pwa_enabled">PWAを有効にする</label>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="pwa_app_name">アプリ名</label>
                                <input type="text" class="form-control" id="pwa_app_name" name="pwa_app_name" value="<?= htmlspecialchars($settings['pwa_app_name'] ?? ($settings['app_name'] ?? 'TeamSpace')) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="pwa_short_name">短縮名</label>
                                <input type="text" class="form-control" id="pwa_short_name" name="pwa_short_name" value="<?= htmlspecialchars($settings['pwa_short_name'] ?? 'TeamSpace') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="pwa_theme_color">テーマカラー</label>
                                <input type="text" class="form-control" id="pwa_theme_color" name="pwa_theme_color" value="<?= htmlspecialchars($settings['pwa_theme_color'] ?? '#2b7de9') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="pwa_background_color">背景カラー</label>
                                <input type="text" class="form-control" id="pwa_background_color" name="pwa_background_color" value="<?= htmlspecialchars($settings['pwa_background_color'] ?? '#ffffff') ?>">
                            </div>
                        </div>

                        <hr>

                        <div class="mb-3 form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="pwa_notifications_enabled" name="pwa_notifications_enabled" <?= ($settings['pwa_notifications_enabled'] ?? '0') === '1' ? 'checked' : '' ?>>
                            <label class="form-check-label" for="pwa_notifications_enabled">PWA Push通知を有効にする</label>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label" for="pwa_vapid_subject">VAPID Subject</label>
                                <input type="text" class="form-control" id="pwa_vapid_subject" name="pwa_vapid_subject" value="<?= htmlspecialchars($settings['pwa_vapid_subject'] ?? 'mailto:admin@example.com') ?>">
                                <div class="form-text">`mailto:admin@example.com` の形式、またはURLを指定してください。</div>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label" for="pwa_vapid_public_key">VAPID公開鍵</label>
                                <input type="text" class="form-control" id="pwa_vapid_public_key" name="pwa_vapid_public_key" value="<?= htmlspecialchars($settings['pwa_vapid_public_key'] ?? '') ?>">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label" for="pwa_vapid_private_key">VAPID秘密鍵</label>
                                <input type="password" class="form-control" id="pwa_vapid_private_key" name="pwa_vapid_private_key" value="<?= htmlspecialchars($settings['pwa_vapid_private_key'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="mt-3 d-flex gap-2 flex-wrap">
                            <button type="submit" class="btn btn-primary">設定を保存</button>
                            <button type="button" class="btn btn-outline-primary" id="btnEnableBrowserPush">このブラウザで購読</button>
                            <button type="button" class="btn btn-outline-danger" id="btnDisableBrowserPush">このブラウザの購読解除</button>
                            <button type="button" class="btn btn-outline-secondary" id="btnSendTestPush">テストPush送信</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">SSO設定（OIDC / SAML）</h5>
                </div>
                <div class="card-body">
                    <form id="ssoSettingsForm">
                        <div class="alert alert-success d-none" id="ssoSuccessAlert">設定を保存しました。</div>
                        <div class="alert alert-danger d-none" id="ssoErrorAlert"></div>

                        <div class="mb-3 form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="sso_enabled" name="sso_enabled" <?= ($settings['sso_enabled'] ?? '0') === '1' ? 'checked' : '' ?>>
                            <label class="form-check-label" for="sso_enabled">SSOを有効にする</label>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="sso_provider">認証方式</label>
                            <select class="form-select" id="sso_provider" name="sso_provider">
                                <option value="oidc" <?= ($settings['sso_provider'] ?? 'oidc') === 'oidc' ? 'selected' : '' ?>>OIDC</option>
                                <option value="saml" <?= ($settings['sso_provider'] ?? '') === 'saml' ? 'selected' : '' ?>>SAML 2.0</option>
                            </select>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-4 form-check form-switch ms-1">
                                <input class="form-check-input" type="checkbox" id="sso_local_login_enabled" name="sso_local_login_enabled" <?= ($settings['sso_local_login_enabled'] ?? '1') === '1' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="sso_local_login_enabled">通常ローカルログインを許可</label>
                            </div>
                            <div class="col-md-4 form-check form-switch ms-1">
                                <input class="form-check-input" type="checkbox" id="sso_jit_provisioning" name="sso_jit_provisioning" <?= ($settings['sso_jit_provisioning'] ?? '1') === '1' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="sso_jit_provisioning">初回ログイン時にユーザー自動作成</label>
                            </div>
                        </div>

                        <div class="row g-3 mt-1">
                            <div class="col-md-4">
                                <label class="form-label" for="sso_default_role">JIT既定ロール</label>
                                <select class="form-select" id="sso_default_role" name="sso_default_role">
                                    <option value="user" <?= ($settings['sso_default_role'] ?? 'user') === 'user' ? 'selected' : '' ?>>user</option>
                                    <option value="manager" <?= ($settings['sso_default_role'] ?? '') === 'manager' ? 'selected' : '' ?>>manager</option>
                                    <option value="admin" <?= ($settings['sso_default_role'] ?? '') === 'admin' ? 'selected' : '' ?>>admin</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="sso_default_organization_id">JIT既定組織ID</label>
                                <input type="number" class="form-control" id="sso_default_organization_id" name="sso_default_organization_id" value="<?= htmlspecialchars($settings['sso_default_organization_id'] ?? '1') ?>">
                            </div>
                        </div>

                        <div class="row g-3 mt-1">
                            <div class="col-md-4">
                                <label class="form-label" for="sso_attr_username">ユーザー名属性</label>
                                <input type="text" class="form-control" id="sso_attr_username" name="sso_attr_username" value="<?= htmlspecialchars($settings['sso_attr_username'] ?? 'preferred_username') ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="sso_attr_email">メール属性</label>
                                <input type="text" class="form-control" id="sso_attr_email" name="sso_attr_email" value="<?= htmlspecialchars($settings['sso_attr_email'] ?? 'email') ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="sso_attr_display_name">表示名属性</label>
                                <input type="text" class="form-control" id="sso_attr_display_name" name="sso_attr_display_name" value="<?= htmlspecialchars($settings['sso_attr_display_name'] ?? 'name') ?>">
                            </div>
                        </div>

                        <hr>
                        <h6 class="mb-2">OIDC</h6>
                        <div class="mb-3 form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="oidc_enabled" name="oidc_enabled" <?= ($settings['oidc_enabled'] ?? '0') === '1' ? 'checked' : '' ?>>
                            <label class="form-check-label" for="oidc_enabled">OIDCを有効にする</label>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6"><input class="form-control" name="oidc_issuer" placeholder="Issuer URL" value="<?= htmlspecialchars($settings['oidc_issuer'] ?? '') ?>"></div>
                            <div class="col-md-6"><input class="form-control" name="oidc_client_id" placeholder="Client ID" value="<?= htmlspecialchars($settings['oidc_client_id'] ?? '') ?>"></div>
                            <div class="col-md-6"><input class="form-control" name="oidc_client_secret" placeholder="Client Secret" value="<?= htmlspecialchars($settings['oidc_client_secret'] ?? '') ?>"></div>
                            <div class="col-md-6"><input class="form-control" name="oidc_redirect_uri" placeholder="Redirect URI (省略時自動)" value="<?= htmlspecialchars($settings['oidc_redirect_uri'] ?? '') ?>"></div>
                            <div class="col-md-12"><input class="form-control" name="oidc_scopes" placeholder="Scopes" value="<?= htmlspecialchars($settings['oidc_scopes'] ?? 'openid profile email') ?>"></div>
                            <div class="col-md-4"><input class="form-control" name="oidc_authorization_endpoint" placeholder="Authorization Endpoint (任意)" value="<?= htmlspecialchars($settings['oidc_authorization_endpoint'] ?? '') ?>"></div>
                            <div class="col-md-4"><input class="form-control" name="oidc_token_endpoint" placeholder="Token Endpoint (任意)" value="<?= htmlspecialchars($settings['oidc_token_endpoint'] ?? '') ?>"></div>
                            <div class="col-md-4"><input class="form-control" name="oidc_userinfo_endpoint" placeholder="UserInfo Endpoint (任意)" value="<?= htmlspecialchars($settings['oidc_userinfo_endpoint'] ?? '') ?>"></div>
                        </div>

                        <hr>
                        <h6 class="mb-2">SAML 2.0</h6>
                        <div class="mb-3 form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="saml_enabled" name="saml_enabled" <?= ($settings['saml_enabled'] ?? '0') === '1' ? 'checked' : '' ?>>
                            <label class="form-check-label" for="saml_enabled">SAMLを有効にする</label>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6"><input class="form-control" name="saml_idp_entity_id" placeholder="IdP Entity ID" value="<?= htmlspecialchars($settings['saml_idp_entity_id'] ?? '') ?>"></div>
                            <div class="col-md-6"><input class="form-control" name="saml_idp_sso_url" placeholder="IdP SSO URL" value="<?= htmlspecialchars($settings['saml_idp_sso_url'] ?? '') ?>"></div>
                            <div class="col-md-6"><input class="form-control" name="saml_idp_slo_url" placeholder="IdP SLO URL" value="<?= htmlspecialchars($settings['saml_idp_slo_url'] ?? '') ?>"></div>
                            <div class="col-md-6"><input class="form-control" name="saml_sp_entity_id" placeholder="SP Entity ID (省略時自動)" value="<?= htmlspecialchars($settings['saml_sp_entity_id'] ?? '') ?>"></div>
                            <div class="col-md-6"><input class="form-control" name="saml_sp_acs_url" placeholder="SP ACS URL (省略時自動)" value="<?= htmlspecialchars($settings['saml_sp_acs_url'] ?? '') ?>"></div>
                            <div class="col-md-6"><input class="form-control" name="saml_nameid_format" placeholder="NameID Format" value="<?= htmlspecialchars($settings['saml_nameid_format'] ?? 'urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress') ?>"></div>
                            <div class="col-md-12">
                                <label class="form-label">IdP X.509証明書</label>
                                <textarea class="form-control" rows="4" name="saml_idp_x509_cert" placeholder="-----BEGIN CERTIFICATE----- ..."><?= htmlspecialchars($settings['saml_idp_x509_cert'] ?? '') ?></textarea>
                            </div>
                        </div>

                        <div class="mt-3">
                            <a class="btn btn-outline-secondary btn-sm" href="<?= BASE_PATH ?>/auth/saml/metadata" target="_blank" rel="noopener noreferrer">SPメタデータを表示</a>
                            <a class="btn btn-outline-secondary btn-sm" href="<?= BASE_PATH ?>/login/local-admin">非常口ローカル管理者ログイン</a>
                        </div>
                        <div class="mt-3 d-grid">
                            <button type="submit" class="btn btn-primary">SSO設定を保存</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">SCIM 2.0 設定</h5>
                </div>
                <div class="card-body">
                    <?php if (!($scimSchemaReady ?? true)): ?>
                        <div class="alert alert-warning">
                            SCIM関連テーブルが未作成のため、トークン管理は無効です。
                            <code>db/upgrade_20260329_pwa_sso_scim.sql</code> を適用してください。
                        </div>
                    <?php endif; ?>
                    <form id="scimSettingsForm">
                        <div class="alert alert-success d-none" id="scimSuccessAlert">設定を保存しました。</div>
                        <div class="alert alert-danger d-none" id="scimErrorAlert"></div>

                        <div class="mb-3 form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="scim_enabled" name="scim_enabled" <?= ($settings['scim_enabled'] ?? '0') === '1' ? 'checked' : '' ?>>
                            <label class="form-check-label" for="scim_enabled">SCIM APIを有効にする</label>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="scim_base_path">SCIMベースパス</label>
                            <input class="form-control" id="scim_base_path" name="scim_base_path" value="<?= htmlspecialchars($scimBasePath) ?>">
                            <div class="form-text">ベースURL: <code><?= htmlspecialchars($scimBaseUrl) ?></code></div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">SCIM設定を保存</button>
                        </div>
                    </form>

                    <hr>

                    <form id="scimTokenCreateForm" class="row g-2 align-items-end">
                        <div class="col-md-8">
                            <label class="form-label" for="scim_token_name">新規トークン名</label>
                            <input class="form-control" id="scim_token_name" name="name" placeholder="例: EntraID Provisioning" <?= !($scimSchemaReady ?? true) ? 'disabled' : '' ?>>
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-outline-primary w-100" <?= !($scimSchemaReady ?? true) ? 'disabled' : '' ?>>SCIMトークン発行</button>
                        </div>
                    </form>
                    <div class="alert alert-warning mt-3 d-none" id="scimTokenPlainAlert"></div>

                    <div class="table-responsive mt-3">
                        <table class="table table-sm table-bordered align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>名称</th>
                                    <th>有効</th>
                                    <th>最終使用</th>
                                    <th>作成日時</th>
                                </tr>
                            </thead>
                            <tbody id="scimTokenTableBody">
                                <?php if (empty($scimTokens)): ?>
                                    <tr><td colspan="5" class="text-center text-muted">SCIMトークンはまだありません</td></tr>
                                <?php else: ?>
                                    <?php foreach ($scimTokens as $token): ?>
                                        <tr>
                                            <td><?= (int)$token['id'] ?></td>
                                            <td><?= htmlspecialchars($token['name']) ?></td>
                                            <td>
                                                <div class="form-check form-switch m-0">
                                                    <input class="form-check-input scim-token-active" type="checkbox" data-id="<?= (int)$token['id'] ?>" <?= (int)$token['is_active'] === 1 ? 'checked' : '' ?> <?= !($scimSchemaReady ?? true) ? 'disabled' : '' ?>>
                                                </div>
                                            </td>
                                            <td><?= htmlspecialchars((string)($token['last_used_at'] ?? '')) ?></td>
                                            <td><?= htmlspecialchars((string)($token['created_at'] ?? '')) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
