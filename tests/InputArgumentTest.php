<?php

declare(strict_types=1);

namespace Test\InitPHP\Console;

use InitPHP\Console\Input;
use InitPHP\Console\InputArgument;
use PHPUnit\Framework\TestCase;
use Test\InitPHP\Console\Support\MemoryOutput;

final class InputArgumentTest extends TestCase
{
    public function testConstructorRejectsUnsupportedType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new InputArgument('x', 'NOPE', null);
    }

    public function testConstructorRejectsDefaultNotMatchingType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new InputArgument('age', InputArgument::INT, 'not-an-int');
    }

    public function testGetters(): void
    {
        $arg = new InputArgument('age', InputArgument::INT, 18, false, 'The age.');

        self::assertSame('--age', $arg->getName());
        self::assertSame(InputArgument::INT, $arg->getType());
        self::assertSame('The age.', $arg->getDefinition());
        self::assertSame('18', $arg->getDefault());
        self::assertFalse($arg->isOptional());
    }

    public function testOptionalMissingArgumentUsesDefault(): void
    {
        $arg = new InputArgument('name', InputArgument::STR, 'guest');
        $input = new Input([]);

        self::assertTrue($arg->run($input, new MemoryOutput()));
        self::assertSame('guest', $input->getArgument('name'));
    }

    public function testRequiredMissingArgumentFailsWithError(): void
    {
        $arg = new InputArgument('name', InputArgument::STR, 'guest', false);
        $input = new Input([]);
        $output = new MemoryOutput();

        self::assertFalse($arg->run($input, $output));
        self::assertStringContainsString('--name', $output->plain());
    }

    public function testValidValueIsAccepted(): void
    {
        $arg = new InputArgument('age', InputArgument::INT, 0);
        $input = new Input(['--age=42']);

        self::assertTrue($arg->run($input, new MemoryOutput()));
        self::assertSame(42, $input->getArgument('age'));
    }

    public function testInvalidOptionalValueFallsBackToDefault(): void
    {
        $arg = new InputArgument('age', InputArgument::INT, 7);
        $input = new Input(['--age=abc']);

        self::assertTrue($arg->run($input, new MemoryOutput()));
        self::assertSame(7, $input->getArgument('age'));
    }

    public function testZeroValueForRequiredArgumentIsPreserved(): void
    {
        // Regression: the old empty() check replaced legitimate 0 with the default.
        $arg = new InputArgument('count', InputArgument::INT, 99, false);
        $input = new Input(['--count=0']);

        self::assertTrue($arg->run($input, new MemoryOutput()));
        self::assertSame(0, $input->getArgument('count'));
    }

    public function testBoolCoercionFromOneZero(): void
    {
        $arg = new InputArgument('flag', InputArgument::BOOL, false);
        $input = new Input(['--flag=1']);

        self::assertTrue($arg->run($input, new MemoryOutput()));
        self::assertTrue($input->getArgument('flag'));
    }

    public function testValueCanComeFromShortOption(): void
    {
        $arg = new InputArgument('n', InputArgument::NUMERIC, 0);
        $input = new Input(['-n=12']);

        self::assertTrue($arg->run($input, new MemoryOutput()));
        self::assertSame(12, $input->getArgument('n'));
    }
}
