# Migration Guide

This page covers two things:

1. Moving from the standalone **`initphp/cli-table`** package to the table
   renderer now bundled here (the main reason this guide exists).
2. Notes on the **2.x line** of `initphp/console` itself.

---

## From `initphp/cli-table`

The standalone [`initphp/cli-table`](https://github.com/InitPHP/CLITable)
package was merged into `initphp/console` as of **2.1** and is now deprecated.
The renderer now lives at `InitPHP\Console\Utils\Table`.

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

`initphp/console` declares a Composer `replace` for `initphp/cli-table`, so the
two are never installed side by side — even if a transitive dependency still
asks for the old one.

### 2. (Optional) update your imports

**No code change is strictly required.** The package registers a `class_alias`
so the legacy fully-qualified name keeps resolving:

```php
use InitPHP\CLITable\Table;   // still works — backed by the alias
```

When you next touch the code, prefer the canonical namespace:

```php
// Before
use InitPHP\CLITable\Table;

// After
use InitPHP\Console\Utils\Table;
```

The public API is unchanged — `create()`, `row()`, `setHeaderStyle()`,
`setCellStyle()`, `setBorderStyle()`, `setColumnCellStyle()`, `getContent()`
and `__toString()` all behave exactly as before. See [Tables](Tables.md).

### How the alias works

`src/aliases.php` (loaded via Composer's `files` autoloading) runs:

```php
if (!class_exists(\InitPHP\CLITable\Table::class, false)) {
    class_alias(
        \InitPHP\Console\Utils\Table::class,
        'InitPHP\\CLITable\\Table'
    );
}
```

The `class_exists(..., false)` check (autoload disabled) is a safety net: if
the legacy class were somehow already loaded, the alias is skipped to avoid a
"cannot redeclare class" fatal. In normal use the `replace` directive
guarantees the legacy class is absent, so the alias is always created.

### Deprecation timeline

Treat `InitPHP\CLITable\Table` as deprecated. The alias is a transition aid and
may be removed in a future major release — migrate to
`InitPHP\Console\Utils\Table` at your convenience.

---

## Within the 2.x line

`2.x` is backwards compatible across its minor releases. Highlights relevant to
upgraders:

- **Bundled table renderer (2.1).** As above — `initphp/cli-table` is now part
  of this package.
- **Interfaces.** [`Input`](Input.md) implements
  [`InputInterface`](API-Reference.md#inputinterface) and [`Output`](Output.md)
  implements [`OutputInterface`](API-Reference.md#outputinterface). Existing code
  that type-hints the concrete classes is unaffected; new code can depend on
  the interfaces.
- **Injectable I/O.** `Output` now accepts optional stream resources
  (`new Output($stdout, $stdin)`) and `Application` accepts an optional
  `Output` (3rd constructor argument). `Application::run()` accepts an optional
  `$argv`. All are additive — calling them with no arguments behaves as before.
  See [Testing Commands](Recipe-Testing-Commands.md).

### Behavioural fixes to be aware of

These correct clear bugs; in the rare case you relied on the old behaviour,
adjust as noted.

| Area | Old behaviour | Now |
| --- | --- | --- |
| Combined short flags `-abc` | also stored a spurious `abc` option key | stores only `a`, `b`, `c` ([Input](Input.md#options---short)) |
| `Question::hasOption()` | loose comparison accepted *any* non-empty answer against the default `[true, false]` | strict match, plus a [cast](Input.md#automatic-type-casting) fallback ([Questions](Questions.md#acceptable-answer-matching)) |
| Required `InputArgument` given `0`/`false`/`''` | treated as "empty" and replaced by the default | a real `0`/`false` is kept ([Input Arguments](Input-Arguments.md#zero-and-false-are-real-values)) |
| `Output::progressBar()` with `$total = 0` | division-by-zero error | throws `\InvalidArgumentException` ([Output](Output.md#progress-bar)) |
| Value casting of tokens like `\|123` | mis-parsed as numeric | returned unchanged as a string ([Input](Input.md#automatic-type-casting)) |

If any of these surprise you, the linked pages document the current behaviour
precisely.

## PHP requirement in the next release

The unreleased changes require PHP `^8.2` (the 2.x releases supported
`>=7.2`) and are listed as breaking in the
[changelog](https://github.com/InitPHP/Console/blob/main/CHANGELOG.md).
Stay on a 2.x release if you still run PHP 7.2 through 8.1.

## See also

- [Tables](Tables.md) — the renderer you are migrating to.
- [FAQ](FAQ.md) — common questions.
- [API Reference](API-Reference.md) — the full surface.
