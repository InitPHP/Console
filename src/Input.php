<?php

/**
 * Input.php
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
 * Parses and exposes the raw command-line tokens that follow the command name.
 *
 * @see InputInterface for the semantics of arguments, options and segments.
 */
class Input implements InputInterface
{
    /**
     * The raw tokens this instance was constructed from.
     *
     * @var array<int, string>
     */
    protected $argv;

    /**
     * Parsed `--name(=value)` arguments.
     *
     * @var array<string, mixed>
     */
    protected $arguments = [];

    /**
     * Parsed `-name(=value)` options.
     *
     * @var array<string, mixed>
     */
    protected $options = [];

    /**
     * Parsed bare positional tokens.
     *
     * @var array<int, mixed>
     */
    protected $segments = [];

    /**
     * @param array<int, string> $argv Tokens that follow the command name.
     */
    public function __construct(array $argv)
    {
        $this->argv = $argv;
        $this->parse($argv);
    }

    /**
     * {@inheritDoc}
     */
    public function hasArgument(string $name): bool
    {
        return \array_key_exists($name, $this->arguments);
    }

    /**
     * {@inheritDoc}
     */
    public function getArgument(string $name, $default = null)
    {
        return \array_key_exists($name, $this->arguments) ? $this->arguments[$name] : $default;
    }

    /**
     * {@inheritDoc}
     */
    public function allArguments(): array
    {
        return $this->arguments;
    }

    /**
     * {@inheritDoc}
     */
    public function hasSegment(int $index): bool
    {
        return \array_key_exists($index, $this->segments);
    }

    /**
     * {@inheritDoc}
     */
    public function getSegment(int $index, $default = null)
    {
        return \array_key_exists($index, $this->segments) ? $this->segments[$index] : $default;
    }

    /**
     * {@inheritDoc}
     */
    public function allSegment(): array
    {
        return $this->segments;
    }

    /**
     * {@inheritDoc}
     */
    public function hasOption(string $name): bool
    {
        return \array_key_exists($name, $this->options);
    }

    /**
     * {@inheritDoc}
     */
    public function getOption(string $name, $default = null)
    {
        return \array_key_exists($name, $this->options) ? $this->options[$name] : $default;
    }

    /**
     * {@inheritDoc}
     */
    public function allOptions(): array
    {
        return $this->options;
    }

    /**
     * {@inheritDoc}
     */
    public function importArguments(array ...$arguments): void
    {
        $this->arguments = \array_merge($this->arguments, ...$arguments);
    }

    /**
     * Splits the raw tokens into arguments, options and segments.
     *
     * @param array<int, string> $argv
     */
    private function parse(array $argv): void
    {
        foreach ($argv as $segment) {
            if (!\is_string($segment)) {
                continue;
            }
            // A token made up solely of whitespace and/or dashes (e.g. "--") carries no data.
            if (\trim($segment, " \t\n\r\0\x0B-") === '') {
                continue;
            }

            if (\strpos($segment, '--') === 0) {
                $this->parseArgument(\ltrim($segment, '-'));
                continue;
            }

            if (\strpos($segment, '-') === 0) {
                $this->parseOption(\ltrim($segment, '-'));
                continue;
            }

            $this->segments[] = Helpers::strValueCast($segment);
        }
    }

    /**
     * Stores a single `--name` or `--name=value` argument.
     */
    private function parseArgument(string $token): void
    {
        if (\strpos($token, '=') !== false) {
            [$name, $value] = \explode('=', $token, 2);
            $this->arguments[$name] = Helpers::strValueCast($value);
            return;
        }
        $this->arguments[$token] = '';
    }

    /**
     * Stores a `-name=value` option, or one/more combined boolean short flags.
     *
     * `-f`   →  `['f' => 'f']`
     * `-abc` →  `['a' => 'a', 'b' => 'b', 'c' => 'c']`
     * `-k=v` →  `['k' => 'v']`
     */
    private function parseOption(string $token): void
    {
        if (\strpos($token, '=') !== false) {
            [$name, $value] = \explode('=', $token, 2);
            $this->options[$name] = Helpers::strValueCast($value);
            return;
        }

        $flags = \preg_split('//', $token, -1, \PREG_SPLIT_NO_EMPTY);
        foreach (($flags ?: []) as $flag) {
            $this->options[$flag] = $flag;
        }
    }
}
