<?php

/**
 * Question.php
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
 * A value object describing an interactive question: its prompt, the set of
 * acceptable answers, whether it is optional and its default answer.
 *
 * Instances are consumed by {@see Output::question()}.
 */
class Question
{
    /**
     * Sentinel returned by {@see getDefault()} when no default has been set.
     *
     * Kept as a public constant for backwards compatibility; prefer
     * {@see hasDefault()} to test for the presence of a default.
     */
    public const NO_DEFAULT = '__Qu€sti0nN0D€f@ultV@lue__';

    /**
     * @var string|null
     */
    protected $question;

    /**
     * The set of acceptable answers.
     *
     * @var array<int|string, mixed>
     */
    protected $options = [true, false];

    /**
     * @var bool
     */
    protected $isOptional = false;

    /**
     * @var mixed
     */
    protected $default;

    /**
     * @var bool
     */
    protected $defaultIsSet = false;

    /**
     * Whether an empty/non-matching answer is allowed to fall back to the default.
     */
    public function isOptional(): bool
    {
        return $this->isOptional;
    }

    /**
     * Marks the question as optional.
     *
     * @return $this
     */
    public function optional(): self
    {
        $this->isOptional = true;

        return $this;
    }

    /**
     * Marks the question as required.
     *
     * @return $this
     */
    public function notOptional(): self
    {
        $this->isOptional = false;

        return $this;
    }

    /**
     * Whether the given answer is among the acceptable options.
     *
     * The answer is matched both verbatim and after {@see Helpers::strValueCast()}
     * so that string input such as `"true"`/`"no"` matches boolean options.
     */
    public function hasOption(string $option): bool
    {
        if (\in_array($option, $this->options, true)) {
            return true;
        }

        return \in_array(Helpers::strValueCast($option), $this->options, true);
    }

    /**
     * Replaces the full set of acceptable answers.
     *
     * @param array<int|string, mixed> $options
     * @return $this
     */
    public function setOptions(array $options): self
    {
        $this->options = $options;

        return $this;
    }

    /**
     * Returns the acceptable answers.
     *
     * @return array<int|string, mixed>
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Adds a single acceptable answer.
     *
     * For backwards compatibility two-argument calls are still honoured: the
     * second argument, when supplied, is the value that gets registered and the
     * first acts only as a human-readable label.
     *
     * @param string $option Answer to register (or a label when `$value` is given).
     * @param mixed  $value  Optional explicit value to register instead of `$option`.
     * @return $this
     */
    public function addOption(string $option, $value = self::NO_DEFAULT): self
    {
        $this->options[] = $value === self::NO_DEFAULT ? $option : $value;

        return $this;
    }

    /**
     * Sets the prompt text.
     *
     * @return $this
     */
    public function setQuestion(string $question): self
    {
        $this->question = $question;

        return $this;
    }

    /**
     * Returns the prompt text (empty string when unset).
     */
    public function getQuestion(): string
    {
        return $this->question ?? '';
    }

    /**
     * Sets the default answer.
     *
     * @param mixed $default
     * @return $this
     */
    public function setDefault($default): self
    {
        $this->default = $default;
        $this->defaultIsSet = true;

        return $this;
    }

    /**
     * Whether a default answer has been set.
     */
    public function hasDefault(): bool
    {
        return $this->defaultIsSet;
    }

    /**
     * Returns the default answer, or {@see NO_DEFAULT} when none was set.
     *
     * @return mixed
     */
    public function getDefault()
    {
        return $this->defaultIsSet ? $this->default : self::NO_DEFAULT;
    }
}
