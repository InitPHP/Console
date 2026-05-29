# Input: arguments, options & segments

`Input` parses the tokens that follow the command name into three buckets and exposes them through a small, symmetric API.

## The three token shapes

| Shape                | Bucket    | Example                    |
|----------------------|-----------|----------------------------|
| `--name`             | argument  | `--verbose`                |
| `--name=value`       | argument  | `--name=John`              |
| `-x`                 | option    | `-v`                       |
| `-xyz` (combined)    | option    | `-abc` → `a`, `b`, `c`     |
| `-key=value`         | option    | `-level=5`                 |
| bare token           | segment   | `migrate`, `42`            |

> In this library `--long` tokens are **arguments** and `-short` tokens are **options** — the opposite of some other CLI frameworks.

A token consisting only of dashes/whitespace (such as a lone `--`) carries no data and is skipped.

## Automatic type casting

Every value is normalised with `Helpers::strValueCast()`:

| Input string             | Result        |
|--------------------------|---------------|
| `"true"`, `"yes"`        | `true`        |
| `"false"`, `"no"`        | `false`       |
| `"null"`                 | `null`        |
| `"42"`, `"-7"`, `"+7"`   | `int`         |
| `"3.14"`, `"3,14"`       | `float`       |
| anything else            | `string`      |

(Matching is case-insensitive. Both `.` and `,` are accepted as the decimal separator.)

```php
$input = new Input(['--age=30', '--active=true', '--ratio=1,5', 'deploy']);

$input->getArgument('age');     // int(30)
$input->getArgument('active');  // bool(true)
$input->getArgument('ratio');   // float(1.5)
$input->getSegment(0);          // string("deploy")
```

## Arguments API

```php
$input->hasArgument('name');              // bool
$input->getArgument('name', $default);    // value or $default (null when omitted)
$input->allArguments();                   // ['name' => 'John', ...]
```

A bare `--flag` (no `=value`) is stored as an empty string `''`, so `hasArgument('flag')` is `true` and `getArgument('flag')` is `''`.

## Options API

```php
$input->hasOption('v');                   // bool
$input->getOption('level', $default);     // value or $default
$input->allOptions();                     // ['v' => 'v', 'level' => 5, ...]
```

Boolean short flags resolve to their own letter as the value:

```php
$input = new Input(['-abc']);
$input->allOptions();   // ['a' => 'a', 'b' => 'b', 'c' => 'c']
```

## Segments API

Segments are bare positional tokens, indexed in order:

```php
$input = new Input(['migrate', 'up', '3']);

$input->getSegment(0);   // string("migrate")
$input->getSegment(1);   // string("up")
$input->getSegment(2);   // int(3)
$input->allSegment();    // ['migrate', 'up', 3]
```

## Importing values

`importArguments()` merges one or more `name => value` maps into the parsed arguments (later maps win). It is mainly used internally by [`InputArgument`](04-input-arguments.md) to write back resolved defaults, but it is available to you too:

```php
$input->importArguments(['name' => 'fallback'], ['env' => 'prod']);
```

## Programming to the interface

`Input` implements `InitPHP\Console\InputInterface`. Type-hint the interface in your own helpers when you want to accept any compatible implementation:

```php
use InitPHP\Console\InputInterface;

function resolveName(InputInterface $input): string
{
    return (string) $input->getArgument('name', 'World');
}
```
