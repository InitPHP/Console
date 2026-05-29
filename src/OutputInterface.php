<?php

/**
 * OutputInterface.php
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
 * Writes formatted text to the console and reads interactive answers back.
 *
 * Formatting is expressed with the SGR (Select Graphic Rendition) integer
 * codes exposed as constants on {@see Output} (colours, backgrounds and text
 * styles such as {@see Output::BOLD}).
 *
 * @see Output The default implementation.
 */
interface OutputInterface
{
    /**
     * Writes a string, optionally interpolating `{placeholder}` tokens and
     * wrapping the result in SGR escape codes.
     *
     * @param string               $str     Text, possibly containing `{key}` placeholders.
     * @param array<string, mixed> $context Replacement map for the placeholders.
     * @param bool                 $newLine Whether to append a trailing newline.
     * @param array<int, int>      $format  SGR codes applied to the whole string.
     */
    public function write(string $str, array $context = [], bool $newLine = false, array $format = []): void;

    /**
     * Same as {@see write()} but always appends a trailing newline.
     *
     * @param array<string, mixed> $context
     * @param array<int, int>      $format
     */
    public function writeln(string $str, array $context = [], array $format = []): void;

    /**
     * Writes a key/value list, one `key : value` pair per line.
     *
     * @param array<string, mixed> $rows      The pairs to render.
     * @param int                  $leftSpace Number of leading tab stops.
     */
    public function list(array $rows, int $leftSpace = 0): void;

    /**
     * Renders a single-line, in-place updating progress bar.
     *
     * @param int|float $done  Work completed so far.
     * @param int|float $total Total work; must be greater than zero.
     */
    public function progressBar($done, $total): void;

    /**
     * Prints a question and blocks until the user submits a line.
     *
     * @param string $question The prompt to display.
     * @param bool   $empty    When false, an empty answer is rejected and re-asked.
     * @return mixed The type-cast answer.
     */
    public function ask(string $question, bool $empty = true);

    /**
     * Prints a {@see Question} and resolves the user's answer against it.
     *
     * @return mixed The accepted answer or the question's default.
     */
    public function question(Question $question);

    /**
     * Writes an error message styled as such.
     *
     * @param array<string, mixed> $context
     */
    public function error(string $msg, array $context = []): void;

    /**
     * Writes a success message styled as such.
     *
     * @param array<string, mixed> $context
     */
    public function success(string $msg, array $context = []): void;

    /**
     * Writes a warning message styled as such.
     *
     * @param array<string, mixed> $context
     */
    public function warning(string $msg, array $context = []): void;

    /**
     * Writes an informational message styled as such.
     *
     * @param array<string, mixed> $context
     */
    public function info(string $msg, array $context = []): void;
}
