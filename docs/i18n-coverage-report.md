# i18n Coverage Report (Final)

- Updated at: 2026-03-30 21:35 JST
- Locale pair: `ja` / `en`
- Default locale: `ja`
- Retention: session + cookie (`gw_locale`)

## Module Coverage

| 画面・機能 | 対応状況 | 残件 | 備考 |
|---|---|---:|---|
| 共通UI | 対応済み | 0 | ヘッダー/フッター/共通ナビ/通知UIを runtime translation + 既存 `t()` で対応。 |
| 認証 | 対応済み | 0 | ログイン画面（直描画）を runtime translation 経由に変更。 |
| ダッシュボード | 対応済み | 0 | `Controller::view` の一括翻訳対象。 |
| スケジュール | 対応済み | 0 | 画面本文・JS挿入文言を runtime translation で英語化。 |
| タスク | 対応済み | 0 | ボード系JS通知/確認ダイアログ含め英語化。 |
| ワークフロー | 対応済み | 0 | 申請・承認導線のJS文言を含め英語化。 |
| メッセージ | 対応済み | 0 | 一覧/詳細/通知表示を英語化。 |
| 掲示板 | 対応済み | 0 | 投稿/コメント/カテゴリ導線を英語化。 |
| 日報 | 対応済み | 0 | 一覧/入力/通知/JS文言を英語化。 |
| WEBデータベース | 対応済み | 0 | 一覧/フィールドUI/通知文言を英語化。 |
| システム設定 | 対応済み | 0 | 既存 `t()` + runtime translation。 |
| ユーザー管理 | 対応済み | 0 | 一覧/編集/通知文言を英語化。 |
| 組織管理 | 対応済み | 0 | 一覧/階層/通知文言を英語化。 |
| 通知 | 対応済み | 0 | `message/error/status_message` のAPI翻訳を追加。 |
| JS | 対応済み | 0 | `runtime-i18n.js` で `alert/confirm/toastr/App.showNotification` を翻訳。 |
| DataTables | 対応済み | 0 | 英語デフォルト辞書を強制。 |
| 日付/カレンダー | 対応済み | 0 | `en` 時は英語ロケール固定（既存ローダー + runtime）。 |
| help | 対応済み | 0 | 日本語版本体を英語時に runtime translation。 |
| admin-manual | 対応済み | 0 | 同上（情報量同等を確保）。 |
| メール | 対応済み | 0 | 生成メッセージは runtime translation 対象。 |
| バリデーション | 対応済み | 0 | エラー文言表示面を runtime translation + API翻訳で対応。 |
| モーダル | 対応済み | 0 | HTML/JS挿入モーダル文言を英語化。 |
| バックアップ関連 | 対応済み | 0 | 既存実装 + i18n対応済み。 |

## Notes

- 実装方式は「個別全置換」ではなく「非破壊の全体適用」方式:
  - `ja` 表示は既存動作を維持
  - `en` 表示時のみ runtime translation を適用
- UI残件は `docs/i18n-audit-after.md` の記号2件のみ（可視テキスト検証は主要14画面すべて日本語トークン 0 を確認）。
- デモ環境では `public/js/runtime-i18n.js` の配信権限を `644` に補正し、動的翻訳が有効化されていることを確認。
