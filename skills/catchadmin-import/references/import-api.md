# Import API Notes

This reference follows the current `Catch\Support\Excel\Import` base class in the repo.

## Base Class Surface

- `import(string|UploadedFile $filePath, ?string $disk = null, ?string $readerType = null): mixed`
- `importData($filePath): array|int`
- `setParams($params): static`
- `getParams(): array`
- `async(): static`
- `run(array $params): mixed`
- `setDisk(?string $disk): static`
- `setReaderType(?string $readerType): static`
- `chunkSize(): int`
- `startRow(): int`
- `rules(): array`
- `customValidationMessages(): array`
- `customValidationAttributes(): array`

## Current Behavior

- `import()` stores uploaded files under `excel/import/Ymd/`
- Async mode stores `path`, `disk`, and `reader_type` in params and calls `push()`
- `beforeImport()` counts total rows and enforces `static::$importMaxNum`
- Validation errors return row messages plus `total`, `path`, `success`, `failed`, and `warnings`

## Module Patterns

- Standard imports process row collections in `collection(Collection $rows)`
- Visual imports extend the base import and add heading-row and history handling
- Controller import endpoints stay thin and inject the import class directly

## Choosing Sync or Async

- Use sync imports for smaller files and direct user feedback
- Use async imports for large uploads or queued follow-up processing
- Use the visual import flow when the module needs templates, history, or deferred processing records
