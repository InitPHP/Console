# Output

[`Output`](API-Reference.md#output) writes coloured/styled text to an output
stream and reads interactive answers from an input stream. Both streams
default to `STDOUT`/`STDIN` but can be injected — see
[Testing Commands](Recipe-Testing-Commands.md). It implements
[`OutputInterface`](API-Reference.md#outputinterface).

## Writing text

```php
$output->write('No trailing newline');
$output->writeln('With a trailing newline');
```

`writeln()` is `write()` with the newline flag set.

### Placeholder interpolation

The `$context` map replaces `{key}` tokens in the string. Array values and
objects without `__toString()` are left untouched (so a stray `{` in your text
is safe):

```php
$output->writeln('Deployed {app} to {env}', [
    'app' => 'api',
    'env' => 'production',
]);
// Deployed api to production
```

### Styling with SGR codes

The last argument is a list of SGR codes applied to the whole string. For
`writeln()` it is the third argument; for `write()` the fourth:

```php
$output->writeln('Important', [], [Output::COLOR_RED, Output::BOLD]);
$output->writeln('On blue',   [], [Output::COLOR_WHITE, Output::BACKGROUND_BLUE]);
$output->write('inline', [], false, [Output::COLOR_GREEN]);
```

Available constants:

**Foreground colours**

`COLOR_DEFAULT`, `COLOR_BLACK`, `COLOR_RED`, `COLOR_GREEN`, `COLOR_YELLOW`,
`COLOR_BLUE`, `COLOR_MAGENTA`, `COLOR_CYAN`, `COLOR_LIGHT_GRAY`,
`COLOR_DARK_GRAY`, `COLOR_LIGHT_RED`, `COLOR_LIGHT_GREEN`,
`COLOR_LIGHT_YELLOW`, `COLOR_LIGHT_BLUE`, `COLOR_LIGHT_MAGENTA`,
`COLOR_LIGHT_CYAN`, `COLOR_WHITE`

**Backgrounds**

`BACKGROUND_BLACK`, `BACKGROUND_RED`, `BACKGROUND_GREEN`, `BACKGROUND_YELLOW`,
`BACKGROUND_BLUE`, `BACKGROUND_MAGENTA`, `BACKGROUND_CYAN`

**Text styles**

`BOLD`, `ITALIC`, `UNDERLINE`, `STRIKETHROUGH`

> The same constants exist on [`Utils\Table`](Tables.md), so a palette you pick
> for messages also drives table styling.

## Message helpers

Pre-styled, prefixed one-liners. Each accepts an optional `$context` map:

```php
$output->error('Disk full');                 // [ERROR]   white on red, bold
$output->success('Done');                     // [SUCCESS] white on green, bold
$output->warning('Low memory');               // [WARNING] white on yellow, bold
$output->info('Cache warmed');                // [INFO]    cyan
$output->error('User {id} not found', ['id' => 42]);
```

## Key/value lists

```php
$output->list([
    'Host' => 'localhost',
    'Port' => 8080,
    'TLS'  => 'enabled',
]);
```

```
Host : localhost
Port : 8080
TLS  : enabled
```

The optional second argument indents the whole block by N tab stops — this is
how the built-in help screen indents grouped commands:

```php
$output->list(['make:model' => 'Generates a model.'], 1);
```

## Progress bar

`progressBar($done, $total)` renders a single, in-place updating line. `$done`
and `$total` must be numeric and `$total` must be greater than zero, otherwise
it throws `\InvalidArgumentException`:

```php
$total = 50;
for ($i = 0; $i <= $total; $i++) {
    $output->progressBar($i, $total);
    usleep(20_000);
}
$output->writeln(''); // move off the progress line when finished
```

```
[==============================>          ] - 60%   30/50
```

## Interactive prompts

`ask()` and `question()` read a line from the input stream. See
[Questions](Questions.md) for the full treatment.

```php
$name = $output->ask('What is your name?');     // free-form, type-cast answer
$ok   = $output->ask('Cannot be empty:', false); // re-asks on empty input
```

Typing `exit` or `quit` at any prompt terminates the application.

## Injecting streams

Pass stream resources to capture output or feed answers — invaluable for
tests:

```php
$buffer = fopen('php://memory', 'r+');
$output = new Output($buffer);          // first arg: output stream

$output->writeln('hello');

rewind($buffer);
echo stream_get_contents($buffer);       // "\e[39mhello\e[0m\n"
```

The second constructor argument overrides the input stream used by
`ask()`/`question()`. See [Testing Commands](Recipe-Testing-Commands.md).

## Programming to the interface

`Output` implements `OutputInterface`; type-hint that interface where you only
need the public surface. (Note: [`Command::execute()`](Commands.md) and
[`InputArgument::run()`](Input-Arguments.md) are typed against the concrete
`Output` for backwards compatibility — see the
[API Reference](API-Reference.md#outputinterface).)

## Where to go next

- [Questions](Questions.md) — structured, option-constrained prompts.
- [Tables](Tables.md) — render tabular data with the same colour palette.
- [API Reference](API-Reference.md#output) — every method and constant.
