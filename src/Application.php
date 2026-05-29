<?php

/**
 * Application.php
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
 * The console application: a registry of commands and the dispatcher that maps
 * a command-line invocation onto the matching command.
 *
 * Commands may be registered either as closures or as classes extending
 * {@see Command}. The built-in `help`/`list` command renders an overview, and
 * `<command> --help` renders per-command usage.
 */
class Application
{
    public const VERSION = '2.1.0';

    /**
     * Human-readable application name shown in help output.
     *
     * @var string
     */
    protected $application;

    /**
     * Application version shown in help output.
     *
     * @var string
     */
    protected $version;

    /**
     * The script name (`$argv[0]`) captured during {@see run()}.
     *
     * @var string
     */
    protected $terminal;

    /**
     * Registered commands keyed by command name.
     *
     * @var array<string, array{command: string, execute: callable|Command, definition: string, help: string}>
     */
    protected $commands = [];

    /**
     * @var InputInterface|null
     */
    protected $input;

    /**
     * The output implementation.
     *
     * Typed as the concrete {@see Output} because {@see Command::execute()} and
     * {@see InputArgument::run()} require it; {@see Output} implements
     * {@see OutputInterface} so consumers may still program to the interface.
     *
     * @var Output|null
     */
    protected $output;

    /**
     * The command name resolved during {@see run()}.
     *
     * @var string|null
     */
    protected $command;

    /**
     * @param string      $application Human-readable application name.
     * @param string      $version     Application version string.
     * @param Output|null $output      Output implementation; a default {@see Output} is created when null.
     */
    public function __construct(string $application = 'InitPHP Console Application', string $version = self::VERSION, Output $output = null)
    {
        $this->application = $application;
        $this->version = $version;
        $this->output = $output;
    }

    /**
     * Registers a command.
     *
     * When `$execute` is null, `$command` is treated as the fully-qualified
     * name of a {@see Command} subclass which is instantiated; its
     * {@see Command::$command}, {@see Command::definition()} and
     * {@see Command::help()} are read from the instance.
     *
     * @param string        $command    Command name, or a {@see Command} class name when `$execute` is null.
     * @param callable|null $execute    Handler invoked with `(Input, Output)`.
     * @param string        $definition Short description for the command listing.
     * @return $this
     * @throws \InvalidArgumentException When the handler does not meet the requirements.
     * @throws \LogicException When a {@see Command} class does not declare its command name.
     */
    public function register(string $command, ?callable $execute = null, string $definition = ''): self
    {
        if ($execute === null) {
            if (!\class_exists($command)) {
                throw new \InvalidArgumentException('The specified executable does not meet the requirements.');
            }
            $commandObj = new $command();
            if (!($commandObj instanceof Command)) {
                throw new \InvalidArgumentException('The specified executable does not meet the requirements.');
            }
            if (empty($commandObj->command)) {
                throw new \LogicException('The command name of the command class is undefined.');
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
        $this->commands[$command] = [
            'command'    => $command,
            'execute'    => $execute,
            'definition' => $definition,
            'help'       => $help,
        ];

        return $this;
    }

    /**
     * Parses the command line and dispatches the matching command.
     *
     * @param array<int, string>|null $argv Tokens to dispatch; defaults to the global `$argv`.
     * @return bool `true` when a command (or help) ran, `false` when nothing matched or no command was given.
     */
    public function run(array $argv = null): bool
    {
        if ($argv === null) {
            $argv = \is_array($GLOBALS['argv'] ?? null) ? $GLOBALS['argv'] : [];
        }

        $this->terminal = (string)($argv[0] ?? '');
        \array_shift($argv);
        if (empty($argv)) {
            return false;
        }

        $this->command = (string)\current($argv);
        \array_shift($argv);

        $this->input = new Input($argv);
        $output = $this->output ?? ($this->output = new Output());

        if (\in_array($this->command, ['help', 'list'], true)) {
            $this->help($this->input, $output);
            return true;
        }

        if (!isset($this->commands[$this->command])) {
            $output->error('The command was not found.');
            return true;
        }

        try {
            $command = $this->commands[$this->command];

            if ($this->input->hasArgument('help')) {
                $this->commandHelp($this->input, $output, $command);
                return true;
            }
            $execute = $command['execute'];

            if ($execute instanceof Command) {
                foreach ($execute->arguments() as $argument) {
                    if (!($argument instanceof InputArgument)) {
                        $output->error('One or more arguments are wrong! Please check that you create arguments from the InputArgument object.');
                        return false;
                    }
                    if ($argument->run($this->input, $output) === false) {
                        return false;
                    }
                }

                $execute->execute($this->input, $output);
                return true;
            }

            if (\is_callable($execute)) {
                \call_user_func_array($execute, [$this->input, $output]);
                return true;
            }

            $output->error('The command is not executable; Please make sure the command is correctly recorded.');
            return false;
        } catch (\Throwable $e) {
            $output->error($e->getMessage());
            return false;
        }
    }

    /**
     * Renders the application banner and the grouped command listing.
     */
    private function help(InputInterface $input, OutputInterface $output): void
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
            $description = !empty($command['definition']) ? $command['definition'] : $command['help'];
            if (\strpos($command['command'], ':') === false) {
                $ungroup[$command['command']] = $description;
                continue;
            }
            $split = \explode(':', $command['command'], 2);
            if (!isset($groups[$split[0]])) {
                $groups[$split[0]] = [];
            }
            $groups[$split[0]][$command['command']] = $description;
        }

        $output->writeln('');
        $output->writeln('[COMMANDS]', [], [Output::COLOR_RED, Output::BOLD]);
        foreach ($groups as $groupName => $group) {
            $output->writeln('');
            $output->writeln($groupName, [], [Output::COLOR_MAGENTA]);
            $output->list($group, 1);
        }
        $output->writeln('');
        $output->list($ungroup);
        $output->writeln('');

        $output->writeln('Copyright © 2022 - ' . \date('Y') . ' InitPHP Console ' . self::VERSION);
        $output->writeln('https://initphp.github.io - https://github.com/InitPHP/Console');
    }

    /**
     * Renders the usage and parameter list for a single command.
     *
     * @param array{command: string, execute: callable|Command, definition: string, help: string} $command
     */
    private function commandHelp(InputInterface $input, OutputInterface $output, array $command): void
    {
        $output->writeln(\PHP_EOL . ($command['help'] !== '' ? $command['help'] : $command['definition']));
        $exe = $command['execute'];
        if (!($exe instanceof Command)) {
            return;
        }

        $usageArguments = ['optional' => [], 'required' => []];
        $arguments = $exe->arguments();
        if (!empty($arguments)) {
            $rows = [];
            foreach ($arguments as $argument) {
                if (!($argument instanceof InputArgument)) {
                    $output->error('One or more arguments are wrong! Please check that you create arguments from the InputArgument object.');
                    return;
                }
                $rows[$argument->getName()] = $argument->getDefinition();
                $usage = \trim($argument->getName() . '=(' . $argument->getType() . ')' . $argument->getDefault());
                if ($argument->isOptional()) {
                    $usageArguments['optional'][] = '[' . $usage . ']';
                } else {
                    $usageArguments['required'][] = $usage;
                }
            }
            $output->writeln(\PHP_EOL . '[PARAMETERS]');
            $output->list($rows, 1);
        }

        $usage = '[USAGE]' . \PHP_EOL . "\t"
            . 'php '
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
