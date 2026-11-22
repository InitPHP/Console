# Input Arguments

[`InputArgument`](API-Reference.md#inputargument) lets a
[class-based command](Commands.md#class-based-commands) declare the `--name`
arguments it accepts, each with a type, a default value and an
optional/required flag. The [`Application`](API-Reference.md#application)
validates them before calling `execute()`.

## Constructing an argument

```php
use InitPHP\Console\InputArgument;

new InputArgument(
    string $name,            // name without dashes, e.g. "name"
    string $type,            // one of the type constants below
    mixed  $default,         // must satisfy $type
    bool   $isOptional = true,
    string $definition = ''  // description shown in the help screen
);
```

The constructor throws `\InvalidArgumentException` when the type is unknown,
or when the default value does not satisfy the declared type:

```php
new InputArgument('age', InputArgument::INT, 'not-an-int');
// InvalidArgumentException: The default value for the --age parameter
//                           must be a type accepted for the parameter.
```

## Types

| Constant | Internal value | Accepts |
| --- | --- | --- |
| `InputArgument::ANY` | `ANY` | Any value. |
| `InputArgument::INT` | `INT` | Integers. |
| `InputArgument::FLOAT` | `FLOAT` | Floats. |
| `InputArgument::NUMERIC` | `NUMBER` | Any numeric value (int, float or numeric string). |
| `InputArgument::BOOL` | `BOOL` | `true`/`false`, or `1`/`0` (coerced to `bool`). |
| `InputArgument::STR` | `STRING` | Strings (numeric/bool values are stringified). |

Because [`Input`](Input.md#automatic-type-casting) casts values first, `--age=30`
already arrives as `int(30)` and passes the `INT` check, while `--age=abc`
stays a string and fails it.

> The `NUMERIC` constant's internal value is the string `"NUMBER"` — use the
> `InputArgument::NUMERIC` constant rather than the literal so you never have to
> remember that.

## How resolution works

For each declared argument the application calls `run()`, which:

1. Looks the value up as an **argument** (`--name`) first, then as an
   **option** (`-name`).
2. **Missing value:**
   - optional → the default is stored, the command proceeds.
   - required → an error is printed and the command aborts (`run()` returns `false`).
3. **Present value:**
   - valid → kept. A required argument given an empty `''`/`null` value falls
     back to its default.
   - invalid + optional → replaced with the default.
   - invalid + required → an error is printed and the command aborts.

The resolved value is written back into [`Input`](Input.md), so `execute()` reads
it with `$input->getArgument('name')`.

## A complete example

```php
use InitPHP\Console\{Command, Input, InputArgument, Output};

final class CreateUserCommand extends Command
{
    public $command = 'user:create';

    public function arguments(): array
    {
        return [
            new InputArgument('email', InputArgument::STR,  '',    false, 'Required e-mail.'),
            new InputArgument('age',   InputArgument::INT,  18,    true,  'Age in years.'),
            new InputArgument('admin', InputArgument::BOOL, false, true,  'Grant admin rights.'),
        ];
    }

    public function execute(Input $input, Output $output)
    {
        $email = $input->getArgument('email');  // guaranteed present (required)
        $age   = $input->getArgument('age');    // int, defaults to 18
        $admin = $input->getArgument('admin');  // bool, defaults to false

        $output->success("Created {$email} (age {$age}, admin: " . ($admin ? 'yes' : 'no') . ')');
    }
}
```

```bash
php console.php user:create --email=ada@example.com --age=36 --admin=true
php console.php user:create                        # [ERROR] The --email parameter is undefined.
php console.php user:create --email=ada@example.com # age=18, admin=false
```

## Zero and `false` are real values

A required argument explicitly set to `0` keeps `0` — it is **not** mistaken
for "missing" and replaced by the default:

```php
new InputArgument('retries', InputArgument::INT, 5, false);

// php console.php run --retries=0   →  getArgument('retries') === 0
```

(The same applies to `false` and `'0'`. This is a deliberate behaviour of the
resolver.)

## Boolean coercion

`BOOL` accepts a real boolean, or the integers `1`/`0`, which are coerced to
`true`/`false`:

```php
new InputArgument('force', InputArgument::BOOL, false);

// --force=true → true        (Input casts "true" → bool true)
// --force=1    → true        (1 coerced to bool)
// --force=0    → false
```

## Reading the metadata

The help renderer (and your own code) can introspect an argument:

```php
$arg = new InputArgument('age', InputArgument::INT, 18, false, 'The age.');

$arg->getName();        // "--age"   (note the leading dashes)
$arg->getType();        // "INT"
$arg->getDefault();     // "18"      (string form, or null when no default)
$arg->getDefinition();  // "The age."
$arg->isOptional();     // false
```

## Where to go next

- [Commands](Commands.md) — how `arguments()` plugs into a command.
- [Input](Input.md) — the parsing and casting that feeds these arguments.
- [API Reference](API-Reference.md#inputargument) — every method and constant.
