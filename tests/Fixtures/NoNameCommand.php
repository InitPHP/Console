<?php

declare(strict_types=1);

namespace Test\InitPHP\Console\Fixtures;

use InitPHP\Console\Command;
use InitPHP\Console\Input;
use InitPHP\Console\Output;

/**
 * A command that (incorrectly) never declares its {@see Command::$command} name.
 */
final class NoNameCommand extends Command
{
    public function execute(Input $input, Output $output)
    {
    }
}
