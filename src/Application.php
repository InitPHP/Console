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

namespace InitPHP\Console;

class Application
{
    public const VERSION = '2.0';

    /** @var string */
    protected $application;

    /** @var string */
    protected $version;

    protected $terminal;

    /** @var array */
    protected $commands = [];

    protected $input;

    protected $output;

    protected $command;

    public function __construct(string $application = 'InitPHP Console Application', string $version = '1.1')
    {
        $this->application = $application;
        $this->version = $version;
    }

    /**
     * @param string $command
     * @param callable|null $execute
     * @param string $definition
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function register(string $command, callable $execute = null, string $definition = ''): self
    {
        if ($execute === null) {
            if (!\class_exists($command)) {
                throw new \InvalidArgumentException("The specified executable does not meet the requirements.");
            }
            $commandObj = new $command();
            if (!($commandObj instanceof Command)) {
                throw new \InvalidArgumentException("The specified executable does not meet the requirements.");
            }
            if (!isset($commandObj->command)) {
                throw new \Exception('The Command feature of the command class is undefined.');
            }
            $command = $commandObj->command;
            $execute = $commandObj;
            $definition = $commandObj->definition();
            $help = $commandObj->help();
            if (empty($help)) {
                $help = $definition;
            }
        } else {
            $help = $definition;
        }
        if (!\is_callable($execute) && !($execute instanceof Command)) {
            throw new \InvalidArgumentException("The specified executable does not meet the requirements.");
        }
        $this->commands[$command] = [
            'command'           => $command,
            'execute'           => $execute,
            'definition'        => $definition,
            'help'              => $help,
        ];

        return $this;
    }

    public function run(): bool
    {
        global $argv;

        $inputs = $argv;
        $this->terminal = $inputs[0];
        \array_shift($inputs);
        if (empty($inputs)) {
            return false;
        }

        $this->command = \current($inputs);
        \array_shift($inputs);

        $this->input = new Input($inputs);
        $this->output = new Output();

        if ($this->command === 'help') {
            $this->help($this->input, $this->output);
            return true;
        }

        if (isset($this->commands[$this->command])) {

            try {
                $command = $this->commands[$this->command];

                if ($this->input->hasArgument('help')) {
                    $this->commandHelp($this->input, $this->output, $command);
                    return true;
                }
                $execute = $command['execute'];

                if (\is_callable($execute)) {
                    \call_user_func_array($execute, [$this->input, $this->output]);
                    return true;
                }

                if ($execute instanceof Command) {
                    $arguments = $execute->arguments();
                    if (!empty($arguments)) {
                        foreach ($arguments as $argument) {
                            if ($argument instanceof InputArgument) {
                                if ($argument->run($this->input, $this->output) === FALSE) {
                                    return false;
                                }
                            } else {
                                $this->output->error("One or more arguments are wrong! Please check that you create arguments from the InputArgument object.");
                            }
                        }
                    }

                    $execute->execute($this->input, $this->output);
                    return true;
                }


                $this->output->error("The command is not executable; Please make sure the command is correctly recorded.");
                return false;
            } catch (\Throwable $e) {
                $this->output->error($e->getMessage());
                return false;
            }

        } else {
            $this->output->error("The command was not found.");
        }
        return true;
    }

    private function help(Input $input, Output $output)
    {
        $output->writeln('
    ____      _ __  ____  __  ______     ______                       __    ___    ____ 
   /  _/___  (_) /_/ __ \/ / / / __ \   / ____/___  ____  _________  / /__ |__ \  / __ \
   / // __ \/ / __/ /_/ / /_/ / /_/ /  / /   / __ \/ __ \/ ___/ __ \/ / _ \__/ / / / / /
 _/ // / / / / /_/ ____/ __  / ____/  / /___/ /_/ / / / (__  ) /_/ / /  __/ __/_/ /_/ / 
/___/_/ /_/_/\__/_/   /_/ /_/_/       \____/\____/_/ /_/____/\____/_/\___/____(_)____/  

        ');
        $output->writeln($this->application . ' v' . $this->version);

        $groups = [];
        $ungroup = [];
        foreach ($this->commands as $command) {
            if (\strpos($command['command'], ':') === FALSE) {
                $ungroup[$command['command']] = !empty($command['definition']) ? $command['definition'] : $command['help'];
                continue;
            }
            $split = \explode(':', $command['command'], 2);
            if (!isset($groups[$split[0]])) {
                $groups[$split[0]] = [];
            }
            $groups[$split[0]][$command['command']] = !empty($command['definition']) ? $command['definition'] : $command['help'];
        }

        $output->writeln('');
        $output->writeln('[COMMANDS]', [], [Output::COLOR_RED, Output::BOLD]);
        foreach ($groups as $group_name => $group) {
            $output->writeln('');
            $output->writeln($group_name, [], [Output::COLOR_MAGENTA]);
            $output->list($group, 1);
        }
        $output->writeln('');
        $output->list($ungroup);
        $output->writeln('');

        $output->writeln('Copyright © 2022 - ' . date("Y") . ' InitPHP Console ' . self::VERSION);
        $output->writeln('http://initphp.org');
    }

    private function commandHelp(Input $input, Output $output, array $command)
    {
        $output->writeln(\PHP_EOL . ($command['help'] ?? $command['definition']));
        $exe = $command['execute'];
        if ($exe instanceof Command) {
            $usageArguments = ['optional' => [], 'required' => []];
            if ($arguments = $exe->arguments()) {
                $rows = [];
                foreach ($arguments as $argument) {
                    if (!($argument instanceof InputArgument)) {
                        $output->error("One or more arguments are wrong! Please check that you create arguments from the InputArgument object.");
                        exit;
                    }
                    $rows[$argument->getName()] = $argument->getDefinition();
                    if ($argument->isOptional()) {
                        $usageArguments['optional'][] = '[' . \trim($argument->getName() . '=(' . $argument->getType() . ')' . $argument->getDefault()) . ']';
                    } else {
                        $usageArguments['required'][] = \trim($argument->getName() . '=(' . $argument->getType() . ')'.$argument->getDefault());
                    }
                }
                $output->writeln(\PHP_EOL . "[PARAMETERS]");
                $output->list($rows, 1);
            }
            $usage = "[USAGE]" . \PHP_EOL . "\t";
            $usage .= 'php '
                . $this->terminal
                . ' '
                . $exe->command
                . ' '
                . \implode(' ', $usageArguments['required'])
                . ' '
                . \implode(' ', $usageArguments['optional']);
            $output->writeln(\trim($usage));
        }
    }

}
