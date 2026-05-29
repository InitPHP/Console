# InitPHP Console

A small, dependency-free helper library for writing console / CLI applications in PHP — command routing, typed arguments, coloured output, interactive questions and a styleable ANSI table renderer.

[![CI](https://github.com/InitPHP/Console/actions/workflows/ci.yml/badge.svg)](https://github.com/InitPHP/Console/actions/workflows/ci.yml)
[![Latest Stable Version](https://poser.pugx.org/initphp/console/v/stable)](https://packagist.org/packages/initphp/console)
[![Total Downloads](https://poser.pugx.org/initphp/console/downloads)](https://packagist.org/packages/initphp/console)
[![License](https://poser.pugx.org/initphp/console/license)](./LICENSE)
[![PHP Version Require](https://poser.pugx.org/initphp/console/require/php)](https://packagist.org/packages/initphp/console)

> Starting with **2.1** this package also ships the ANSI table renderer that used to be distributed as the separate [`initphp/cli-table`](https://github.com/InitPHP/CLITable) package (now deprecated). See [Migrating from `initphp/cli-table`](#migrating-from-initphpcli-table).

## Features

- **Command routing** — register commands as closures or as classes extending `Command`.
- **Grouped help** — automatic `help` / `list` overview and per-command `--help` usage.
- **Typed arguments** — declare `--name` arguments with a type (`INT`, `FLOAT`, `BOOL`, …), a default and an optional/required flag; values are validated automatically.
- **Input parsing** — long arguments (`--name=value`), short options (`-v`, `-abc`, `-k=value`) and bare positional segments, with automatic scalar type casting.
- **Coloured output** — 16/256-colour SGR helpers, message styles (`error`, `success`, `warning`, `info`), key/value lists and a progress bar.
- **Interactive prompts** — free-form `ask()` and option-constrained `question()`.
- **Table rendering** — a styleable, multibyte-aware ASCII/ANSI table.
- **Testable I/O** — output and input streams are injectable, so commands can be unit tested without touching `STDOUT`/`STDIN`.

## Requirements

- PHP **7.2** or higher
- `ext-mbstring` *(optional)* — improves table alignment for multibyte (UTF-8) values

## Installation

```bash
composer require initphp/console
```

## Quick start

Create an entry script (e.g. `console.php`):

```php
#!/usr/bin/env php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use InitPHP\Console\{Application, Input, Output};

$console = new Application('My Console Application', '1.0.0');

// A closure command:  php console.php hello --name=John
$console->register('hello', function (Input $input, Output $output) {
    $output->writeln('Hello {name}!', [
        'name' => $input->getArgument('name', 'World'),
    ]);
}, 'Says hello.');

$console->run();
```

Run it:

```bash
php console.php hello --name=John   # Hello John!
php console.php hello               # Hello World!
php console.php list                # Show all registered commands
```

## Terminology

This library distinguishes three kinds of tokens that follow the command name:

| Token            | Shape                          | Accessor        |
|------------------|--------------------------------|-----------------|
| **Argument**     | `--name`, `--name=value`       | `getArgument()` |
| **Option**       | `-v`, `-abc`, `-key=value`     | `getOption()`   |
| **Segment**      | bare value, e.g. `migrate`     | `getSegment()`  |

> **Note:** here `--long` tokens are called *arguments* and `-short` tokens are called *options*. This is the opposite of some other frameworks — keep it in mind when porting code.

All scalar values are cast automatically: `"true"`/`"false"`/`"yes"`/`"no"` → `bool`, `"null"` → `null`, integer/decimal strings → `int`/`float`.

## Class-based commands

For anything beyond a one-liner, extend `Command`:

```php
use InitPHP\Console\{Command, Input, InputArgument, Output};

class GreetCommand extends Command
{
    public $command = 'app:greet';

    public function definition(): string
    {
        return 'Greets a person.';
    }

    public function help(): string
    {
        return 'Prints a friendly greeting to the given name.';
    }

    public function arguments(): array
    {
        return [
            new InputArgument('name', InputArgument::STR, 'World', true, 'Who to greet.'),
        ];
    }

    public function execute(Input $input, Output $output)
    {
        $output->success('Hi ' . $input->getArgument('name'));
    }
}

$console->register(GreetCommand::class);
```

Declared `arguments()` are validated *before* `execute()` runs: missing required arguments or values that do not match the declared type abort the command with an error.

```bash
php console.php app:greet --name=Ada
php console.php app:greet --help        # shows the generated usage + parameters
```

## Output

```php
$output->writeln('Plain line');
$output->writeln('Coloured', [], [Output::COLOR_GREEN, Output::BOLD]);
$output->write('No newline; {token} interpolated', ['token' => 42]);

$output->error('Something failed');
$output->success('All good');
$output->warning('Heads up');
$output->info('FYI');

$output->list(['host' => 'localhost', 'port' => 8080]);

for ($i = 0; $i <= 100; $i += 10) {
    $output->progressBar($i, 100);
    usleep(50_000);
}
```

## Interactive prompts

```php
$name = $output->ask('What is your name?');

use InitPHP\Console\Question;

$question = (new Question())
    ->setQuestion('Continue? (yes/no)')
    ->setOptions(['yes', 'no'])
    ->optional()
    ->setDefault('no');

$answer = $output->question($question);
```

Typing `exit` or `quit` at any prompt terminates the application.

## Rendering tables

```php
use InitPHP\Console\Utils\Table;

$table = Table::create()
    ->setHeaderStyle(Table::COLOR_RED, Table::BOLD)
    ->setBorderStyle(Table::COLOR_BLUE)
    ->setCellStyle(Table::COLOR_GREEN);

$table->row(['id' => 1, 'name' => 'Matthew S.', 'email' => 'matthew@example.com', 'status' => true])
      ->row(['id' => 2, 'name' => 'Millie J.',  'email' => 'millie@example.com',  'status' => false]);

echo $table; // or $table->getContent()
```

Non-string cell values are stringified (`[NULL]`, `[TRUE]`, `[FALSE]`, `[ARRAY]`, `[CALLABLE]`, `[RESOURCE]`, or the class name for objects), columns are auto-sized, and `mb_strlen()` is used when available so multibyte values align correctly.

## Documentation

In-depth, example-driven guides live in [`docs/`](./docs):

1. [Getting started](./docs/01-getting-started.md)
2. [Commands](./docs/02-commands.md)
3. [Input: arguments, options & segments](./docs/03-input.md)
4. [Typed input arguments](./docs/04-input-arguments.md)
5. [Output & formatting](./docs/05-output.md)
6. [Interactive questions](./docs/06-questions.md)
7. [Tables](./docs/07-tables.md)
8. [Migrating from `initphp/cli-table`](./docs/08-migrating-from-cli-table.md)

## Migrating from `initphp/cli-table`

The standalone [`initphp/cli-table`](https://github.com/InitPHP/CLITable) package has been merged into this one as of **2.1** and is now deprecated.

If your code uses `\InitPHP\CLITable\Table`, **no source changes are required** — this package ships a `class_alias` keeping the old fully-qualified name working. Just switch the dependency:

```diff
- "initphp/cli-table": "^1.0",
+ "initphp/console": "^2.1"
```

(`initphp/console` declares a Composer `replace` for `initphp/cli-table`, so the two will never be installed side by side.) When you next touch the code, prefer the canonical namespace:

```php
// Before
use InitPHP\CLITable\Table;
// After
use InitPHP\Console\Utils\Table;
```

The alias is a transition aid and may be removed in a future major release. See the [migration guide](./docs/08-migrating-from-cli-table.md) for details.

## Testing & quality

```bash
composer test     # PHPUnit
composer cs       # PHP_CodeSniffer (PSR-12)
composer stan     # PHPStan (level 6)
composer qa       # all of the above
```

## Contributing

Contributions are welcome. Please run `composer qa` before opening a pull request. See the organisation [contributing guidelines](https://github.com/InitPHP/.github/blob/main/CONTRIBUTING.md).

## Credits

- [Muhammet ŞAFAK](https://www.muhammetsafak.com.tr) <<info@muhammetsafak.com.tr>>

## License

Released under the [MIT License](./LICENSE). Copyright © 2022 InitPHP.
