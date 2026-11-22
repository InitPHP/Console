# Recipe: Interactive Installer

A guided "setup wizard" command that asks a few questions, applies sensible
defaults, shows progress while it works, and reports the result. It combines
[`ask()`](Questions.md#ask--free-form-input),
[`Question`](Questions.md#question--constrained-input),
[`progressBar()`](Output.md#progress-bar) and the
[message helpers](Output.md#message-helpers).

## The command

```php
namespace App\Command;

use InitPHP\Console\{Command, Input, Output, Question};

final class InstallCommand extends Command
{
    public $command = 'app:install';

    public function definition(): string
    {
        return 'Interactive project setup.';
    }

    public function execute(Input $input, Output $output)
    {
        $output->writeln('Welcome! Let’s set up your project.', [], [Output::COLOR_CYAN, Output::BOLD]);

        // 1. Free-form question with a default applied manually.
        $name = $output->ask('Project name [my-app]:');
        if ($name === '') {
            $name = 'my-app';
        }

        // 2. Constrained, optional question with a default.
        $driver = $output->question(
            (new Question())
                ->setQuestion('Database driver? [mysql/pgsql/sqlite]')
                ->setOptions(['mysql', 'pgsql', 'sqlite'])
                ->optional()
                ->setDefault('sqlite')
        );

        // 3. A yes/no confirmation (relies on the default [true, false] options).
        $seed = $output->question(
            (new Question())
                ->setQuestion('Seed demo data? [yes/no]')
                ->optional()
                ->setDefault(false)
        );

        $output->writeln('');
        $output->list([
            'Project' => $name,
            'Driver'  => $driver,
            'Seed'    => $seed ? 'yes' : 'no',
        ]);
        $output->writeln('');

        // 4. Do the work with a progress bar.
        $steps = ['Writing config', 'Creating directories', 'Installing assets', 'Finalising'];
        $total = count($steps);
        foreach ($steps as $i => $label) {
            // ... perform $label ...
            $output->progressBar($i + 1, $total);
        }
        $output->writeln('');           // leave the progress line

        $output->success("Project “{$name}” is ready.");
    }
}
```

```bash
php console.php app:install
```

```
Welcome! Let’s set up your project.
Project name [my-app]:
Database driver? [mysql/pgsql/sqlite]
Seed demo data? [yes/no]

Project : my-app
Driver  : sqlite
Seed    : no

[========================================>] - 100%   4/4
[SUCCESS] Project “my-app” is ready.
```

## Notes on the question flow

- **`ask()` returns a cast value.** An empty line comes back as `''`; the
  example treats that as "use the default". To *force* a non-empty answer,
  use `ask('…', false)` which re-prompts until something is entered.
- **`question()` with `optional()` + `setDefault()`** returns the default when
  the answer is not one of the options. Without `optional()` it re-asks until a
  listed option is entered. See
  [Questions → constrained input](Questions.md#question--constrained-input).
- **Yes/no** works against the default `[true, false]` options because
  `hasOption()` matches the [cast](Input.md#automatic-type-casting) form
  (`"yes"`/`"no"`/`"true"`/`"false"` → `bool`).
- **`exit` / `quit`** typed at any prompt terminates the process. If you need
  to intercept that (for cleanup, or in tests), override
  [`Output::terminate()`](API-Reference.md#output) — see
  [Testing Commands](Recipe-Testing-Commands.md).

## Gating destructive steps

Pair a required confirmation with an early return:

```php
$confirm = (new Question())
    ->setQuestion('This will overwrite existing files. Continue? [yes/no]')
    ->setOptions(['yes', 'no'])
    ->notOptional();

if ($output->question($confirm) !== 'yes') {
    $output->info('Aborted.');
    return;
}
```

## See also

- [Questions](Questions.md) · [Output](Output.md)
- [Recipe: Testing Commands](Recipe-Testing-Commands.md) — drive this wizard from a test by feeding stdin.
