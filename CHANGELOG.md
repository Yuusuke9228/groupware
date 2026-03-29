# Changelog

このプロジェクトの注目すべき変更はすべてこのファイルに記録されます。

フォーマットは [Keep a Changelog](https://keepachangelog.com/ja/1.1.0/) に基づいています。

---

## [0.9.0-beta.1] - 2026-03-30

### Added / 追加

- GitHub公開向けメタデータ管理ドキュメントを追加
  - `docs/github-metadata.md`
- 初回ベータ公開用のリリースノート原稿を追加
  - `docs/releases/v0.9.0-beta.1.md`
- READMEで利用するスクリーンショット資産を追加
  - `docs/screenshots/*.png`

### Changed / 変更

- 公開リリースチャネルを `v0.9.0-beta.1` として明記
- README のスクリーンショット案内とロードマップ表現を整理
- バージョン表記を `0.9.0-beta.1` に更新
  - `config/config.php`
  - `config/config_sample.php`
  - `config/config.production.php`
  - `README.md` の設定例

### Notes / 補足

- 本タグは「初回公開ベータ」の位置づけです。
- 既存機能の事実を維持し、今後の多言語展開・海外OSS展開は roadmap として分離表記しています。

---

## [1.2.0] - 2026-03-29

### Added / 追加

- PWA基盤を追加
  - `manifest.json` 配信
  - `service-worker.js` 配信
  - オフラインフォールバック画面（`/offline`）
  - インストール導線（対応ブラウザ）
- Web Push 通知基盤を追加
  - 購読登録 / 解除 API（`/api/pwa/subscribe`, `/api/pwa/unsubscribe`）
  - テスト送信 API（`/api/pwa/test-push`）
  - VAPID 鍵管理（設定画面）
- SSO 対応を追加
  - OIDC ログイン（`/auth/oidc/login`, `/auth/oidc/callback`）
  - SAML 2.0 ログイン（`/auth/saml/login`, `/auth/saml/acs`）
  - SAML SP メタデータ配信（`/auth/saml/metadata`）
  - 外部ID連携テーブル（`external_identities`）
- SCIM 2.0 API を追加
  - `ServiceProviderConfig`, `Users` の基本 CRUD
  - Bearer Token 認証
  - SCIMトークン管理（管理画面から発行/無効化）
  - SCIM監査ログ記録
- 非常用ローカル管理者ログイン導線を追加（`/login/local-admin`）
- 管理設定画面「認証・PWA・SCIM」を追加
- 管理者向け/一般向けヘルプに PWA・SSO・SCIM の運用手順を追記

### Changed / 変更

- スマホ表示の改善
  - 日報月間画面の可読性向上（セルサイズ・バッジ・配色）
  - ワークフロー一覧テーブルの sticky header / 先頭列固定を追加
- APIレスポンスで `code` を返した場合、HTTPステータスへ反映するよう Router を改善
- SCIM API の `Content-Type` を `application/scim+json` で返すよう改善
- フッターにバージョン表示を追加
- 依存未導入時に SSO / Push で致命エラーにならないよう autoload 読み込みをフェイルセーフ化

### Tests / テスト

- ユニットテストを追加
  - `SettingControllerValidationTest`
  - `SsoServiceAttributeTest`
  - `SsoControllerSafeRedirectTest`
  - `ScimControllerErrorFormatTest`

### Database / DB変更

- 新規テーブル:
  - `external_identities`
  - `push_subscriptions`
  - `scim_tokens`
  - `auth_audit_logs`
  - `scim_audit_logs`
- 新規アップグレードSQL:
  - `db/upgrade_20260329_pwa_sso_scim.sql`
- 設定キー追加:
  - PWA / SSO(OIDC/SAML) / SCIM 関連の `settings` キー群

---

## [1.1.0] - 2026-03-29

### Added / 追加

- 管理画面（`/settings`）に「デモデータ管理」を追加
  - 本日から N 年分（1-5年）のデモデータ補充
  - 全機能データをデモ用に再構築（破壊的）
- `Services/DemoDataService.php` を追加し、以下モジュールの大量デモデータ生成を実装
  - スケジュール
  - 日報（分析明細・月目標を含む）
  - タスク管理（ボード・カード・チェックリスト・コメント）
  - ワークフロー（テンプレート・申請・承認）
  - 掲示板
  - メッセージ
  - 施設予約
  - アドレス帳
  - ファイル管理（実ファイル付きサンプル）
  - WEBデータベース（レコード・ビュー）
  - 通知
- CLI バッチ `scripts/rebuild_demo_data.php` を追加
  - `--mode=refresh|rebuild`
  - `--years=1..5`
  - CRON 実行に対応

### Changed / 変更

- ライセンスを MIT から GPL-3.0 に変更
  - `LICENSE`
  - `README.md`
  - `CONTRIBUTING.md`
  - `composer.json` (`license` フィールド追加)
- README にデモデータ運用手順（手動実行 / CRON）を追記
- `project-structure.txt` を現行構成に更新

### Notes / 補足

- 既存の運用データを保持したい場合は `refresh` を使用してください。
- `rebuild` は破壊的処理です。デモ環境専用での利用を推奨します。

---

## [1.0.0] - 2024-01-01

### Added / 追加

- スケジュール管理（個人・組織単位、日・週・月ビュー）
- メッセージ機能（受信・送信・スター・返信・転送）
- 掲示板機能（カテゴリ管理、コメント）
- ワークフロー機能（申請テンプレート、フォームデザイナー、承認経路、代理承認、PDF/CSVエクスポート）
- タスク管理機能（カンバンボード、チーム管理、チェックリスト、ラベル）
- 日報機能（テンプレート、コメント、いいね、統計）
- ファイル管理機能（バージョン管理、権限設定、承認リクエスト）
- WEBデータベース機能（カスタムフィールド、リレーション、CSV入出力）
- アドレス帳機能
- 施設予約機能
- 通知システム（アプリ内通知、メール通知）
- 全文検索機能
- 外部連携（Google Calendar、iCal インポート・エクスポート・購読）
- 管理機能（ユーザー管理、組織管理、CSV一括インポート、システム設定）
- レスポンシブデザイン対応
