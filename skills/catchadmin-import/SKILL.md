---
name: catchadmin-import
description: Use when implementing Excel/CSV import classes, controller import endpoints, column-index validation, async import dispatch, or visual import flows under `modules/**/Excel/Import/`.
---

# CatchAdmin Import

## Scope

Use the repo's import base class and the existing module import style first.

## Core Rules

- Create import classes under `modules/{Module}/Excel/Import/`
- Extend `Catch\Support\Excel\Import`
- Implement `collection(Collection $rows): void`
- Read row values by numeric index, such as `$row[0]` and `$row[1]`
- Use `rules()` with column index keys like `'0'`, `'1'`
- Keep controller endpoints thin: accept the file, inject the import class, return `$import->import(...)`
- Keep async import explicit with `AsyncTaskInterface` plus `AsyncTaskDispatch`

## Minimal Shape

```php
<?php

declare(strict_types=1);

namespace Modules\{Module}\Excel\Import;

use Catch\Contracts\AsyncTaskInterface;
use Catch\Support\Excel\Import;
use Illuminate\Support\Collection;
use Modules\System\Support\Traits\AsyncTaskDispatch;

class {Name} extends Import implements AsyncTaskInterface
{
    use AsyncTaskDispatch;

    protected int $chunkSize = 200;
    protected int $start = 2;
    protected static int $importMaxNum = 5000;

    public function collection(Collection $rows): void
    {
        // Process rows by numeric index.
    }

    public function rules(): array
    {
        return [
            '0' => 'required',
            '1' => 'nullable',
        ];
    }
}
```

## Controller Integration

```php
public function import(Request $request, {Name} $import): mixed
{
    return $import->import($request->file('file'));
}
```

## Validation and Flow

- Keep validation aligned with imported columns and field mapping
- Set `$start` to match the first data row
- Keep `$importMaxNum` in place for large files
- Use async mode for larger uploads with `->async()->import(...)`
- Use the visual import stack only when the module needs template tracking or import history

## References

- [references/import-api.md](references/import-api.md)
