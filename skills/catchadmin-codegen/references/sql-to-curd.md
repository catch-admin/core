# SQL to CRUD Reference

This reference holds the mapping detail that the main skill keeps lightweight.

## Inputs

- SQL `CREATE TABLE` statement or existing table structure
- Module name in PascalCase
- Controller / resource name
- Dialog form mode or page form mode
- Operation set such as `export`, `import`, `enable`, `dynamic`

## Generator order

`Generator::generate()` runs in this order:

1. Optional dynamic builder
2. Model
3. Request
4. Controller
5. Frontend list page
6. Frontend form
7. Route append
8. Menu create
9. Schema file snapshot save

## Output paths

- `modules/{Module}/Models/{Name}.php`
- `modules/{Module}/Http/Requests/{Name}Request.php`
- `modules/{Module}/Http/Controllers/{Name}Controller.php`
- `web/src/views/{module}/{resource}/index.vue`
- `web/src/views/{module}/{resource}/form/create.vue`
- `web/src/views/{module}/{resource}/create.vue`
- `modules/{Module}/routes/route.php`

## Route behavior

- The generator appends `Route::adminResource('{api}', {Controller}::class);`
- The append point is the `//next` marker, with support for `// next`
- The route file keeps the module prefix wrapper
- The API path comes from the controller name, such as `ArticleCategoryController` → `article/category`
- `Route::adminResource` reflects the controller methods that exist on the class

## Rollback behavior

- Non-menu exceptions trigger rollback
- Rollback deletes created files
- Rollback restores the original route file content
- `MenuCreateFailException` is surfaced separately
- Because files are written during each create step, a menu failure can leave generated files in place

## Menu behavior

- The permissions table receives the menu data
- A top menu is created for the module when one is missing
- The page menu points to the generated list view
- Page mode creates a hidden create-page menu
- Action buttons come from public controller methods

## Column mapping

### SQL to model arrays

- `$fillable`: all columns
- `$fields`: list display columns
- `$form`: edit form columns
- `$searchable`: searchable columns with operators

### Common searchable operators

| Column pattern | Operator |
| --- | --- |
| `varchar` name/title fields | `like` |
| `tinyint` status/type fields | `=` |
| `int` foreign keys ending in `_id` | `=` |
| time fields | `between` |
| enum-like fields | `in` |

### Frontend table behavior

- Enum fields render with the generated text field
- Switch fields render with `switch: true`
- Upload image fields render with `image: true`
- Upload images render with `image: true` and `preview: true`
- `id` uses the label `ID`
- The last table column is `type: 'operate'`

### Frontend search behavior

- Search fields reuse the structure config
- Fields with options render as `select`
- Remote fields render as `remote-select` with `table`, `value`, `label`, and optional `pid`

### Frontend form behavior

- Dialog forms use `web/src/views/{module}/{resource}/form/create.vue`
- Page forms use `web/src/views/{module}/{resource}/create.vue`
- The form stub uses `useCreate` and `useShow`
- Default values come from column defaults when the structure supports them
- Dialog forms pass `primary` into `Create`

## Form component types

The generator currently supports the standard CatchAdmin form components:

- `input`
- `textarea`
- `input-number`
- `select`
- `radio`
- `checkbox`
- `switch`
- `date`
- `datetime`
- `cascader`
- `tree`
- `tree-select`
- `enum`
- `icon`
- `rate`
- `remote-select`
- `remote-tree`
- `remote-tree-select`
- `remote-cascader`
- `upload-image`
- `upload-images`
- `upload-oss`
- `upload-file`
- `upload-files`
- `upload-attachment`
- `upload-attachments`

## Menu defaults

- Top menu type is `1`
- Page menu type is `2`
- Action menu type is `3`
- Status defaults stay aligned with the repo convention: `1` enabled, `2` disabled
