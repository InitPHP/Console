# FAQ

Quick answers to questions that come up when using the package. If yours is
not here, please [open an issue](https://github.com/InitPHP/Console/issues/new)
— documentation fixes are reviewed eagerly.

## General

### What is `initphp/console` for?

Writing small-to-medium command-line tools in PHP: routing a command name to a
handler, parsing its arguments, printing coloured output, asking questions,
and rendering tables. It is dependency-free and deliberately minimal.

### What is it NOT for?

- Deep sub-command trees, shell auto-completion, or a rich argument grammar
  (variadics, negatable flags, `--` passthrough). The parser is flat — see
  [Input](Input.md).
- A built-in DI/service layer. Compose dependencies yourself; the package only
  routes the invocation.
- Output verbosity levels / structured logging out of the box. Build them on
  top of [`Output`](Output.md).

### Does it have any runtime dependencies?

No. Dev tools (PHPUnit, PHPStan, PHP-CS-Fixer) live under `require-dev`. The
runtime requires PHP only; `ext-mbstring` is an optional enhancement for table
alignment.

## Terminology

### Why is `--name` an "argument" but `-v` an "option"?

That is this library's convention: `--long` tokens are **arguments**, `-short`
tokens are **options**, and bare tokens are **segments**. It is the opposite of
some other frameworks, so double-check when porting code. See
[Input](Input.md#the-three-token-shapes).

## Usage

### How do I read a value that may be `--name` or `-name`?

Declare an [`InputArgument`](Input-Arguments.md): its `run()` looks the value up
as an argument first, then as an option, under the same name. For ad-hoc
reads, check both with `getArgument()` / `getOption()`.

### Why did `--age=30` come back as an `int`, not a string?

All tokens are cast by [`Helpers::strValueCast()`](Input.md#automatic-type-casting):
integer-like → `int`, decimal-like → `float`, `true`/`false`/`yes`/`no` →
`bool`, `null` → `null`. Pass it through `(string)` if you need the text form,
or declare the argument as `InputArgument::STR` to have it stringified.

### How do I give a command a default for a missing argument?

Two ways: read with a default — `$input->getArgument('name', 'World')` — or
declare an optional [`InputArgument`](Input-Arguments.md) with a default value,
which the framework writes back before `execute()` runs.

### How do I abort a command with an error message?

`throw` from the handler. The dispatcher catches any `Throwable`, prints
`[ERROR] <message>`, and `run()` returns `false`. You do not need to call
`exit()` yourself. See [Exceptions](Exceptions.md#exceptions-thrown-inside-a-command).

### How do I set the process exit code?

`run()` returns a bool. Map it in your entry script:

```php
exit($console->run() ? 0 : 1);
```

### Why does typing `exit` at a prompt kill my program?

`ask()` and `question()` treat `exit` and `quit` as a request to terminate —
they call the protected `Output::terminate()`, which `exit`s by default. To
intercept it (cleanup, or testing), subclass `Output` and override
`terminate()`. See [Testing Commands](Recipe-Testing-Commands.md).

### My yes/no question rejects "yes". Why?

`question()` accepts an answer only if it is in the question's options. The
default options are `[true, false]`, and `hasOption()` matches the
[cast](Input.md#automatic-type-casting) form — so `"yes"`/`"no"`/`"true"`/`"false"`
all work against the defaults. If you set custom string options
(`setOptions(['y', 'n'])`), the answer must match one of those. See
[Questions](Questions.md#acceptable-answer-matching).

### Can I register a command as an object method or invokable?

Yes. The handler is any PHP callable: a closure, `'Class::method'`,
`[$object, 'method']`, or an invokable object. Class-based commands use the
[`Command`](Commands.md) base class and are registered by class name.

## Output & tables

### How do I capture output for a test?

Inject a `php://memory` stream: `new Output($stream)`, then read the stream
after running. See [Output → injecting streams](Output.md#injecting-streams) and
[Testing Commands](Recipe-Testing-Commands.md).

### Why do my table booleans show as `[TRUE]`/`[FALSE]`?

That is how non-string values are stringified. Convert to `'yes'`/`'no'` (or
any string) before calling `row()` if you want different text. See
[Tables → how values are rendered](Tables.md#how-values-are-rendered).

### My UTF-8 table columns are misaligned.

Install `ext-mbstring`. Widths are measured with `mb_strlen()` when available;
without it the renderer falls back to byte length. See
[Tables → multibyte](Tables.md#multibyte-text).

## PHP / tooling

### Which PHP versions are supported?

PHP **8.2 and up**. CI runs the QA matrix on 8.2, 8.3, 8.4 and 8.5.

### Why does the code use PHPDoc types instead of native types in places?

Several signatures (for example `getArgument()`, `ask()` and `question()`)
carry no native type and are documented with PHPDoc. Adding native types to
them would change the contract that subclasses and `InputInterface` /
`OutputInterface` implementations rely on. See the note atop the
[API Reference](API-Reference.md).

### What is the PHPStan level?

Level 6, and the source is clean at that level. The coding standard is PER-CS,
checked with PHP-CS-Fixer (`composer cs-check`).

### Where are the tests?

Under `tests/` in the source repository. They cover input parsing, argument
validation, output formatting, the question flow, the dispatcher, and the
table renderer — using injected in-memory streams (see
[Testing Commands](Recipe-Testing-Commands.md)).

## Versioning

### Is the table renderer the same as `initphp/cli-table`?

Yes — it was merged in as of 2.1 and the old class name still resolves via a
`class_alias`. See the [Migration Guide](Migration-Guide.md).

### Will 2.x see breaking changes?

Not within the 2.x releases. The one pending breaking change, the PHP `^8.2`
requirement, is recorded in the
[changelog](https://github.com/InitPHP/Console/blob/main/CHANGELOG.md) under
"Unreleased". Anything that breaks the public contract (classes,
interfaces, the `Command` hooks, exception behaviour) would require a major
bump. New optional parameters and new methods are added compatibly.

## Other

### Where do I report a security issue?

See [`SECURITY.md`](https://github.com/InitPHP/.github/blob/main/SECURITY.md)
in the InitPHP org profile. Please do not file public issues for
vulnerabilities.

### Where do I open feature requests or discussions?

[InitPHP Discussions](https://github.com/orgs/InitPHP/discussions) is the place
for open-ended threads; concrete bug reports and proposals belong in
[the issue tracker](https://github.com/InitPHP/Console/issues).
