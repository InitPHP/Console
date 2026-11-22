# Recipes

Practical, copy-pasteable patterns built on top of the public API. Each recipe
is self-contained and links back into the reference material for the
components it uses.

| Recipe | Solves |
| --- | --- |
| [Git-like CLI](Recipe-Git-Like-CLI.md) | A multi-command binary with grouped, class-based commands and typed arguments. |
| [Interactive Installer](Recipe-Interactive-Installer.md) | A guided flow using questions, defaults and a progress bar. |
| [Styled Report Table](Recipe-Report-Table.md) | Rendering query results as a coloured, aligned table. |
| [Testing Commands](Recipe-Testing-Commands.md) | Unit-testing commands with injected in-memory streams. |

If you have built something on top of the package that other users might
benefit from, please
[open an issue](https://github.com/InitPHP/Console/issues/new) with a sketch —
recipe contributions are welcomed.

## General shape of a console app

Most applications follow the same skeleton:

```php
#!/usr/bin/env php
<?php
require __DIR__ . '/vendor/autoload.php';

use InitPHP\Console\Application;

$console = new Application('My Tool', '1.0.0');

// Register commands (closures and/or Command classes) ...

exit($console->run() ? 0 : 1);   // map dispatch result to a process exit code
```

- Group related commands with a `name:subname` convention so they cluster in
  `php tool list` — see [Commands → Grouping](Commands.md#grouping-in-the-listing).
- Declare typed [`InputArgument`](Input-Arguments.md)s on class commands so the
  framework validates input for you before `execute()` runs.
- Throw from a command to abort with a message — the dispatcher catches it and
  prints `[ERROR] …`. See [Exceptions](Exceptions.md).

## When NOT to use this package

`initphp/console` is intentionally small. Reach for a heavier framework when
you need:

- **Sub-command trees beyond one level**, auto-completion, or a sophisticated
  argument grammar (variadics, negatable flags, `--` passthrough). The parser
  here is deliberately flat — see [Input](Input.md).
- **A full DI/service layer wired into commands.** Compose your own container
  and instantiate command dependencies yourself; this package only routes the
  invocation.
- **Structured logging / output verbosity levels** out of the box. Build them
  on top of [`Output`](Output.md) (e.g. wrap it and gate `info()` on a `-v`
  option).
