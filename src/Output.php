<?php
/**
 * Output.php
 *
 * This file is part of Console.
 *
 * @author     Muhammet ŞAFAK <info@muhammetsafak.com.tr>
 * @copyright  Copyright © 2022 Muhammet ŞAFAK
 * @license    ./LICENSE  MIT
 * @version    2.0
 * @link       https://www.muhammetsafak.com.tr
 */

declare(strict_types=1);

namespace InitPHP\Console;

class Output
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
    public const COLOR_LIGHT_MAGENTA= 95;
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

    function progressBar($done, $total) {

        if (!\is_numeric($done) || !\is_numeric($total)) {
            throw new \InvalidArgumentException('\$done and \$total can be integer or float.');
        }

        $progress = \floor(($done / $total) * 100);
        $done_progress = 100 - $progress;

        \fwrite(\STDOUT, \sprintf("\033[0G\033[2K[%'=" . $progress . "s>%-" . $done_progress . "s] - " .$progress ."%%   ". $done . "/" . $total, "", ""));
    }

    public function list(array $rows, int $leftSpace = 0)
    {
        $space = 1;

        foreach ($rows as $key => $value) {
            if (!\is_string($key)) {
                continue;
            }
            $rowSpace = (int)\ceil(strlen($key) / 8);
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
            $line = $lineStart
                . $key
                . \str_repeat("\t", $space)
                . ': '
                . $value;
            $output .= $line . \PHP_EOL;
        }
        \fwrite(\STDOUT, $output);
    }

    public function ask(string $question, bool $empty = true)
    {
        $this->write($question, [], true);

        do {
            $input = Helpers::strValueCast(\trim(\fgets(\STDIN)));
            if (\in_array($input, ['exit', 'quit'])) {
                exit;
            }
        } while ($empty === FALSE && $input == '');

        return $input;
    }

    public function question(Question $question)
    {
        $this->write($question->getQuestion(), [], true);

        do {
            $answer = \trim(\fgets(\STDIN));
            if ($question->hasOption($answer)) {
                return Helpers::strValueCast($answer);
            }
            if (\in_array($answer, ['exit', 'quit'])) {
                exit;
            }
            if ($question->isOptional()) {
                $default = $question->getDefault();
                if ($default !== '__Qu€sti0nN0D€f@ultV@lue__') {
                    return $default;
                } else {
                    return $answer;
                }
            }
        } while (true);
    }

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
            . ($newLine !== FALSE ? \PHP_EOL : '');
        \fwrite(\STDOUT, $msg);
    }

    public function writeln(string $str, array $context = [], array $format = [self::COLOR_DEFAULT]): void
    {
        $this->write($str, $context, true, $format);
    }

    public function error(string $msg, array $context = array()): void
    {
        $this->writeln('[ERROR] ' . $msg, $context, [self::COLOR_WHITE, self::BOLD, self::BACKGROUND_RED]);
    }

    public function success(string $msg, array $context = array()): void
    {
        $this->writeln('[SUCCESS] ' . $msg, $context, [self::COLOR_WHITE, self::BACKGROUND_GREEN, self::BOLD]);
    }

    public function warning(string $msg, array $context = array()): void
    {
        $this->writeln('[WARNING] ' . $msg, $context, [self::COLOR_WHITE, self::BACKGROUND_YELLOW, self::BOLD]);
    }

    public function info(string $msg, array $context = array()): void
    {
        $this->writeln('[INFO] ' . $msg, $context, [self::COLOR_CYAN]);
    }

}
