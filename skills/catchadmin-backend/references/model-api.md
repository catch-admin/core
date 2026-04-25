# CatchModel API Reference

Use when you need the `Catch\Base\CatchModel` method surface or property defaults.

## Base Class

`Catch\Base\CatchModel` extends `Illuminate\Database\Eloquent\Model`

Traits: `BaseOperate`, `ScopeTrait`, `SoftDeletes`, `Trans`, `WithAttributes`, `DateformatTrait`

Key defaults: `$timestamps = false`, `$dateFormat = 'U'`, soft delete via `deleted_at` integer (0 = not deleted).

## CURD Methods (BaseOperate)

### getList(): mixed

Returns one of three shapes: a paginator when `$isPaginate` is true, a flat collection when pagination is disabled and `$asTree` is false, or a tree collection when pagination is disabled and `$asTree` is true. Automatically applies:
- `$fields` for column selection
- `scopeCreator` for creator username when `creator_id` is fillable
- the `quickSearch()` builder macro for `$searchable`
- `$dataRange` for data permission
- `setBeforeGetList` callback
- `$sortField` + `$sortDesc` for ordering
- Dynamic sort from request params (`sortField`, `order`)

### storeBy(array $data): mixed

Creates a record. Auto-behavior:
- Filters data by `$fillable` + `$form`
- Converts null to `''` if `$autoNull2EmptyString = true`
- Sets `created_at` and `updated_at` to `time()` when those columns are available to the model save path
- Sets `creator_id` from authenticated user if `$isFillCreatorId = true`
- Syncs `$formRelations` after creation
- Returns primary key on success, `false` on failure

### createBy(array $data): mixed

Same as `storeBy` for fill/save behavior, but always creates a new model instance and does not sync `$formRelations`. Use when calling in a loop.

### updateBy($id, array $data): mixed

Updates record by primary key. Auto-behavior:
- Filters data by `$fillable` + `$form`
- Removes `created_at` from update data
- Sets `updated_at` to `time()` when that column is available to the model save path
- Syncs `$formRelations` after update
- Returns `true` on success

### deleteBy($id, bool $force = false, bool $softForce = false): ?bool

Soft deletes a record. For models with the parent ID column in `$fillable`, checks for child rows first and throws `FailedException('请先删除子级')` when children exist. Set `$force = true` for hard delete. Set `$softForce = true` to skip relation cleanup after a successful delete.

### deletesBy(array|string $ids, bool $force = false, ?Closure $callback = null): bool

Batch delete. Accepts comma-separated string or array. Runs inside a DB transaction and accepts an optional callback after the delete loop.

### restoreBy(array|string $ids): bool

Restores soft-deleted records by setting `deleted_at = 0`.

### toggleBy($id, string $field = 'status'): bool

Toggles between `Status::Enable (1)` and `Status::Disable (2)`. If `$syncParentFields` includes the field, recursively updates children.

### firstBy($value, $field = null, array $columns = ['*']): ?Model

Finds a single record. Defaults to primary key lookup. Applies `$columnAccess` if enabled, then runs the `afterFirstBy` callback when one is registered.

### setBeforeGetList(Closure $callback): static

Registers a callback to modify the query builder before `getList()` executes.

```php
$this->model->setBeforeGetList(function ($query) {
    return $query->where('type', 1);
})->getList();
```

### batchUpdate(string $field, array $condition, array $data): bool

Batch updates multiple records using raw SQL CASE WHEN for performance.

## Fluent Setters

| Method | Description |
|--------|-------------|
| `setPaginate(bool)` | Enable/disable pagination |
| `disablePaginate()` | Shortcut for `setPaginate(false)` |
| `asTree()` | Enable tree structure output |
| `setDataRange(bool)` | Enable/disable data permission |
| `setColumnAccess(bool)` | Enable/disable column-level access |
| `setParentIdColumn(string)` | Set parent ID column |
| `fillCreatorId(bool)` | Enable/disable creator auto-fill |
| `setAutoNull2EmptyString(bool)` | Enable/disable null-to-empty conversion |
| `withoutForm()` | Clear `$form` array |
| `setSearchable(array)` | Override search config |
| `setQuickSearchCallback(Closure)` | Custom search data transformer |
| `setSyncParentFields(array)` | Set fields synced with parent on toggle |

## Scopes

| Scope | Description |
|-------|-------------|
| `scopeCreator` | Adds `creator` field via subquery from user model |
| `quickSearch()` builder macro | Applies `$searchable` filters from request parameters |
| `scopeDataRange` | Filters by data permission rules |

## Searchable Operators

```php
public array $searchable = [
    'name'   => 'like',    // WHERE name LIKE '%value%'
    'status' => '=',       // WHERE status = value
    'id'     => '<>',      // WHERE id <> value
    'age'    => '>',       // WHERE age > value
    'date'   => 'between', // WHERE date BETWEEN start AND end
    'type'   => 'in',      // WHERE type IN (values)
];
```
