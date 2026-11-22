# Quick Start

A five-minute tour of the public API. Every snippet runs as-is.

## 1. The entry script

A console application is one `Application` instance that you register commands
on and then `run()`:

```php
#!/usr/bin/env php
<?php
require __DIR__ . '/vendor/autoload.php';

use InitPHP\Console\{Application, Input, Output};

$console = new Application('Acme CLI', '1.0.0');

// ... register commands here ...

$console->run();
```

`run()` reads the global `$argv` by default. You can also pass an explicit
token list — handy for tests:

```php
$console->run(['console.php', 'greet', '--name=Ada']);
```

## 2. A closure command

```php
$console->register('greet', function (Input $input, Output $output) {
    $output->writeln('Hello {name}!', [
        'name' => $input->getArgument('name', 'World'),
    ]);
}, 'Greets someone.');
```

```bash
php console.php greet --name=Ada   # Hello Ada!
php console.php greet              # Hello World!
```

The third argument to `register()` is a short description shown in the
command listing.

## 3. Reading input

Tokens after the command name fall into three buckets — **arguments**
(`--long`), **options** (`-short`) and **segments** (bare values) — and scalar
values are cast automatically:

```php
$console->register('show', function (Input $input, Output $output) {
    $input->getArgument('age');    // --age=30      → int(30)
    $input->getArgument('active'); // --active=true → bool(true)
    $input->getOption('v');        // -v            → 'v'
    $input->getSegment(0);         // first bare token
}, 'Demonstrates input.');
```

See [Input](Input.md) for the full grammar.

## 4. A class-based command

For anything beyond a one-liner, extend `Command` and declare typed arguments:

```php
use InitPHP\Console\{Command, Input, InputArgument, Output};

final class MakeModelCommand extends Command
{
    public $command = 'make:model';

    public function definition(): string
    {
        return 'Generates a model class.';
    }

    public function arguments(): array
    {
        return [
            new InputArgument('name', InputArgument::STR, '', false, 'Model class name.'),
            new InputArgument('force', InputArgument::BOOL, false, true, 'Overwrite if it exists.'),
        ];
    }

    public function execute(Input $input, Output $output)
    {
        $output->success('Model ' . $input->getArgument('name') . ' created.');
    }
}

$console->register(MakeModelCommand::class);
```

Declared arguments are validated **before** `execute()` runs:

```bash
php console.php make:model --name=User --force=true
php console.php make:model                  # [ERROR] The --name parameter is undefined.
php console.php make:model --help           # generated usage + parameter list
```

See [Commands](Commands.md) and [Input Arguments](Input-Arguments.md).

## 5. Coloured output and messages

```php
$output->writeln('Plain line');
$output->writeln('Coloured', [], [Output::COLOR_GREEN, Output::BOLD]);

$output->error('Something failed');
$output->success('All good');
$output->warning('Heads up');
$output->info('FYI');

$output->list(['Host' => 'localhost', 'Port' => 8080]);
```

See [Output](Output.md).

## 6. Asking questions

```php
use InitPHP\Console\Question;

$name = $output->ask('What is your name?');

$question = (new Question())
    ->setQuestion('Continue? (yes/no)')
    ->setOptions(['yes', 'no'])
    ->optional()
    ->setDefault('no');

$answer = $output->question($question);
```

See [Questions](Questions.md).

## 7. Rendering a table

```php
use InitPHP\Console\Utils\Table;

echo Table::create()
    ->setHeaderStyle(Table::COLOR_CYAN, Table::BOLD)
    ->row(['id' => 1, 'name' => 'Ada',  'admin' => true])
    ->row(['id' => 2, 'name' => 'Linus', 'admin' => false]);
```

See [Tables](Tables.md).

## The built-in `help` / `list` command

`help` and `list` are reserved. Running either prints the banner, the
application name/version and every registered command, grouped by the prefix
before a `:` in the command name:

```bash
php console.php list
```

## How dispatch works

`Application::run()`:

1. Reads the token list, from the global `$argv` or from the array you pass.
2. Drops the script name (`$argv[0]`) and takes the next token as the
   **command name**. With no command token, `run()` returns `false`.
3. Wraps the remaining tokens in an [`Input`](Input.md) object and creates an
   [`Output`](Output.md) unless one was injected.
4. Dispatches:
   - `help` / `list` renders the command overview.
   - an unknown name prints `The command was not found.`; `run()` returns `true`.
   - a registered command with the `--help` argument renders that command's
     usage.
   - any other registered command runs its handler. For a class-based command
     the declared arguments are validated first.

## Injecting output

The constructor accepts an optional `Output` as its third argument. Back it
with an in-memory stream to capture everything a command prints:

```php
use InitPHP\Console\Output;

$stream = fopen('php://memory', 'r+');
$console = new Application('Acme CLI', '1.0.0', new Output($stream));
$console->run(['bin/console', 'greet', '--name=Ada']);

rewind($stream);
echo stream_get_contents($stream);
```

See [Testing Commands](Recipe-Testing-Commands.md) for a full test harness.

## Where to go next

- [Commands](Commands.md) — closures vs. classes, grouping, per-command help.
- [Input](Input.md) — arguments, options, segments and casting.
- [Input Arguments](Input-Arguments.md) — typed validation and defaults.
- [API Reference](API-Reference.md) — exhaustive method list.
