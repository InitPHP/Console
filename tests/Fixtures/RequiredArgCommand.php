<?php

declare(strict_types=1);

namespace Test\InitPHP\Console\Fixtures;

use InitPHP\Console\Command;
use InitPHP\Console\Input;
use InitPHP\Console\InputArgument;
use InitPHP\Console\Output;

final class RequiredArgCommand extends Command
{
    /** @var string */
    public $command = 'app:req';

    public function arguments(): array
    {
        return [
            new InputArgument('id', InputArgument::INT, 0, false, 'Required id.'),
        ];
    }

    public function execute(Input $input, Output $output)
    {
        $output->writeln('EXECUTED id={id}', ['id' => $input->getArgument('id')]);
    }
}
