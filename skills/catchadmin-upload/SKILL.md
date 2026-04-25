---
name: catchadmin-upload
description: Use when implementing file/image upload, local upload, cloud upload, chunk upload, or attachment picker flows in CatchAdmin Vue code.
---

# CatchAdmin Upload

Use the current upload component in `web/src/components/admin/upload/` that matches the storage path and model shape.

- Local server upload: `UploadImage`, `UploadFile`, `UploadFiles`, `UploadAttach`, `index.vue`
- Cloud storage: `OssUpload`, `CosUpload`, `QiniuUpload`
- Large files: `ChunkUpload`
- System attachments: `AttachUpload`

Prefer `useUpload.ts` for local and cloud-adjacent upload flows, and `useChunkUpload.ts` for resumable chunk workflows.

See `references/upload-paths.md` for the current component matrix, prop notes, and backend endpoint shape.
