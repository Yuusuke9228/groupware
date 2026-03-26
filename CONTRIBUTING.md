# Contributing / コントリビューションガイド

GroupWare プロジェクトへのコントリビュートに興味をお持ちいただきありがとうございます。
このドキュメントでは、コントリビュートの方法とガイドラインについて説明します。

---

## Issue の報告

バグの報告や機能リクエストは GitHub Issues で受け付けています。

### バグ報告

バグを報告する際は、以下の情報を含めてください:

- 再現手順（できるだけ詳細に）
- 期待される動作
- 実際の動作
- 環境情報（PHP バージョン、MySQL バージョン、OS、ブラウザ）
- エラーメッセージやスクリーンショット（ある場合）

### 機能リクエスト

新機能のリクエストには以下を含めてください:

- 機能の概要
- その機能が必要な理由・ユースケース
- 可能であれば、実装案やモックアップ

---

## Pull Request の作成手順

### 1. 開発環境の準備

```bash
# リポジトリをFork後、クローン
git clone https://github.com/YOUR_USERNAME/groupware.git
cd groupware

# 依存パッケージをインストール
composer install

# 設定ファイルを準備
cp config/database_sample.php config/database.php
cp config/config_sample.php config/config.php

# データベースをセットアップ
mysql -u root -p < db/schema.sql
```

### 2. ブランチの作成

```bash
# mainブランチを最新にする
git checkout main
git pull upstream main

# フィーチャーブランチを作成
git checkout -b feature/your-feature-name
```

ブランチ名の規則:

- 新機能: `feature/機能名`
- バグ修正: `fix/修正内容`
- ドキュメント: `docs/変更内容`
- リファクタリング: `refactor/変更内容`

### 3. コードの変更

以下のガイドラインに従ってください。

### 4. コミット

```bash
git add -A
git commit -m "変更内容の簡潔な説明"
```

コミットメッセージの規則:

- 日本語または英語で記述
- 1行目は変更内容の要約（50文字以内推奨）
- 必要に応じて空行の後に詳細を記述

### 5. Push と Pull Request

```bash
git push origin feature/your-feature-name
```

GitHub上でPull Requestを作成し、以下の情報を含めてください:

- 変更内容の説明
- 関連するIssue番号（ある場合は `#123` のように参照）
- テスト方法

---

## コーディング規約

### PHP

- PSR-12 コーディングスタイルに準拠
- クラス名はパスカルケース (`UserController`)
- メソッド名はキャメルケース (`getUserList`)
- インデントはスペース4つ
- ファイルの文字コードは UTF-8（BOMなし）

### JavaScript

- ES6+ の構文を使用
- インデントはスペース2つまたは4つで統一

### SQL

- テーブル名・カラム名はスネークケース (`user_organizations`)
- 予約語は大文字 (`SELECT`, `FROM`, `WHERE`)

### 全般

- 新しいコントローラーは `Controllers/` ディレクトリに配置
- 新しいモデルは `Models/` ディレクトリに配置
- ビューは `views/` ディレクトリに機能単位のサブディレクトリを作成
- APIレスポンスは JSON 形式で統一

---

## ディレクトリ構造

新しいファイルを追加する場合は、既存の構造に従ってください:

```
Controllers/    - コントローラークラス
Models/         - モデルクラス
Core/           - コアライブラリ
views/          - ビューテンプレート（機能単位のサブディレクトリ）
public/css/     - スタイルシート
public/js/      - JavaScript
db/             - データベーススキーマ・マイグレーション
```

---

## テスト

- 新しい機能にはテストを含めてください
- 既存のテストが壊れないことを確認してください

```bash
composer test
```

---

## セキュリティ

セキュリティの脆弱性を発見した場合は、Issue ではなく直接メンテナーに連絡してください。
公開前にセキュリティ修正を行う必要があるためです。

---

## ライセンス

コントリビュートされたコードは [MIT License](LICENSE) のもとで公開されます。
Pull Request を送信することで、このライセンスに同意したものとみなされます。

---

ご質問がある場合は、Issue でお気軽にお尋ねください。
