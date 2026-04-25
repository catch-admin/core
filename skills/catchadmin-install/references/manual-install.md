# CatchAdmin Pro Manual Install

## Stage 1: Project Root And Tools

Run from the project root:

```shell
pwd
test -f composer.json
git clone -b v5 https://gitee.com/catchadmin/catch-admin-vue.git web
test -f web/package.json
```

Check required tools:

```shell
php -v
composer --version
git --version
node -v
yarn -v
```

Required tools:

- PHP 8.3+
- Composer
- Git
- Node 22+
- Yarn

## Stage 2: Database Settings

Before creating or patching `.env`, read existing database values when `.env` exists:

```shell
test -f .env && rg '^(DB_DATABASE|DB_USERNAME|DB_PASSWORD)=' .env
```

Ask the user only for missing values:

- `DB_DATABASE`
- `DB_USERNAME`
- `DB_PASSWORD`

Use these values as configuration only. Skip Docker checks, database service checks, MySQL connection tests, default credential attempts, and container startup suggestions.

Keep provided or existing values in shell variables for the `.env` patch command:

```shell
export DB_DATABASE="<database>"
export DB_USERNAME="<username>"
export DB_PASSWORD="<password>"
```

Use these fixed project defaults:

```dotenv
APP_NAME=Admin
APP_URL=http://127.0.0.1:8000
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_PREFIX=
```

Validate the database name before using it:

```shell
php -r '$name = getenv("DB_DATABASE") ?: ""; if (! preg_match("/^[A-Za-z_][A-Za-z0-9_]{0,99}$/", $name)) { fwrite(STDERR, "Invalid database name\n"); exit(1); }'
```

## Stage 3: Backend Environment

Preserve an existing `.env`. Create it only when missing:

```shell
if [ -f .env ]; then
  cp .env ".env.backup.$(date +%Y%m%d%H%M%S)"
else
  cp .env.example .env
fi
```

Patch only the required keys and preserve all other `.env` values:

```shell
php -r '
$path = ".env";
$updates = [
    "APP_NAME" => "Admin",
    "APP_ENV" => "local",
    "APP_DEBUG" => "true",
    "APP_URL" => "http://127.0.0.1:8000",
    "DB_CONNECTION" => "mysql",
    "DB_HOST" => "127.0.0.1",
    "DB_PORT" => "3306",
    "DB_DATABASE" => getenv("DB_DATABASE") ?: "",
    "DB_USERNAME" => getenv("DB_USERNAME") ?: "",
    "DB_PASSWORD" => getenv("DB_PASSWORD") ?: "",
    "DB_PREFIX" => "",
];
$lines = file($path, FILE_IGNORE_NEW_LINES);
$seen = [];
foreach ($lines as &$line) {
    foreach ($updates as $key => $value) {
        if (preg_match("/^".preg_quote($key, "/")."=/", $line)) {
            $line = $key."=".$value;
            $seen[$key] = true;
            break;
        }
    }
}
unset($line);
foreach ($updates as $key => $value) {
    if (! isset($seen[$key])) {
        $lines[] = $key."=".$value;
    }
}
file_put_contents($path, implode(PHP_EOL, $lines).PHP_EOL);
'
```

The resulting required values are:

```dotenv
APP_NAME=Admin
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE={DB_DATABASE}
DB_USERNAME={DB_USERNAME}
DB_PASSWORD={DB_PASSWORD}
DB_PREFIX=
```

`APP_URL` drives the frontend API base URL. Keep it aligned with the backend server address.

## Stage 4: Keys, Published Config, And Sanctum Assets

Generate missing application secrets and keep existing values:

```shell
if ! grep -q '^APP_KEY=base64:' .env; then
  php artisan key:generate
fi
```

Publish configuration and Sanctum assets:

```shell
php artisan vendor:publish --tag=catch-config

if [ ! -f config/sanctum.php ]; then
  php artisan vendor:publish --tag=sanctum-config
fi

if ! find database/migrations -name '*_create_personal_access_tokens_table.php' | grep -q .; then
  php artisan vendor:publish --tag=sanctum-migrations
fi
```

Verify published files:

```shell
test -f config/catch.php
test -f config/sanctum.php
find database/migrations -name '*_create_personal_access_tokens_table.php'
```

There should be one `create_personal_access_tokens_table` migration file in `database/migrations`.

## Stage 5: Database Migration And Base Modules

Run root migrations:

```shell
php artisan migrate
```

Install the `user` and `develop` module database assets:

```shell
php artisan catch:migrate user
php artisan catch:db:seed user

php artisan catch:migrate develop
php artisan catch:db:seed develop

php artisan catch:module:install --all --no-interaction
```

## Stage 6: Storage Link And Autoload

Create the public storage link:

```shell
php artisan storage:link
```

If the command reports that `public/uploads` already exists, continue.

Refresh Composer autoload:

```shell
composer dump-autoload
```

## Stage 7: Frontend Dependencies And Environment

Install frontend dependencies:

```shell
cd web
yarn config set registry https://registry.npmmirror.com
yarn install
```

Create `web/.env`:

```dotenv
VITE_BASE_URL=http://127.0.0.1:8000/api/
VITE_APP_NAME=Admin
```

`VITE_BASE_URL` must match backend `APP_URL` and keep the trailing `/api/`.

Return to project root after frontend setup:

```shell
cd ..
```

## Stage 8: Development Startup

For local development with the built-in server:

```shell
composer run dev
```

For local environments already served by Nginx, Apache, XAMPP, WAMP, or Laragon, start the frontend only:

```shell
cd web
yarn dev
```

## Final Verification

Confirm:

- install commands finished without errors
- `composer run dev` starts the backend, frontend, queue, and log processes
- when using a local web server instead, `cd web && yarn dev` starts the frontend

Stop here after startup succeeds. Leave login, business validation, and browser checks to the customer.

Send the default login information to the customer:

```text
账号: catch@admin.com
密码: catchadmin
```

## Troubleshooting

Sanctum migration failure:

- Keep a single `*_create_personal_access_tokens_table.php` file in `database/migrations`.
- If the database already contains a `personal_access_tokens` table and `migrations` records a different filename, rename the local migration file to match the recorded migration before rerunning `php artisan migrate`.
- Rerun `php artisan migrate` after duplicate files are resolved.

Frontend startup failure:

- Confirm backend `APP_URL`.
- Confirm `web/.env` uses `VITE_BASE_URL=<APP_URL>/api/`.
- Restart `yarn dev` after changing `web/.env`.
