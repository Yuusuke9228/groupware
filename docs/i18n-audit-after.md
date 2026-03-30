# i18n Audit After

- Generated at: 2026-03-30 21:35 JST
- Branch: `main`
- Method:
  - Runtime dictionary generation: `python3 -u scripts/generate_runtime_i18n_map.py`
  - Global runtime translation: `Core/RuntimeI18n.php` (`en` locale only)
  - Re-scan: dictionary value Japanese residue check

## Summary

- Scanned files: `206`
- Extracted Japanese UI candidates: `10019`
- Runtime translation dictionary entries: `10019`
- Pending translation count after regeneration: `0` (new candidates were merged)
- Dictionary values still containing Japanese: `2` (punctuation-only exceptions)

## Residual Exceptions

| カテゴリ | ファイル | 日本語文言 | 想定翻訳キー | 優先度 |
|---|---|---|---|---|
| 共通UI | `config/runtime_i18n_en.json` | `・` | `runtime.literal.dot` | P3 |
| 共通UI | `config/runtime_i18n_en.json` | `Ver. ・` | `runtime.literal.version_dot` | P3 |

注記: 上記は意味語ではなく記号のため、英語UI実用性への影響はありません。

## Demo Verification (2026-03-30)

Playwrightで `?lang=en` の可視テキストを確認した結果（管理者ログイン後）:

| 画面 | 日本語トークン件数 | 判定 |
|---|---:|---|
| `/` | 0 | UI残件なし |
| `/schedule` | 0 | UI残件なし |
| `/task` | 0 | UI残件なし |
| `/workflow` | 0 | UI残件なし |
| `/messages` | 0 | UI残件なし |
| `/bulletin` | 0 | UI残件なし |
| `/daily-report` | 0 | UI残件なし |
| `/webdatabase` | 0 | UI残件なし |
| `/settings` | 0 | UI残件なし |
| `/users` | 0 | UI残件なし |
| `/organizations` | 0 | UI残件なし |
| `/notifications` | 0 | UI残件なし |
| `/help` | 0 | UI残件なし |
| `/help/admin-manual` | 0 | UI残件なし |

補足: 途中で `public/js/runtime-i18n.js` がデモ環境で `403`（配信権限不備）となり、動的翻訳が効かない期間がありました。`644` に修正し再検証済みです。

## Runtime Coverage Notes

- `Controller::view` 経由の全画面は、`en` 時に HTMLテキスト/主要属性を自動翻訳。
- `public/index.php` と `SsoController` のログイン直描画も runtime translation 対象化。
- APIレスポンスは `message/error/status_message` を `en` 時に翻訳。
- JS動的文言は `public/js/runtime-i18n.js` で以下を補完:
  - `alert / confirm / prompt`
  - `toastr`
  - `App.showNotification`
  - MutationObserver による遅延DOM翻訳
  - DataTables英語デフォルト強制

## Help / Admin Manual

- `HelpController` は `en` 時も同一ソース（`help/index`, `help/admin_manual`, `help/install_manual`, `help/terms`）を利用。
- これにより、情報量を日本語版と同等のまま runtime translation で英語表示。
