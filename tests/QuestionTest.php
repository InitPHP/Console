<?php

declare(strict_types=1);

namespace Test\InitPHP\Console;

use InitPHP\Console\Question;
use PHPUnit\Framework\TestCase;

final class QuestionTest extends TestCase
{
    public function testFluentSettersReturnSelf(): void
    {
        $q = new Question();

        self::assertSame($q, $q->setQuestion('Q?'));
        self::assertSame($q, $q->optional());
        self::assertSame($q, $q->notOptional());
        self::assertSame($q, $q->setOptions(['a']));
        self::assertSame($q, $q->setDefault('x'));
        self::assertSame($q, $q->addOption('b'));
    }

    public function testQuestionTextDefaultsToEmpty(): void
    {
        self::assertSame('', (new Question())->getQuestion());
        self::assertSame('Ready?', (new Question())->setQuestion('Ready?')->getQuestion());
    }

    public function testDefaultBooleanOptionsMatchCastStrings(): void
    {
        $q = new Question();

        // Regression: the old loose in_array() matched every non-empty answer.
        self::assertTrue($q->hasOption('true'));
        self::assertTrue($q->hasOption('false'));
        self::assertTrue($q->hasOption('yes'));
        self::assertFalse($q->hasOption('maybe'));
    }

    public function testCustomStringOptions(): void
    {
        $q = (new Question())->setOptions(['red', 'green']);

        self::assertTrue($q->hasOption('red'));
        self::assertFalse($q->hasOption('blue'));
        self::assertSame(['red', 'green'], $q->getOptions());
    }

    public function testAddOptionSingleArgumentRegistersTheOption(): void
    {
        $q = (new Question())->setOptions([])->addOption('alpha');

        self::assertSame(['alpha'], $q->getOptions());
        self::assertTrue($q->hasOption('alpha'));
    }

    public function testAddOptionTwoArgumentLegacyFormRegistersTheValue(): void
    {
        $q = (new Question())->setOptions([])->addOption('Label', 'value');

        self::assertSame(['value'], $q->getOptions());
        self::assertTrue($q->hasOption('value'));
    }

    public function testDefaultHandling(): void
    {
        $q = new Question();
        self::assertFalse($q->hasDefault());
        self::assertSame(Question::NO_DEFAULT, $q->getDefault());

        $q->setDefault('home');
        self::assertTrue($q->hasDefault());
        self::assertSame('home', $q->getDefault());
    }

    public function testNullDefaultIsStillADefault(): void
    {
        $q = (new Question())->setDefault(null);

        self::assertTrue($q->hasDefault());
        self::assertNull($q->getDefault());
    }

    public function testOptionalState(): void
    {
        $q = new Question();
        self::assertFalse($q->isOptional());
        self::assertTrue($q->optional()->isOptional());
        self::assertFalse($q->notOptional()->isOptional());
    }
}
