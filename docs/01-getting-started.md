# Getting started

`initphp/console` helps you build command-line tools in PHP: it routes a command name to a handler, parses the tokens that follow it, and gives you helpers for coloured output, prompts and tables.

## Installation

```bash
composer require initphp/console
```

Requires PHP 7.2+. The optional `ext-mbstring` extension improves table alignment for multibyte text.

## Your first application

Create an executable entry script, for example `bin/console`:

```php
#!/usr/bin/env php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

use InitPHP\Console\{Application, Input, Output};

$console = new Application('Acme CLI', '1.0.0');

$console->register('greet', function (Input $input, Output $output) {
    $output->writeln('Hello {name}!', [
        'name' => $input->getArgument('name', 'World'),
    ]);
}, 'Greets someone.');

$console->run();
```

Make it runnable and try it:

```bash
chmod +x bin/console

php bin/console greet --name=Ada   # Hello Ada!
php bin/console greet              # Hello World!
php bin/console list               # Overview of all commands
```

## How dispatch works

`Application::run()`:

1. Reads the token list (from the global `$argv`, or from the array you pass to `run()`).
2. Drops the script name (`$argv[0]`) and takes the next token as the **command name**.
3. Wraps the remaining tokens in an [`Input`](03-input.md) object and creates an [`Output`](05-output.md).
4. Dispatches:
   - `help` / `list` → renders the command overview.
   - a registered command with the `--help` argument → renders that command's usage.
   - a registered command → runs its handler.
   - anything else → prints `The command was not found.`

`run()` returns `true` when a command (or help) ran and `false` when no command was supplied or nothing matched.

## Passing the token list explicitly

`run()` accepts an optional array, which makes the application trivial to drive from tests or another script:

```php
$console->run(['bin/console', 'greet', '--name=Ada']);
```

When omitted, the global `$argv` is used (just like a normal CLI program).

## Injecting output

The constructor accepts an optional `Output` instance. Inject one backed by an in-memory stream to capture everything a command prints:

```php
use InitPHP\Console\Output;

$stream = fopen('php://memory', 'r+');
$console = new Application('Acme CLI', '1.0.0', new Output($stream));
$console->run(['bin/console', 'greet', '--name=Ada']);

rewind($stream);
echo stream_get_contents($stream);
```

## Next steps

- [Commands](02-commands.md) — closures vs. class-based commands and grouping.
- [Input](03-input.md) — arguments, options and segments.
- [Typed input arguments](04-input-arguments.md) — validation and defaults.
- [Output](05-output.md), [Questions](06-questions.md), [Tables](07-tables.md).
