<?php

/**
 * Table.php
 *
 * This file is part of InitPHP Console.
 *
 * Originally distributed as the standalone `initphp/cli-table` package, merged
 * into `initphp/console` as of 2.1.
 *
 * @author     Muhammet ŞAFAK <info@muhammetsafak.com.tr>
 * @copyright  Copyright © 2022 Muhammet ŞAFAK
 * @license    https://github.com/InitPHP/Console/blob/main/LICENSE  MIT
 * @link       https://www.muhammetsafak.com.tr
 */

declare(strict_types=1);

namespace InitPHP\Console\Utils;

/**
 * A lightweight, styleable ASCII/ANSI table renderer.
 *
 * Rows are supplied as associative arrays; the union of their keys forms the
 * header. Columns are auto-sized, non-string cell values are stringified, and
 * styles are emitted as SGR escape sequences. Multibyte values are measured
 * with `mb_strlen()` when the extension is available.
 */
class Table
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
     * Placeholder used for missing cells.
     */
    private const NULL_PLACEHOLDER = '[NULL]';

    /**
     * SGR codes applied to the header row.
     *
     * @var array<int, int>
     */
    private $headerStyle = [
        self::BOLD,
    ];

    /**
     * SGR codes applied to every body cell.
     *
     * @var array<int, int>
     */
    private $cellStyle = [];

    /**
     * SGR codes applied to the border characters.
     *
     * @var array<int, int>
     */
    private $borderStyle = [];

    /**
     * Per-column body-cell SGR codes, keyed by column name.
     *
     * @var array<string, array<int, int>>
     */
    private $columnCellStyle = [];

    /**
     * The box-drawing characters used to build the frame.
     *
     * @var array<string, string>
     */
    private $chars = [
        'top'          => '═',
        'top-mid'      => '╤',
        'top-left'     => '╔',
        'top-right'    => '╗',
        'bottom'       => '═',
        'bottom-mid'   => '╧',
        'bottom-left'  => '╚',
        'bottom-right' => '╝',
        'left'         => '║',
        'left-mid'     => '╟',
        'mid'          => '─',
        'mid-mid'      => '┼',
        'right'        => '║',
        'right-mid'    => '╢',
        'middle'       => '│ ',
    ];

    /**
     * The accumulated rows.
     *
     * @var array<int, array<string, string>>
     */
    private $rows = [];

    /**
     * Convenience factory for fluent construction.
     */
    public static function create(): Table
    {
        return new self();
    }

    /**
     * Renders the table when the instance is used in a string context.
     */
    public function __toString(): string
    {
        return $this->getContent();
    }

    /**
     * Sets the SGR codes applied to the header row.
     *
     * @param int ...$format One or more `self::*` style constants.
     * @return $this
     */
    public function setHeaderStyle(int ...$format): self
    {
        $this->headerStyle = $format;

        return $this;
    }

    /**
     * Sets the SGR codes applied to every body cell.
     *
     * @param int ...$format One or more `self::*` style constants.
     * @return $this
     */
    public function setCellStyle(int ...$format): self
    {
        $this->cellStyle = $format;

        return $this;
    }

    /**
     * Sets the SGR codes applied to the border characters.
     *
     * @param int ...$format One or more `self::*` style constants.
     * @return $this
     */
    public function setBorderStyle(int ...$format): self
    {
        $this->borderStyle = $format;

        return $this;
    }

    /**
     * Sets the SGR codes applied to the body cells of a single column.
     *
     * @param string $column   Column name.
     * @param int    ...$format One or more `self::*` style constants.
     * @return $this
     */
    public function setColumnCellStyle(string $column, int ...$format): self
    {
        $this->columnCellStyle[$column] = $format;

        return $this;
    }

    /**
     * Appends a row.
     *
     * Keys become column names. Non-string values are stringified:
     * objects to their class name, and `null`/booleans/resources/callables/
     * arrays to bracketed placeholders.
     *
     * @param array<string, mixed> $assoc
     * @return $this
     */
    public function row(array $assoc): self
    {
        $row = [];
        foreach ($assoc as $key => $value) {
            $row[\trim((string)$key)] = \trim($this->stringify($value));
        }
        $this->rows[] = $row;

        return $this;
    }

    /**
     * Renders the table to a string.
     */
    public function getContent(): string
    {
        $headerData = [];
        $columnLengths = [];

        foreach ($this->rows as $row) {
            foreach (\array_keys($row) as $key) {
                if (isset($headerData[$key])) {
                    continue;
                }
                $headerData[$key] = $key;
                $columnLengths[$key] = $this->strlen($key);
            }
        }

        foreach ($this->rows as $row) {
            foreach ($headerData as $column) {
                $len = \max($columnLengths[$column], $this->strlen($row[$column] ?? self::NULL_PLACEHOLDER));
                if ($len % 2 !== 0) {
                    ++$len;
                }
                $columnLengths[$column] = $len;
            }
        }
        foreach ($columnLengths as &$length) {
            $length += 4;
        }
        unset($length);

        $res = $this->getTableTopContent($columnLengths)
            . $this->getFormattedRowContent($headerData, $columnLengths, "\e[" . \implode(';', $this->headerStyle) . "m", true)
            . $this->getTableSeparatorContent($columnLengths);
        foreach ($this->rows as $row) {
            foreach ($headerData as $column) {
                if (!isset($row[$column])) {
                    $row[$column] = self::NULL_PLACEHOLDER;
                }
            }
            $res .= $this->getFormattedRowContent($row, $columnLengths, "\e[" . \implode(';', $this->cellStyle) . "m");
        }

        return $res . $this->getTableBottomContent($columnLengths);
    }

    /**
     * Coerces a cell value into a printable string.
     *
     * @param mixed $value
     */
    private function stringify($value): string
    {
        if (\is_string($value)) {
            return $value;
        }
        if (\is_object($value)) {
            return \get_class($value);
        }
        if (\is_resource($value)) {
            return '[RESOURCE]';
        }
        if (\is_array($value)) {
            return \is_callable($value) ? '[CALLABLE]' : '[ARRAY]';
        }
        if (\is_callable($value)) {
            return '[CALLABLE]';
        }
        if ($value === null) {
            return self::NULL_PLACEHOLDER;
        }
        if (\is_bool($value)) {
            return $value === false ? '[FALSE]' : '[TRUE]';
        }

        return (string)$value;
    }

    /**
     * Builds a single (header or body) row line.
     *
     * @param array<string, string> $data
     * @param array<string, int>    $lengths
     */
    private function getFormattedRowContent(array $data, array $lengths, string $format = '', bool $isHeader = false): string
    {
        $res = $this->getChar('left') . ' ';
        $cells = [];
        foreach ($data as $key => $value) {
            $customFormat = '';
            $value = ' ' . $value;
            $len = $this->strlen($value) - $lengths[$key] + 1;
            if ($isHeader === false && !empty($this->columnCellStyle[$key])) {
                $customFormat = "\e[" . \implode(';', $this->columnCellStyle[$key]) . "m";
            }
            $cells[] = $format
                . $customFormat
                . $value
                . ($format !== '' || $customFormat !== '' ? "\e[0m" : '')
                . \str_repeat(' ', (int)\abs($len));
        }
        $res .= \implode($this->getChar('middle'), $cells);

        return $res . $this->getChar('right') . \PHP_EOL;
    }

    /**
     * @param array<string, int> $lengths
     */
    private function getTableTopContent(array $lengths): string
    {
        return $this->buildRule($lengths, 'top-left', 'top', 'top-mid', 'top-right');
    }

    /**
     * @param array<string, int> $lengths
     */
    private function getTableBottomContent(array $lengths): string
    {
        return $this->buildRule($lengths, 'bottom-left', 'bottom', 'bottom-mid', 'bottom-right');
    }

    /**
     * @param array<string, int> $lengths
     */
    private function getTableSeparatorContent(array $lengths): string
    {
        return $this->buildRule($lengths, 'left-mid', 'mid', 'mid-mid', 'right-mid');
    }

    /**
     * Builds a horizontal rule (top, separator or bottom) from the given chars.
     *
     * @param array<string, int> $lengths
     */
    private function buildRule(array $lengths, string $left, string $fill, string $mid, string $right): string
    {
        $cells = [];
        foreach ($lengths as $length) {
            $cells[] = $this->getChar($fill, $length);
        }

        return $this->getChar($left) . \implode($this->getChar($mid), $cells) . $this->getChar($right) . \PHP_EOL;
    }

    /**
     * Returns a border character (optionally repeated) wrapped in the border style.
     */
    private function getChar(string $char, int $len = 1): string
    {
        if (!isset($this->chars[$char])) {
            return '';
        }
        $res = empty($this->borderStyle) ? '' : "\e[" . \implode(';', $this->borderStyle) . "m";
        $res .= $len === 1 ? $this->chars[$char] : \str_repeat($this->chars[$char], $len);
        $res .= empty($this->borderStyle) ? '' : "\e[0m";

        return $res;
    }

    /**
     * Multibyte-aware string length, falling back to `strlen()` when needed.
     */
    private function strlen(string $str): int
    {
        if (!\function_exists('mb_strlen')) {
            return \strlen($str);
        }

        return \mb_strlen($str);
    }
}
