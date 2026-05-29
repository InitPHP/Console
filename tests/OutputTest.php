<?php

declare(strict_types=1);

namespace Test\InitPHP\Console;

use InitPHP\Console\Output;
use InitPHP\Console\OutputInterface;
use InitPHP\Console\Question;
use PHPUnit\Framework\TestCase;
use Test\InitPHP\Console\Support\MemoryOutput;
use Test\InitPHP\Console\Support\TerminateException;

final class OutputTest extends TestCase
{
    public function testImplementsInterface(): void
    {
        self::assertInstanceOf(OutputInterface::class, new MemoryOutput());
    }

    public function testWriteInterpolatesContextAndWrapsInSgr(): void
    {
        $out = new MemoryOutput();
        $out->write('Hello {name}', ['name' => 'Bob']);

        self::assertSame('Hello Bob', $out->plain());
        self::assertStringContainsString("\e[", $out->captured());
        self::assertStringContainsString("\e[0m", $out->captured());
    }

    public function testWriteIgnoresArrayAndNonStringableObjectContext(): void
    {
        $out = new MemoryOutput();
        $out->write('A {arr} B {obj}', ['arr' => [1, 2], 'obj' => new \stdClass()]);

        self::assertSame('A {arr} B {obj}', $out->plain());
    }

    public function testWritelnAppendsNewline(): void
    {
        $out = new MemoryOutput();
        $out->writeln('line');

        self::assertStringEndsWith(\PHP_EOL, $out->captured());
        self::assertSame('line' . \PHP_EOL, $out->plain());
    }

    public function testMessageHelpersArePrefixed(): void
    {
        $out = new MemoryOutput();
        $out->error('boom');
        $out->success('great');
        $out->warning('careful');
        $out->info('fyi');

        $plain = $out->plain();
        self::assertStringContainsString('[ERROR] boom', $plain);
        self::assertStringContainsString('[SUCCESS] great', $plain);
        self::assertStringContainsString('[WARNING] careful', $plain);
        self::assertStringContainsString('[INFO] fyi', $plain);
    }

    public function testList(): void
    {
        $out = new MemoryOutput();
        $out->list(['name' => 'value', 'k' => 'v']);

        $plain = $out->plain();
        self::assertStringContainsString('name', $plain);
        self::assertStringContainsString(': value', $plain);
        self::assertStringContainsString(': v', $plain);
    }

    public function testProgressBarRendersPercentageAndRatio(): void
    {
        $out = new MemoryOutput();
        $out->progressBar(50, 100);

        $captured = $out->captured();
        self::assertStringContainsString('50%', $captured);
        self::assertStringContainsString('50/100', $captured);
    }

    public function testProgressBarRejectsNonNumeric(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new MemoryOutput())->progressBar('a', 'b');
    }

    public function testProgressBarRejectsZeroTotal(): void
    {
        // Regression: division-by-zero guard.
        $this->expectException(\InvalidArgumentException::class);
        (new MemoryOutput())->progressBar(1, 0);
    }

    public function testAskReturnsCastAnswer(): void
    {
        $out = new MemoryOutput("42\n");

        self::assertSame(42, $out->ask('How many?'));
        self::assertStringContainsString('How many?', $out->plain());
    }

    public function testAskRejectsEmptyWhenNotAllowed(): void
    {
        $out = new MemoryOutput("\n  \nfinally\n");

        self::assertSame('finally', $out->ask('Value:', false));
    }

    public function testAskExitTerminates(): void
    {
        $out = new MemoryOutput("exit\n");

        $this->expectException(TerminateException::class);
        $out->ask('Value:');
    }

    public function testQuestionReturnsMatchingOption(): void
    {
        $out = new MemoryOutput("red\n");
        $question = (new Question())->setQuestion('Colour?')->setOptions(['red', 'green']);

        self::assertSame('red', $out->question($question));
    }

    public function testQuestionFallsBackToDefaultWhenOptional(): void
    {
        $out = new MemoryOutput("purple\n");
        $question = (new Question())
            ->setQuestion('Colour?')
            ->setOptions(['red'])
            ->optional()
            ->setDefault('blue');

        self::assertSame('blue', $out->question($question));
    }

    public function testQuestionQuitTerminates(): void
    {
        $out = new MemoryOutput("quit\n");
        $question = (new Question())->setQuestion('Colour?')->setOptions(['red']);

        $this->expectException(TerminateException::class);
        $out->question($question);
    }

    public function testDefaultConstructorUsesStandardStreams(): void
    {
        // Construction must not require arguments (BC) and must not write anything.
        $out = new Output();
        self::assertInstanceOf(OutputInterface::class, $out);
    }
}
