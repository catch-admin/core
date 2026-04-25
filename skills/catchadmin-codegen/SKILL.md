---
name: catchadmin-codegen
description: Use when generating CatchAdmin CRUD code from SQL or table definitions inside an existing module, producing backend files, Vue views, routes, and menus.
---

# CatchAdmin Code Generation

Generate CatchAdmin backend and frontend CRUD from a table definition or SQL `CREATE TABLE` input.

## Use this skill for

- SQL to CRUD generation
- generator output checks for route, menu, rollback, and file paths

## Output paths

- List page: `web/src/views/{module}/{resource}/index.vue`
- Dialog form: `web/src/views/{module}/{resource}/form/create.vue`
- Page form: `web/src/views/{module}/{resource}/create.vue`
- Route file: `modules/{Module}/routes/route.php`

See [references/sql-to-curd.md](references/sql-to-curd.md) for the current generator order, output paths, route behavior, rollback flow, and menu rules.

Use the backend and frontend skills alongside this one when you need implementation-level file edits.
