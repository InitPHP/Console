<?php

/**
 * InputArgument.php
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
 * Declares a typed `--name` argument for a {@see Command}: its accepted type,
 * default value, whether it is required and a human-readable definition.
 *
 * During dispatch {@see run()} validates the value supplied on the command
 * line (falling back to the default when appropriate) and writes the resolved
 * value back into the {@see Input} bag.
 */
final class InputArgument
{
    /** Accepts any value. */
    public const ANY = 'ANY';

    /** Accepts an integer. */
    public const INT = 'INT';

    /** Accepts a float. */
    public const FLOAT = 'FLOAT';

    /** Accepts any numeric value (int, float or numeric string). */
    public const NUMERIC = 'NUMBER';

    /** Accepts a boolean (`true`/`false`, or `1`/`0`). */
    public const BOOL = 'BOOL';

    /** Accepts a string (numeric and boolean values are stringified). */
    public const STR = 'STRING';

    /**
     * The set of types accepted by the constructor.
     *
     * @var array<int, string>
     */
    protected const SUPPORTED_TYPES = [
        self::ANY, self::INT, self::FLOAT, self::NUMERIC, self::BOOL, self::STR,
    ];

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $definition;

    /**
     * @var bool
     */
    protected $isOptional;

    /**
     * @var mixed
     */
    protected $default;

    /**
     * @var string
     */
    protected $type;

    /**
     * @param string $name       Argument name without the leading dashes.
     * @param string $type       One of the `self::*` type constants.
     * @param mixed  $default    Default value; must satisfy `$type`.
     * @param bool   $isOptional Whether the argument may be omitted.
     * @param string $definition Human-readable description shown in help output.
     * @throws \InvalidArgumentException When `$type` is unsupported or `$default` does not satisfy it.
     */
    public function __construct(string $name, $type, $default, bool $isOptional = true, string $definition = '')
    {
        $this->name = $name;
        if (!\in_array($type, self::SUPPORTED_TYPES, true)) {
            throw new \InvalidArgumentException('The type defined for the --' . $this->name . ' parameter is not supported.');
        }
        $this->type = $type;
        if (!$this->valueCheck($default)) {
            throw new \InvalidArgumentException('The default value for the --' . $this->name . ' parameter must be a type accepted for the parameter.');
        }
        $this->default = $default;
        $this->isOptional = $isOptional;
        $this->definition = $definition;
    }

    /**
     * Whether the argument may be omitted from the command line.
     */
    public function isOptional(): bool
    {
        return $this->isOptional;
    }

    /**
     * The declared type, one of the `self::*` type constants.
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * The argument name, prefixed with `--`.
     */
    public function getName(): string
    {
        return '--' . $this->name;
    }

    /**
     * The human-readable description.
     */
    public function getDefinition(): string
    {
        return $this->definition;
    }

    /**
     * The default value rendered as a string, or `null` when there is none.
     */
    public function getDefault(): ?string
    {
        return isset($this->default) ? (string)$this->default : null;
    }

    /**
     * Resolves and validates this argument against the parsed input.
     *
     * The value is looked up as an argument first, then as an option. When it
     * is missing or invalid the default is used for optional arguments; for
     * required arguments an error is written and `false` is returned. The
     * resolved value is written back into `$input`.
     *
     * @return bool `false` when a required argument is missing or invalid.
     */
    public function run(Input $input, Output $output): bool
    {
        if ($input->hasArgument($this->name)) {
            $value = $input->getArgument($this->name);
        } elseif ($input->hasOption($this->name)) {
            $value = $input->getOption($this->name);
        } elseif ($this->isOptional === false) {
            $output->error('The --' . $this->name . ' parameter is undefined.');
            return false;
        } else {
            $input->importArguments([$this->name => $this->default]);
            return true;
        }

        if (!$this->valueCheck($value)) {
            if ($this->isOptional === false) {
                $output->error('The value given for the --' . $this->name . ' parameter is invalid.');
                return false;
            }
            $value = $this->default;
        } elseif (($value === '' || $value === null) && $this->isOptional === false && isset($this->default)) {
            $value = $this->default;
        }

        $input->importArguments([$this->name => $value]);
        return true;
    }

    /**
     * Validates (and, for some types, coerces) a value against the declared type.
     *
     * @param mixed $value Passed by reference so coercible values can be normalised.
     */
    private function valueCheck(&$value): bool
    {
        if ($this->type === self::ANY) {
            return true;
        }
        if ($this->type === self::BOOL) {
            if (\is_bool($value)) {
                return true;
            }
            if (\in_array($value, [0, 1], true)) {
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
