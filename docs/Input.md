# Input

[`Input`](API-Reference.md#input) parses the tokens that follow the command name
into three buckets and exposes them through a small, symmetric API. It
implements [`InputInterface`](API-Reference.md#inputinterface), so you can
type-hint the interface in your own helpers.

## The three token shapes

| Shape | Bucket | Example |
| --- | --- | --- |
| `--name` | argument | `--verbose` |
| `--name=value` | argument | `--name=John` |
| `-x` | option | `-v` |
| `-xyz` (combined) | option | `-abc` → `a`, `b`, `c` |
| `-key=value` | option | `-level=5` |
| bare token | segment | `migrate`, `42` |

> In this library `--long` tokens are **arguments** and `-short` tokens are
> **options** — the opposite of some other CLI frameworks.

A token consisting only of dashes and/or whitespace (such as a lone `--`)
carries no data and is skipped entirely.

## Automatic type casting

Every argument value, option value and segment is normalised with
[`Helpers::strValueCast()`](API-Reference.md#helpers):

| Input string | Result |
| --- | --- |
| `"true"`, `"yes"` | `true` |
| `"false"`, `"no"` | `false` |
| `"null"` | `null` |
| `"42"`, `"-7"`, `"+7"` | `int` |
| `"3.14"`, `"3,14"` | `float` |
| `""` (empty) | `""` (empty string) |
| anything else | `string` |

Matching of the keyword literals is case-insensitive (`TRUE` == `true`). Both
`.` and `,` are accepted as the decimal separator (the comma form is
normalised to a dot before casting).

```php
use InitPHP\Console\Input;

$input = new Input(['--age=30', '--active=true', '--ratio=1,5', 'deploy']);

$input->getArgument('age');     // int(30)
$input->getArgument('active');  // bool(true)
$input->getArgument('ratio');   // float(1.5)
$input->getSegment(0);          // string("deploy")
```

## Arguments — `--long`

```php
$input->hasArgument('name');           // bool
$input->getArgument('name', $default); // value, or $default (default null)
$input->allArguments();                // ['name' => 'John', ...]
```

A bare `--flag` (no `=value`) is stored as an empty string `''`, so
`hasArgument('flag')` is `true` and `getArgument('flag')` is `''`:

```php
$input = new Input(['--verbose', '--name=John']);

$input->hasArgument('verbose');  // true
$input->getArgument('verbose');  // ''
$input->getArgument('name');     // 'John'
```

## Options — `-short`

```php
$input->hasOption('v');            // bool
$input->getOption('level', $def);  // value, or $def (default null)
$input->allOptions();              // ['v' => 'v', 'level' => 5, ...]
```

A single short flag resolves to its own letter as the value, and combined
flags expand to one entry per letter:

```php
$input = new Input(['-v', '-abc', '-level=5']);

$input->getOption('v');    // 'v'
$input->allOptions();      // ['v' => 'v', 'a' => 'a', 'b' => 'b', 'c' => 'c', 'level' => 5]
```

> Combined short flags (`-abc`) only expand when there is no `=`. A token like
> `-level=5` is treated as a single named option, not as the flags `l e v ...`.

## Segments — bare positional tokens

Segments are bare tokens (no leading dash), indexed in the order they appear:

```php
$input = new Input(['migrate', 'up', '3']);

$input->hasSegment(0);   // true
$input->getSegment(0);   // 'migrate'
$input->getSegment(1);   // 'up'
$input->getSegment(2);   // int(3)
$input->getSegment(9, 'fallback'); // 'fallback'
$input->allSegment();    // ['migrate', 'up', 3]
```

## Importing values

`importArguments()` merges one or more `name => value` maps into the parsed
arguments (later maps win). It is used internally by
[`InputArgument`](Input-Arguments.md) to write resolved defaults back, and is
available to you too:

```php
$input->importArguments(['name' => 'fallback'], ['env' => 'prod']);
```

## Programming to the interface

```php
use InitPHP\Console\InputInterface;

function resolveName(InputInterface $input): string
{
    return (string) $input->getArgument('name', 'World');
}
```

`InputInterface` declares every public reader plus `allOptions()` and
`importArguments()` — see the [API Reference](API-Reference.md#inputinterface).

## A worked example

```php
// php console.php deploy app1 --env=prod --force -v
$input = new Input(['app1', '--env=prod', '--force', '-v']);

$input->getSegment(0);          // 'app1'
$input->getArgument('env');     // 'prod'
$input->hasArgument('force');   // true   (value is '')
$input->hasOption('v');         // true
```

## Where to go next

- [Input Arguments](Input-Arguments.md) — turn raw input into typed, validated values.
- [Commands](Commands.md) — where `Input` is handed to your handler.
- [API Reference](API-Reference.md#input) — every method, with signatures.
