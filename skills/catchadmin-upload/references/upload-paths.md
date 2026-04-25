# CatchAdmin Upload Paths

## Choose The Path

| Need | Current component | Model shape |
| --- | --- | --- |
| Single image from backend upload | `UploadImage` | `string` |
| Single file from backend upload | `UploadFile` | `string` |
| Multiple files from backend upload | `UploadFiles` | `string[]` |
| Simple image upload shell | `index.vue` | `string` |
| Cloud image upload, OSS | `OssUpload` | `string`, comma-joined for multi |
| Cloud image upload, COS | `CosUpload` | `string`, comma-joined for multi |
| Cloud image upload, Qiniu | `QiniuUpload` | `string`, comma-joined for multi |
| Resumable large file upload | `ChunkUpload` | `string` |
| Pick from system attachments | `AttachUpload` | `string` or `string[]` |
| Upload into attachment manager | `UploadAttach` | emits `refresh` |

## Current Repo Patterns

- Components live in `web/src/components/admin/upload/` and are auto-imported.
- Local upload helpers live in `web/src/composables/useUpload.ts`.
- Chunk upload logic lives in `web/src/composables/useChunkUpload.ts`.
- `UploadImage` and `UploadFile` use backend upload actions such as `/upload/image` and `/upload/file`.
- `UploadFiles` appends each returned path into an array model.
- `AttachUpload` opens the system attachment picker and supports `multi`, `center`, `imageWidth`, and `imageClass`.

## Cloud Upload Notes

- `OssUpload`, `CosUpload`, and `QiniuUpload` fetch temporary credentials from `upload/token`.
- The request shape follows the driver in use, with `driver=oss|cos|qiniu`.
- Multi-file cloud uploads return comma-joined URLs in the current components.

## Chunk Upload Notes

- `ChunkUpload` uses `action`, `chunkSize`, `maxFileSize`, `accept`, `showChunkInfo`, `disk`, and `path`.
- It emits `upload-success` and `upload-error`.
- The resumable state comes from `useChunkUpload.ts`.
