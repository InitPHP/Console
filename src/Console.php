<?php
/**
 * Console.php
 *
 * This file is part of Console.
 *
 * @author     Muhammet ŞAFAK <info@muhammetsafak.com.tr>
 * @copyright  Copyright © 2022 Muhammet ŞAFAK
 * @license    ./LICENSE  MIT
 * @version    1.0
 * @link       https://www.muhammetsafak.com.tr
 */

declare(strict_types=1);

namespace InitPHP\Console;

use function trim;
use function strlen;
use function str_repeat;
use function implode;
use function strtolower;
use function call_user_func_array;
use function array_search;
use function in_array;
use function fopen;
use function fgets;
use function fclose;
use function array_shift;
use function count;
use function substr;
use function ltrim;
use function strpos;
use function explode;
use function is_array;
use function is_object;
use function method_exists;
use function strtr;

final class Console
{

    public const COLOR_DEFAULT      = 39;
    public const COLOR_BLACK        = 30;
    public const COLOR_RED          = 31;
    public const COLOR_GREEN        = 32;
    public const COLOR_YELLOW       = 33;
    public const COLOR_BLUE         = 34;
    public const COLOR_MAGENTA      = 35;
    public const COLOR_CYAN         = 36;
    public const COLOR_LIGHT_GRAY   = 37;
    public const COLOR_DARK_GRAY    = 90;
    public const COLOR_LIGHT_RED    = 91;
    public const COLOR_LIGHT_GREEN  = 92;
    public const COLOR_LIGHT_YELLOW = 93;
    public const COLOR_LIGHT_BLUE   = 94;
    public const COLOR_LIGHT_MAGENTA= 95;
    public const COLOR_LIGHT_CYAN   = 96;
    public const COLOR_WHITE        = 97;

    public const BACKGROUND_BLACK   = 40;
    public const BACKGROUND_RED     = 41;
    public const BACKGROUND_GREEN   = 42;
    public const BACKGROUND_YELLOW  = 43;
    public const BACKGROUND_BLUE    = 44;
    public const BACKGROUND_MAGENTA = 45;
    public const BACKGROUND_CYAN    = 46;

    public const ITALIC             = 3;
    public const BOLD               = 1;
    public const UNDERLINE          = 4;
    public const STRIKETHROUGH      = 9;


    /** @var null|string */
    protected $command = null;

    /** @var array */
    protected $commands = [];

    /** @var array */
    protected $flags = [];

    /** @var array */
    protected $segments = [];

    /** @var array */
    protected $helps = [];

    public function __construct()
    {
        $this->setUp();

        $this->register('help', function (Console $console) {
            $outputs = []; $max_command_len = 0;
            unset($console->helps['help']);
            foreach ($console->helps as $key => $value) {
                if(empty($value)){
                    $value = "\e[3mNo description has been written for this command.\e[0m";
                }
                $outputs[$key] = trim($value);
                $len = strlen($key);
                if($max_command_len < $len){
                    $max_command_len = $len;
                }
            }
            foreach ($outputs as $key => $value) {
                $space_size = $max_command_len - strlen($key);
                $message = "\e[1m" . $key . "\e[0m"
                    . str_repeat(' ', $space_size)
                    . ' : ' . $value;
                $console->message($message . \PHP_EOL);
            }
        });
    }

    public function message(string $msg, array $context = array(), array $format = [self::COLOR_DEFAULT]): void
    {
        echo "\e[" . implode(';', $format) . 'm' . $this->interpolate($msg, $context) . "\e[0m";
    }

    public function error(string $msg, array $context = array()): void
    {
        $this->message('[ERROR] ' . $msg, $context, [self::COLOR_WHITE, self::BOLD, self::BACKGROUND_RED]);
    }

    public function success(string $msg, array $context = array()): void
    {
        $this->message('[SUCCESS] ' . $msg, $context, [self::COLOR_WHITE, self::BACKGROUND_GREEN, self::BOLD]);
    }

    public function warning(string $msg, array $context = array()): void
    {
        $this->message('[WARNING] ' . $msg, $context, [self::COLOR_WHITE, self::BACKGROUND_YELLOW, self::BOLD]);
    }

    public function info(string $msg, array $context = array()): void
    {
        $this->message('[INFO] ' . $msg, $context, [self::COLOR_CYAN]);
    }

    /**
     * @param string $command
     * @param \Callable $execute
     * @param string $definition
     * @return $this
     */
    public function register(string $command, callable $execute, string $definition = ''): Console
    {
        $lowercase = strtolower($command);
        $this->commands[$lowercase] = $execute;
        $this->helps[$lowercase] = $definition;
        return $this;
    }

    public function run(): bool
    {
        if($this->command === null){
            $this->error('A command to run was not found.');
            return false;
        }
        $lowercase = strtolower($this->command);
        if(!isset($this->commands[$lowercase])){
            $this->error('A command to run is not defined.');
            return false;
        }
        call_user_func_array($this->commands[$lowercase], [$this]);
        return true;
    }

    public function segment(int $id = 0, $default = null)
    {
        return $this->segments[$id] ?? $default;
    }

    public function segments(): array
    {
        return $this->segments;
    }

    /**
     * @return false|int
     */
    public function search_segment(string $search)
    {
        return array_search($search, $this->segments, true);
    }

    public function has_segment(string $search): bool
    {
        return in_array($search, $this->segments, true);
    }

    public function has_flag(string $name): bool
    {
        $lowercase = strtolower($name);
        return isset($this->flags[$lowercase]);
    }

    public function flag(string $name, $default = null)
    {
        $lowercase = strtolower($name);
        return $this->flags[$lowercase] ?? $default;
    }

    public function flags(): array
    {
        return $this->flags;
    }

    public function ask(string $question)
    {
        $this->message( \PHP_EOL . $question . \PHP_EOL);
        $handle = fopen("php://stdin", "r");
        do {
            $line = fgets($handle);
        } while ($line == '');
        fclose($handle);
        return $line;
    }

    /**
     * @return void
     */
    protected function setUp(): void
    {
        global $argv;
        array_shift($argv);
        if(empty($argv)){
            return;
        }
        $this->command = $argv[0];
        array_shift($argv);
        $this->segments = $argv;
        for ($i = 0; $i < count($this->segments); ++$i) {
            if(!isset($this->segments[$i])){
                continue;
            }
            $segment = $this->segments[$i];
            if (substr($segment, 0, 1) === '-') {
                $segment = ltrim($segment, '-');
                if(strpos($segment, '=')){
                    $parse = explode('=', $segment, 2);
                    $lowercase = strtolower($parse[0]);
                    $this->flags[$lowercase] = $parse[1];
                }else{
                    $valueId = $i + 1;
                    $lowercase = strtolower($segment);
                    if(!isset($this->segments[$valueId])){
                        $this->flags[$lowercase] = '';
                        continue;
                    }
                    if(substr($this->segments[$valueId], 0, 1) === '-') {
                        $this->flags[$lowercase] = '';
                        continue;
                    }
                    $this->flags[$lowercase] = $this->segments[$valueId];
                    ++$i;
                    continue;
                }
            }
        }
    }

    /**
     * @param string $message
     * @param array $context
     * @return string
     */
    private function interpolate(string $message, array $context = []): string
    {
        if(empty($context)){
            return $message;
        }
        $replace = [];
        foreach ($context as $key => $val) {
            if (!is_array($val) && (!is_object($val) || method_exists($val, '__toString'))) {
                $replace['{' . $key . '}'] = (string)$val;
            }
        }
        return strtr($message, $replace);
    }

}
