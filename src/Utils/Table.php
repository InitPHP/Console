<?php
/**
 * Application.php
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
namespace InitPHP\Console\Utils;

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

    private $headerStyle = [
        self::BOLD,
    ];

    private $cellStyle = [];

    private $borderStyle = [];

    private $columnCellStyle = [];

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

    /** @var array */
    private $rows = [];

    public function __construct()
    {
    }

    public function __toString()
    {
        return $this->getContent();
    }

    public static function create(): Table
    {
        return new self();
    }

    public function setHeaderStyle(int ...$format): self
    {
        $styles = [];
        foreach ($format as $style) {
            $styles[] = $style;
        }
        $this->headerStyle = $styles;
        return $this;
    }

    public function setCellStyle(int ...$format): self
    {
        $styles = [];
        foreach ($format as $style) {
            $styles[] = $style;
        }
        $this->cellStyle = $styles;
        return $this;
    }

    public function setBorderStyle(int ...$format): self
    {
        $styles = [];
        foreach ($format as $style) {
            $styles[] = $style;
        }
        $this->borderStyle = $styles;
        return $this;
    }

    public function setColumnCellStyle(string $column, int ...$format): self
    {
        $styles = [];
        foreach ($format as $style) {
            $styles[] = $style;
        }
        $this->columnCellStyle[$column] = $styles;
        return $this;
    }

    public function row(array $assoc): self
    {
        $row = [];
        foreach ($assoc as $key => $value) {
            if(!\is_string($value)){
                if(\is_object($value)){
                    $value = \get_class($value);
                }elseif (\is_resource($value)) {
                    $value = '[RESOURCE]';
                }elseif (\is_callable($value)){
                    $value = '[CALLABLE]';
                }elseif(\is_null($value)){
                    $value = '[NULL]';
                }elseif(\is_bool($value)){
                    $value = $value === FALSE ? '[FALSE]' : '[TRUE]';
                }else{
                    $value = (string)$value;
                }
            }
            $key = \trim((string)$key);
            $row[$key] = \trim($value);
        }
        $this->rows[] = $row;
        return $this;
    }

    public function getContent(): string
    {
        $columnLengths = [];
        $headerData = [];

        foreach ($this->rows as $row) {
            $keys = \array_keys($row);
            foreach ($keys as $key) {
                if(isset($headerData[$key])){
                    continue;
                }
                $headerData[$key] = $key;
                $columnLengths[$key] = $this->strlen($key);
            }
        }

        foreach ($this->rows as $row) {
            foreach ($headerData as $column) {
                $len = \max($columnLengths[$column], $this->strlen($row[$column]));
                if($len % 2 !== 0){
                    ++$len;
                }
                $columnLengths[$column] = $len;
            }
        }
        foreach ($columnLengths as &$length) {
            $length += 4;
        }

        $res = $this->getTableTopContent($columnLengths)
            . $this->getFormattedRowContent($headerData, $columnLengths, "\e[" . \implode(';', $this->headerStyle) . "m", true)
            . $this->getTableSeparatorContent($columnLengths);
        foreach ($this->rows as $row) {
            foreach ($headerData as $column) {
                if(!isset($row[$column])){
                    $row[$column] = '[NULL]';
                }
            }
            $res .= $this->getFormattedRowContent($row, $columnLengths, "\e[" . \implode(';', $this->cellStyle) . "m");
        }
        return $res . $this->getTableBottomContent($columnLengths);
    }

    private function getFormattedRowContent($data, $lengths, string $format = '', bool $isHeader = false): string
    {
        $res = $this->getChar('left') . ' ';
        $rows = [];
        foreach ($data as $key => $value) {
            $customFormat = '';
            $value = ' ' . $value;
            $len = $this->strlen($value) - $lengths[$key] + 1;
            if($isHeader === FALSE && isset($this->columnCellStyle[$key]) && !empty($this->columnCellStyle[$key])){
                $customFormat = "\e[" . \implode(";", $this->columnCellStyle[$key]) . "m";
            }
            $rows[] = ($format !== '' ? $format : '')
                . ($customFormat !== '' ? $customFormat : '')
                . $value
                . ($format !== '' || $customFormat !== '' ? "\e[0m" : '')
                . \str_repeat(' ', (int)\abs($len));
        }
        $res .= \implode($this->getChar('middle'), $rows);
        return $res . $this->getChar('right') . \PHP_EOL;
    }

    private function getTableTopContent($lengths): string
    {
        $res = $this->getChar('top-left');
        $rows = [];
        foreach ($lengths as $length) {
            $rows[] = $this->getChar('top', $length);
        }
        $res .= \implode($this->getChar('top-mid'), $rows);
        return  $res . $this->getChar('top-right') . \PHP_EOL;
    }

    private function getTableBottomContent($lengths): string
    {
        $res = $this->getChar('bottom-left');
        $rows = [];
        foreach ($lengths as $length) {
            $rows[] = $this->getChar('bottom', $length);
        }
        $res .= \implode($this->getChar('bottom-mid'), $rows);
        return $res . $this->getChar('bottom-right') . \PHP_EOL;
    }

    private function getTableSeparatorContent($lengths): string
    {
        $res = $this->getChar('left-mid');
        $rows = [];
        foreach ($lengths as $length) {
            $rows[] = $this->getChar('mid', $length);
        }
        $res .= \implode($this->getChar('mid-mid'), $rows);
        return $res . $this->getChar('right-mid') . \PHP_EOL;
    }

    private function getChar(string $char, int $len = 1): string
    {
        if(!isset($this->chars[$char])){
            return '';
        }
        $res = (empty($this->borderStyle) ? '' : "\e[" . \implode(";", $this->borderStyle) . "m");
        if($len === 1){
            $res .= $this->chars[$char];;
        }else{
            $res .= \str_repeat($this->chars[$char], $len);
        }
        $res .= empty($this->borderStyle) ? '' : "\e[0m";
        return $res;
    }

    private function strlen(string $str): int
    {
        if(!\function_exists('mb_strlen')){
            return \strlen($str);
        }
        return \mb_strlen($str);
    }

}
