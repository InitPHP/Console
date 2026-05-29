<?php

declare(strict_types=1);

namespace Test\InitPHP\Console\Support;

use InitPHP\Console\Output;

/**
 * Test double that captures everything written to an in-memory stream and
 * turns the interactive {@see Output::terminate()} call into an exception so
 * that `exit`/`quit` flows can be asserted without killing the test runner.
 */
final class MemoryOutput extends Output
{
    /** @var resource */
    private $buffer;

    /**
     * @param string $stdinContent Pre-seeded answers read by ask()/question().
     */
    public function __construct(string $stdinContent = '')
    {
        $buffer = \fopen('php://memory', 'r+');
        $stdin = \fopen('php://memory', 'r+');
        \fwrite($stdin, $stdinContent);
        \rewind($stdin);

        $this->buffer = $buffer;
        parent::__construct($buffer, $stdin);
    }

    /**
     * Returns everything written so far.
     */
    public function captured(): string
    {
        \rewind($this->buffer);

        return (string)\stream_get_contents($this->buffer);
    }

    /**
     * Returns the captured output with SGR escape sequences stripped.
     */
    public function plain(): string
    {
        return (string)\preg_replace('/\e\[[0-9;]*m/', '', $this->captured());
    }

    /**
     * @throws TerminateException instead of exiting the process.
     */
    protected function terminate(int $code = 0): void
    {
        throw new TerminateException('terminated', $code);
    }
}
