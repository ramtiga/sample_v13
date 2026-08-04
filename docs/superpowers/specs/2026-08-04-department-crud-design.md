# 所属マスタ（Department）CRUD画面 設計

## 目的

所属マスタ（部署マスタ）の登録・参照・更新・削除をブラウザから行える管理画面を、Laravel 13 + laravel-doctrine/orm を使って実装する。

## スコープ

- 所属マスタの一覧・詳細・新規作成・編集・削除（ソフトデリート）画面
- 認証・権限制御は対象外（別タスク）
- APIは提供しない（Blade + 従来型MVCのみ）

## データモデル

エンティティ: `App\Entities\Department`（テーブル名: `departments`）

| プロパティ  | カラム        | 型        | 制約                         |
|-------------|---------------|-----------|------------------------------|
| id          | id            | integer   | PK, auto increment           |
| code        | code          | string    | 必須, unique                 |
| name        | name          | string    | 必須                         |
| sortOrder   | sort_order    | integer   | デフォルト 0                 |
| isActive    | is_active     | boolean   | デフォルト true               |
| deletedAt   | deleted_at    | datetime  | nullable。ソフトデリート用    |

- ソフトデリートは `gedmo/doctrine-extensions` の `SoftDeleteable` 拡張を使用する。
  - 追加パッケージ: `laravel-doctrine/extensions`
  - `config/doctrine.php` の `extensions` に `LaravelDoctrine\Extensions\SoftDeletes\SoftDeleteableExtension::class` を追加して有効化する。
  - エンティティに `#[Gedmo\SoftDeleteable(fieldName: 'deletedAt')]` を付与する。
  - フィルターが有効な間、通常のリポジトリ検索・DQLからは自動的に削除済みレコードが除外される。

## ルーティング

`Route::resource('departments', DepartmentController::class)` を `routes/web.php` に追加。

生成されるルート:
- `GET /departments` — index（一覧）
- `GET /departments/create` — create（新規作成フォーム）
- `POST /departments` — store（新規作成処理）
- `GET /departments/{department}` — show（詳細表示）
- `GET /departments/{department}/edit` — edit（編集フォーム）
- `PUT/PATCH /departments/{department}` — update（更新処理）
- `DELETE /departments/{department}` — destroy（削除処理・ソフトデリート）

`{department}` の実体解決（Route Model Binding）はEloquent前提の仕組みのため使わず、コントローラー内で `EntityManager` の `find()` を使って明示的に取得する。

## コントローラー設計

`App\Http\Controllers\DepartmentController` で `Doctrine\ORM\EntityManagerInterface` をコンストラクタ経由でDI（Laravelのサービスコンテナが自動解決）。

- **index**: 全件を `sortOrder` 昇順で取得し一覧表示（ページネーションなし）
- **create**: 空の入力フォームを表示
- **store**: `StoreDepartmentRequest` でバリデーション後、`Department` を new して setter で値を詰め、`persist` + `flush`
- **show**: `find()` で該当エンティティを取得し詳細表示。見つからなければ404
- **edit**: `find()` で取得し編集フォームに値を渡す。見つからなければ404
- **update**: `UpdateDepartmentRequest` でバリデーション後、対象エンティティの setter を呼んで `flush`
- **destroy**: `find()` で取得し `remove` + `flush`（SoftDeleteable拡張により実際にはUPDATE文でdeleted_atがセットされる）

## バリデーション

- `App\Http\Requests\StoreDepartmentRequest`
  - `code`: required, string, max:50, unique:App\Entities\Department,code
  - `name`: required, string, max:255
  - `sort_order`: nullable, integer
  - `is_active`: nullable, boolean
- `App\Http\Requests\UpdateDepartmentRequest`
  - 同様だが `code` のunique判定はルートパラメータのIDを除外して判定する（`unique:App\Entities\Department,code,{id}`相当）

`config/doctrine.php` の `doctrine_presence_verifier` が既に `true` に設定されているため、LaravelのバリデーションルールでDoctrineエンティティを直接指定した `unique` / `exists` ルールがそのまま機能する。

## 画面（Blade）

`resources/views/departments/` 配下に以下を作成:
- `index.blade.php` — 一覧テーブル（code, name, sort_order, is_active、各行に詳細/編集/削除リンク）
- `create.blade.php` — 新規作成フォーム
- `edit.blade.php` — 編集フォーム
- `show.blade.php` — 詳細表示
- フォーム部分は `create` / `edit` で共通の `_form.blade.php` パーシャルに切り出す

レイアウトは既存の `resources/views/welcome.blade.php` 相当の最小限のレイアウトを流用・簡略化した独自レイアウト（`layouts/app.blade.php`）を新規作成する。

## エラーハンドリング

- バリデーションエラー: Form Requestが自動的にリダイレクト＋エラーメッセージをセッションに格納。Bladeで `@error` ディレクティブを使い表示。
- 存在しないID指定（show/edit/update/destroy）: `find()` の結果が `null` の場合、`abort(404)`。

## テスト方針

- Feature test（`tests/Feature/DepartmentControllerTest.php`）で以下を確認:
  - 一覧表示に作成済みレコードが含まれる
  - 新規作成（正常系・バリデーションエラー系）
  - 更新（正常系）
  - 削除後、一覧に表示されなくなる（ソフトデリートのため物理的にはレコードが残ることも確認）
- テスト用DBはsqlite（既存の`.env`設定を流用、テスト実行時にスキーマを構築）
