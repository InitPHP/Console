# InitPHP Console — Wiki

Welcome to the official documentation for **`initphp/console`** — a small,
dependency-free toolkit for writing command-line applications in PHP: command
routing, typed arguments, coloured output, interactive prompts, and a
styleable ANSI table renderer.

```bash
composer require initphp/console
```

```php
#!/usr/bin/env php
<?php
require __DIR__ . '/vendor/autoload.php';

use InitPHP\Console\{Application, Input, Output};

$console = new Application('Acme CLI', '1.0.0');

$console->register('greet', function (Input $input, Output $output) {
    $output->writeln('Hello {name}!', ['name' => $input->getArgument('name', 'World')]);
}, 'Greets someone.');

$console->run();
```

```bash
php console.php greet --name=Ada   # Hello Ada!
php console.php list               # Overview of all commands
```

## The building blocks

| Type | Purpose |
| --- | --- |
| [`Application`](API-Reference.md#application) | The command registry and dispatcher. |
| [`Command`](API-Reference.md#command) | Base class for class-based commands. |
| [`Input`](API-Reference.md#input) / [`InputInterface`](API-Reference.md#inputinterface) | Parsed access to arguments, options and segments. |
| [`InputArgument`](API-Reference.md#inputargument) | A typed, validated `--name` argument declaration. |
| [`Output`](API-Reference.md#output) / [`OutputInterface`](API-Reference.md#outputinterface) | Coloured writing, lists, progress bar and prompts. |
| [`Question`](API-Reference.md#question) | A value object describing an interactive question. |
| [`Helpers`](API-Reference.md#helpers) | Scalar value casting shared across the components. |
| [`Utils\Table`](API-Reference.md#utilstable) | A styleable, multibyte-aware ASCII/ANSI table renderer. |

## Start here

- **New to the package?** Read [Installation](Installation.md), then [Quick Start](Quick-Start.md).
- **Building commands?** See [Commands](Commands.md), then [Input](Input.md) and [Input Arguments](Input-Arguments.md).
- **Formatting output?** Read [Output](Output.md); for prompts see [Questions](Questions.md).
- **Rendering tables?** See [Tables](Tables.md).
- **Coming from `initphp/cli-table`?** Read the [Migration Guide](Migration-Guide.md).
- **Looking for a specific method?** The full [API Reference](API-Reference.md) lists every class member.

## A note on terminology

This library splits the tokens after the command name into three buckets:

| Token | Shape | Accessor |
| --- | --- | --- |
| **Argument** | `--name`, `--name=value` | `getArgument()` |
| **Option** | `-v`, `-abc`, `-key=value` | `getOption()` |
| **Segment** | bare value, e.g. `migrate` | `getSegment()` |

> Here `--long` tokens are called **arguments** and `-short` tokens are called
> **options** — the opposite of some other CLI frameworks. Keep it in mind when
> porting code. See [Input](Input.md) for the full grammar.

## Package metadata

- **License**: MIT
- **Minimum PHP**: 8.2 (CI matrix runs 8.2 – 8.5)
- **Runtime dependencies**: none
- **Optional extension**: `ext-mbstring` (improves table alignment for UTF-8 values)
- **Packagist**: [`initphp/console`](https://packagist.org/packages/initphp/console)
- **Source**: [github.com/InitPHP/Console](https://github.com/InitPHP/Console)
- **Issues**: [github.com/InitPHP/Console/issues](https://github.com/InitPHP/Console/issues)
- **Discussions**: [github.com/orgs/InitPHP/discussions](https://github.com/orgs/InitPHP/discussions)
- **Security**: [`SECURITY.md`](https://github.com/InitPHP/.github/blob/main/SECURITY.md)

> Since **2.1** this package also ships the table renderer formerly distributed
> as `initphp/cli-table` (now deprecated). The old class name keeps working via
> a `class_alias` — see [Migration Guide](Migration-Guide.md).

If something in this wiki is unclear, ambiguous, or wrong, please
[open an issue](https://github.com/InitPHP/Console/issues/new) —
documentation fixes are reviewed eagerly.
