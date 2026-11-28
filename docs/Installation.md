# Installation

## Requirements

| | |
| --- | --- |
| PHP | `^8.2` (CI runs 8.2, 8.3, 8.4, 8.5 and 8.6) |
| Extensions | None required; `ext-mbstring` recommended |
| Runtime dependencies | None |

The package has zero runtime dependencies. Development dependencies
(PHPUnit, PHPStan, PHP-CS-Fixer) live under `require-dev` and are not
installed when you depend on `initphp/console` from another project.

`ext-mbstring` is optional: the [table renderer](Tables.md) uses `mb_strlen()`
when it is available so multibyte (UTF-8) columns align correctly, and falls
back to `strlen()` otherwise.

## Install via Composer

```bash
composer require initphp/console
```

The classes are autoloaded under the `InitPHP\Console` namespace via PSR-4,
and a small backwards-compatibility shim is loaded via Composer's `files`
autoloading:

```json
{
    "autoload": {
        "psr-4": {
            "InitPHP\\Console\\": "src/"
        },
        "files": [
            "src/aliases.php"
        ]
    }
}
```

The `files` entry registers the `\InitPHP\CLITable\Table` alias used by the
[migration path](Migration-Guide.md) from the old `initphp/cli-table` package.

## Verifying the install

Create `console.php`:

```php
#!/usr/bin/env php
<?php
require __DIR__ . '/vendor/autoload.php';

use InitPHP\Console\{Application, Input, Output};

$console = new Application('Demo CLI', '1.0.0');
$console->register('ping', function (Input $input, Output $output) {
    $output->success('pong');
}, 'Health check.');

$console->run();
```

Run it:

```bash
php console.php ping     # [SUCCESS] pong
php console.php list     # banner + command listing
```

If the autoloader cannot locate the classes, double-check that you ran
`composer install` (or `composer dump-autoload`) and that your script requires
`vendor/autoload.php` before using the application.

## Supported PHP versions in CI

Every pull request runs the QA matrix (coding standards, static analysis,
unit tests) against:

- PHP 8.2, 8.3, 8.4, 8.5, 8.6

If you discover a regression on a supported version, please
[file an issue](https://github.com/InitPHP/Console/issues/new) and include the
PHP version reported by `php -v`.

## Next steps

- Take the five-minute tour in [Quick Start](Quick-Start.md).
- Learn the two ways to declare commands in [Commands](Commands.md).
- Upgrading from `initphp/cli-table`? Read the [Migration Guide](Migration-Guide.md).
