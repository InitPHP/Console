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
use InitPHP\Console\Console;

$console = new Console();

// Register commands ...

// hello -name=John
$console->register('hello', function (Console $console) {
    if ($console->has_flag('name')) {
        $console->message("Hello {name}", [
            'name'  => $console->flag('name')
        ]);
    }else{
        $console->message('Hello World!');
    }
}, 'Says hello.');


$console->run();
```

## Credits

- [Muhammet ŞAFAK](https://www.muhammetsafak.com.tr) <<info@muhammetsafak.com.tr>>

## License

Copyright &copy; 2022 [MIT License](./LICENSE)