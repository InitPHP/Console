<?php

declare(strict_types=1);

namespace Test\InitPHP\Console\Fixtures;

use InitPHP\Console\Command;
use InitPHP\Console\Input;
use InitPHP\Console\Output;

/**
 * A command whose {@see arguments()} contains an entry that is not an
 * {@see \InitPHP\Console\InputArgument}.
 */
final class BadArgsCommand extends Command
{
    /** @var string */
    public $command = 'app:bad';

    public function arguments(): array
    {
        return ['not-an-input-argument'];
    }

    public function execute(Input $input, Output $output)
    {
        $output->writeln('EXECUTED');
    }
}
