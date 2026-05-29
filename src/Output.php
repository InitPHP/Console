<?php

/**
 * Output.php
 *
 * This file is part of InitPHP Console.
 *
 * @author     Muhammet ŞAFAK <info@muhammetsafak.com.tr>
 * @copyright  Copyright © 2022 Muhammet ŞAFAK
 * @license    https://github.com/InitPHP/Console/blob/main/LICENSE  MIT
 * @link       https://www.muhammetsafak.com.tr
 */

declare(strict_types=1);

namespace InitPHP\Console;

/**
 * Writes coloured/styled text to an output stream and reads interactive
 * answers from an input stream.
 *
 * Both streams default to the standard CLI handles but may be injected — for
 * example with `php://memory` handles — to make the component testable.
 */
class Output implements OutputInterface
{
    public const COLOR_DEFAULT      = 39;
    public const COLOR_BLACK        = 30;
    public const COLOR_RED          = 31;
    public const COLOR_GREEN        = 32;
    public const COLOR_YELLOW       = 33;
    public const COLOR_BLUE         = 34;
    public const COLOR_MAGENTA      = 35;
    public const COLOR_CYAN         = 36;
    public const COLOR_LIGHT_GRAY   = 37;
    public const COLOR_DARK_GRAY    = 90;
    public const COLOR_LIGHT_RED    = 91;
    public const COLOR_LIGHT_GREEN  = 92;
    public const COLOR_LIGHT_YELLOW = 93;
    public const COLOR_LIGHT_BLUE   = 94;
    public const COLOR_LIGHT_MAGENTA = 95;
    public const COLOR_LIGHT_CYAN   = 96;
    public const COLOR_WHITE        = 97;

    public const BACKGROUND_BLACK   = 40;
    public const BACKGROUND_RED     = 41;
    public const BACKGROUND_GREEN   = 42;
    public const BACKGROUND_YELLOW  = 43;
    public const BACKGROUND_BLUE    = 44;
    public const BACKGROUND_MAGENTA = 45;
    public const BACKGROUND_CYAN    = 46;

    public const ITALIC             = 3;
    public const BOLD               = 1;
    public const UNDERLINE          = 4;
    public const STRIKETHROUGH      = 9;

    /**
     * Stream that all output is written to.
     *
     * @var resource
     */
    protected $stdout;

    /**
     * Stream that interactive answers are read from.
     *
     * @var resource
     */
    protected $stdin;

    /**
     * @param resource|null $stdout Output stream; defaults to `STDOUT`.
     * @param resource|null $stdin  Input stream; defaults to `STDIN`.
     */
    public function __construct($stdout = null, $stdin = null)
    {
        $this->stdout = $stdout ?? \STDOUT;
        $this->stdin = $stdin ?? \STDIN;
    }

    /**
     * Renders a single-line, in-place updating progress bar.
     *
     * @param int|float $done  Work completed so far.
     * @param int|float $total Total work; must be greater than zero.
     * @throws \InvalidArgumentException When the inputs are not numeric or `$total <= 0`.
     */
    public function progressBar($done, $total): void
    {
        if (!\is_numeric($done) || !\is_numeric($total)) {
            throw new \InvalidArgumentException('$done and $total must be integer or float.');
        }
        if ($total <= 0) {
            throw new \InvalidArgumentException('$total must be greater than zero.');
        }

        $progress = (int)\floor(($done / $total) * 100);
        if ($progress < 0) {
            $progress = 0;
        } elseif ($progress > 100) {
            $progress = 100;
        }
        $remaining = 100 - $progress;

        \fwrite($this->stdout, \sprintf(
            "\033[0G\033[2K[%'=" . $progress . "s>%-" . $remaining . "s] - %d%%   %s/%s",
            '',
            '',
            $progress,
            $done,
            $total
        ));
    }

    /**
     * {@inheritDoc}
     */
    public function list(array $rows, int $leftSpace = 0): void
    {
        $space = 1;
        foreach ($rows as $key => $value) {
            if (!\is_string($key)) {
                continue;
            }
            $rowSpace = (int)\ceil(\strlen($key) / 8);
            if ($space < $rowSpace) {
                $space = $rowSpace;
            }
        }

        $output = '';
        $lineStart = $leftSpace > 0 ? \str_repeat("\t", $leftSpace) : '';
        foreach ($rows as $key => $value) {
            if ($value === \PHP_EOL) {
                $output .= $value;
                continue;
            }
            $output .= $lineStart
                . $key
                . \str_repeat("\t", $space)
                . ': '
                . $value
                . \PHP_EOL;
        }
        \fwrite($this->stdout, $output);
    }

    /**
     * {@inheritDoc}
     */
    public function ask(string $question, bool $empty = true)
    {
        $this->write($question, [], true);

        do {
            $input = Helpers::strValueCast(\trim((string)\fgets($this->stdin)));
            if (\in_array($input, ['exit', 'quit'], true)) {
                $this->terminate();
                return null;
            }
        } while ($empty === false && $input === '');

        return $input;
    }

    /**
     * {@inheritDoc}
     */
    public function question(Question $question)
    {
        $this->write($question->getQuestion(), [], true);

        do {
            $answer = \trim((string)\fgets($this->stdin));
            if ($question->hasOption($answer)) {
                return Helpers::strValueCast($answer);
            }
            if (\in_array($answer, ['exit', 'quit'], true)) {
                $this->terminate();
                return null;
            }
            if ($question->isOptional()) {
                return $question->hasDefault() ? $question->getDefault() : $answer;
            }
        } while (true);
    }

    /**
     * {@inheritDoc}
     */
    public function write(string $str, array $context = [], bool $newLine = false, array $format = [self::COLOR_DEFAULT]): void
    {
        if (!empty($context)) {
            $replace = [];
            foreach ($context as $key => $val) {
                if (!\is_array($val) && (!\is_object($val) || \method_exists($val, '__toString'))) {
                    $replace['{' . $key . '}'] = (string)$val;
                }
            }
            $str = \strtr($str, $replace);
        }

        $msg = "\e[" . \implode(';', $format) . "m" . $str . "\e[0m"
            . ($newLine ? \PHP_EOL : '');
        \fwrite($this->stdout, $msg);
    }

    /**
     * {@inheritDoc}
     */
    public function writeln(string $str, array $context = [], array $format = [self::COLOR_DEFAULT]): void
    {
        $this->write($str, $context, true, $format);
    }

    /**
     * {@inheritDoc}
     */
    public function error(string $msg, array $context = []): void
    {
        $this->writeln('[ERROR] ' . $msg, $context, [self::COLOR_WHITE, self::BOLD, self::BACKGROUND_RED]);
    }

    /**
     * {@inheritDoc}
     */
    public function success(string $msg, array $context = []): void
    {
        $this->writeln('[SUCCESS] ' . $msg, $context, [self::COLOR_WHITE, self::BACKGROUND_GREEN, self::BOLD]);
    }

    /**
     * {@inheritDoc}
     */
    public function warning(string $msg, array $context = []): void
    {
        $this->writeln('[WARNING] ' . $msg, $context, [self::COLOR_WHITE, self::BACKGROUND_YELLOW, self::BOLD]);
    }

    /**
     * {@inheritDoc}
     */
    public function info(string $msg, array $context = []): void
    {
        $this->writeln('[INFO] ' . $msg, $context, [self::COLOR_CYAN]);
    }

    /**
     * Terminates the current process.
     *
     * Extracted into a dedicated method so that interactive flows can be
     * exercised in tests by overriding it (e.g. to throw instead of exit).
     *
     * @codeCoverageIgnore
     */
    protected function terminate(int $code = 0): void
    {
        exit($code);
    }
}
