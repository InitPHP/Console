# Recipe: Testing Commands

Because [`Application`](API-Reference.md#application) accepts an injected
[`Output`](Output.md) and `run()` accepts an explicit `$argv`, commands can be
unit-tested without touching `STDOUT`/`STDIN` or the global `$argv`. This is
exactly how the package tests itself.

## A capturing Output

Back an `Output` with `php://memory` streams: the first stream captures
everything written, the second feeds answers to prompts. Overriding the
protected [`terminate()`](API-Reference.md#output) hook turns the `exit`/`quit`
prompt path into a catchable exception instead of killing the test runner.

```php
namespace Tests\Support;

use InitPHP\Console\Output;

final class MemoryOutput extends Output
{
    /** @var resource */
    private $buffer;

    public function __construct(string $stdinContent = '')
    {
        $buffer = fopen('php://memory', 'r+');
        $stdin  = fopen('php://memory', 'r+');
        fwrite($stdin, $stdinContent);
        rewind($stdin);

        $this->buffer = $buffer;
        parent::__construct($buffer, $stdin);
    }

    public function captured(): string
    {
        rewind($this->buffer);
        return (string) stream_get_contents($this->buffer);
    }

    /** Captured output with SGR colour codes stripped. */
    public function plain(): string
    {
        return (string) preg_replace('/\e\x5B[0-9;]*m/', '', $this->captured());
    }

    protected function terminate(int $code = 0): void
    {
        throw new TerminateException('terminated', $code);
    }
}

final class TerminateException extends \RuntimeException {}
```

## Testing a closure command

```php
use PHPUnit\Framework\TestCase;
use InitPHP\Console\{Application, Input, Output};
use Tests\Support\MemoryOutput;

final class GreetTest extends TestCase
{
    public function testGreetUsesTheNameArgument(): void
    {
        $out = new MemoryOutput();
        $app = new Application('Test', '1.0.0', $out);

        $app->register('greet', function (Input $input, Output $output) {
            $output->writeln('Hello {n}', ['n' => $input->getArgument('name', 'World')]);
        });

        self::assertTrue($app->run(['console.php', 'greet', '--name=Ada']));
        self::assertStringContainsString('Hello Ada', $out->plain());
    }
}
```

The key moves:

- Inject `MemoryOutput` as the third constructor argument.
- Pass an explicit token list to `run()` — the first element stands in for the
  script name.
- Assert against `plain()` (colour codes stripped) so assertions stay readable.

## Testing a class-based command and its validation

```php
public function testRequiredArgumentAbortsBeforeExecute(): void
{
    $out = new MemoryOutput();
    $app = new Application('Test', '1.0.0', $out);
    $app->register(\App\Command\CommitCommand::class);

    // Missing the required --message:
    self::assertFalse($app->run(['console.php', 'commit']));
    self::assertStringContainsString('--message', $out->plain());
}

public function testCommitRuns(): void
{
    $out = new MemoryOutput();
    $app = new Application('Test', '1.0.0', $out);
    $app->register(\App\Command\CommitCommand::class);

    self::assertTrue($app->run(['console.php', 'commit', '--message=Initial']));
    self::assertStringContainsString('Initial', $out->plain());
}
```

## Testing prompts

Seed the answers in the constructor; each `fgets()` consumes one line:

```php
public function testInstallerAcceptsDefaults(): void
{
    // Two prompts: blank project name (→ default), then "sqlite".
    $out = new MemoryOutput("\nsqlite\n");
    $app = new Application('Test', '1.0.0', $out);
    $app->register(\App\Command\InstallCommand::class);

    self::assertTrue($app->run(['console.php', 'app:install']));
    self::assertStringContainsString('ready', $out->plain());
}
```

Test the `exit`/`quit` path via the overridden `terminate()`:

```php
public function testQuitTerminates(): void
{
    $out = new MemoryOutput("quit\n");

    $this->expectException(\Tests\Support\TerminateException::class);
    $out->ask('Anything?');
}
```

## Testing Output directly

You do not need an `Application` to test formatting:

```php
public function testProgressBarGuardsZeroTotal(): void
{
    $this->expectException(\InvalidArgumentException::class);
    (new MemoryOutput())->progressBar(1, 0);
}

public function testListRendersPairs(): void
{
    $out = new MemoryOutput();
    $out->list(['Host' => 'localhost', 'Port' => 8080]);

    self::assertStringContainsString(': localhost', $out->plain());
}
```

## Testing the table renderer

`Utils\Table` returns a string, so assert on its content directly — no streams
needed:

```php
use InitPHP\Console\Utils\Table;

public function testTableRendersHeadersAndPlaceholders(): void
{
    $content = Table::create()
        ->row(['id' => 1, 'name' => 'Ada'])
        ->row(['id' => 2])               // missing name → [NULL]
        ->getContent();

    self::assertStringContainsString('name', $content);
    self::assertStringContainsString('[NULL]', $content);
}
```

## See also

- [Output → injecting streams](Output.md#injecting-streams)
- [Questions → testing prompts](Questions.md#testing-prompts)
- [API Reference → Output::terminate](API-Reference.md#output)
