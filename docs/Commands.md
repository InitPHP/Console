# Commands

A command is the unit of work the [`Application`](API-Reference.md#application)
dispatches to. There are two ways to register one: as a **closure** or as a
**class** extending [`Command`](API-Reference.md#command).

## Closure commands

```php
use InitPHP\Console\{Input, Output};

$console->register('cache:clear', function (Input $input, Output $output) {
    // ... do the work ...
    $output->success('Cache cleared.');
}, 'Clears the application cache.');
```

`register()` has the signature:

```php
public function register(
    string    $command,        // the name the command is invoked by
    ?callable $execute = null, // the handler
    string    $definition = '' // short description for the listing
): self;
```

The handler is invoked with `(Input $input, Output $output)`. Its return value
is ignored. `register()` returns the application, so calls chain:

```php
$console
    ->register('up',   $up,   'Run migrations.')
    ->register('down', $down, 'Roll back the last batch.');
```

Any PHP callable works — a closure, an invokable object, `'Class::method'`, or
`[$object, 'method']`.

## Class-based commands

For richer commands extend `InitPHP\Console\Command`:

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
        return 'Creates a new model in app/Models from the given name.';
    }

    public function arguments(): array
    {
        return [
            new InputArgument('name', InputArgument::STR, '', false, 'Model class name.'),
        ];
    }

    public function execute(Input $input, Output $output)
    {
        $name = $input->getArgument('name');
        $output->success("Model {$name} created.");
    }
}
```

Register it **by class name** — the application instantiates it and reads the
metadata from the instance:

```php
$console->register(MakeModelCommand::class);
```

> When the second argument to `register()` is `null`, the first argument is
> treated as a class name. The class must exist and extend `Command`, and its
> `$command` property must be set — otherwise registration throws. See
> [Exceptions](Exceptions.md).

### The `Command` contract

| Member | Required | Default | Purpose |
| --- | --- | --- | --- |
| `public $command` | ✅ | — | The name the command is invoked by. Must be set. |
| `execute(Input, Output)` | ✅ | — | Runs the command. |
| `definition(): string` | ❌ | `''` | One-line description for the listing. |
| `help(): string` | ❌ | `'No explanation for this command is specified.'` | Long help for `<command> --help`. |
| `arguments(): array` | ❌ | `[]` | Array of [`InputArgument`](Input-Arguments.md) declarations. |

Every entry returned by `arguments()` is validated **before** `execute()`
runs. If a required argument is missing, or a value fails its type check, the
command aborts with an error and `execute()` is never called. See
[Input Arguments](Input-Arguments.md) for the full resolution rules.

## Grouping in the listing

If a command name contains a colon, the part before it becomes a group heading
in `php console list`:

```php
$console->register(MakeModelCommand::class);            // group: make
$console->register('make:controller', $fn, 'Make ...'); // group: make
$console->register('serve', $fn, 'Start the server.');  // ungrouped
```

```
[COMMANDS]

make
        make:model       : Generates a model class.
        make:controller  : Make ...

serve : Start the server.
```

The colon is purely a naming convention for display grouping; it has no other
behaviour.

## Per-command help

Every command supports an automatic usage screen via the `--help` argument.
For a class-based command it lists the declared parameters and renders a usage
line:

```bash
php console.php make:model --help
```

```
Creates a new model in app/Models from the given name.

[PARAMETERS]
        --name  : Model class name.

[USAGE]
        php console.php make:model --name=(STRING)
```

Required parameters are shown bare; optional ones are wrapped in `[ ]`. The
long text comes from `help()` (falling back to `definition()`).

## Reserved names and lookup

- `help` and `list` are reserved for the built-in overview and cannot be used
  as command names.
- An unknown command prints `The command was not found.` and `run()` returns
  `true` (the application handled the invocation gracefully).
- Running with no command at all makes `run()` return `false`.

## Error handling inside a command

`execute()` runs inside a `try/catch` in the dispatcher. Any `Throwable` it
raises is caught, its message is printed via `Output::error()`, and `run()`
returns `false`:

```php
$console->register('risky', function (Input $input, Output $output) {
    throw new \RuntimeException('boom');
});

// php console.php risky   →  [ERROR] boom    (run() returns false)
```

This means you can `throw` from a command to abort with a message, rather than
calling `exit()` yourself.

## Where to go next

- [Input](Input.md) — read arguments, options and segments.
- [Input Arguments](Input-Arguments.md) — declare typed, validated arguments.
- [Output](Output.md) — format the command's output.
