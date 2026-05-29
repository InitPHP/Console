# Commands

A command is the unit of work the application dispatches to. You can register a command either as a **closure** or as a **class** extending `Command`.

## Closure commands

```php
use InitPHP\Console\{Input, Output};

$console->register('cache:clear', function (Input $input, Output $output) {
    // ... do the work ...
    $output->success('Cache cleared.');
}, 'Clears the application cache.');
```

`register()` signature:

```php
public function register(
    string   $command,        // command name, e.g. "cache:clear"
    ?callable $execute = null, // the handler
    string   $definition = '' // short description for the listing
): self
```

The handler is called with `(Input $input, Output $output)`. The return value is ignored. `register()` returns the application, so calls can be chained.

## Class-based commands

For richer commands, extend `InitPHP\Console\Command`:

```php
use InitPHP\Console\{Command, Input, InputArgument, Output};

final class MakeModelCommand extends Command
{
    public $command = 'make:model';

    public function definition(): string
    {
        return 'Generates a model class.';
    }

    public function help(): string
    {
        return 'Creates a new model in app/Models from a name argument.';
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
        $name = $input->getArgument('name');
        $output->success("Model {$name} created.");
    }
}
```

Register it by class name — the application instantiates it and reads the metadata from the instance:

```php
$console->register(MakeModelCommand::class);
```

### The `Command` contract

| Member          | Required | Purpose                                                                 |
|-----------------|----------|-------------------------------------------------------------------------|
| `$command`      | ✅       | The name the command is invoked by. Must be set or registration throws. |
| `execute()`     | ✅       | Runs the command. Receives `(Input, Output)`.                           |
| `definition()`  | ❌       | One-line description shown in the listing (defaults to `''`).           |
| `help()`        | ❌       | Long help shown by `<command> --help`.                                  |
| `arguments()`   | ❌       | Array of [`InputArgument`](04-input-arguments.md) declarations.         |

Every entry returned by `arguments()` is validated **before** `execute()` runs. If a required argument is missing or a value fails its type check, the command aborts with an error and `execute()` is never called.

## Grouping in the listing

If a command name contains a colon, the prefix becomes a group heading in `php console list`:

```php
$console->register(MakeModelCommand::class);       // group: make
$console->register('make:controller', $fn, '...'); // group: make
$console->register('serve', $fn, 'Start server.'); // ungrouped
```

```
[COMMANDS]

make
        make:model       : Generates a model class.
        make:controller  : ...

serve : Start server.
```

## Per-command help

Any command supports an automatic usage screen via the `--help` argument:

```bash
php console make:model --help
```

```
Creates a new model in app/Models from a name argument.

[PARAMETERS]
        --name   : Model class name.
        --force  : Overwrite if it exists.

[USAGE]
        php console make:model --name=(STRING) [--force=(BOOL)]
```

Required parameters are shown bare; optional ones are wrapped in `[ ]`.

## Reserved names

`help` and `list` are reserved for the built-in overview and cannot be used as command names.
