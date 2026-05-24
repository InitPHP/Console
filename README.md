# Console

A simple helper library for writing console/CLI applications in PHP.

Starting with **2.1**, this package also ships the ANSI-coloured table renderer that used to be distributed as the separate [`initphp/cli-table`](https://github.com/InitPHP/CLITable) package (now deprecated). See the [migration section](#migrating-from-initphpcli-table) below if you are coming from that package.

## Requirements

- PHP 7.2 or higher

## Installation

```
composer require initphp/console
```

## Usage

```php
#!/usr/bin/env php
<?php
require_once __DIR__ . '/../vendor/autoload.php';
use \InitPHP\Console\{Application, Input, Output};

$console = new Application("My Console Application", '1.0');

// Register commands ...

// hello -name=John
$console->register('hello', function (Input $input, Output $output) {
    if ($input->hasArgument('name')) {
        $output->writeln('Hello {name}', [
            'name'  => $input->getArgument('name')
        ]);
    } else {
        $output->writeln('Hello World!');
    }
}, 'Says hello.');


$console->run();
```

```
php console.php list
```

## Rendering tables

This package ships a styleable ASCII/ANSI table renderer under `\InitPHP\Console\Utils\Table`:

```php
use InitPHP\Console\Utils\Table;

$table = Table::create()
    ->setHeaderStyle(Table::COLOR_RED, Table::BOLD)
    ->setBorderStyle(Table::COLOR_BLUE)
    ->setCellStyle(Table::COLOR_GREEN);

$table->row(['id' => 1, 'name' => 'Matthew S.', 'email' => 'matthew@example.com', 'status' => true])
      ->row(['id' => 2, 'name' => 'Millie J.',  'email' => 'millie@example.com',  'status' => false])
      ->row(['id' => 3, 'name' => 'Regina G.',  'email' => 'regina@example.com',  'status' => true]);

echo $table; // or $table->getContent()
```

The renderer is intentionally lightweight: it stringifies non-string cell values (`[NULL]`, `[TRUE]`, `[FALSE]`, `[CALLABLE]`, `[RESOURCE]`, class name for objects), auto-sizes columns, and emits standard SGR escape sequences. `mb_strlen()` is used when available so multibyte values align correctly.

## Migrating from `initphp/cli-table`

The standalone [`initphp/cli-table`](https://github.com/InitPHP/CLITable) package has been merged into this one starting with **2.1** and is now deprecated.

If your code currently uses `\InitPHP\CLITable\Table`, **no source changes are required** — this package ships a `class_alias` that keeps the old fully-qualified class name working. Just switch your dependency:

```diff
- "initphp/cli-table": "^1.0",
+ "initphp/console": "^2.1"
```

(`initphp/console:^2.1` declares a Composer `replace` for `initphp/cli-table`, so Composer will not install both side-by-side.)

When you next touch the code, prefer the new canonical namespace:

```php
// Before
use InitPHP\CLITable\Table;

// After
use InitPHP\Console\Utils\Table;
```

The alias is intended as a transition aid and may be removed in a future major release.

## Credits

- [Muhammet ŞAFAK](https://www.muhammetsafak.com.tr) <<info@muhammetsafak.com.tr>>

## License

Copyright &copy; 2022 [MIT License](./LICENSE)