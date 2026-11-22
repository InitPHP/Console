# Tables

[`InitPHP\Console\Utils\Table`](API-Reference.md#utilstable) renders styleable
ASCII/ANSI tables. Rows are associative arrays; the union of their keys forms
the header, columns are auto-sized, and styles are emitted as SGR escape
sequences.

> This is the renderer formerly published as `initphp/cli-table`. The old
> class name `\InitPHP\CLITable\Table` still resolves to it via a
> `class_alias` — see [Migration Guide](Migration-Guide.md).

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

`Table::create()` is a convenience factory equivalent to `new Table()`. Both
return a fresh instance, and every mutator returns `$this`, so construction
chains naturally.

## Styling

All four style setters are variadic and take one or more SGR codes — the same
constants as [`Output`](Output.md#styling-with-sgr-codes), also exposed directly
on `Table`:

```php
$table = Table::create()
    ->setHeaderStyle(Table::COLOR_RED, Table::BOLD)      // header row
    ->setCellStyle(Table::COLOR_GREEN)                   // every body cell
    ->setBorderStyle(Table::COLOR_BLUE)                  // the frame
    ->setColumnCellStyle('status', Table::COLOR_YELLOW); // one column's body cells
```

| Setter | Applies to | Default |
| --- | --- | --- |
| `setHeaderStyle(int ...$sgr)` | the header row | `[BOLD]` |
| `setCellStyle(int ...$sgr)` | all body cells | none |
| `setBorderStyle(int ...$sgr)` | box-drawing characters | none |
| `setColumnCellStyle(string $col, int ...$sgr)` | body cells of one column | none |

Per-column styling overrides the global cell style for that column only, and
applies to body cells, not the header.

## How values are rendered

Cell values are coerced to a printable string. Strings pass through verbatim
(trimmed); everything else is mapped as follows:

| Value | Rendered as |
| --- | --- |
| `null` | `[NULL]` |
| `true` / `false` | `[TRUE]` / `[FALSE]` |
| array | `[ARRAY]` |
| array that is callable (`[$obj, 'method']`) | `[CALLABLE]` |
| other callable | `[CALLABLE]` |
| resource | `[RESOURCE]` |
| object | its class name (e.g. `App\User`) |
| int / float | string cast |

## Ragged rows

Rows may have different key sets. The header is the union of all keys, and any
cell missing from a row is rendered as `[NULL]`:

```php
Table::create()
    ->row(['id' => 1, 'name' => 'Ada', 'active' => true])
    ->row(['id' => 2, 'name' => null,  'role'   => 'admin'])
    ->getContent();
// Columns: id | name | active | role
// Row 1 has no "role"  → [NULL]; Row 2 has no "active" → [NULL]; name=null → [NULL]
```

## Multibyte text

Column widths are measured with `mb_strlen()` when the `mbstring` extension is
available, so UTF-8 values such as `İstanbul` or `Mühammet` align correctly.
Without `mbstring` the renderer falls back to `strlen()` (byte length), which
may misalign multibyte cells.

## A styled example

```php
use InitPHP\Console\Utils\Table;

echo Table::create()
    ->setHeaderStyle(Table::COLOR_CYAN, Table::BOLD)
    ->setBorderStyle(Table::COLOR_DARK_GRAY)
    ->setColumnCellStyle('admin', Table::COLOR_YELLOW)
    ->row(['id' => 1, 'name' => 'Ada Lovelace',   'admin' => true])
    ->row(['id' => 2, 'name' => 'Linus Torvalds', 'admin' => false])
    ->row(['id' => 3, 'name' => 'Margaret Hamilton', 'admin' => true]);
```

## Combining with the application

The table is just a string, so print it from a command like any other output:

```php
use InitPHP\Console\{Command, Input, Output};
use InitPHP\Console\Utils\Table;

final class UsersCommand extends Command
{
    public $command = 'user:list';

    public function execute(Input $input, Output $output)
    {
        $table = Table::create()->setHeaderStyle(Table::COLOR_GREEN, Table::BOLD);
        foreach (User::all() as $user) {
            $table->row(['id' => $user->id, 'name' => $user->name, 'admin' => $user->isAdmin]);
        }
        $output->writeln((string) $table);
    }
}
```

See the [Styled Report Table recipe](Recipe-Report-Table.md) for a fuller
example.

## Where to go next

- [Recipe: Styled Report Table](Recipe-Report-Table.md) — a complete reporting command.
- [Migration Guide](Migration-Guide.md) — moving from `initphp/cli-table`.
- [API Reference](API-Reference.md#utilstable) — every method and constant.
