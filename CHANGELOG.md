# Changelog

このプロジェクトの注目すべき変更はすべてこのファイルに記録されます。

フォーマットは [Keep a Changelog](https://keepachangelog.com/ja/1.1.0/) に基づいています。

---

## [Unreleased]

### Added / 追加

- 新機能 `Visual Boards` を追加（既存カンバン機能とは分離）
  - ノードベースの思考整理キャンバス（パン / ズーム / 親子ノード / 接続線）
  - テンプレート（Blank / Mind Map / Flowchart / Brainstorm / Planning / Team Planning / Personal Thinking）
  - Undo / Redo、折りたたみ、自動レイアウト
  - 個人 / チーム / 組織共有、タスク連携
  - JSON / PDF / PNG 出力
  - 新規追加ファイル:
    - `Core/VisualBoardModule.php`
    - `Models/VisualBoard.php`
    - `Core/visual_boards_views/*.php`
    - `scripts/upgrade_20260331_visual_boards.sql`
- 認証セキュリティ設定を追加
  - パスワードポリシー（最小文字数 / 英大小文字 / 数字 / 記号）
  - ログイン失敗ロック（回数上限 / 集計時間 / ロック時間）
  - セッションタイムアウト（分）
  - 管理者設定画面のIP許可リスト（IPv4/IPv6 + IPv4 CIDR）
- ユーザーごとのスケジュール表示色設定を追加
  - ユーザー作成時に重複しにくい色を自動割り当て
  - ユーザー編集画面から色変更可能
- 独立モジュール `ファイル共有` を追加（旧 `Drive`）
  - ルート: `/file-share`（`/drive` は互換維持）
  - 大容量ファイル共有向けのアップロード・ダウンロード
  - 共有リンク（公開/限定、有効期限 / パスワード / 回数上限 / 無効化）
  - 共有先ユーザー・組織指定と通知（メール連携）
  - 共有先にアドレス帳連絡先・メールアドレス直指定を追加
  - 専用DBテーブル: `drive_items`, `drive_share_links`, `drive_share_targets`
- システム設定に `ファイル共有` の容量ガバナンスを追加
  - 1ファイル上限 / 全体容量 / ユーザー容量 / 組織容量
  - 共有リンク既定有効日数
- 新機能 `チャット` を追加
  - 2名以上でのチャットグループ作成（組織・権限に依存しないユーザー選択）
  - リアルタイム更新（短周期ポーリング）
  - グループチャット、ファイル添付、既読人数表示
  - チャット未読バッジ（モジュールメニュー / モバイル）
  - PWA/ブラウザ通知対応（メール通知は抑止）
  - 専用DBテーブル: `chat_rooms`, `chat_room_members`, `chat_messages`

### Changed / 変更

- `Core/Router.php` に `Visual Boards` 用の拡張ルート登録を追加
- README の Demo Accounts 重複記載を解消
- `Visual Boards` ノード詳細に「親ノード変更」「ノード複製」を追加
- 組織選択の候補取得を見直し、作成可能な組織一覧を拡張
- ルート直下ファイル監査ドキュメントを追加（`docs/root-file-audit.md`）
- アプリバージョンを `v0.9.0-beta.6` に更新
- スケジュール画面（日/週/月・組織週/月）の配色とカード視認性を改善
- 月間ビュー（個人/組織）をモバイル全幅で表示し、予定カードのタップ視認性を改善
- ヘッダーモジュールメニューを `ファイル共有` 表記に変更
- `ファイル共有` メニューアイコンを `icon_drive.svg` に変更
- アップロード画面で公開共有リンク（ログイン不要）を即時発行できる導線を追加
- ファイル一覧から共有設定/削除に直接アクセスできる導線を追加

### Fixed / 修正

- ノード間接続線の描画を補強（親子関係から描画対象を補完）
- ノード詳細編集時に選択が外れやすい挙動を調整（空白タップ判定を厳密化）
- テンプレートごとの差異が分かる初期構造に修正（同一初期構造を解消）
- PDF/PNG 出力時の接続線描画を通常表示と整合
- ログイン処理のセキュリティ改善
  - 認証デバッグログの過剰出力を削除
  - ログイン成功時のセッションID再生成
  - ログイン失敗の蓄積と一時ロック
- 組織スケジュール月表示でユーザー色が反映されない問題を修正
- `/files/file/:id/checkout` で環境差異によりエラー化するケースを修正
  - `file_checkout_history` 未作成環境でもチェックアウト/解除が500にならないよう防御
  - チェックアウト履歴が使える環境では従来通り履歴記録を継続
  - GET直打ち時は404ではなく詳細画面へ誘導するフォールバック導線を追加
- ファイル共有リンク画面を専用表示へ変更し、ログイン状態に関わらず他メニューが表示されないよう修正
- 限定リンクで管理者が全共有リンクへアクセスできるよう権限判定を改善
- `notification_settings` スキーマに `schedule_view_start_time` / `schedule_view_end_time` を追加し、`Models/Notification.php` との不整合を解消
- インストーラで `db/upgrade_*.sql` を自動適用する処理を追加し、初回導入時の不足テーブル/カラムによるSQLエラーを防止
- パスワード変更画面 `views/user/change_password.php` を追加し、`View user/change_password not found` 例外を解消

### i18n / 多言語

- `Visual Boards` 画面内UIの日本語 / 英語切替表示を追加（`tr_text()` ベース）
- テンプレート名、操作ラベル、ショートカット案内、出力導線を日英対応

---

## [0.9.0-beta.5] - 2026-03-31

### Added / 追加

- `Visual Boards` 作成時に「関連タスクプロジェクト」を選択できる機能を追加
- 選択した関連タスクプロジェクトに合わせて、ノードのタスク連携候補を絞り込む機能を追加
- `/help` 内に `Visual Boards` セクションを追加（`/help#sec-visual-boards`）

### Fixed / 修正

- PC環境で `Visual Boards` のキャンバスが表示されない問題を修正
- スマホ環境でのキャンバス表示と操作性（パン / ピンチズーム / ボタン配置）を改善
- 接続モードで線が作成されないケースを修正し、重複線判定を追加
- 「全体表示」ボタン押下時の反応が分かりにくい問題を修正（ステータスメッセージ表示）

### Changed / 変更

- `Visual Boards` 画面のヘルプ導線を `/help/visual-boards` から `/help#sec-visual-boards` へ変更
- フッターバージョン表示の参照順を改善（`config/config.php` を優先）
- アプリバージョンを `v0.9.0-beta.5` に更新

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
