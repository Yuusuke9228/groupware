# GroupWare

組織管理、ユーザー管理、スケジュール管理、メッセージ機能を備えた業務向けグループウェアシステムです。

## 主な機能

- **組織管理**: 階層的な組織構造の管理
- **ユーザー管理**: ユーザー情報の管理と組織への割り当て
- **スケジュール管理**: 日／週／月単位でのスケジュール管理および共有
- **メッセージ機能**: 組織やユーザー間でメッセージを行う

## 動作環境

- PHP 7.4 以上
- MySQL 5.7 以上
- Apache（mod_rewrite有効）または同等のWebサーバー

## インストール手順

### 1. リポジトリのクローン

```bash
git clone https://github.com/Yuusuke9228/groupware.git
cd groupware
```

### 2. データベース設定

```bash
cp config/database_sample.php config/database.php
cp config/config_sample.php config/config.php
nano config/database.php # 必要に応じて設定を編集
nano config/config.php # 必要に応じて設定を編集
composer install
```

### 3. データベースのセットアップ

```bash
mysql -u username -p < db/schema.sql
```

### 4. Webサーバーの設定

- `DocumentRoot`を`groupware/public`に設定
- `mod_rewrite`を有効化

### 初期ログイン情報

| 項目        | 値          |
|-------------|-------------|
| ユーザー名  | `admin`     |
| パスワード  | `admin123`  |

---

## ディレクトリ構造

```
config/         # 設定ファイル
core/           # コアシステム機能
models/         # データモデル
controllers/    # コントローラー
views/          # ビューテンプレート
public/         # 公開Webディレクトリ
db/             # データベース関連ファイル
```

## 開発について

### コーディング規約

- PSR-4 に準拠したオートロード
- 名前空間を使用したクラス分類
- MVC アーキテクチャパターンを採用

### 主要コンポーネント

#### Core

- `Auth`: 認証管理
- `Database`: データベース接続
- `Router`: ルーティング

#### Models

- `Organization`: 組織データ
- `User`: ユーザーデータ
- `Schedule`: スケジュールデータ

#### Controllers

- `OrganizationController`: 組織管理
- `UserController`: ユーザー管理
- `ScheduleController`: スケジュール管理

#### Views

- 各機能の画面テンプレート

---

## ライセンス

MIT License

## 貢献方法

1. リポジトリをForkする。
2. Featureブランチを作成する。

```bash
git checkout -b feature/amazing-feature
```

3. 変更をコミットする。

```bash
git commit -m 'Add some amazing feature'
```

4. ブランチをPushする。

```bash
git push origin feature/amazing-feature
```

5. Pull Requestを作成する。

---

## 連絡先

- 作者: Yuusuke9228
- GitHub: [github.com/Yuusuke9228](https://github.com/Yuusuke9228)
