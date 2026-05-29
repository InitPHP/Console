# Output & formatting

`Output` writes coloured/styled text to an output stream and reads interactive answers from an input stream. Both streams default to `STDOUT`/`STDIN` but can be injected for testing.

## Writing text

```php
$output->write('No trailing newline');
$output->writeln('With a trailing newline');
```

### Placeholder interpolation

The second argument is a context map; `{key}` tokens in the string are replaced. Array values and objects without `__toString()` are left untouched.

```php
$output->writeln('Deployed {app} to {env}', [
    'app' => 'api',
    'env' => 'production',
]);
// Deployed api to production
```

### Styling

The third argument (for `writeln`) / fourth (for `write`) is a list of SGR codes applied to the whole string:

```php
$output->writeln('Important', [], [Output::COLOR_RED, Output::BOLD]);
$output->writeln('On blue',   [], [Output::COLOR_WHITE, Output::BACKGROUND_BLUE]);
```

Available constants:

- **Foreground:** `COLOR_DEFAULT`, `COLOR_BLACK`, `COLOR_RED`, `COLOR_GREEN`, `COLOR_YELLOW`, `COLOR_BLUE`, `COLOR_MAGENTA`, `COLOR_CYAN`, `COLOR_LIGHT_GRAY`, `COLOR_DARK_GRAY`, `COLOR_LIGHT_RED`, `COLOR_LIGHT_GREEN`, `COLOR_LIGHT_YELLOW`, `COLOR_LIGHT_BLUE`, `COLOR_LIGHT_MAGENTA`, `COLOR_LIGHT_CYAN`, `COLOR_WHITE`
- **Background:** `BACKGROUND_BLACK`, `BACKGROUND_RED`, `BACKGROUND_GREEN`, `BACKGROUND_YELLOW`, `BACKGROUND_BLUE`, `BACKGROUND_MAGENTA`, `BACKGROUND_CYAN`
- **Styles:** `BOLD`, `ITALIC`, `UNDERLINE`, `STRIKETHROUGH`

## Message helpers

Pre-styled, prefixed one-liners:

```php
$output->error('Disk full');       // [ERROR]   white on red, bold
$output->success('Done');          // [SUCCESS] white on green, bold
$output->warning('Low memory');    // [WARNING] white on yellow, bold
$output->info('Cache warmed');     // [INFO]    cyan
```

Each accepts a context map too:

```php
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

The optional second argument indents the whole block by N tab stops — used by the built-in help screen to indent grouped commands.

## Progress bar

`progressBar($done, $total)` renders a single, in-place updating line. `$total` must be greater than zero (a `0` total throws `InvalidArgumentException`).

```php
$total = 50;
for ($i = 0; $i <= $total; $i++) {
    $output->progressBar($i, $total);
    usleep(20_000);
}
$output->writeln(''); // move off the progress line when finished
```

## Injecting streams (testing)

Pass a stream resource to capture output or feed answers:

```php
$buffer = fopen('php://memory', 'r+');
$output = new Output($buffer);

$output->writeln('hello');

rewind($buffer);
echo stream_get_contents($buffer); // "\e[39mhello\e[0m\n"
```

The second constructor argument overrides the input stream used by [`ask()`/`question()`](06-questions.md).

## Programming to the interface

`Output` implements `InitPHP\Console\OutputInterface`; type-hint that interface where you only need the public surface.
