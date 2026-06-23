# デプロイ戦略ガイド

> BIMONIの local → stg → production 運用方針。Xserver前提。

---

## 全体構成

```
開発（ローカル）
  ↓ git push
GitHub（コード管理）
  ↓ git pull（手動 or 自動）
STG環境（テスト確認）
  ↓ 確認OK後
本番環境（実サービス）
```

---

## 各環境の役割

### 🖥️ Local（ローカル）

| 項目 | 内容 |
|------|------|
| URL | `http://127.0.0.1:8000` |
| 目的 | 機能開発・デバッグ |
| 操作 | `php artisan serve` で起動 |
| DB | ローカルMySQL（Laragon） |
| .env | `APP_ENV=local` `APP_DEBUG=true` |
| 特徴 | エラー詳細が画面に表示される |

**ローカルでの開発手順：**
```bash
# サーバー起動
cd C:\laragon\www\bimoni
php artisan serve

# 変更後、GitHubへ送る
git add .
git commit -m "feat: ○○機能を追加"
git push origin main
```

---

### 🧪 STG（ステージング環境）

| 項目 | 内容 |
|------|------|
| 推奨URL | `https://stg.bimoni.online` |
| 目的 | 本番と同じ環境でのテスト確認 |
| サーバー | Xserver VPS（推奨）または Xserverビジネス |
| DB | Xserver上のMySQL（本番とは別のDB） |
| .env | `APP_ENV=staging` `APP_DEBUG=false` |
| 特徴 | エラーはログに記録、画面には表示しない |

**STGの用途：**
- 新機能をクライアントに確認してもらう
- 本番反映前の最終テスト
- LIFFなどのLINE連携テスト（本番LINE設定と分離）

---

### 🚀 Production（本番環境）

| 項目 | 内容 |
|------|------|
| 推奨URL | `https://bimoni.online` |
| 目的 | 実際のサービス提供 |
| サーバー | Xserver VPS |
| DB | 本番MySQL（定期バックアップ必須） |
| .env | `APP_ENV=production` `APP_DEBUG=false` |
| 特徴 | セキュリティ最優先、エラーは絶対に表示しない |

---

## Xserverでの環境構築手順

### 前提
- Xserver VPS（Ubuntu 22.04推奨）
- SSH接続可能
- ドメイン取得済み（bimoni.online）
- DNSにAレコード設定済み

---

### STG環境セットアップ手順

#### 1. SSHでサーバーに接続

```bash
ssh root@サーバーIPアドレス
# または
ssh -i ~/.ssh/id_rsa user@サーバーIPアドレス
```

#### 2. 必要なソフトウェアをインストール

```bash
# パッケージ更新
apt update && apt upgrade -y

# PHP 8.3 インストール
apt install -y software-properties-common
add-apt-repository ppa:ondrej/php
apt install -y php8.3 php8.3-fpm php8.3-mysql php8.3-mbstring \
  php8.3-xml php8.3-curl php8.3-zip php8.3-bcmath php8.3-gd

# Composer インストール
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer

# Node.js インストール
curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
apt install -y nodejs

# MySQL インストール
apt install -y mysql-server

# Nginx インストール
apt install -y nginx

# Git インストール
apt install -y git
```

#### 3. MySQLのセットアップ

```bash
mysql_secure_installation

mysql -u root -p
```

```sql
-- データベースとユーザー作成
CREATE DATABASE bimoni_stg CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'bimoni_stg'@'localhost' IDENTIFIED BY '強いパスワード';
GRANT ALL PRIVILEGES ON bimoni_stg.* TO 'bimoni_stg'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

#### 4. GitHubからコードをClone

```bash
cd /var/www
git clone https://github.com/あなたのID/bimoni.git bimoni_stg
cd bimoni_stg
```

#### 5. 環境設定

```bash
# .env作成（.env.exampleをコピー）
cp .env.example .env

# .envを編集
nano .env
```

**.envの主要設定項目：**
```env
APP_NAME=BIMONI
APP_ENV=staging
APP_KEY=（後でartisanで生成）
APP_DEBUG=false
APP_URL=https://stg.bimoni.online

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=bimoni_stg
DB_USERNAME=bimoni_stg
DB_PASSWORD=設定したパスワード

LINE_CHANNEL_ID=（LINEデベロッパーコンソールから）
LINE_CHANNEL_SECRET=
LINE_CHANNEL_ACCESS_TOKEN=
LIFF_ID=
```

#### 6. Laravel セットアップ

```bash
# Composerパッケージインストール（本番用）
composer install --no-dev --optimize-autoloader

# アプリケーションキー生成
php artisan key:generate

# フロントエンドビルド
npm ci
npm run build

# ストレージリンク作成
php artisan storage:link

# データベースマイグレーション
php artisan migrate --force

# キャッシュ最適化
php artisan config:cache
php artisan route:cache
php artisan view:cache

# パーミッション設定
chown -R www-data:www-data /var/www/bimoni_stg
chmod -R 755 /var/www/bimoni_stg/storage
chmod -R 755 /var/www/bimoni_stg/bootstrap/cache
```

#### 7. Nginx 設定

```bash
nano /etc/nginx/sites-available/bimoni_stg
```

```nginx
server {
    listen 80;
    server_name stg.bimoni.online;
    root /var/www/bimoni_stg/public;

    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

```bash
# 設定を有効化
ln -s /etc/nginx/sites-available/bimoni_stg /etc/nginx/sites-enabled/
nginx -t
systemctl reload nginx
```

#### 8. SSL証明書取得（Let's Encrypt・無料）

```bash
apt install -y certbot python3-certbot-nginx
certbot --nginx -d stg.bimoni.online
```

---

### デプロイ（コード更新）手順

ローカルでコードを変更してGitHubにPushした後、STGサーバーに反映する手順：

```bash
# SSHでSTGサーバーに接続
ssh user@サーバーIP

cd /var/www/bimoni_stg

# GitHubから最新コードを取得
git pull origin main

# パッケージ更新（必要な場合）
composer install --no-dev --optimize-autoloader

# フロントエンド再ビルド（CSS/JS変更があった場合）
npm ci && npm run build

# DBマイグレーション（テーブル変更があった場合）
php artisan migrate --force

# キャッシュ再生成
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

### 本番環境（Production）の追加注意事項

本番はSTGと同じ手順ですが、以下を追加で設定してください：

```bash
# STGと同じ手順でセットアップ（DB名・URLを本番用に変更）
# DB: bimoni_prod
# URL: bimoni.online

# 本番.envの追加設定
APP_ENV=production
APP_DEBUG=false
LOG_LEVEL=error

# セッションをsecureに
SESSION_SECURE_COOKIE=true
```

#### 本番運用チェックリスト

- [ ] `APP_DEBUG=false` になっているか
- [ ] `APP_KEY` が設定されているか
- [ ] DBのバックアップが設定されているか（Xserver自動バックアップを活用）
- [ ] SSL証明書が有効か（`https://`でアクセスできるか）
- [ ] `.env`ファイルが`.gitignore`に含まれているか
- [ ] LINE本番チャネルの設定が完了しているか

---

## ブランチとデプロイの対応

```
main ブランチ
  → STG環境に自動or手動デプロイ
  → 確認OK後、本番へ手動デプロイ

feature/xxx ブランチ
  → ローカルでのみ開発
  → mainにマージしてから反映
```

---

## Xserverドメイン・DNS設定

### ドメイン設定

| ドメイン | 役割 | DNSレコード |
|---------|------|-----------|
| `bimoni.online` | 本番 | Aレコード → 本番サーバーIP |
| `stg.bimoni.online` | STG | Aレコード → STGサーバーIP |

### Xserverパネルでの設定手順

1. Xserverコントロールパネルにログイン
2. 「DNSレコード設定」を選択
3. ドメイン `bimoni.online` を選択
4. 「DNSレコード追加」でAレコードを追加：
   - ホスト名: `stg`
   - 種別: `A`
   - 内容: `VPSのIPアドレス`
   - TTL: `3600`

---

## まとめ：最初にやること（優先順）

1. **GitHub** → リポジトリ作成・Pushする（今日）
2. **Xserver VPS** → 契約・SSH接続確認
3. **ドメイン** → bimoni.online 取得・DNS設定
4. **STG環境** → サーバーセットアップ・動作確認
5. **LINE設定** → STG用チャネル作成
6. **本番環境** → STGと同じ手順で構築

---

*最終更新: 2026-06-23*
