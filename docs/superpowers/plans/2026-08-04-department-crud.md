# 所属マスタ（Department）CRUD Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** 所属マスタ（Department）の一覧・詳細・作成・編集・削除（ソフトデリート）ができるBlade画面をLaravel 13 + laravel-doctrine/ormで実装する。

**Architecture:** `App\Entities\Department` というDoctrineエンティティを中心に、`DepartmentController`が`EntityManagerInterface`を直接使って永続化を行う、従来型MVC構成。バリデーションはLaravelのForm Requestで行い、`doctrine_presence_verifier`経由でDoctrineエンティティに対するunique/existsチェックを行う。ソフトデリートはgedmo拡張（`SoftDeleteableExtension`）に任せ、`remove()`+`flush()`が自動的にUPDATE文に変換される。

**Tech Stack:** Laravel 13.23.0 (PHP 8.4使用。`/opt/homebrew/opt/php/bin/php`で実行), laravel-doctrine/orm 3.3.3, laravel-doctrine/extensions 2.0.3, gedmo/doctrine-extensions ^3.22, SQLite

## Global Constraints

- PHPコマンドは必ず `/opt/homebrew/opt/php/bin/php`（PHP 8.4系）を使う。デフォルトの`php`は8.2でLaravel 13の要件（^8.3）を満たさない。
- Composerコマンドは `/opt/homebrew/opt/php/bin/php /opt/homebrew/bin/composer` の形式で実行する。
- `php artisan doctrine:schema:update --force` を実行する前に、`app/Providers/AppServiceProvider.php` の `DOCTRINE_MANAGED_TABLES` 定数に対象テーブル名が含まれていることを必ず確認する。含まれていないと、Doctrineが管理していない全テーブル（`users`, `cache`, `jobs`など）がDROPされる重大な事故につながる。
- エンティティは `App\Entities` 名前空間、`app/Entities/` ディレクトリに配置する（`config/doctrine.php` の `paths` 設定に従う）。
- テストは `Doctrine\ORM\Tools\SchemaTool` を使ってインメモリSQLite上にスキーマを構築する（Eloquentの`RefreshDatabase`はDoctrineエンティティに効かないため使わない）。

---

## File Structure

| ファイル | 責務 |
|---|---|
| `app/Providers/AppServiceProvider.php` | Doctrine SchemaToolの対象テーブルをホワイトリストで絞るfilter設定（既存ファイルに追記・修正） |
| `bootstrap/providers.php` | `PresenceVerifierProvider`を明示登録（既存ファイルに追記） |
| `config/doctrine.php` | `SoftDeleteableExtension`を有効化（既存ファイルに1行変更） |
| `app/Entities/Department.php` | Departmentエンティティ本体（新規） |
| `app/Http/Requests/StoreDepartmentRequest.php` | 新規作成時のバリデーション（新規） |
| `app/Http/Requests/UpdateDepartmentRequest.php` | 更新時のバリデーション（新規） |
| `app/Http/Controllers/DepartmentController.php` | 7アクション（index/create/store/show/edit/update/destroy）（新規） |
| `routes/web.php` | `Route::resource('departments', ...)` を追加（既存ファイルに追記） |
| `resources/views/layouts/app.blade.php` | 共通レイアウト（新規） |
| `resources/views/departments/index.blade.php` | 一覧画面（新規） |
| `resources/views/departments/_form.blade.php` | create/edit共通フォームパーシャル（新規） |
| `resources/views/departments/create.blade.php` | 新規作成画面（新規） |
| `resources/views/departments/edit.blade.php` | 編集画面（新規） |
| `resources/views/departments/show.blade.php` | 詳細画面（新規） |
| `tests/Feature/DepartmentControllerTest.php` | Feature test一式（新規） |

---

### Task 1: 基盤整備（gedmo拡張導入・schema filter設定・PresenceVerifier登録）

**Files:**
- Modify: `composer.json`（`laravel-doctrine/extensions`, `gedmo/doctrine-extensions` を追加）
- Modify: `config/doctrine.php:108`（`SoftDeleteableExtension`のコメントを外す）
- Modify: `bootstrap/providers.php`
- Modify: `app/Providers/AppServiceProvider.php`

**Interfaces:**
- Produces: `app/Providers/AppServiceProvider.php` の `DOCTRINE_MANAGED_TABLES` 定数（`string[]`）— 以降のタスクでDepartmentテーブルを追加する際にここに `'departments'` を足す。

- [ ] **Step 1: パッケージをインストール**

```bash
/opt/homebrew/opt/php/bin/php /opt/homebrew/bin/composer require laravel-doctrine/extensions gedmo/doctrine-extensions
```

Expected: `laravel-doctrine/extensions (2.0.3)` と `gedmo/doctrine-extensions (^3.22)` がインストールされ、`composer.json`の`require`に追加される。

- [ ] **Step 2: SoftDeleteableExtensionを有効化**

`config/doctrine.php` の107〜108行目付近を以下のように変更する（`SoftDeleteableExtension`の行だけコメントを外す）:

```php
    'extensions'                 => [
        //LaravelDoctrine\Extensions\Timestamps\TimestampableExtension::class,
        LaravelDoctrine\Extensions\SoftDeletes\SoftDeleteableExtension::class,
        //LaravelDoctrine\Extensions\Sluggable\SluggableExtension::class,
```

- [ ] **Step 3: PresenceVerifierProviderを明示登録**

`bootstrap/providers.php` を以下の内容に置き換える:

```php
<?php

use App\Providers\AppServiceProvider;
use LaravelDoctrine\ORM\Validation\PresenceVerifierProvider;

return [
    AppServiceProvider::class,
    PresenceVerifierProvider::class,
];
```

- [ ] **Step 4: AppServiceProviderにschema asset filterを実装**

`app/Providers/AppServiceProvider.php` を以下の内容に置き換える:

```php
<?php

namespace App\Providers;

use Doctrine\ORM\EntityManagerInterface;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Any table not managed by a Doctrine entity is invisible to
     * doctrine:schema:* commands, so Eloquent-owned tables never get dropped.
     */
    private const DOCTRINE_MANAGED_TABLES = [
        'posts',
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(EntityManagerInterface $em): void
    {
        $em->getConnection()->getConfiguration()->setSchemaAssetsFilter(
            static fn (string|object $assetName): bool => in_array(
                is_string($assetName) ? $assetName : $assetName->getName(),
                self::DOCTRINE_MANAGED_TABLES,
                true
            )
        );
    }
}
```

- [ ] **Step 5: unique validationがDoctrineエンティティに対して動作することを確認**

```bash
/opt/homebrew/opt/php/bin/php artisan tinker --execute="
echo get_class(app('validation.presence')) . PHP_EOL;
"
```

Expected: `LaravelDoctrine\ORM\Validation\DoctrinePresenceVerifier` と出力される（`Illuminate\Validation\DatabasePresenceVerifier` ではない）。

- [ ] **Step 6: schema filterがEloquent管理テーブルを保護することを確認**

```bash
/opt/homebrew/opt/php/bin/php artisan doctrine:schema:update --dump-sql
```

Expected: `[OK] Nothing to update - your database is already in sync with the current entity metadata.` と出力される。`DROP TABLE`を含むSQLが一切出力されないこと。

- [ ] **Step 7: Commit**

```bash
git add composer.json composer.lock config/doctrine.php bootstrap/providers.php app/Providers/AppServiceProvider.php
git commit -m "$(cat <<'EOF'
Add gedmo soft-delete extension and protect Eloquent tables from Doctrine schema tool

Installs laravel-doctrine/extensions + gedmo/doctrine-extensions for
SoftDeleteable support, registers the Doctrine presence verifier
explicitly (deferred provider was losing to Laravel's default), and
adds a schema-assets filter so `doctrine:schema:update` only ever
touches Doctrine-managed tables. Without the filter it drops every
Eloquent-managed table (users, cache, jobs, ...) that isn't mapped as
a Doctrine entity.

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>
EOF
)"
```

---

### Task 2: Departmentエンティティ作成とスキーマ構築

**Files:**
- Create: `app/Entities/Department.php`
- Modify: `app/Providers/AppServiceProvider.php:15`（`DOCTRINE_MANAGED_TABLES`に`'departments'`を追加）

**Interfaces:**
- Consumes: なし（Task 1の基盤設定のみに依存）
- Produces:
  - `App\Entities\Department` クラス。以降のタスクで使うpublicメソッド:
    - `getId(): int`
    - `getCode(): string` / `setCode(string $code): void`
    - `getName(): string` / `setName(string $name): void`
    - `getSortOrder(): int` / `setSortOrder(int $sortOrder): void`
    - `isActive(): bool` / `setIsActive(bool $isActive): void`
  - テーブル `departments`（カラム: `id`, `code`, `name`, `sort_order`, `is_active`, `deleted_at`）

- [ ] **Step 1: Departmentエンティティを作成**

`app/Entities/Department.php` を新規作成:

```php
<?php

namespace App\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity]
#[ORM\Table(name: 'departments')]
#[Gedmo\SoftDeleteable(fieldName: 'deletedAt')]
class Department
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private int $id;

    #[ORM\Column(type: 'string', length: 50, unique: true, nullable: false)]
    private string $code;

    #[ORM\Column(type: 'string', length: 255, nullable: false)]
    private string $name;

    #[ORM\Column(name: 'sort_order', type: 'integer', nullable: false, options: ['default' => 0])]
    private int $sortOrder = 0;

    #[ORM\Column(name: 'is_active', type: 'boolean', nullable: false, options: ['default' => true])]
    private bool $isActive = true;

    #[ORM\Column(name: 'deleted_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $deletedAt = null;

    public function getId(): int
    {
        return $this->id;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): void
    {
        $this->code = $code;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(int $sortOrder): void
    {
        $this->sortOrder = $sortOrder;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): void
    {
        $this->isActive = $isActive;
    }
}
```

- [ ] **Step 2: マッピングが認識されることを確認**

```bash
/opt/homebrew/opt/php/bin/php artisan doctrine:info
```

Expected: `[OK]   App\Entities\Department` が一覧に含まれる。

- [ ] **Step 3: AppServiceProviderのホワイトリストにdepartmentsを追加**

`app/Providers/AppServiceProvider.php` の `DOCTRINE_MANAGED_TABLES` を以下に変更:

```php
    private const DOCTRINE_MANAGED_TABLES = [
        'posts',
        'departments',
    ];
```

- [ ] **Step 4: スキーマ更新内容をdump-sqlで確認（実行はまだしない）**

```bash
/opt/homebrew/opt/php/bin/php artisan doctrine:schema:update --dump-sql
```

Expected: `CREATE TABLE departments (...)` のみが出力される。`DROP TABLE`が含まれないことを目視確認する。

- [ ] **Step 5: スキーマを実際に適用**

```bash
/opt/homebrew/opt/php/bin/php artisan doctrine:schema:update --force
```

Expected: `[OK] Database schema updated successfully!`

- [ ] **Step 6: 他のテーブルが無事であることを確認**

```bash
sqlite3 database/database.sqlite ".tables"
```

Expected: `cache`, `cache_locks`, `departments`, `failed_jobs`, `job_batches`, `jobs`, `migrations`, `password_reset_tokens`, `posts`, `sessions`, `users` が全て存在する。

- [ ] **Step 7: スキーマ検証**

```bash
/opt/homebrew/opt/php/bin/php artisan doctrine:schema:validate
```

Expected: `[OK] The mapping files are correct.` と `[OK] The database schema is in sync with the mapping files.` の両方。

- [ ] **Step 8: Commit**

```bash
git add app/Entities/Department.php app/Providers/AppServiceProvider.php
git commit -m "$(cat <<'EOF'
Add Department entity with soft delete support

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>
EOF
)"
```

---

### Task 3: Feature testの土台（SchemaTool経由のインメモリDBセットアップ）

**Files:**
- Create: `tests/Feature/DepartmentControllerTest.php`

**Interfaces:**
- Consumes: `App\Entities\Department`（Task 2で定義したgetter/setter一式）
- Produces: `DepartmentControllerTest` 内の `protected function setUp(): void` パターン。以降のタスクで同じファイルにテストメソッドを追記していく。

- [ ] **Step 1: テストファイルの土台と最初の一覧表示テストを書く**

`tests/Feature/DepartmentControllerTest.php` を新規作成:

```php
<?php

namespace Tests\Feature;

use App\Entities\Department;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Tests\TestCase;

class DepartmentControllerTest extends TestCase
{
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        parent::setUp();

        $this->em = $this->app->make(EntityManagerInterface::class);

        $schemaTool = new SchemaTool($this->em);
        $metadata = $this->em->getMetadataFactory()->getAllMetadata();
        $schemaTool->createSchema($metadata);
    }

    protected function tearDown(): void
    {
        $schemaTool = new SchemaTool($this->em);
        $metadata = $this->em->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($metadata);

        parent::tearDown();
    }

    private function createDepartment(string $code = 'DEV', string $name = '開発部', int $sortOrder = 1): Department
    {
        $department = new Department();
        $department->setCode($code);
        $department->setName($name);
        $department->setSortOrder($sortOrder);
        $department->setIsActive(true);

        $this->em->persist($department);
        $this->em->flush();

        return $department;
    }

    public function test_index_displays_existing_departments(): void
    {
        $this->createDepartment('DEV', '開発部');
        $this->createDepartment('SALES', '営業部');

        $response = $this->get('/departments');

        $response->assertOk();
        $response->assertSee('開発部');
        $response->assertSee('営業部');
    }
}
```

- [ ] **Step 2: テストを実行し失敗を確認（ルート未定義のため）**

```bash
cd /Users/dhane31/php/sample_v13 && /opt/homebrew/opt/php/bin/php artisan test --filter=DepartmentControllerTest
```

Expected: FAIL（`/departments` ルートが存在しないため 404 になる）

- [ ] **Step 3: Commit（まだ実装がないので red state のコミット。次タスクで green にする）**

```bash
git add tests/Feature/DepartmentControllerTest.php
git commit -m "$(cat <<'EOF'
Add failing feature test for department index page

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>
EOF
)"
```

---

### Task 4: ルーティングとコントローラーのindex/showアクション

**Files:**
- Modify: `routes/web.php`
- Create: `app/Http/Controllers/DepartmentController.php`
- Create: `resources/views/layouts/app.blade.php`
- Create: `resources/views/departments/index.blade.php`
- Create: `resources/views/departments/show.blade.php`

**Interfaces:**
- Consumes: `App\Entities\Department` の getter一式（Task 2）
- Produces:
  - `DepartmentController::index(EntityManagerInterface $em): \Illuminate\View\View`
  - `DepartmentController::show(EntityManagerInterface $em, int $department): \Illuminate\View\View`
  - Blade変数: `index.blade.php` は `$departments`（`Department[]`）を受け取る。`show.blade.php` は `$department`（`Department`）を受け取る。

- [ ] **Step 1: ルーティングを追加**

`routes/web.php` を以下の内容に変更:

```php
<?php

use App\Http\Controllers\DepartmentController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::resource('departments', DepartmentController::class);
```

- [ ] **Step 2: 共通レイアウトを作成**

`resources/views/layouts/app.blade.php` を新規作成:

```blade
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <title>@yield('title', '所属マスタ管理')</title>
    <style>
        body { font-family: sans-serif; margin: 2rem; }
        table { border-collapse: collapse; width: 100%; margin-bottom: 1rem; }
        th, td { border: 1px solid #ccc; padding: 0.5rem; text-align: left; }
        .errors { color: #c00; margin-bottom: 1rem; }
        .actions a, .actions button { margin-right: 0.5rem; }
    </style>
</head>
<body>
    <main>
        @yield('content')
    </main>
</body>
</html>
```

- [ ] **Step 3: DepartmentControllerを作成（index, showのみ実装）**

`app/Http/Controllers/DepartmentController.php` を新規作成:

```php
<?php

namespace App\Http\Controllers;

use App\Entities\Department;
use Doctrine\ORM\EntityManagerInterface;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DepartmentController extends Controller
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    public function index(): View
    {
        $departments = $this->em
            ->getRepository(Department::class)
            ->findBy([], ['sortOrder' => 'ASC']);

        return view('departments.index', ['departments' => $departments]);
    }

    public function show(int $department): View
    {
        $entity = $this->findOrFail($department);

        return view('departments.show', ['department' => $entity]);
    }

    private function findOrFail(int $id): Department
    {
        $entity = $this->em->find(Department::class, $id);

        if ($entity === null) {
            throw new NotFoundHttpException('Department not found.');
        }

        return $entity;
    }
}
```

- [ ] **Step 4: 一覧画面を作成**

`resources/views/departments/index.blade.php` を新規作成:

```blade
@extends('layouts.app')

@section('title', '所属マスタ一覧')

@section('content')
    <h1>所属マスタ一覧</h1>

    <p><a href="{{ route('departments.create') }}">新規作成</a></p>

    <table>
        <thead>
            <tr>
                <th>コード</th>
                <th>名称</th>
                <th>表示順</th>
                <th>有効</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($departments as $department)
                <tr>
                    <td>{{ $department->getCode() }}</td>
                    <td>{{ $department->getName() }}</td>
                    <td>{{ $department->getSortOrder() }}</td>
                    <td>{{ $department->isActive() ? '有効' : '無効' }}</td>
                    <td class="actions">
                        <a href="{{ route('departments.show', $department->getId()) }}">詳細</a>
                        <a href="{{ route('departments.edit', $department->getId()) }}">編集</a>
                        <form action="{{ route('departments.destroy', $department->getId()) }}" method="POST" style="display:inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" onclick="return confirm('削除しますか？')">削除</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
```

- [ ] **Step 5: 詳細画面を作成**

`resources/views/departments/show.blade.php` を新規作成:

```blade
@extends('layouts.app')

@section('title', '所属マスタ詳細')

@section('content')
    <h1>所属マスタ詳細</h1>

    <table>
        <tr><th>コード</th><td>{{ $department->getCode() }}</td></tr>
        <tr><th>名称</th><td>{{ $department->getName() }}</td></tr>
        <tr><th>表示順</th><td>{{ $department->getSortOrder() }}</td></tr>
        <tr><th>有効</th><td>{{ $department->isActive() ? '有効' : '無効' }}</td></tr>
    </table>

    <p>
        <a href="{{ route('departments.edit', $department->getId()) }}">編集</a>
        <a href="{{ route('departments.index') }}">一覧に戻る</a>
    </p>
@endsection
```

- [ ] **Step 6: index系のテストを実行して通ることを確認**

```bash
/opt/homebrew/opt/php/bin/php artisan test --filter=DepartmentControllerTest
```

Expected: PASS（`test_index_displays_existing_departments`）

- [ ] **Step 7: showアクションのテストを追加**

`tests/Feature/DepartmentControllerTest.php` の末尾（クラスの閉じ括弧の直前）に以下を追記:

```php

    public function test_show_displays_department_detail(): void
    {
        $department = $this->createDepartment('HR', '人事部');

        $response = $this->get('/departments/' . $department->getId());

        $response->assertOk();
        $response->assertSee('人事部');
        $response->assertSee('HR');
    }

    public function test_show_returns_404_for_missing_department(): void
    {
        $response = $this->get('/departments/99999');

        $response->assertNotFound();
    }
```

- [ ] **Step 8: テストを実行し全て通ることを確認**

```bash
/opt/homebrew/opt/php/bin/php artisan test --filter=DepartmentControllerTest
```

Expected: PASS（3件とも）

- [ ] **Step 9: Commit**

```bash
git add routes/web.php app/Http/Controllers/DepartmentController.php resources/views/layouts/app.blade.php resources/views/departments/index.blade.php resources/views/departments/show.blade.php tests/Feature/DepartmentControllerTest.php
git commit -m "$(cat <<'EOF'
Add department index and show pages

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>
EOF
)"
```

---

### Task 5: 新規作成（create/store）

**Files:**
- Create: `app/Http/Requests/StoreDepartmentRequest.php`
- Create: `resources/views/departments/_form.blade.php`
- Create: `resources/views/departments/create.blade.php`
- Modify: `app/Http/Controllers/DepartmentController.php`（create/storeアクション追加）

**Interfaces:**
- Consumes: `App\Entities\Department` の setter一式（Task 2）
- Produces:
  - `DepartmentController::create(): \Illuminate\View\View`
  - `DepartmentController::store(StoreDepartmentRequest $request): \Illuminate\Http\RedirectResponse`
  - `_form.blade.php` パーシャルが期待する変数: `$department`（新規時は `null`）, `$action`（フォームのsubmit先URL）, `$method`（`'POST'` または `'PUT'`）

- [ ] **Step 1: StoreDepartmentRequestを作成**

`app/Http/Requests/StoreDepartmentRequest.php` を新規作成:

```php
<?php

namespace App\Http\Requests;

use App\Entities\Department;
use Illuminate\Foundation\Http\FormRequest;

class StoreDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:50', 'unique:' . Department::class . ',code'],
            'name' => ['required', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
```

- [ ] **Step 2: 共通フォームパーシャルを作成**

`resources/views/departments/_form.blade.php` を新規作成:

```blade
<form action="{{ $action }}" method="POST">
    @csrf
    @if ($method === 'PUT')
        @method('PUT')
    @endif

    <div>
        <label for="code">コード</label>
        <input type="text" id="code" name="code" value="{{ old('code', $department?->getCode()) }}">
        @error('code')
            <div class="errors">{{ $message }}</div>
        @enderror
    </div>

    <div>
        <label for="name">名称</label>
        <input type="text" id="name" name="name" value="{{ old('name', $department?->getName()) }}">
        @error('name')
            <div class="errors">{{ $message }}</div>
        @enderror
    </div>

    <div>
        <label for="sort_order">表示順</label>
        <input type="number" id="sort_order" name="sort_order" value="{{ old('sort_order', $department?->getSortOrder() ?? 0) }}">
        @error('sort_order')
            <div class="errors">{{ $message }}</div>
        @enderror
    </div>

    <div>
        <label>
            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $department?->isActive() ?? true) ? 'checked' : '' }}>
            有効
        </label>
    </div>

    <button type="submit">保存</button>
</form>
```

- [ ] **Step 3: 新規作成画面を作成**

`resources/views/departments/create.blade.php` を新規作成:

```blade
@extends('layouts.app')

@section('title', '所属マスタ新規作成')

@section('content')
    <h1>所属マスタ新規作成</h1>

    @include('departments._form', [
        'department' => null,
        'action' => route('departments.store'),
        'method' => 'POST',
    ])

    <p><a href="{{ route('departments.index') }}">一覧に戻る</a></p>
@endsection
```

- [ ] **Step 4: DepartmentControllerにcreate/storeアクションを追加**

`app/Http/Controllers/DepartmentController.php` の `use` 文と `show` メソッドの後に以下を追加:

```php
use App\Http\Requests\StoreDepartmentRequest;
use Illuminate\Http\RedirectResponse;
```

（既存の `use` 文の直後に追加）

`show` メソッドの直後に以下を追加:

```php
    public function create(): View
    {
        return view('departments.create');
    }

    public function store(StoreDepartmentRequest $request): RedirectResponse
    {
        $department = new Department();
        $department->setCode($request->validated('code'));
        $department->setName($request->validated('name'));
        $department->setSortOrder((int) ($request->validated('sort_order') ?? 0));
        $department->setIsActive((bool) $request->boolean('is_active', true));

        $this->em->persist($department);
        $this->em->flush();

        return redirect()
            ->route('departments.index')
            ->with('status', '所属マスタを作成しました。');
    }
```

- [ ] **Step 5: 作成成功のテストを追加**

`tests/Feature/DepartmentControllerTest.php` の末尾に追記:

```php

    public function test_store_creates_a_new_department(): void
    {
        $response = $this->post('/departments', [
            'code' => 'MKT',
            'name' => 'マーケティング部',
            'sort_order' => 3,
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('departments.index'));

        $department = $this->em->getRepository(Department::class)->findOneBy(['code' => 'MKT']);
        $this->assertNotNull($department);
        $this->assertSame('マーケティング部', $department->getName());
        $this->assertSame(3, $department->getSortOrder());
        $this->assertTrue($department->isActive());
    }

    public function test_store_fails_validation_with_duplicate_code(): void
    {
        $this->createDepartment('DEV', '開発部');

        $response = $this->post('/departments', [
            'code' => 'DEV',
            'name' => '別の開発部',
        ]);

        $response->assertSessionHasErrors('code');
    }

    public function test_store_fails_validation_without_required_fields(): void
    {
        $response = $this->post('/departments', []);

        $response->assertSessionHasErrors(['code', 'name']);
    }
```

- [ ] **Step 6: テストを実行し全て通ることを確認**

```bash
/opt/homebrew/opt/php/bin/php artisan test --filter=DepartmentControllerTest
```

Expected: PASS（6件とも）

- [ ] **Step 7: Commit**

```bash
git add app/Http/Requests/StoreDepartmentRequest.php resources/views/departments/_form.blade.php resources/views/departments/create.blade.php app/Http/Controllers/DepartmentController.php tests/Feature/DepartmentControllerTest.php
git commit -m "$(cat <<'EOF'
Add department create/store flow with validation

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>
EOF
)"
```

---

### Task 6: 編集（edit/update）

**Files:**
- Create: `app/Http/Requests/UpdateDepartmentRequest.php`
- Create: `resources/views/departments/edit.blade.php`
- Modify: `app/Http/Controllers/DepartmentController.php`（edit/updateアクション追加）

**Interfaces:**
- Consumes: `App\Entities\Department` の getter/setter一式（Task 2）、`_form.blade.php`（Task 5）
- Produces:
  - `DepartmentController::edit(int $department): \Illuminate\View\View`
  - `DepartmentController::update(UpdateDepartmentRequest $request, int $department): \Illuminate\Http\RedirectResponse`

- [ ] **Step 1: UpdateDepartmentRequestを作成**

`app/Http/Requests/UpdateDepartmentRequest.php` を新規作成:

```php
<?php

namespace App\Http\Requests;

use App\Entities\Department;
use Illuminate\Foundation\Http\FormRequest;

class UpdateDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $departmentId = (int) $this->route('department');

        return [
            'code' => [
                'required',
                'string',
                'max:50',
                'unique:' . Department::class . ',code,' . $departmentId . ',id',
            ],
            'name' => ['required', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
```

- [ ] **Step 2: 編集画面を作成**

`resources/views/departments/edit.blade.php` を新規作成:

```blade
@extends('layouts.app')

@section('title', '所属マスタ編集')

@section('content')
    <h1>所属マスタ編集</h1>

    @include('departments._form', [
        'department' => $department,
        'action' => route('departments.update', $department->getId()),
        'method' => 'PUT',
    ])

    <p><a href="{{ route('departments.index') }}">一覧に戻る</a></p>
@endsection
```

- [ ] **Step 3: DepartmentControllerにedit/updateアクションを追加**

`app/Http/Controllers/DepartmentController.php` の `use` 文に以下を追加:

```php
use App\Http\Requests\UpdateDepartmentRequest;
```

`store` メソッドの直後に以下を追加:

```php
    public function edit(int $department): View
    {
        $entity = $this->findOrFail($department);

        return view('departments.edit', ['department' => $entity]);
    }

    public function update(UpdateDepartmentRequest $request, int $department): RedirectResponse
    {
        $entity = $this->findOrFail($department);

        $entity->setCode($request->validated('code'));
        $entity->setName($request->validated('name'));
        $entity->setSortOrder((int) ($request->validated('sort_order') ?? 0));
        $entity->setIsActive((bool) $request->boolean('is_active', true));

        $this->em->flush();

        return redirect()
            ->route('departments.index')
            ->with('status', '所属マスタを更新しました。');
    }
```

- [ ] **Step 4: 更新のテストを追加**

`tests/Feature/DepartmentControllerTest.php` の末尾に追記:

```php

    public function test_update_modifies_existing_department(): void
    {
        $department = $this->createDepartment('DEV', '開発部');

        $response = $this->put('/departments/' . $department->getId(), [
            'code' => 'DEV',
            'name' => '開発本部',
            'sort_order' => 5,
            'is_active' => '0',
        ]);

        $response->assertRedirect(route('departments.index'));

        $this->em->clear();
        $updated = $this->em->find(Department::class, $department->getId());
        $this->assertSame('開発本部', $updated->getName());
        $this->assertSame(5, $updated->getSortOrder());
        $this->assertFalse($updated->isActive());
    }

    public function test_update_allows_keeping_same_code(): void
    {
        $department = $this->createDepartment('DEV', '開発部');

        $response = $this->put('/departments/' . $department->getId(), [
            'code' => 'DEV',
            'name' => '開発部（改称なし）',
        ]);

        $response->assertRedirect(route('departments.index'));
        $response->assertSessionDoesntHaveErrors();
    }
```

- [ ] **Step 5: テストを実行し全て通ることを確認**

```bash
/opt/homebrew/opt/php/bin/php artisan test --filter=DepartmentControllerTest
```

Expected: PASS（8件とも）

- [ ] **Step 6: Commit**

```bash
git add app/Http/Requests/UpdateDepartmentRequest.php resources/views/departments/edit.blade.php app/Http/Controllers/DepartmentController.php tests/Feature/DepartmentControllerTest.php
git commit -m "$(cat <<'EOF'
Add department edit/update flow with validation

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>
EOF
)"
```

---

### Task 7: 削除（destroy・ソフトデリート）

**Files:**
- Modify: `app/Http/Controllers/DepartmentController.php`（destroyアクション追加）

**Interfaces:**
- Consumes: `App\Entities\Department`（Task 2）
- Produces: `DepartmentController::destroy(int $department): \Illuminate\Http\RedirectResponse`

- [ ] **Step 1: DepartmentControllerにdestroyアクションを追加**

`app/Http/Controllers/DepartmentController.php` の `update` メソッドの直後に以下を追加:

```php
    public function destroy(int $department): RedirectResponse
    {
        $entity = $this->findOrFail($department);

        $this->em->remove($entity);
        $this->em->flush();

        return redirect()
            ->route('departments.index')
            ->with('status', '所属マスタを削除しました。');
    }
```

- [ ] **Step 2: 削除（ソフトデリート）のテストを追加**

`tests/Feature/DepartmentControllerTest.php` の末尾に追記:

```php

    public function test_destroy_soft_deletes_department(): void
    {
        $department = $this->createDepartment('TEMP', '一時部署');
        $id = $department->getId();

        $response = $this->delete('/departments/' . $id);

        $response->assertRedirect(route('departments.index'));

        $this->em->clear();

        // フィルターが有効な通常検索では見つからない
        $found = $this->em->find(Department::class, $id);
        $this->assertNull($found);

        // 物理的にはレコードが残っており、deleted_atがセットされている
        $connection = $this->em->getConnection();
        $row = $connection->fetchAssociative(
            'SELECT deleted_at FROM departments WHERE id = ?',
            [$id]
        );
        $this->assertNotNull($row);
        $this->assertNotNull($row['deleted_at']);
    }

    public function test_index_does_not_show_deleted_department(): void
    {
        $department = $this->createDepartment('TEMP', '一時部署');
        $this->em->remove($department);
        $this->em->flush();
        $this->em->clear();

        $response = $this->get('/departments');

        $response->assertOk();
        $response->assertDontSee('一時部署');
    }
```

- [ ] **Step 3: テストを実行し全て通ることを確認**

```bash
/opt/homebrew/opt/php/bin/php artisan test --filter=DepartmentControllerTest
```

Expected: PASS（10件とも）

- [ ] **Step 4: Commit**

```bash
git add app/Http/Controllers/DepartmentController.php tests/Feature/DepartmentControllerTest.php
git commit -m "$(cat <<'EOF'
Add department soft-delete via destroy action

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>
EOF
)"
```

---

### Task 8: 手動E2E確認

**Files:** なし（動作確認のみ）

**Interfaces:** なし

- [ ] **Step 1: 開発サーバーを起動**

```bash
/opt/homebrew/opt/php/bin/php artisan serve --port=8123 &
```

- [ ] **Step 2: 一覧画面を確認**

```bash
curl -s -o /dev/null -w "%{http_code}\n" http://127.0.0.1:8123/departments
```

Expected: `200`

- [ ] **Step 3: 新規作成〜一覧反映〜編集〜削除の一連の流れをcurlで確認**

```bash
curl -s -c /tmp/cookies.txt http://127.0.0.1:8123/departments/create -o /dev/null
CSRF_TOKEN=$(curl -s -b /tmp/cookies.txt http://127.0.0.1:8123/departments/create | grep -o 'name="_token" value="[^"]*"' | sed 's/.*value="//;s/"//')
curl -s -b /tmp/cookies.txt -c /tmp/cookies.txt -X POST http://127.0.0.1:8123/departments \
  -d "_token=$CSRF_TOKEN&code=E2E&name=E2Eテスト部&sort_order=1&is_active=1" \
  -o /dev/null -w "%{http_code}\n"
curl -s http://127.0.0.1:8123/departments | grep -o "E2Eテスト部"
```

Expected: 最初のcurlは `303`（リダイレクト）、最後のgrepで `E2Eテスト部` がヒットする。

- [ ] **Step 4: サーバーを停止し、テスト用cookieファイルを削除**

```bash
kill %1
rm -f /tmp/cookies.txt
```

- [ ] **Step 5: 全テストスイートを実行し、既存のPostエンティティ関連テストも壊れていないことを確認**

```bash
/opt/homebrew/opt/php/bin/php artisan test
```

Expected: 全テストPASS（`DepartmentControllerTest`の10件 + 既存の`ExampleTest`など）。
