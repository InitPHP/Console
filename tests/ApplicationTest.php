<?php

declare(strict_types=1);

namespace Test\InitPHP\Console;

use InitPHP\Console\Application;
use InitPHP\Console\Input;
use InitPHP\Console\Output;
use PHPUnit\Framework\TestCase;
use Test\InitPHP\Console\Fixtures\BadArgsCommand;
use Test\InitPHP\Console\Fixtures\GreetCommand;
use Test\InitPHP\Console\Fixtures\NoNameCommand;
use Test\InitPHP\Console\Fixtures\RequiredArgCommand;
use Test\InitPHP\Console\Support\MemoryOutput;

final class ApplicationTest extends TestCase
{
    private function app(MemoryOutput $output): Application
    {
        return new Application('My App', '1.2.3', $output);
    }

    public function testRegisterReturnsSelf(): void
    {
        $app = $this->app(new MemoryOutput());

        self::assertSame($app, $app->register('noop', static function (): void {
        }));
    }

    public function testRegisterRejectsUnknownClass(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->app(new MemoryOutput())->register('This\\Class\\Does\\Not\\Exist');
    }

    public function testRegisterRejectsNonCommandClass(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->app(new MemoryOutput())->register(\stdClass::class);
    }

    public function testRegisterRejectsCommandWithoutName(): void
    {
        $this->expectException(\LogicException::class);
        $this->app(new MemoryOutput())->register(NoNameCommand::class);
    }

    public function testRunReturnsFalseWhenNoCommandGiven(): void
    {
        $out = new MemoryOutput();
        self::assertFalse($this->app($out)->run(['console.php']));
    }

    public function testRunReportsUnknownCommand(): void
    {
        $out = new MemoryOutput();

        self::assertTrue($this->app($out)->run(['console.php', 'ghost']));
        self::assertStringContainsString('command was not found', $out->plain());
    }

    public function testRunDispatchesClosureWithInputAndOutput(): void
    {
        $out = new MemoryOutput();
        $app = $this->app($out);
        $app->register('hello', static function (Input $input, Output $output): void {
            $output->writeln('Hello {n}', ['n' => $input->getArgument('name', 'nobody')]);
        }, 'Say hello.');

        self::assertTrue($app->run(['console.php', 'hello', '--name=Sam']));
        self::assertStringContainsString('Hello Sam', $out->plain());
    }

    public function testRunCatchesThrowableFromCommand(): void
    {
        $out = new MemoryOutput();
        $app = $this->app($out);
        $app->register('boom', static function (): void {
            throw new \RuntimeException('kaboom');
        });

        self::assertFalse($app->run(['console.php', 'boom']));
        self::assertStringContainsString('kaboom', $out->plain());
    }

    public function testRunDispatchesCommandClassAndValidatesArguments(): void
    {
        $out = new MemoryOutput();
        $app = $this->app($out);
        $app->register(GreetCommand::class);

        self::assertTrue($app->run(['console.php', 'app:greet', '--name=Ada']));
        self::assertStringContainsString('Hi Ada', $out->plain());
    }

    public function testCommandClassUsesArgumentDefault(): void
    {
        $out = new MemoryOutput();
        $app = $this->app($out);
        $app->register(GreetCommand::class);

        self::assertTrue($app->run(['console.php', 'app:greet']));
        self::assertStringContainsString('Hi World', $out->plain());
    }

    public function testRequiredArgumentMissingAbortsBeforeExecute(): void
    {
        $out = new MemoryOutput();
        $app = $this->app($out);
        $app->register(RequiredArgCommand::class);

        self::assertFalse($app->run(['console.php', 'app:req']));
        self::assertStringContainsString('--id', $out->plain());
        self::assertStringNotContainsString('EXECUTED', $out->plain());
    }

    public function testRequiredArgumentProvidedRunsExecute(): void
    {
        $out = new MemoryOutput();
        $app = $this->app($out);
        $app->register(RequiredArgCommand::class);

        self::assertTrue($app->run(['console.php', 'app:req', '--id=5']));
        self::assertStringContainsString('EXECUTED id=5', $out->plain());
    }

    public function testInvalidArgumentDefinitionAborts(): void
    {
        $out = new MemoryOutput();
        $app = $this->app($out);
        $app->register(BadArgsCommand::class);

        self::assertFalse($app->run(['console.php', 'app:bad']));
        self::assertStringNotContainsString('EXECUTED', $out->plain());
    }

    public function testListRendersRegisteredCommands(): void
    {
        $out = new MemoryOutput();
        $app = $this->app($out);
        $app->register(GreetCommand::class);
        $app->register('solo', static function (): void {
        }, 'A solo command.');

        self::assertTrue($app->run(['console.php', 'list']));
        $plain = $out->plain();
        self::assertStringContainsString('My App v1.2.3', $plain);
        self::assertStringContainsString('app:greet', $plain);
        self::assertStringContainsString('solo', $plain);
    }

    public function testCommandHelpRendersUsage(): void
    {
        $out = new MemoryOutput();
        $app = $this->app($out);
        $app->register(GreetCommand::class);

        self::assertTrue($app->run(['console.php', 'app:greet', '--help']));
        $plain = $out->plain();
        self::assertStringContainsString('[USAGE]', $plain);
        self::assertStringContainsString('[PARAMETERS]', $plain);
        self::assertStringContainsString('app:greet', $plain);
    }
}
