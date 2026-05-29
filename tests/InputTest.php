<?php

declare(strict_types=1);

namespace Test\InitPHP\Console;

use InitPHP\Console\Input;
use InitPHP\Console\InputInterface;
use PHPUnit\Framework\TestCase;

final class InputTest extends TestCase
{
    public function testImplementsInterface(): void
    {
        self::assertInstanceOf(InputInterface::class, new Input([]));
    }

    public function testLongArgumentsWithAndWithoutValues(): void
    {
        $input = new Input(['--name=John', '--verbose']);

        self::assertTrue($input->hasArgument('name'));
        self::assertSame('John', $input->getArgument('name'));
        self::assertTrue($input->hasArgument('verbose'));
        self::assertSame('', $input->getArgument('verbose'));
        self::assertFalse($input->hasArgument('missing'));
        self::assertSame('fallback', $input->getArgument('missing', 'fallback'));
        self::assertSame(['name' => 'John', 'verbose' => ''], $input->allArguments());
    }

    public function testArgumentValuesAreTypeCast(): void
    {
        $input = new Input(['--age=30', '--ratio=1.5', '--active=true']);

        self::assertSame(30, $input->getArgument('age'));
        self::assertSame(1.5, $input->getArgument('ratio'));
        self::assertTrue($input->getArgument('active'));
    }

    public function testSingleShortOption(): void
    {
        $input = new Input(['-v']);

        self::assertTrue($input->hasOption('v'));
        self::assertSame('v', $input->getOption('v'));
    }

    public function testCombinedShortOptionsDoNotLeakAggregateKey(): void
    {
        $input = new Input(['-abc']);

        // Regression: the old parser also stored a bogus "abc" key.
        self::assertSame(['a' => 'a', 'b' => 'b', 'c' => 'c'], $input->allOptions());
        self::assertFalse($input->hasOption('abc'));
    }

    public function testShortOptionWithValue(): void
    {
        $input = new Input(['-level=5']);

        self::assertSame(5, $input->getOption('level'));
        self::assertSame('def', $input->getOption('nope', 'def'));
    }

    public function testSegmentsAreCollectedAndCast(): void
    {
        $input = new Input(['foo', '10', '--flag=x']);

        self::assertTrue($input->hasSegment(0));
        self::assertSame('foo', $input->getSegment(0));
        self::assertSame(10, $input->getSegment(1));
        self::assertFalse($input->hasSegment(5));
        self::assertSame('d', $input->getSegment(5, 'd'));
        self::assertSame(['foo', 10], $input->allSegment());
    }

    public function testBareDashTokenIsIgnored(): void
    {
        $input = new Input(['--', '-', 'real']);

        self::assertSame([], $input->allArguments());
        self::assertSame([], $input->allOptions());
        self::assertSame(['real'], $input->allSegment());
    }

    public function testImportArgumentsMerges(): void
    {
        $input = new Input(['--a=1']);
        $input->importArguments(['b' => 2], ['c' => 3]);

        self::assertSame(['a' => 1, 'b' => 2, 'c' => 3], $input->allArguments());
    }
}
