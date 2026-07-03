# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.
`AGENTS.md` in the repo root is a symlink to this file, so both names stay in sync automatically.

## Phorum Core

Phorum is a PHP forum application (version 5.2.x). The goal of this project is twofold:

1. **PHP modernization** — make it run correctly on PHP 8.x
2. **Security audit** — identify and fix security vulnerabilities

## Project structure

```
Core/
├── *.php                   # Entry points (index.php, post.php, login.php, etc.)
├── admin/                  # Admin-only entry points
├── include/
│   ├── api/                # Core API functions (user.php, file.php, forums.php, …)
│   ├── db/
│   │   ├── mysql.php       # DB abstraction layer (shared functions, high-level queries)
│   │   └── mysql/
│   │       ├── mysql.php   # Deprecated ext/mysql driver (DO NOT USE — PHP 7+ removed it)
│   │       ├── mysqli.php  # Current driver — use this
│   │       └── mysqli_replication.php
│   ├── admin/              # Admin sub-pages and sanity checks
│   ├── controlcenter/      # User control center pages
│   ├── cache/              # Cache backend implementations
│   ├── posting/            # Post composition/handling
│   ├── ajax/               # AJAX handlers
│   ├── lang/               # Language/localization files
│   ├── format_functions.php
│   ├── forum_functions.php
│   └── templates.php       # Custom template engine (compiles .tpl → PHP)
├── mods/                   # Optional modules/plugins (bbcode, smtp_mail, spamhurdles, …)
├── templates/              # HTML templates (classic, emerald, lightweight themes)
│   └── */.htaccess         # Denies direct .tpl access
├── include/.htaccess       # Denies all direct access to include/
└── tests/                  # Codeception test suite
```

All DB access flows through `phorum_db_interact()` in `include/db/mysql.php`. The actual
driver (`mysql.php` vs `mysqli.php`) is selected by `$PHORUM['DBCONFIG']['type']`
(set in `include/db/config.php`). **Always configure the driver as `mysqli`.**

### Routing

Each entry point defines `phorum_page`, then includes `common.php`, which handles
session init, DB connection, settings load, and user auth. URLs use a custom
comma-separated query string format (e.g., `read.php?1,2,3` → forum 1, thread 2,
message 3) parsed into `$PHORUM['args']`.

### Session system

Three custom cookie-based session types (not PHP native sessions):
- `PHORUM_SESSION_LONG_TERM` — persistent login cookie (`phorum_session_v5`)
- `PHORUM_SESSION_SHORT_TERM` — tighter auth for sensitive actions
- `PHORUM_SESSION_ADMIN` — admin back-end only

Session IDs are generated and stored in the database; see `phorum_api_user_session_create()` in `include/api/user.php`.

## PHP modernization work

### Removed/changed functions to fix

| Deprecated/removed | Replacement |
|--------------------|-------------|
| `mysql_*` functions (ext/mysql removed in PHP 7) | Already have `include/db/mysql/mysqli.php` — ensure it's used everywhere |
| `ereg()`, `eregi()`, `ereg_replace()`, `eregi_replace()`, `split()` | Replace with `preg_*` equivalents |
| `preg_replace('/pattern/e', …)` | Use `preg_replace_callback()` |
| `create_function()` | Replace with anonymous functions |
| `each()` | Replace with `foreach` |
| `list()` on non-arrays | Guard with `is_array()` |
| String `{$var}` interpolation deprecation warnings | Use `${var}` or explicit concatenation |

### PHP 8 breaking changes to address

- **`match` as identifier** — `match` became a reserved keyword in PHP 8. Search for variables/functions named `match`.
- **Nullsafe operator / null coalescing** — where code uses `isset()` chains, prefer `??`.
- **Deprecated dynamic properties** — PHP 8.2 deprecates creating properties dynamically on stdClass/plain objects.
- **`str_contains()`, `str_starts_with()`** — prefer over `strpos()` where clarity improves.
- **`mysqli` prepared statements** — where possible, migrate raw string-interpolated queries to `mysqli::prepare()` with bound parameters. This is both a modernization and security improvement.
- **Strict type coercion** — PHP 8 is stricter about passing wrong types to built-in functions. Audit `intval()`, `(int)`, etc. at input boundaries.

### How to find remaining issues

```bash
# Find all deprecated POSIX regex usage
grep -rn "ereg\|eregi\|split(" --include="*.php" .

# Find mysql_ (old driver) calls outside the mysql.php driver file
grep -rn "mysql_" --include="*.php" . | grep -v "include/db/mysql/mysql.php"

# Find preg_replace /e modifier
grep -rn "preg_replace.*\/e[^a-z]" --include="*.php" .

# Find create_function
grep -rn "create_function" --include="*.php" .

# Find each() usage
grep -rn "\beach\b(" --include="*.php" .
```

## Security audit work

### Known issues to address

#### Critical

- **MD5 password hashing** — `include/api/user.php` hashes passwords with plain `md5()`. Migrate to `password_hash()` / `password_verify()` with bcrypt or argon2. Provide a migration path that re-hashes on successful login.
- **Session ID generation** — session tokens use `md5($username . microtime() . $password)`. Replace with `random_bytes()` / `bin2hex()` or PHP's built-in `session_regenerate_id()`.
- **Raw SQL with string interpolation** — `include/db/mysql.php` and the high-level query functions build SQL by interpolating values. Audit every query for user-controlled values. Short-term: ensure all user input goes through `phorum_db(DB_RETURN_QUOTED, …)`. Long-term: migrate to prepared statements.

#### High

- **XSS** — Audit all template output. Check that user-supplied content (usernames, post bodies, custom profile fields) is always escaped with `htmlspecialchars($val, ENT_QUOTES, 'UTF-8')` before output.
- **CSRF** — Token infrastructure exists (`include/constants.php`, `include/admin_functions.php`). Verify all state-changing POST forms include and validate CSRF tokens.
- **File uploads** — `include/api/file.php` and `include/controlcenter/files.php` handle uploads. Verify MIME type is validated server-side (not just extension), and files are stored outside webroot or with `.htaccess` deny.
- **`$_SERVER['PHP_SELF']` in forms** — `include/admin/install.php` prints `$_SERVER['PHP_SELF']` unescaped. All such uses need `htmlspecialchars()`.

#### Medium

- **`extract()` on user input** — check `common.php` and others for `extract($_GET)` / `extract($_POST)` patterns; remove or replace with explicit variable assignment.
- **Open redirect** — `redirect.php` and `include/phorum_get_url.php` may be exploitable. Validate redirect targets against an allowlist of internal paths.
- **Email header injection** — `include/api/mail.php` and `include/email_functions.php`. Ensure all `To:`, `Subject:`, and other headers sanitize newlines (`\r`, `\n`).
- **Information disclosure** — Ensure PHP error display is off in production; any `error_reporting` changes should only tighten, not loosen.

### Security audit workflow

When auditing a file:

1. Identify all sources of user input (`$_GET`, `$_POST`, `$_REQUEST`, `$_COOKIE`, `$_FILES`, `$_SERVER`).
2. Trace each value to its sinks (SQL queries, HTML output, file paths, shell commands, headers).
3. Verify sanitization/escaping exists at the sink, not just the source.
4. Flag missing CSRF checks on any form that changes state (post, delete, profile update, admin action).
5. Check for use of deprecated crypto (md5/sha1 for security purposes).

## Testing

The project uses Codeception (`codeception.yml`), with four suites in `tests/`:

- `tests/unit` — pure PHP unit tests, no DB/HTTP dependency (e.g. `PasswordMigrationTest.php`).
- `tests/functional` — emulated web requests against the app.
- `tests/acceptance` — full browser tests via PhpBrowser against a running server, with a MySQL `Db` module that loads `tests/_data/dump-forum.sql`.
- `tests/install` — acceptance-style tests for the web installer, using `tests/_data/dump-install.sql`.

```bash
composer install                              # install dev dependencies
composer lint                                 # lint all PHP files (php-parallel-lint)
composer analyze                              # static analysis (phan, see .phan/config.php)
vendor/bin/codecept run                       # run all suites
vendor/bin/codecept run unit                  # run one suite
vendor/bin/codecept run unit PasswordMigrationTest       # run one test file
vendor/bin/codecept run unit PasswordMigrationTest:testX # run one test method
```

Functional/acceptance/install suites need a real MySQL DB and a running PHP server (see `.travis.yml` for the reference setup: create the DB, copy `include/db/config.php.sample` to `include/db/config.php`, point it at the test DB, then `php -S localhost:8000`). A `docker-compose.yml` is also available for local dev (nginx + php-fpm 8.4 + MySQL 8); it mounts `docker/db-config.php` as the DB config.

Before any significant change, confirm which test suite covers the area being modified. Write or update tests when fixing security issues — especially for authentication, input handling, and SQL query construction.

`phan` is configured for `target_php_version` 8.4 with many issue types suppressed to cut noise from Phorum's legacy `$PHORUM` global-array style — see `.phan/config.php` before treating a suppressed category as unused.

## Development conventions

- Do not introduce new dependencies without discussion. The project has minimal composer requirements.
- Match existing code style (K&R braces, spaces for indent in most files).
- When replacing a deprecated function, add a brief comment explaining what was replaced and why only if the replacement is non-obvious.
- Do not refactor beyond the scope of the immediate fix. A security fix should not also reformat the file.
- All DB values that originate from user input must go through `phorum_db(DB_RETURN_QUOTED, $value)` at minimum. Prefer prepared statements when rewriting a query.

## Priority order for this modernization effort

1. Fix all PHP 8 fatal errors (function removals, reserved keyword conflicts) so the app boots.
2. Fix critical security issues (password hashing, session token generation).
3. Fix high security issues (XSS, CSRF gaps, SQL injection vectors).
4. Fix PHP 8 deprecation warnings (non-fatal but noisy and a sign of fragile code).
5. Improve test coverage for security-sensitive paths.
