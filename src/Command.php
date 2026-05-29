<?php

/**
 * Command.php
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
 * Base class for class-based commands.
 *
 * Subclasses must set {@see $command} to the name the command is invoked by
 * and implement {@see execute()}. The remaining hooks ({@see help()},
 * {@see definition()}, {@see arguments()}) are optional and may be overridden
 * to enrich the generated help output and to declare typed arguments.
 */
abstract class Command
{
    /**
     * The name the command is registered and invoked under (e.g. `make:model`).
     *
     * @var string
     */
    public $command;

    /**
     * Runs the command.
     *
     * Invoked by {@see Application::run()} after every declared
     * {@see arguments()} entry has been validated.
     *
     * @return mixed
     */
    abstract public function execute(Input $input, Output $output);

    /**
     * Long help text shown by `<command> --help`.
     */
    public function help(): string
    {
        return 'No explanation for this command is specified.';
    }

    /**
     * Short one-line description shown in the command listing.
     */
    public function definition(): string
    {
        return '';
    }

    /**
     * The typed arguments this command accepts.
     *
     * @return array<int, InputArgument>
     */
    public function arguments(): array
    {
        return [];
    }
}
