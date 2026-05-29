# Typed input arguments

`InputArgument` lets a [class-based command](02-commands.md) declare the `--name` arguments it accepts, each with a type, a default value and an optional/required flag. The application validates them before calling `execute()`.

## Constructing an argument

```php
use InitPHP\Console\InputArgument;

new InputArgument(
    string $name,          // name without dashes, e.g. "name"
    string $type,          // one of the type constants below
    mixed  $default,       // must satisfy $type
    bool   $isOptional = true,
    string $definition = '' // description for the help screen
);
```

The constructor throws `InvalidArgumentException` if the type is unknown, or if the default value does not satisfy the type.

## Types

| Constant                  | Underlying value | Accepts                                                     |
|---------------------------|------------------|-------------------------------------------------------------|
| `InputArgument::ANY`      | `ANY`            | Any value.                                                  |
| `InputArgument::INT`      | `INT`            | Integers.                                                   |
| `InputArgument::FLOAT`    | `FLOAT`          | Floats.                                                     |
| `InputArgument::NUMERIC`  | `NUMBER`         | Any numeric value (int, float or numeric string).          |
| `InputArgument::BOOL`     | `BOOL`           | `true`/`false`, or `1`/`0` (coerced to bool).               |
| `InputArgument::STR`      | `STRING`         | Strings (numeric/bool values are stringified).              |

Because `Input` casts values first (see [Input](03-input.md)), `--age=30` already arrives as `int(30)` and passes the `INT` check; `--age=abc` stays a string and fails it.

## How resolution works

For each declared argument, `run()` does the following:

1. Look the value up as an **argument** (`--name`), then as an **option** (`-name`).
2. **Missing value:**
   - optional → the default is stored and the command proceeds.
   - required → an error is printed and the command aborts.
3. **Present value:**
   - valid → kept (a required argument given an empty `''`/`null` value falls back to its default).
   - invalid + optional → replaced with the default.
   - invalid + required → an error is printed and the command aborts.

The resolved value is written back into `Input`, so `execute()` reads it with `$input->getArgument('name')`.

## Example

```php
public function arguments(): array
{
    return [
        new InputArgument('name',  InputArgument::STR,   '',    false, 'Required model name.'),
        new InputArgument('count', InputArgument::INT,   1,     true,  'How many to create.'),
        new InputArgument('force', InputArgument::BOOL,  false, true,  'Overwrite existing files.'),
    ];
}

public function execute(Input $input, Output $output)
{
    $name  = $input->getArgument('name');   // guaranteed present (required)
    $count = $input->getArgument('count');  // int, defaults to 1
    $force = $input->getArgument('force');  // bool, defaults to false
    // ...
}
```

```bash
php console make:model --name=User --count=3 --force=true
php console make:model                 # error: The --name parameter is undefined.
php console make:model --name=User     # count=1, force=false
```

## Zero and `false` are real values

A required argument explicitly set to `0` keeps `0` (it is not mistaken for "missing" and replaced by the default):

```php
new InputArgument('retries', InputArgument::INT, 5, false);
// php console run --retries=0   →  getArgument('retries') === 0
```

## Reading the metadata

```php
$arg = new InputArgument('age', InputArgument::INT, 18, false, 'The age.');

$arg->getName();        // "--age"
$arg->getType();        // "INT"
$arg->getDefault();     // "18"  (string form, or null when no default)
$arg->getDefinition();  // "The age."
$arg->isOptional();     // false
```
