# Console
It is a simple helper library that will allow you to write your console/cli application with PHP.

## Requirements

- PHP 7.2 or higher

## Installation

```
composer require initphp/console
```

## Usage

```php
#!usr/bin/php
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

## Credits

- [Muhammet ŞAFAK](https://www.muhammetsafak.com.tr) <<info@muhammetsafak.com.tr>>

## License

Copyright &copy; 2022 [MIT License](./LICENSE)