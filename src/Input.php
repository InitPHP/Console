<?php
/**
 * Input.php
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

class Input
{

    protected $argv;

    protected $arguments = [];

    protected $options = [];

    protected $segments = [];

    public function __construct(array $argv)
    {
        $this->argv = $argv;
        for ($i = 0; $i < \count($this->argv); ++$i) {
            if (!isset($this->argv[$i])) {
                continue;
            }
            $segment = $this->argv[$i];
            if (\trim($segment, " \t\n\r\0\x0B-") === '') {
                continue;
            }
            if (\substr($segment, 0, 2) == '--') {
                /** ARGUMENTS */
                $seg = \ltrim($segment, '-');
                if (\strpos($seg, '=') !== FALSE) {
                    $split = \explode('=', $seg, 2);
                    $this->arguments[$split[0]] = Helpers::strValueCast($split[1]);
                } else {
                    $this->arguments[$seg] = '';
                }
                continue;
            }

            if (\substr($segment, 0, 1) == '-') {
                /** OPTIONS */
                $seg = \ltrim($segment, '-');
                if (\strpos($seg, '=') === FALSE) {
                    !isset($this->options[$seg]) && $this->options[$seg] = '';
                    $options = \preg_split('//', $seg, -1, \PREG_SPLIT_NO_EMPTY);
                    foreach ($options as $option) {
                        $this->options[$option] = $option;
                    }
                } else {
                    $split = \explode('=', $seg, 2);
                    $this->options[$split[0]] = Helpers::strValueCast($split[1]);
                }
                continue;
            }

            $this->segments[] = Helpers::strValueCast($segment);
        }
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasArgument(string $name): bool
    {
        return \array_key_exists($name, $this->arguments);
    }

    /**
     * @param string $name
     * @param mixed $default
     * @return mixed|null
     */
    public function getArgument(string $name, $default = null)
    {
        return \array_key_exists($name, $this->arguments) ? $this->arguments[$name] : $default;
    }

    /**
     * @return array
     */
    public function allArguments(): array
    {
        return $this->arguments;
    }

    /**
     * @param int $index
     * @return bool
     */
    public function hasSegment(int $index): bool
    {
        return \array_key_exists($index, $this->segments);
    }

    /**
     * @param int $index
     * @param mixed $default
     * @return mixed
     */
    public function getSegment(int $index, $default = null)
    {
        return \array_key_exists($index, $this->segments) ? $this->segments[$index] : $default;
    }

    /**
     * @return array
     */
    public function allSegment(): array
    {
        return $this->segments;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasOption(string $name): bool
    {
        return array_key_exists($name, $this->options);
    }

    /**
     * @param string $name
     * @param $default
     * @return mixed|null
     */
    public function getOption(string $name, $default = null)
    {
        return \array_key_exists($name, $this->options) ? $this->options[$name] : $default;
    }

    public function importArguments(array ...$arguments)
    {
        $this->arguments = \array_merge($this->arguments, ...$arguments);
    }

}
