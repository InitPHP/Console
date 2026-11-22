# Recipe: Styled Report Table

Render query results (or any row set) as a coloured, aligned table inside a
command, using [`Utils\Table`](Tables.md).

## A reporting command

```php
namespace App\Command;

use InitPHP\Console\{Command, Input, Output};
use InitPHP\Console\Utils\Table;

final class UserReportCommand extends Command
{
    public $command = 'report:users';

    public function definition(): string
    {
        return 'Print a table of users.';
    }

    public function execute(Input $input, Output $output)
    {
        $users = [
            ['id' => 1, 'name' => 'Ada Lovelace',     'role' => 'admin',  'active' => true],
            ['id' => 2, 'name' => 'Linus Torvalds',   'role' => 'editor', 'active' => true],
            ['id' => 3, 'name' => 'Margaret Hamilton','role' => 'admin',  'active' => false],
        ];

        $table = Table::create()
            ->setHeaderStyle(Table::COLOR_CYAN, Table::BOLD)
            ->setBorderStyle(Table::COLOR_DARK_GRAY)
            ->setColumnCellStyle('role', Table::COLOR_YELLOW);

        foreach ($users as $user) {
            $table->row($user);
        }

        $output->writeln((string) $table);
        $output->info(count($users) . ' user(s).');
    }
}
```

```bash
php console.php report:users
```

```
╔══════╤═════════════════════╤══════════╤══════════╗
║ id   │ name                │ role     │ active   ║
╟──────┼─────────────────────┼──────────┼──────────╢
║ 1    │ Ada Lovelace        │ admin    │ [TRUE]   ║
║ 2    │ Linus Torvalds      │ editor   │ [TRUE]   ║
║ 3    │ Margaret Hamilton   │ admin    │ [FALSE]  ║
╚══════╧═════════════════════╧══════════╧══════════╝
[INFO] 3 user(s).
```

## Friendlier booleans

The renderer stringifies `true`/`false` as `[TRUE]`/`[FALSE]`. If you want
`yes`/`no` (or `✓`/`✗`), convert before adding the row:

```php
$table->row([
    'id'     => $user['id'],
    'name'   => $user['name'],
    'role'   => $user['role'],
    'active' => $user['active'] ? 'yes' : 'no',
]);
```

See the full [value rendering table](Tables.md#how-values-are-rendered) for how
every type is displayed.

## Ragged data is fine

Rows need not share the same keys — the header is the union, and missing cells
render as `[NULL]`:

```php
$table = Table::create();
$table->row(['id' => 1, 'name' => 'Ada', 'email' => 'ada@example.com']);
$table->row(['id' => 2, 'name' => 'Linus']);   // no email → [NULL] in that cell
echo $table;
```

## Building rows from a PDO query

```php
$table = Table::create()->setHeaderStyle(Table::COLOR_GREEN, Table::BOLD);

$stmt = $pdo->query('SELECT id, name, role FROM users ORDER BY id');
foreach ($stmt as $row) {
    $table->row($row);   // associative row from PDO::FETCH_ASSOC
}

echo $table;
```

## Multibyte alignment

With `ext-mbstring` installed, UTF-8 names align correctly because widths are
measured with `mb_strlen()`. Without it, columns containing multibyte
characters may be a little off — see [Tables → multibyte](Tables.md#multibyte-text).

## See also

- [Tables](Tables.md) — full renderer reference.
- [Output](Output.md) — the colour constants reused here.
- [Migration Guide](Migration-Guide.md) — if you came from `initphp/cli-table`.
