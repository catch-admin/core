---
name: catchadmin-module
description: Use when creating a new module, scaffolding a module skeleton, setting up Installer or ServiceProvider files, registering a module, or adding the initial route placeholder.
---

# CatchAdmin Module

## Scope

Create the module skeleton only. Use this skill for `modules/{ModuleName}/` setup, registration, and the route placeholder.

## Create

1. `Installer.php` from `assets/installer.stub`
2. `Providers/{ModuleName}ServiceProvider.php` from `assets/service-provider.stub`
3. `routes/route.php` from `assets/route.stub`
4. `storage/app/modules.json` entry

## Placeholder Rules

- `{ModuleName}` is PascalCase
- `{module_name}` is the lowercase route prefix
- `{module_title}`, `{keywords}`, `{description}` come from the module brief

## Route Rule

Keep `routes/route.php` as the placeholder group with `//next`. Add business routes later with `Route::adminResource(...)` before `//next`.

## Next Step

Use `catchadmin-codegen` or `catchadmin-backend` for controllers, models, migrations, and CRUD.
