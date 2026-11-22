# Recipe: A Git-like CLI

Build a small multi-command binary — grouped, class-based commands with typed
arguments and a clean help screen. The shape generalises to any tool with a
handful of sub-commands (`make:*`, `db:*`, `cache:*`, …).

## The entry script

```php
#!/usr/bin/env php
<?php
// bin/vcs
require __DIR__ . '/../vendor/autoload.php';

use InitPHP\Console\Application;
use App\Command\{InitCommand, AddCommand, CommitCommand, LogCommand};

$app = new Application('VCS', '1.0.0');

$app->register(InitCommand::class);
$app->register(AddCommand::class);
$app->register(CommitCommand::class);
$app->register(LogCommand::class);

exit($app->run() ? 0 : 1);
```

## A leaf command

```php
namespace App\Command;

use InitPHP\Console\{Command, Input, Output};

final class InitCommand extends Command
{
    public $command = 'init';

    public function definition(): string
    {
        return 'Create an empty repository.';
    }

    public function execute(Input $input, Output $output)
    {
        // ... create the .vcs directory ...
        $output->success('Initialised empty VCS repository.');
    }
}
```

## A command with a required argument

```php
namespace App\Command;

use InitPHP\Console\{Command, Input, InputArgument, Output};

final class CommitCommand extends Command
{
    public $command = 'commit';

    public function definition(): string
    {
        return 'Record changes to the repository.';
    }

    public function help(): string
    {
        return 'Creates a new commit containing the staged changes.';
    }

    public function arguments(): array
    {
        return [
            new InputArgument('message', InputArgument::STR, '', false, 'Commit message.'),
            new InputArgument('amend',   InputArgument::BOOL, false, true, 'Amend the previous commit.'),
        ];
    }

    public function execute(Input $input, Output $output)
    {
        $message = $input->getArgument('message');   // required → guaranteed present
        $amend   = $input->getArgument('amend');     // bool, defaults to false

        if ($amend) {
            $output->info('Amending the previous commit…');
        }

        // ... write the commit ...
        $output->success('Created commit: ' . $message);
    }
}
```

```bash
php bin/vcs commit --message="Initial import"
php bin/vcs commit --message="Fix typo" --amend=true
php bin/vcs commit                    # [ERROR] The --message parameter is undefined.
php bin/vcs commit --help             # generated usage + parameters
```

## Grouping sub-commands

Name commands `group:action` so the listing clusters them:

```php
final class RemoteAddCommand extends Command
{
    public $command = 'remote:add';
    public function definition(): string { return 'Add a remote.'; }
    // ...
}

final class RemoteRemoveCommand extends Command
{
    public $command = 'remote:remove';
    public function definition(): string { return 'Remove a remote.'; }
    // ...
}
```

```bash
php bin/vcs list
```

```
[COMMANDS]

remote
        remote:add     : Add a remote.
        remote:remove  : Remove a remote.

init   : Create an empty repository.
commit : Record changes to the repository.
```

## Positional sub-arguments via segments

If you prefer `vcs log 10` over `vcs log --limit=10`, read a
[segment](Input.md#segments--bare-positional-tokens) instead of declaring an
argument:

```php
final class LogCommand extends Command
{
    public $command = 'log';

    public function execute(Input $input, Output $output)
    {
        $limit = $input->getSegment(0, 20);   // `vcs log 10` → 10; default 20
        // ... print the last $limit commits ...
    }
}
```

> Segments are not validated by the framework (only declared
> [`InputArgument`](Input-Arguments.md)s are). Validate them yourself inside
> `execute()` if they must be, say, a positive integer.

## Mapping results to exit codes

`run()` returns `false` when a command fails (missing argument, thrown
exception, …). Forward that to the shell so scripts and CI can react:

```php
exit($app->run() ? 0 : 1);
```

## See also

- [Commands](Commands.md) · [Input Arguments](Input-Arguments.md) · [Input](Input.md)
- [Recipe: Testing Commands](Recipe-Testing-Commands.md) — test the binary above.
