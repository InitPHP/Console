<?php
/**
 * Question.php
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

class Question
{

    protected $question;

    protected $options = [true, false];

    protected $isOptional = false;

    protected $default;

    public function isOptional(): bool
    {
        return $this->isOptional;
    }

    public function optional(): self
    {
        $this->isOptional = true;

        return $this;
    }

    public function notOptional(): self
    {
        $this->isOptional = false;

        return $this;
    }

    public function hasOption(string $option): bool
    {
        return \in_array($option, $this->options);
    }

    public function setOptions(array $options): self
    {
        $this->options = $options;

        return $this;
    }

    public function addOption(string $option, $value): self
    {
        $this->options[] = $value;

        return $this;
    }

    public function setQuestion(string $question): self
    {
        $this->question = $question;

        return $this;
    }

    public function getQuestion(): string
    {
        return $this->question ?? '';
    }

    public function setDefault($default): self
    {
        $this->default = $default;

        return $this;
    }

    public function getDefault()
    {
        return $this->default ?? '__Qu€sti0nN0D€f@ultV@lue__';
    }

}
