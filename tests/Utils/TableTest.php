<?php

declare(strict_types=1);

namespace Test\InitPHP\Console\Utils;

use InitPHP\Console\Utils\Table;
use PHPUnit\Framework\TestCase;

final class TableTest extends TestCase
{
    public function testCreateReturnsInstanceAndStringCastEqualsGetContent(): void
    {
        $table = Table::create()->row(['id' => 1, 'name' => 'Ada']);

        self::assertInstanceOf(Table::class, $table);
        self::assertSame($table->getContent(), (string)$table);
    }

    public function testFluentSettersReturnSelf(): void
    {
        $table = Table::create();

        self::assertSame($table, $table->setHeaderStyle(Table::BOLD));
        self::assertSame($table, $table->setCellStyle(Table::COLOR_GREEN));
        self::assertSame($table, $table->setBorderStyle(Table::COLOR_BLUE));
        self::assertSame($table, $table->setColumnCellStyle('id', Table::COLOR_RED));
        self::assertSame($table, $table->row(['id' => 1]));
    }

    public function testRendersHeadersValuesAndFrame(): void
    {
        $content = Table::create()
            ->row(['id' => 1, 'name' => 'Ada'])
            ->row(['id' => 2, 'name' => 'Bob'])
            ->getContent();

        self::assertStringContainsString('id', $content);
        self::assertStringContainsString('name', $content);
        self::assertStringContainsString('Ada', $content);
        self::assertStringContainsString('Bob', $content);
        // Frame corners.
        self::assertStringContainsString('╔', $content);
        self::assertStringContainsString('╝', $content);
    }

    public function testRaggedRowsBackfillMissingCells(): void
    {
        // Regression: differing column sets used to emit "undefined array key" warnings.
        $content = Table::create()
            ->row(['a' => 'x', 'b' => 'y'])
            ->row(['a' => 'z'])
            ->getContent();

        self::assertStringContainsString('[NULL]', $content);
    }

    public function testValueStringification(): void
    {
        $resource = \fopen('php://memory', 'r');
        $content = Table::create()
            ->row([
                'nullv' => null,
                'truev' => true,
                'falsev' => false,
                'arr' => [1, 2],
                'obj' => new \stdClass(),
                'res' => $resource,
            ])
            ->getContent();
        if (\is_resource($resource)) {
            \fclose($resource);
        }

        self::assertStringContainsString('[NULL]', $content);
        self::assertStringContainsString('[TRUE]', $content);
        self::assertStringContainsString('[FALSE]', $content);
        self::assertStringContainsString('[ARRAY]', $content);
        self::assertStringContainsString('[RESOURCE]', $content);
        self::assertStringContainsString('stdClass', $content);
    }

    public function testCallableArrayIsLabelled(): void
    {
        $content = Table::create()
            ->row(['cb' => [$this, 'testCallableArrayIsLabelled']])
            ->getContent();

        self::assertStringContainsString('[CALLABLE]', $content);
    }

    public function testStylesEmitEscapeSequences(): void
    {
        $content = Table::create()
            ->setBorderStyle(Table::COLOR_BLUE)
            ->setHeaderStyle(Table::COLOR_RED, Table::BOLD)
            ->setCellStyle(Table::COLOR_GREEN)
            ->row(['id' => 1])
            ->getContent();

        self::assertStringContainsString("\e[34m", $content); // border blue
        self::assertStringContainsString("\e[31;1m", $content); // header red+bold
        self::assertStringContainsString("\e[32m", $content); // cell green
    }

    public function testColumnCellStyleAppliesToBodyOnly(): void
    {
        $content = Table::create()
            ->setColumnCellStyle('id', Table::COLOR_YELLOW)
            ->row(['id' => 1, 'name' => 'Ada'])
            ->getContent();

        self::assertStringContainsString("\e[33m", $content);
    }

    public function testMultibyteValuesRenderWithoutError(): void
    {
        $content = Table::create()
            ->row(['ad' => 'Mühammet', 'şehir' => 'İstanbul'])
            ->getContent();

        self::assertStringContainsString('Mühammet', $content);
        self::assertStringContainsString('İstanbul', $content);
    }

    public function testLegacyClassAliasResolvesToTable(): void
    {
        self::assertTrue(\class_exists('InitPHP\\CLITable\\Table'));
        self::assertInstanceOf(Table::class, new \InitPHP\CLITable\Table());
    }
}
