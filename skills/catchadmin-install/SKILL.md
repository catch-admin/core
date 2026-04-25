---
name: catchadmin-install
description: Use when installing or troubleshooting a CatchAdmin Pro project, including database setup, Laravel initialization, module setup, frontend env configuration, and project startup.
---

# CatchAdmin Install

## Scope

Use this skill for CatchAdmin Pro project installation, interrupted install recovery, environment initialization, and project startup checks.

Work from the project root containing `composer.json` and `web/package.json`.

## Core Flow

1. Confirm the project root.
2. Check local tools: PHP 8.3+, Composer, Git, Node 22+, and Yarn.
3. Read `.env` if it exists and check `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD`.
4. Ask the user only for missing database values.
5. Preserve existing `.env`; create it only when missing, then patch the required keys.
6. Install modules with `php artisan catch:module:install --all --no-interaction`.
7. Continue the manual installation steps in [references/manual-install.md](references/manual-install.md).

## Database Prompt Rule

- Read only `.env` for `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD`.
- Ask the user directly for any missing database value.
- Skip Docker checks, database service checks, MySQL connection tests, default credential attempts, and container startup suggestions.
- Let Laravel migrations surface database connection errors later in the install flow.

## Failure Handling

- For initialization failure, continue from the failed stage after verifying the previous stage outputs.

## Reference

Read [references/manual-install.md](references/manual-install.md) for the full command order, environment examples, validation checklist, and troubleshooting notes.
