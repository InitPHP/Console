<?php

declare(strict_types=1);

namespace Test\InitPHP\Console\Fixtures;

use InitPHP\Console\Command;
use InitPHP\Console\Input;
use InitPHP\Console\InputArgument;
use InitPHP\Console\Output;

final class GreetCommand extends Command
{
    /** @var string */
    public $command = 'app:greet';

    public function definition(): string
    {
        return 'Greets someone.';
    }

    public function help(): string
    {
        return 'Prints a greeting to the given name.';
    }

    public function arguments(): array
    {
        return [
            new InputArgument('name', InputArgument::STR, 'World', true, 'Who to greet.'),
        ];
    }

    public function execute(Input $input, Output $output)
    {
        $output->writeln('Hi {name}', ['name' => $input->getArgument('name')]);
    }
}
