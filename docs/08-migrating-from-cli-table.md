# Migrating from `initphp/cli-table`

The standalone [`initphp/cli-table`](https://github.com/InitPHP/CLITable) package was merged into `initphp/console` as of **2.1** and is now deprecated. The table renderer now lives at `InitPHP\Console\Utils\Table`.

## What you need to do

### 1. Switch the dependency

```diff
  "require": {
-     "initphp/cli-table": "^1.0",
+     "initphp/console": "^2.1"
  }
```

```bash
composer update initphp/console
```

`initphp/console` declares a Composer `replace` for `initphp/cli-table`, so Composer will never install both packages side by side — even if a transitive dependency still asks for the old one.

### 2. (Optional) update your imports

**No code change is strictly required.** This package registers a `class_alias` so the legacy fully-qualified name keeps working:

```php
use InitPHP\CLITable\Table; // still resolves — backed by the alias
```

When you next touch the code, prefer the canonical namespace:

```php
// Before
use InitPHP\CLITable\Table;

// After
use InitPHP\Console\Utils\Table;
```

The public API (`create()`, `row()`, `setHeaderStyle()`, `setCellStyle()`, `setBorderStyle()`, `setColumnCellStyle()`, `getContent()`, `__toString()`) is unchanged — see [Tables](07-tables.md).

## How the alias works

`src/aliases.php` (loaded automatically via Composer's `files` autoloading) runs:

```php
if (!class_exists(\InitPHP\CLITable\Table::class, false)) {
    class_alias(
        \InitPHP\Console\Utils\Table::class,
        'InitPHP\\CLITable\\Table'
    );
}
```

The `class_exists(..., false)` check (autoload disabled) is a safety net: if the legacy class were somehow already loaded, the alias is skipped to avoid a "cannot redeclare class" fatal. In normal use the `replace` directive guarantees the legacy class is absent, so the alias is always created.

## Deprecation timeline

The alias is a **transition aid**. Treat the legacy `InitPHP\CLITable\Table` name as deprecated and migrate to `InitPHP\Console\Utils\Table` at your convenience; the alias may be removed in a future major release.
