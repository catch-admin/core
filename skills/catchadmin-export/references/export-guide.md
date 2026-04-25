# CatchAdmin Export Guide

This reference keeps the main skill short and captures the current export surface in the repo.

## Current file paths

- Export class: `modules/{Module}/Excel/Export/{Name}Export.php`
- Controller: `modules/{Module}/Http/Controllers/{Name}Controller.php`

## Current frontend wiring

- `catch-table` shows the export button when the `exports` prop is present.
- The generator adds an `exportUrl` prop for pages with export operations, following the current generated resource path.
- Controller actions commonly return a query collection `download()` result for one-off exports.

## Engine choice

| Use case | Path |
| --- | --- |
| Small or standard export | `Catch\Support\Excel\Export` |
| Large export with lower memory pressure | `Catch\Support\Excel\XlsWriterExport` |
| One-off query download | Collection export macros such as `download()` |
| CSV streaming | CSV-oriented export macros or a CSV-capable export flow |

## Controller shape

```php
public function export(): mixed
{
    return User::query()
        ->select('id', 'username', 'email', 'created_at')
        ->get()
        ->download(['id', '昵称', '邮箱', '创建时间']);
}
```

Controller code stays thin: build the query, call `download()`, and return the response.

## Async flow

- Use `AsyncTaskInterface` when the export should run through the async task worker.
- Use `AsyncTaskDispatch` on the export class to enqueue the job payload.
- Keep `run(array $params)` as the async entry point and route it through `setParams($params)->export()`.

## Export class shape

```php
<?php

declare(strict_types=1);

namespace Modules\{Module}\Excel\Export;

use Catch\Support\Excel\Export;

class {Name}Export extends Export
{
    protected array $header = ['ID', '名称', '状态'];

    public function array(): array
    {
        return \Modules\{Module}\Models\{Model}::query()
            ->select('id', 'name', 'status')
            ->get()
            ->toArray();
    }
}
```

## Practical checks

- Keep header order aligned with the returned columns.
- Use `XlsWriterExport` for large datasets and memory-sensitive jobs.
- Keep async exports compatible with the task worker.
