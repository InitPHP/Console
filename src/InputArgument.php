<?php
/**
 * InputArgument.php
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

final class InputArgument
{

    public const ANY = 'ANY';
    public const INT = 'INT';
    public const FLOAT = 'FLOAT';
    public const NUMERIC = 'NUMBER';
    public const BOOL = 'BOOL';
    public const STR = 'STRING';

    protected const SUPPORTED_TYPES = [
        self::ANY, self::INT, self::FLOAT, self::NUMERIC, self::BOOL, self::STR,
    ];

    protected $name;

    protected $definition;

    protected $isOptional = true;

    protected $default;

    protected $type;

    public function __construct(string $name, $type, $default, bool $isOptional = true, string $definition = '')
    {
        $this->name = $name;
        if (!\in_array($type, self::SUPPORTED_TYPES, true)) {
            throw new \InvalidArgumentException('The species defined for the --' . $this->name . ' parameter is not supported.');
        }
        $this->type = $type;
        if (!$this->valueCheck($default)) {
            throw new \InvalidArgumentException('The default value for the --' . $this->name . ' parameter must be a type accepted for the parameter.');
        }
        $this->default = $default;
        $this->isOptional = $isOptional;
        $this->definition = $definition;
    }

    public function isOptional(): bool
    {
        return $this->isOptional;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getName(): string
    {
        return '--' . $this->name;
    }

    public function getDefinition(): string
    {
        return $this->definition;
    }

    public function getDefault(): ?string
    {
        return isset($this->default) ? (string)$this->default : null;
    }

    public function run(Input &$input, Output &$output): bool
    {
        if ($input->hasArgument($this->name)) {
            $value = $input->getArgument($this->name);
        } elseif ($input->hasOption($this->name)) {
            $value = $input->getOption($this->name);
        } else {
            if ($this->isOptional === FALSE) {
                $output->error('The --' . $this->name . ' parameter is undefined.');
                return false;
            } else {
                $input->importArguments([$this->name => $this->default]);
                return true;
            }
        }

        if (!$this->valueCheck($value)) {
            if ($this->isOptional === FALSE) {
                $output->error('The value given for the --' . $this->name . ' parameter is invalid.');
                return false;
            } else {
                $value = $this->default;
            }
        } else {
            if (empty($value) && $this->isOptional === FALSE && isset($this->default)) {
                $value = $this->default;
            }
        }
        $input->importArguments([$this->name => $value]);
        return true;
    }

    private function valueCheck(&$value): bool
    {
        if ($this->type === self::ANY) {
            return true;
        }
        if ($this->type === self::BOOL) {
            if (\is_bool($value)) {
                return true;
            }
            if (\in_array($value, [0, 1])) {
                $value = (bool)$value;
                return true;
            }
            return false;
        }
        if ($this->type === self::INT) {
            return \is_int($value);
        }
        if ($this->type === self::FLOAT) {
            return \is_float($value);
        }
        if ($this->type === self::NUMERIC) {
            return \is_numeric($value);
        }
        if ($this->type === self::STR) {
            if (\is_numeric($value) || \is_bool($value)) {
                $value = (string)$value;
            }
            return \is_string($value);
        }
        return false;
    }


}
