<?php

/**
 * InputInterface.php
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
 * Read access to the tokens passed to a command after the command name.
 *
 * The parser recognises three token shapes:
 *
 * - **Arguments** — long, `--name` or `--name=value` form.
 * - **Options**   — short, `-f`, `-abc` (combined) or `-key=value` form.
 * - **Segments**  — bare positional tokens that carry neither `-` nor `--`.
 *
 * @see Input The default implementation.
 */
interface InputInterface
{
    /**
     * Whether a `--name` argument was supplied.
     *
     * @param string $name Argument name without the leading dashes.
     */
    public function hasArgument(string $name): bool;

    /**
     * Returns the (type-cast) value of a `--name` argument.
     *
     * @param string $name    Argument name without the leading dashes.
     * @param mixed  $default Value returned when the argument is absent.
     * @return mixed
     */
    public function getArgument(string $name, $default = null);

    /**
     * All parsed arguments as a `name => value` map.
     *
     * @return array<string, mixed>
     */
    public function allArguments(): array;

    /**
     * Whether a positional segment exists at the given index.
     */
    public function hasSegment(int $index): bool;

    /**
     * Returns the (type-cast) positional segment at the given index.
     *
     * @param mixed $default Value returned when the index is absent.
     * @return mixed
     */
    public function getSegment(int $index, $default = null);

    /**
     * All parsed positional segments in order.
     *
     * @return array<int, mixed>
     */
    public function allSegment(): array;

    /**
     * Whether a `-name` option was supplied.
     *
     * @param string $name Option name without the leading dash.
     */
    public function hasOption(string $name): bool;

    /**
     * Returns the (type-cast) value of a `-name` option.
     *
     * @param string $name    Option name without the leading dash.
     * @param mixed  $default Value returned when the option is absent.
     * @return mixed
     */
    public function getOption(string $name, $default = null);

    /**
     * All parsed options as a `name => value` map.
     *
     * @return array<string, mixed>
     */
    public function allOptions(): array;

    /**
     * Merges one or more `name => value` maps into the parsed arguments.
     *
     * Later maps overwrite earlier keys.
     *
     * @param array<string, mixed> ...$arguments
     */
    public function importArguments(array ...$arguments): void;
}
