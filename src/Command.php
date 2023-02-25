<?php
/**
 * Command.php
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

abstract class Command
{

    /** @var string */
    public $command;

    abstract public function execute(Input $input, Output $output);

    public function help(): string
    {
        return 'No explanation for this command is specified.';
    }

    public function definition(): string
    {
        return '';
    }

    public function arguments(): array
    {
        return [];
    }

}
