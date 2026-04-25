# Support API

Current frontend support modules live in `web/src/support/`:

- `http.ts` exports a shared `http` instance from the local `Request` wrapper
- `helper.ts` exports token, env, URL, date, route, and general helper functions
- `cache.ts` exports `Cache`
- `message.ts` exports `Message`
- `options.ts` exports shared option lists such as `status`

## `http`

Use the shared `http` instance for custom requests:

```ts
import http from '@/support/http'

await http.get('path', { page: 1 })
await http.post('path', data)
await http.put('path/1', data)
await http.delete('path/1')
```

The wrapper currently exposes `setTimeout()`, `setBaseUrl()`, `setHeader()`, and `setResponseType()`. Blob responses return the raw Axios response.

## `helper`

Useful exports currently include:

- `rememberAuthToken`, `removeAuthToken`, `getAuthToken`, `getBearerToken`
- `env`, `isProd`, `isTenancyMode`, `getBaseUrl`, `getLoginPath`
- `warpHost`, `date`, `setPageTitle`, `loadModuleRoute`
- `t`, `unique`, `ucfirst`, `lcfirst`, `randomString`, `generateFilename`
- `getFileExt`, `getFilename`, `strToFunction`, `isMiniScreen`

`date()` formats Unix timestamps in seconds. `warpHost()` builds a full host URL for relative paths.

## `cache`

`Cache` wraps `localStorage` with the `catchadmin_` prefix.

## `message`

`Message` wraps Element Plus messages and confirm dialogs. Use it for success, error, warning, and confirm flows.

## `options`

`status` currently maps to:

```ts
[
  { label: '开启', value: 1 },
  { label: '关闭', value: 2 }
]
```

## Usage notes

- Prefer CURD composables for standard CRUD pages.
- Reach for support utilities when the flow needs custom request, cache, message, URL, or formatting logic.
