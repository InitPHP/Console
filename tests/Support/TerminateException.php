<?php

declare(strict_types=1);

namespace Test\InitPHP\Console\Support;

/**
 * Thrown by {@see MemoryOutput::terminate()} in place of `exit`.
 */
final class TerminateException extends \RuntimeException
{
}
