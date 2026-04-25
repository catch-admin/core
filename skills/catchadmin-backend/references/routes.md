# CatchAdmin Route Guidance

## Selection Rule

Inspect `modules/{ModuleName}/routes/route.php` first.

- Match the route style already used in the target module.
- Prefer `Route::adminResource` for new admin CRUD routes.
- Use `Route::apiResource` when the module already uses it or when the route needs `only`, `except`, or custom names.

## Shared Conventions

- Route prefixes use lowercase module names.
- Resource paths use lowercase segments.
- Keep `//next` in generator-managed route files so generated routes append in the right place.

## Examples

```php
Route::prefix('cms')->group(function () {
    Route::adminResource('post', PostController::class);
    //next
});
```

```php
Route::prefix('permissions')->group(function () {
    Route::apiResource('roles', RolesController::class);
});
```

```php
Route::apiResource('menu', OfficialMenuController::class)->only(['index', 'store', 'destroy']);
```
