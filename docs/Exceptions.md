# Exceptions

`initphp/console` throws only standard SPL exceptions — there are no
package-specific exception classes. This keeps integration simple: catch the
SPL types you already handle.

```
\LogicException
└── (thrown by Application::register for a misconfigured Command class)

\InvalidArgumentException
└── (thrown by Application::register and InputArgument::__construct)
```

## When they are raised

| Trigger | Type | Where |
| --- | --- | --- |
| `register()` given a class name that does not exist | `\InvalidArgumentException` | `Application::register()` |
| `register()` given a class that is not a `Command` | `\InvalidArgumentException` | `Application::register()` |
| A `Command` class whose `$command` is not set | `\LogicException` | `Application::register()` |
| `InputArgument` constructed with an unsupported type | `\InvalidArgumentException` | `InputArgument::__construct()` |
| `InputArgument` default value does not satisfy the type | `\InvalidArgumentException` | `InputArgument::__construct()` |
| `progressBar()` with non-numeric input or `$total <= 0` | `\InvalidArgumentException` | `Output::progressBar()` |

### Registration errors

```php
$console->register('App\\Missing\\CommandClass');
// InvalidArgumentException: The specified executable does not meet the requirements.

$console->register(\stdClass::class);
// InvalidArgumentException: The specified executable does not meet the requirements.

final class BadCommand extends \InitPHP\Console\Command {
    public function execute($i, $o) {}   // forgot: public $command = '...';
}
$console->register(BadCommand::class);
// LogicException: The command name of the command class is undefined.
```

These are programming errors — surface them during development; they should
never reach a released binary.

### Argument declaration errors

```php
use InitPHP\Console\InputArgument;

new InputArgument('age', 'WRONG', 0);
// InvalidArgumentException: The type defined for the --age parameter is not supported.

new InputArgument('age', InputArgument::INT, 'not-an-int');
// InvalidArgumentException: The default value for the --age parameter
//                           must be a type accepted for the parameter.
```

### Progress bar errors

```php
$output->progressBar(1, 0);
// InvalidArgumentException: $total must be greater than zero.

$output->progressBar('a', 'b');
// InvalidArgumentException: $done and $total must be integer or float.
```

## Errors that surface as output, not exceptions

Several conditions are handled gracefully at runtime rather than thrown.
During dispatch the `Application` writes a styled `[ERROR]` message via
`Output::error()` and returns `false` from `run()`:

| Situation | What happens |
| --- | --- |
| Unknown command | `[ERROR] The command was not found.` — `run()` returns `true`. |
| Required argument missing / value invalid | `[ERROR] The --<name> parameter is …` — `run()` returns `false`, `execute()` is skipped. |
| `arguments()` contains a non-`InputArgument` entry | `[ERROR] One or more arguments are wrong! …` — `run()` returns `false`. |
| Handler is registered but not executable | `[ERROR] The command is not executable; …` — `run()` returns `false`. |

## Exceptions thrown inside a command

Any `Throwable` raised inside a command handler (closure or `execute()`) is
caught by the dispatcher, its message is printed via `Output::error()`, and
`run()` returns `false`:

```php
$console->register('deploy', function ($input, $output) {
    if (!is_writable('/var/www')) {
        throw new \RuntimeException('Target directory is not writable.');
    }
    // ...
});

// php console.php deploy
// [ERROR] Target directory is not writable.   (run() returns false)
```

This lets you abort a command with a clear message by throwing, instead of
calling `exit()` yourself. If you need a specific process exit code, inspect
the boolean return of `run()` in your entry script:

```php
exit($console->run() ? 0 : 1);
```

## Catching registration errors at boot

Because registration errors are programming mistakes, the usual approach is to
let them bubble during development. If you build commands dynamically (e.g.
from a directory scan) and want a friendlier boot failure, wrap registration:

```php
try {
    foreach ($discoveredCommandClasses as $class) {
        $console->register($class);
    }
} catch (\LogicException | \InvalidArgumentException $e) {
    fwrite(STDERR, 'Command registration failed: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
```

## See also

- [Commands](Commands.md) — registration and the `Command` contract.
- [Input Arguments](Input-Arguments.md) — argument validation behaviour.
- [API Reference](API-Reference.md) — where each exception is documented per method.
