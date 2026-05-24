<?php
/**
 * aliases.php
 *
 * Backwards-compatibility shim for users migrating from the deprecated
 * `initphp/cli-table` package. Aliases the old fully-qualified class
 * name to the canonical class that now lives in this package.
 *
 * Existing code using `use InitPHP\CLITable\Table;` continues to work
 * unchanged after switching to `initphp/console:^2.1`. The class_exists
 * guard with autoload disabled prevents fatal "cannot declare class"
 * errors if the legacy package is somehow still loaded alongside this
 * one — Composer's `replace` directive normally prevents that.
 *
 * @see https://github.com/InitPHP/Console#migrating-from-initphpcli-table
 */

if (!class_exists(\InitPHP\CLITable\Table::class, false)) {
    class_alias(
        \InitPHP\Console\Utils\Table::class,
        'InitPHP\\CLITable\\Table'
    );
}
