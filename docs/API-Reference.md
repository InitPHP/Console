# API Reference

The full public surface of `initphp/console`. Behavioural deep-dives are
linked into the [Building Commands](Commands.md) and
[Output & Interaction](Output.md) sections.

> Signatures are shown as they appear in the source. The library requires
> **PHP 8.2+**. Where a signature below carries a `// mixed` comment, the source
> has no native type and documents it with PHPDoc; `?Type` nullable hints and
> scalar parameter/return types are native.

**Classes & interfaces**

- [`Application`](#application)
- [`Command`](#command)
- [`Input`](#input) / [`InputInterface`](#inputinterface)
- [`InputArgument`](#inputargument)
- [`Output`](#output) / [`OutputInterface`](#outputinterface)
- [`Question`](#question)
- [`Helpers`](#helpers)
- [`Utils\Table`](#utilstable)

---

## `Application`

`InitPHP\Console\Application` — the command registry and dispatcher.

```php
public const VERSION = '2.1.0';
```

### Constructor

```php
public function __construct(
    string  $application = 'InitPHP Console Application',
    string  $version = self::VERSION,
    ?Output $output = null
);
```

Stores the application name and version shown in help output. When `$output`
is `null` a default [`Output`](#output) is created lazily during `run()`. Inject
one (e.g. backed by a memory stream) to capture output in tests.

### `register`

```php
public function register(string $command, ?callable $execute = null, string $definition = ''): self;
```

Registers a command. Two modes:

- **Closure mode** (`$execute` is a callable): `$command` is the command name,
  `$definition` is the short listing description.
- **Class mode** (`$execute` is `null`): `$command` is the fully-qualified name
  of a [`Command`](#command) subclass. It is instantiated; its `$command`,
  `definition()` and `help()` are read from the instance.

Returns `$this`. Throws:

- `\InvalidArgumentException` — class mode: the class does not exist or is not
  a `Command`.
- `\LogicException` — class mode: a `Command` class does not set its `$command`
  name.
- `\TypeError` — closure mode: `$execute` is not callable. The `?callable`
  parameter type rejects it before the method body runs.

See [Exceptions](Exceptions.md).

### `run`

```php
public function run(?array $argv = null): bool;
```

Parses the token list and dispatches. When `$argv` is `null` the global
`$argv` is used. The first element is treated as the script name and dropped;
the next token is the command name.

Returns:

- `false` — no command was supplied (only the script name, or an empty list).
- `true` — `help`/`list` ran, a command ran, or the command was not found
  (handled gracefully with an error message).

`false` is also returned when a command's declared argument validation fails,
when the handler is not executable, or when the handler throws (the `Throwable`
is caught and its message printed via `Output::error()`).

Reserved command names `help` and `list` render the overview screen; the
`--help` argument on any registered command renders its per-command usage.

---

## `Command`

`InitPHP\Console\Command` — abstract base class for class-based commands.

```php
public $command;                                  // the invocation name (set in a subclass)

abstract public function execute(Input $input, Output $output);

public function help(): string;        // default: 'No explanation for this command is specified.'
public function definition(): string;  // default: ''
public function arguments(): array;    // default: []  — array<int, InputArgument>
```

`$command` must be set by the subclass. `arguments()` returns the typed
[`InputArgument`](#inputargument) declarations validated before `execute()`.
See [Commands](Commands.md).

---

## `InputInterface`

`InitPHP\Console\InputInterface` — read access to parsed tokens.

```php
public function hasArgument(string $name): bool;
public function getArgument(string $name, $default = null);   // mixed
public function allArguments(): array;                        // array<string, mixed>

public function hasSegment(int $index): bool;
public function getSegment(int $index, $default = null);      // mixed
public function allSegment(): array;                          // array<int, mixed>

public function hasOption(string $name): bool;
public function getOption(string $name, $default = null);     // mixed
public function allOptions(): array;                          // array<string, mixed>

public function importArguments(array ...$arguments): void;   // merge maps (later wins)
```

Implemented by [`Input`](#input). Depend on the interface in helpers that only
read input.

---

## `Input`

`InitPHP\Console\Input implements InputInterface` — parses the tokens that
follow the command name.

### Constructor

```php
public function __construct(array $argv);
```

`$argv` is the token list **after** the command name. Parsing rules:

- `--name=value` / `--name` → **argument** (bare form stored as `''`).
- `-key=value` → **option**; `-abc` (no `=`) → combined boolean flags
  (`a`, `b`, `c`), each stored as its own letter.
- bare token → **segment**.
- a token of only dashes/whitespace (e.g. `--`) is skipped.

All values are cast with [`Helpers::strValueCast()`](#helpers).

### Methods

Identical to [`InputInterface`](#inputinterface). See [Input](Input.md) for
worked examples.

---

## `InputArgument`

`InitPHP\Console\InputArgument` (final) — a typed `--name` argument.

### Constants

```php
public const ANY     = 'ANY';
public const INT     = 'INT';
public const FLOAT   = 'FLOAT';
public const NUMERIC = 'NUMBER';
public const BOOL    = 'BOOL';
public const STR     = 'STRING';
```

### Constructor

```php
public function __construct(
    string $name,
    $type,                       // one of the constants above
    $default,                    // must satisfy $type
    bool   $isOptional = true,
    string $definition = ''
);
```

Throws `\InvalidArgumentException` if `$type` is unsupported or `$default`
does not satisfy it.

### Methods

```php
public function isOptional(): bool;
public function getType(): string;          // the type constant value, e.g. "INT"
public function getName(): string;          // the name prefixed with "--"
public function getDefinition(): string;
public function getDefault(): ?string;      // default as a string, or null
public function run(Input $input, Output $output): bool;
```

`run()` resolves the value (argument first, then option), validates it,
applies the default where appropriate, and writes the result back into
`$input`. Returns `false` when a required argument is missing or invalid. See
[Input Arguments](Input-Arguments.md).

---

## `OutputInterface`

`InitPHP\Console\OutputInterface` — the writing/prompting contract.

```php
public function write(string $str, array $context = [], bool $newLine = false, array $format = []): void;
public function writeln(string $str, array $context = [], array $format = []): void;
public function list(array $rows, int $leftSpace = 0): void;
public function progressBar($done, $total): void;
public function ask(string $question, bool $empty = true);          // mixed
public function question(Question $question);                       // mixed
public function error(string $msg, array $context = []): void;
public function success(string $msg, array $context = []): void;
public function warning(string $msg, array $context = []): void;
public function info(string $msg, array $context = []): void;
```

Implemented by [`Output`](#output).

> The concrete `Output` uses `[Output::COLOR_DEFAULT]` as the default
> `$format` for `write()`/`writeln()` (the interface declares `[]` since it
> cannot reference the implementation's constants). Both render uncoloured
> text by default.

---

## `Output`

`InitPHP\Console\Output implements OutputInterface`.

### Style constants

**Foreground:** `COLOR_DEFAULT` (39), `COLOR_BLACK` (30), `COLOR_RED` (31),
`COLOR_GREEN` (32), `COLOR_YELLOW` (33), `COLOR_BLUE` (34), `COLOR_MAGENTA`
(35), `COLOR_CYAN` (36), `COLOR_LIGHT_GRAY` (37), `COLOR_DARK_GRAY` (90),
`COLOR_LIGHT_RED` (91), `COLOR_LIGHT_GREEN` (92), `COLOR_LIGHT_YELLOW` (93),
`COLOR_LIGHT_BLUE` (94), `COLOR_LIGHT_MAGENTA` (95), `COLOR_LIGHT_CYAN` (96),
`COLOR_WHITE` (97).

**Background:** `BACKGROUND_BLACK` (40), `BACKGROUND_RED` (41),
`BACKGROUND_GREEN` (42), `BACKGROUND_YELLOW` (43), `BACKGROUND_BLUE` (44),
`BACKGROUND_MAGENTA` (45), `BACKGROUND_CYAN` (46).

**Styles:** `ITALIC` (3), `BOLD` (1), `UNDERLINE` (4), `STRIKETHROUGH` (9).

### Constructor

```php
public function __construct($stdout = null, $stdin = null);
```

`$stdout`/`$stdin` are stream resources, defaulting to `STDOUT`/`STDIN`. Inject
`php://memory` handles to capture output / feed answers in tests.

### Methods

```php
public function write(string $str, array $context = [], bool $newLine = false, array $format = [self::COLOR_DEFAULT]): void;
public function writeln(string $str, array $context = [], array $format = [self::COLOR_DEFAULT]): void;
public function list(array $rows, int $leftSpace = 0): void;
public function progressBar($done, $total): void;   // throws \InvalidArgumentException
public function ask(string $question, bool $empty = true);
public function question(Question $question);
public function error(string $msg, array $context = []): void;
public function success(string $msg, array $context = []): void;
public function warning(string $msg, array $context = []): void;
public function info(string $msg, array $context = []): void;
```

`progressBar()` throws `\InvalidArgumentException` when `$done`/`$total` are
not numeric or when `$total <= 0`. `ask()`/`question()` terminate the process
when the user enters `exit` or `quit`.

### Protected hook

```php
protected function terminate(int $code = 0): void;   // default: exit($code)
```

Override in a subclass (e.g. to throw) so the `exit`/`quit` prompt path can be
exercised in tests without killing the runner. See
[Testing Commands](Recipe-Testing-Commands.md).

---

## `Question`

`InitPHP\Console\Question` — value object for an interactive question.

```php
public const NO_DEFAULT = '__Qu€sti0nN0D€f@ultV@lue__';  // sentinel; prefer hasDefault()

public function isOptional(): bool;
public function optional(): self;
public function notOptional(): self;

public function hasOption(string $option): bool;        // matches verbatim or cast
public function setOptions(array $options): self;
public function getOptions(): array;
public function addOption(string $option, $value = self::NO_DEFAULT): self;

public function setQuestion(string $question): self;
public function getQuestion(): string;                  // '' when unset

public function setDefault($default): self;
public function hasDefault(): bool;
public function getDefault();                           // mixed, or NO_DEFAULT when unset
```

Default options are `[true, false]`. See [Questions](Questions.md).

---

## `Helpers`

`InitPHP\Console\Helpers` (final).

```php
public static function strValueCast(string $value);     // mixed
```

Casts a raw command-line token to the most appropriate scalar:
`""`→`""`, `"null"`→`null`, `"true"`/`"yes"`→`true`, `"false"`/`"no"`→`false`,
integer-like→`int`, decimal-like (`.` or `,`)→`float`, otherwise the string
unchanged. Keyword matching is case-insensitive. See
[Input → casting](Input.md#automatic-type-casting).

---

## `Utils\Table`

`InitPHP\Console\Utils\Table` — ASCII/ANSI table renderer.

### Style constants

The same `COLOR_*`, `BACKGROUND_*`, `ITALIC`/`BOLD`/`UNDERLINE`/`STRIKETHROUGH`
constants (and values) as [`Output`](#output).

### Methods

```php
public static function create(): Table;
public function __toString(): string;                              // === getContent()

public function setHeaderStyle(int ...$format): self;              // default [BOLD]
public function setCellStyle(int ...$format): self;
public function setBorderStyle(int ...$format): self;
public function setColumnCellStyle(string $column, int ...$format): self;

public function row(array $assoc): self;                           // append a row
public function getContent(): string;                              // render
```

`row()` keys form the header (union across rows). Non-string values are
stringified (`[NULL]`, `[TRUE]`, `[FALSE]`, `[ARRAY]`, `[CALLABLE]`,
`[RESOURCE]`, or the class name for objects). Missing cells render as `[NULL]`.
Widths use `mb_strlen()` when available. See [Tables](Tables.md).

---

## Backwards-compatibility alias

`src/aliases.php` (loaded via Composer `files` autoloading) registers:

```php
class_alias(\InitPHP\Console\Utils\Table::class, 'InitPHP\\CLITable\\Table');
```

so code written against the old `initphp/cli-table` package keeps working. See
[Migration Guide](Migration-Guide.md).
