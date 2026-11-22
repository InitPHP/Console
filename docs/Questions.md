# Questions

[`Output`](Output.md) offers two ways to read input from the user: free-form
`ask()` and the option-constrained `question()` built around the
[`Question`](API-Reference.md#question) value object.

## `ask()` — free-form input

```php
$name = $output->ask('What is your name?');
```

- The prompt is printed, then a line is read from the input stream.
- The answer is run through the same
  [type casting](Input.md#automatic-type-casting) as command input, so `"42"`
  comes back as `int(42)`, `"yes"` as `true`, and so on.
- Passing `false` as the second argument rejects an empty answer and keeps
  asking until something is entered:

```php
$value = $output->ask('This cannot be empty:', false);
```

- Typing `exit` or `quit` terminates the application.

## `question()` — constrained input

`Question` describes the prompt, the set of acceptable answers, whether it is
optional, and a default. Configure it fluently, then hand it to
`Output::question()`:

```php
use InitPHP\Console\Question;

$question = (new Question())
    ->setQuestion('Pick an environment:')
    ->setOptions(['dev', 'staging', 'prod'])
    ->optional()
    ->setDefault('dev');

$env = $output->question($question);
```

Behaviour when an answer is submitted:

1. If it matches one of the options → the (cast) answer is returned.
2. If it is `exit` / `quit` → the application terminates.
3. If the question is **optional** → the default is returned (or the raw
   answer when no default is set).
4. If the question is **required** and nothing matched → the user is asked
   again.

## Acceptable-answer matching

`hasOption()` matches both the verbatim answer and its
[cast](Input.md#automatic-type-casting) form, so string input lines up with
boolean or numeric options:

```php
$q = new Question();        // default options: [true, false]

$q->hasOption('true');      // true   ("true" casts to bool true)
$q->hasOption('yes');       // true   ("yes"  casts to bool true)
$q->hasOption('false');     // true
$q->hasOption('maybe');     // false
```

For a yes/no prompt you can therefore rely on the defaults, or set explicit
string options:

```php
(new Question())
    ->setQuestion('Continue?')
    ->setOptions(['yes', 'no']);
```

## Defaults

A default is distinct from "no default": `Question` tracks whether one was set
rather than inferring it from the value, so `null` is a perfectly valid
default.

```php
$q = new Question();

$q->hasDefault();              // false
$q->getDefault();              // Question::NO_DEFAULT  (sentinel)

$q->setDefault('home');
$q->hasDefault();              // true
$q->getDefault();              // 'home'

(new Question())->setDefault(null)->hasDefault(); // true
```

`Question::NO_DEFAULT` is a public sentinel constant; prefer `hasDefault()`
over comparing against it.

## The `Question` API at a glance

| Method | Purpose |
| --- | --- |
| `setQuestion(string)` / `getQuestion()` | The prompt text (empty string when unset). |
| `setOptions(array)` / `getOptions()` | Replace / read the acceptable answers. |
| `addOption(string)` | Append a single acceptable answer. |
| `hasOption(string)` | Whether an answer is acceptable (verbatim or cast). |
| `optional()` / `notOptional()` / `isOptional()` | Toggle / read the optional flag. |
| `setDefault(mixed)` / `getDefault()` / `hasDefault()` | Manage the default answer. |

> **`addOption()` compatibility note.** A legacy two-argument form
> `addOption($label, $value)` is still honoured: when a second argument is
> given it is the value that gets registered and the first acts only as a
> label. Prefer the single-argument form `addOption($value)` in new code.

## A worked example

```php
use InitPHP\Console\Question;

$confirm = (new Question())
    ->setQuestion('Delete all records? [yes/no]')
    ->setOptions(['yes', 'no'])
    ->notOptional();

if ($output->question($confirm) === 'yes') {
    // ... destructive action ...
    $output->success('Done.');
} else {
    $output->info('Cancelled.');
}
```

## Testing prompts

Inject an input stream pre-seeded with the answers:

```php
use InitPHP\Console\Output;

$stdin = fopen('php://memory', 'r+');
fwrite($stdin, "Ada\n");
rewind($stdin);

$output = new Output(STDOUT, $stdin);
$output->ask('Name?'); // 'Ada'
```

See [Testing Commands](Recipe-Testing-Commands.md) for a complete strategy,
including how to exercise the `exit`/`quit` path without killing the test
runner.

## Where to go next

- [Output](Output.md) — the writer that prints the prompts.
- [Recipe: Interactive Installer](Recipe-Interactive-Installer.md) — questions + progress in a real flow.
- [API Reference](API-Reference.md#question) — every method and the sentinel.
