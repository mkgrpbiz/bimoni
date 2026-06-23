# Git & GitHub 運用ガイド

> 対象：Git初心者向け。BIMONIプロジェクトのバージョン管理について解説します。

---

## Gitとは何か

**Git**は「ファイルの変更履歴を記録するシステム」です。

| よくある例え | Gitでの役割 |
|------------|-----------|
| ゲームのセーブデータ | コミット（保存ポイント） |
| セーブデータの場所 | リポジトリ（Gitの管理エリア） |
| クラウド保存 | GitHub（ネット上のリポジトリ） |
| 開発の分岐 | ブランチ |

### なぜ使うのか
- **バックアップ**：コードを誤って消しても戻せる
- **履歴管理**：「いつ・誰が・何を変えたか」が分かる
- **チーム開発**：複数人が同時に作業できる
- **本番デプロイ**：ローカルのコードを本番サーバーに反映できる

---

## 現在の構成

```
ローカル（あなたのPC）
  C:\laragon\www\bimoni
  └── .git/  ← ここにGitの管理情報が入っている

GitHub（ネット上）
  https://github.com/あなたのID/bimoni
  ↑ ローカルの内容をPushして同期する場所
```

---

## 基本操作：Push方法（ローカル → GitHub）

コードを変更した後、以下の3ステップでGitHubに反映します。

```bash
# ① 変更したファイルをステージング（保存候補にする）
git add .

# ② コミット（保存ポイントを作る）
#    -m の後ろに変更内容のメモを書く
git commit -m "変更内容のメモ（例: ユーザー一覧ページを追加）"

# ③ Push（GitHubに送信）
git push origin main
```

### よく使うコマンド一覧

```bash
# 現在の状態を確認（どのファイルが変更されたか）
git status

# 変更差分を見る
git diff

# コミット履歴を見る
git log --oneline

# 特定のファイルだけステージング
git add ファイル名
```

---

## Pull方法（GitHub → ローカル）

他の人が変更した内容や、サーバーで修正したものをローカルに取り込む場合：

```bash
# GitHubの最新状態をローカルに取り込む
git pull origin main
```

---

## ブランチ運用

**ブランチ**とは「作業用の分岐」です。  
直接`main`を編集せず、作業用ブランチを作るのが安全な運用です。

### 推奨フロー

```
main（本番・安定版）
  └── develop（開発・結合用）
        ├── feature/ログイン機能
        ├── feature/応募フォーム修正
        └── fix/バグ修正
```

### ブランチ操作コマンド

```bash
# 新しいブランチを作って移動
git checkout -b feature/機能名

# 例：ログイン機能のブランチ作成
git checkout -b feature/login-improvement

# ブランチ一覧を見る
git branch

# mainブランチに戻る
git checkout main

# 作業ブランチをmainにマージ（取り込む）
git merge feature/機能名
```

### 現在のシンプル運用（一人開発の場合）

まずはブランチ切り替えなしで`main`に直接Pushしても構いません。  
チームが増えたら上記のブランチ運用に移行してください。

---

## ⚠️ 注意事項

### 絶対にGitHubにアップしてはいけないもの

| ファイル | 理由 |
|---------|------|
| `.env` | DBパスワード・APIキーが含まれる |
| `vendor/` | Composerで再生成できる（容量が大きい） |
| `node_modules/` | npmで再生成できる |
| `storage/logs/` | ログは環境依存 |

> `.gitignore`に記載済みのため、`git add .`しても自動的に除外されます。

### コミットメッセージのルール（推奨）

```
feat: 新機能追加
fix: バグ修正
docs: ドキュメント更新
style: デザイン変更
refactor: リファクタリング
chore: 設定・雑多な変更
```

例：`git commit -m "feat: 案件一覧ページにカテゴリ絞り込みを追加"`

### 困ったときのコマンド

```bash
# 直前のコミットを取り消す（ファイルは残る）
git reset --soft HEAD~1

# 変更を全て破棄してコミット時点に戻す ⚠️危険
git checkout -- ファイル名

# GitHubの状態を確認
git remote -v
```

---

## Git初期設定（初回のみ）

```bash
# ユーザー名とメールアドレスを登録
git config --global user.name "あなたの名前"
git config --global user.email "your-email@example.com"

# 設定確認
git config --list
```

---

*最終更新: 2026-06-23*
