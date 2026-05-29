<?php

declare(strict_types=1);

namespace Test\InitPHP\Console;

use InitPHP\Console\Helpers;
use PHPUnit\Framework\TestCase;

final class HelpersTest extends TestCase
{
    /**
     * @dataProvider castProvider
     * @param mixed $expected
     */
    public function testStrValueCast(string $input, $expected): void
    {
        self::assertSame($expected, Helpers::strValueCast($input));
    }

    /**
     * @return array<string, array{0: string, 1: mixed}>
     */
    public static function castProvider(): array
    {
        return [
            'empty string'        => ['', ''],
            'null literal'        => ['null', null],
            'NULL upper'          => ['NULL', null],
            'true'                => ['true', true],
            'yes'                 => ['yes', true],
            'false'               => ['false', false],
            'no'                  => ['no', false],
            'positive int'        => ['42', 42],
            'signed negative int' => ['-7', -7],
            'signed positive int' => ['+7', 7],
            'float dot'           => ['3.14', 3.14],
            'float comma'         => ['3,14', 3.14],
            'plain string'        => ['hello', 'hello'],
            // Regression: pipe must not be treated as a sign (old [-|+] class bug).
            'pipe is not numeric' => ['|123', '|123'],
            'pipe float guard'    => ['1|2', '1|2'],
            'double sign guard'   => ['--5', '--5'],
            'leading zero string' => ['007', 7],
        ];
    }
}
