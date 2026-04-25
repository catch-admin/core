---
name: catchadmin-export
description: Use when adding or modifying CatchAdmin export classes, controller export actions, export button wiring, Excel or CSV engine choice, or async export flow inside an existing module.
---

# CatchAdmin Export

Use this skill for export work under `modules/{Module}/Excel/Export/` and controller `export()` actions.

## Keep to the current pattern

- Put reusable export classes in the module export folder.
- Keep controller `export()` methods thin.
- Wire `catch-table` with the `exports` prop and the matching export URL.
- Use the async path when the export can run for a long time.

## Engine and API details

See [references/export-guide.md](references/export-guide.md) for the engine choice, controller shape, file paths, and async flow.
