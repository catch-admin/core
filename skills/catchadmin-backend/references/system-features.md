# System Module Features Reference

Use when you need built-in System module capabilities before creating a parallel module-level implementation.

The System module (`modules/System/`) provides built-in infrastructure for common admin panel needs. When exact endpoints matter, read `modules/System/routes/route.php`.

## Dictionary

Manages key-value configuration pairs with hierarchical structure.

- **Model**: `Modules\System\Models\Dictionary`, `Modules\System\Models\DictionaryValues`
- **Routes**: `system/dictionary`, `system/dic/values`
- **Feature**: Auto-generates PHP Enum classes from dictionary data via admin UI
- **Usage**: Store configurable options such as order status, payment types, or admin-facing enums

## Attachments

File upload management with category organization.

- **Model**: `Modules\System\Models\SystemAttachments`, `Modules\System\Models\SystemAttachmentCategory`
- **Routes**: `system/attachments`, `system/attachment/category`
- **Upload Config**: Managed via `UploadConfigController` (storage driver, max size, allowed types)

## Scheduled Tasks (CronTasks)

Admin-manageable scheduled tasks without modifying `Console/Kernel.php`.

- **Model**: `Modules\System\Models\SystemCronTasks`, `Modules\System\Models\SystemCronTasksLog`
- **Routes**: `system/cron/tasks`, `system/cron/log`
- **Features**: Create/edit/enable/disable cron jobs via admin UI, execution logging

## System Configuration

Dynamic configuration loaded into Laravel's config at application boot.

- **Model**: `Modules\System\Models\SystemConfig`
- **Pattern**: `SystemServiceProvider` reads config from cache and merges into `app('config')`

```php
// In SystemServiceProvider::boot()
$systemConfig = Cache::get('system_config', []);
foreach ($systemConfig as $k => $value) {
    app('config')->set($k, $value);
}
```

## Import/Export History

Tracks data import operations with per-record status.

- **Model**: `Modules\System\Models\ImportHistory`, `Modules\System\Models\ImportHistoryRecord`
- **Routes**: `system/import/template`, `system/import/history`
- **Import Template**: `Modules\System\Models\ImportTemplate` - manage reusable import templates

## Webhooks

Outbound webhook management for event notifications.

- **Model**: `Modules\System\Models\Webhooks`
- **Routes**: `system/webhook`
- **Features**: URL, events, secret key, retry configuration

## Connector Log

API request/response logging with analytics.

- **Model**: `Modules\System\Models\ConnectorLog`, `Modules\System\Models\ConnectorLogStatistics`
- **Routes**: `system/connector/*`
- **Analytics**: Summary stats, status code distribution, response time analysis, top-10 requests, throughput views

## Async Tasks

Background task execution framework.

- **Model**: `Modules\System\Models\AsyncTask`
- **Interface**: `Catch\Contracts\AsyncTaskInterface`
- **Routes**: `system/async/task`
- **Usage**: Queue long-running operations such as exports, imports, and batch processing

## SMS

SMS template and verification code management.

- **Model**: `Modules\System\Models\SystemSmsTemplate`, `Modules\System\Models\SystemSmsCode`
- **Routes**: `system/sms/template` plus the SMS config and code routes in `modules/System/routes/route.php`
- **Config**: SMS provider configuration is managed by `SmsConfigController`

## Schema Management

Database table browser with column-level access control.

- **Routes**: `system/schema` plus the field-management routes in `modules/System/routes/route.php`
- **Features**:
  - View all database tables with size, rows, engine info
  - Manage per-role column visibility
  - Column-level access control for sensitive data

## Personal Access Tokens

API token management for programmatic access.

- **Model**: `Modules\System\Models\PersonalAccessTokens`
- **Routes**: `system/personal/access/tokens`

## Available Modules Overview

| Module | Name | Description |
|--------|------|-------------|
| User | `user` | User management, login/operate logs, import/export |
| Permissions | `permissions` | RBAC roles, departments, permissions, dynamic menus |
| System | `system` | Config, dictionary, attachments, cron, webhooks, logs |
| Develop | `develop` | Code generation, module management, schema tools |
| Common | `common` | Shared services: upload, permission import |
| Cms | `cms` | Content management system |
| Wechat | `wechat` | WeChat official account management |
| Openapi | `openapi` | API gateway with HMAC-SHA256 signature, QPS limiting |
| Pay | `pay` | Payment integration (Alipay, WeChat Pay, Douyin Pay) |
| Shop | `shop` | E-commerce management |
| Mail | `mail` | Email marketing |
| Member | `member` | Frontend member management |
| Domain | `domain` | Domain management |
| Ai | `ai` | AI assistant integration |
