---
name: catchadmin-backend
description: Use when creating or modifying CatchAdmin backend modules, controllers, models, migrations, enums, routes, services, or other PHP/Laravel code under `modules/`.
---

# CatchAdmin Backend

## Scope

CatchAdmin backend work follows the repo's current PHP and Laravel config, the modular layout under `modules/{ModuleName}/`, and the conventions already used in the target module.
Use `catchadmin-module` for module skeleton creation, installer/provider scaffolding, route placeholders, and module registration.

## Core Rules

- Use `declare(strict_types=1);` in every PHP file.
- Keep controllers thin. Route requests, call the model or a service, return data.
- Extend `Catch\Base\CatchController` for controllers and `Catch\Base\CatchModel` for models.
- Use `protected readonly` constructor injection when a dependency is injected.
- Keep enum values starting at `1`.
- Use `1 = Enabled` and `2 = Disabled` for status fields.
- Store timestamps and soft-delete fields as `unsignedInteger` Unix timestamps.

## Route Guidance

Check the target module's `routes/route.php` first and follow the route style already in that module.

- Prefer `Route::adminResource` for new admin CRUD routes.
- Use `Route::apiResource` when the module already uses it or when the route needs explicit `only`, `except`, or custom names.
- Keep route prefixes lowercase.
- Keep generator-managed route files aligned with the `//next` marker.

See [references/routes.md](references/routes.md) for route examples and selection notes.

## Model Conventions

`Catch\Base\CatchModel` handles the common list, create, update, delete, toggle, and search flow.

- Define `$table`, `$fillable`, `$fields`, and `$form`.
- Use `$searchable` for quick search fields.
- Keep `creator_id` in fillable lists when the model uses creator tracking.
- Use `$dataRange` when data permission filtering applies.
- Prefer `setBeforeGetList()` for query adjustments that belong with the model.

See [references/model-api.md](references/model-api.md) for the model API surface.

## Validation

- Controllers stay focused on orchestration.
- Models expose the expected list and CRUD fields.
- Routes match the target module style.
- Migrations use unsigned integer timestamps and soft-delete columns.
- Enums start at `1`.

## References

- [references/model-api.md](references/model-api.md)
- [references/system-features.md](references/system-features.md)
- [references/routes.md](references/routes.md)
