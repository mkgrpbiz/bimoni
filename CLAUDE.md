# BIMONI 開発メモ

## プロジェクト概要
美容モニターキャンペーン管理システム。代理店が招待コードでユーザーを集め、ユーザーがモニター案件に応募・報告する。

## 技術スタック
- **フレームワーク**: Laravel 11 (Blade, Eloquent)
- **認証**: LINE LIFF（会員）/ セッション（管理画面・ポータル）
- **ローカル環境**: Laragon (`C:\laragon\www\bimoni`)
- **本番/STG**: Xserver (`sv16576`)

---

## サーバー・デプロイ

### SSH接続
```bash
ssh -i "$env:USERPROFILE\.ssh\xserver.key" -p 10022 mkgrp@sv16576.xserver.jp
```

### デプロイ手順（STGサーバー）
```bash
cd /home/mkgrp/bimoni
git pull
php8.3 artisan migrate --force
```
> **注意**: サーバーのデフォルトPHPは8.0。必ず `php8.3` を使う。

### Git（ローカル）
```powershell
& "C:\laragon\bin\git\cmd\git.exe" add -A
& "C:\laragon\bin\git\cmd\git.exe" commit -m "メッセージ"
& "C:\laragon\bin\git\cmd\git.exe" push origin main
```
> Laragonのgitはシステムパスに入っていないのでフルパス指定が必要。

---

## 主要モデル・DB設計

### User
- `bimoni_user_id`: 独自ID（例: BMN010001）。BMN + 6桁、10001スタート、登録順自動採番
- `referred_by_code`: 登録時に使った招待コード（どのコードから来たか）
- `referral_code` は**廃止**。ユーザー個人の紹介コードは存在しない

### Agent（代理店）
- 親子構造（`parent_id`）。親は子を複数持てる
- `getAllCodeStrings()`: 自分＋子の全コード文字列を返す
- `portalUrl()`: ポータルログインURL

### AgentReferralCode（招待コード）
- 代理店に紐づく招待コード（1代理店複数持てる）
- 登録者がいない場合のみ削除可能

### ReferralPaymentStatus
- 月次の紹介報酬支払い状況を管理

---

## LINE LIFF

```javascript
liff.login({ botPrompt: 'aggressive' });
```
- `aggressive`: 友だち追加を強制（スキップ不可）
- `resources/views/member/auth/login.blade.php` に実装済み

### LINE友だち未追加モーダル
- `resources/views/layouts/member.blade.php` に実装済み
- ログイン済みユーザーに対し `liff.getFriendship()` で友だち追加状態を確認
- 未追加の場合はモーダルを表示して `@204zmull` へ誘導
- **必須設定**: LINEデベロッパーコンソールのLIFFアプリで「Add friend option」をONにして `@204zmull` を紐付けること（これがないと全員に表示される）

---

## ポータル（代理店ポータル）

### 共通フィルター（ユーザー管理・報告管理・報酬管理）
- 累計 / 月次 トグル
- 子代理店フィルター（親代理店のみ表示）
- コード別プルダウン（コードが複数ある場合）

### ページ構成
| ページ | 内容 |
|--------|------|
| ユーザー管理 | 登録者一覧・集計（登録/応募/報告数） |
| 報告管理 | 承認済み報告一覧 |
| 報酬管理 | 案件別報酬集計 |
| 子代理店管理 | 子代理店一覧・コード管理（親のみ） |

### 子代理店作成
- 代理店名・報酬設定・招待コード（任意/空欄=自動生成）
- コード追加・削除（登録者がいない場合のみ削除可）

---

## 管理画面

### 代理店管理
- 一覧: 代理店名・子代理店数・コード数・登録数・応募数・報告数・詳細/削除
- 削除: 登録者がいない代理店のみ可（子代理店・コードも一括削除）

### 紹介報酬管理
- 月次の報酬一覧・承認/支払い処理
- 詳細: 代理店のコード別登録者・承認済み報告を表示

### ユーザー管理
- 登録コード（`referred_by_code`）を表示

---

## 報酬計算ロジック（PortalService::calcReward）
- **親代理店**: `campaign.referral_fee` をそのまま受け取る
- **子代理店**: 親が設定した `child_reward_{fee}` を受け取る（差額が親の利益）

---

## 注意事項・過去のミス

- `alert()` を Promise の `.then()/.catch()` 内で呼ぶとブラウザにブロックされる → `document.execCommand('copy')` で同期コピー後に `alert()` を呼ぶ
- コピーボタンは必ず同期処理 + `alert('コピーしました')` のセットで実装
- SSHの秘密鍵は `C:\Users\user\.ssh\xserver.key`
