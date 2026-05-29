# Tables

`InitPHP\Console\Utils\Table` renders styleable ASCII/ANSI tables. Rows are associative arrays; the union of their keys forms the header, columns are auto-sized, and styles are emitted as SGR escape sequences.

## Basic usage

```php
use InitPHP\Console\Utils\Table;

$table = Table::create();

$table->row(['id' => 1, 'name' => 'Matthew S.', 'email' => 'matthew@example.com'])
      ->row(['id' => 2, 'name' => 'Millie J.',  'email' => 'millie@example.com']);

echo $table;             // uses __toString()
// or
echo $table->getContent();
```

```
╔══════╤══════════════╤══════════════════════════╗
║ id   │ name         │ email                    ║
╟──────┼──────────────┼──────────────────────────╢
║ 1    │ Matthew S.   │ matthew@example.com      ║
║ 2    │ Millie J.    │ millie@example.com       ║
╚══════╧══════════════╧══════════════════════════╝
```

`Table::create()` is a convenience factory equivalent to `new Table()`; both return a fresh instance and every mutator returns `$this` for chaining.

## Styling

All four style setters take one or more SGR codes (the same constants as [`Output`](05-output.md), also exposed on `Table`):

```php
$table = Table::create()
    ->setHeaderStyle(Table::COLOR_RED, Table::BOLD)  // header row
    ->setCellStyle(Table::COLOR_GREEN)               // every body cell
    ->setBorderStyle(Table::COLOR_BLUE)              // the frame
    ->setColumnCellStyle('status', Table::COLOR_YELLOW); // one column's body cells
```

- `setHeaderStyle()` — defaults to `[BOLD]`.
- `setCellStyle()` — applied to all body cells.
- `setBorderStyle()` — applied to the box-drawing characters.
- `setColumnCellStyle($column, ...)` — overrides body styling for a single column.

## How values are rendered

Non-string cell values are converted to a printable form:

| Value type                         | Rendered as          |
|------------------------------------|----------------------|
| `null`                             | `[NULL]`             |
| `true` / `false`                   | `[TRUE]` / `[FALSE]` |
| array                              | `[ARRAY]`            |
| array that is callable             | `[CALLABLE]`         |
| other callable                     | `[CALLABLE]`         |
| resource                           | `[RESOURCE]`         |
| object                             | its class name       |
| int / float                        | string cast          |

Rows with different key sets are allowed: any cell missing from a row is rendered as `[NULL]`.

```php
Table::create()
    ->row(['id' => 1, 'name' => 'Ada', 'active' => true])
    ->row(['id' => 2, 'name' => null,  'meta' => ['x' => 1]])
    ->getContent();
// missing/extra columns are filled with [NULL]; null → [NULL]; array → [ARRAY]
```

## Multibyte text

Column widths are measured with `mb_strlen()` when the `mbstring` extension is available, so UTF-8 values such as `İstanbul` or `Mühammet` align correctly. Without `mbstring` the renderer falls back to `strlen()`.

## Legacy alias

This class was previously shipped as `initphp/cli-table`. The old fully-qualified name `\InitPHP\CLITable\Table` still resolves to it via a `class_alias`, so existing code keeps working. See [Migrating from `initphp/cli-table`](08-migrating-from-cli-table.md).
