# Interactive questions

`Output` offers two ways to read input from the user: free-form `ask()` and the option-constrained `question()` built around the `Question` value object.

## `ask()` — free-form input

```php
$name = $output->ask('What is your name?');
```

- The prompt is printed, then a line is read from the input stream.
- The answer is run through the same [type casting](03-input.md#automatic-type-casting) as command input, so `"42"` comes back as `int(42)`, `"yes"` as `true`, etc.
- Passing `false` as the second argument rejects an empty answer and keeps asking:

```php
$value = $output->ask('This cannot be empty:', false);
```

- Typing `exit` or `quit` terminates the application.

## `question()` — constrained input

`Question` describes the prompt, the set of acceptable answers, whether it is optional and a default. Configure it fluently:

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
2. If it is `exit`/`quit` → the application terminates.
3. If the question is **optional** → the default is returned (or the raw answer when no default is set).
4. If the question is **required** and nothing matched → the user is asked again.

### Acceptable-answer matching

`hasOption()` matches both the verbatim answer and its cast form, so string input lines up with boolean/numeric options:

```php
$q = new Question();          // default options: [true, false]
$q->hasOption('true');        // true   ("true" casts to bool true)
$q->hasOption('yes');         // true
$q->hasOption('maybe');       // false
```

For yes/no prompts you can therefore rely on the defaults, or set explicit string options:

```php
(new Question())->setQuestion('Continue?')->setOptions(['yes', 'no']);
```

## The `Question` API

| Method                         | Purpose                                                        |
|--------------------------------|----------------------------------------------------------------|
| `setQuestion(string)` / `getQuestion()` | The prompt text.                                      |
| `setOptions(array)` / `getOptions()`    | Replace / read the acceptable answers.                |
| `addOption(string)`            | Append a single acceptable answer.                             |
| `hasOption(string)`            | Whether an answer is acceptable (verbatim or cast).            |
| `optional()` / `notOptional()` / `isOptional()` | Toggle / read the optional flag.             |
| `setDefault(mixed)` / `getDefault()` | Set / read the default answer.                           |
| `hasDefault()`                 | Whether a default was set.                                     |
| `Question::NO_DEFAULT`         | Sentinel returned by `getDefault()` when no default is set.    |

> **`addOption()` compatibility note:** the method also accepts a legacy two-argument form `addOption($label, $value)`, where the second argument is the value that gets registered and the first is just a label. Prefer the single-argument form for new code.

## Testing prompts

Inject an input stream pre-seeded with the answers:

```php
$stdin = fopen('php://memory', 'r+');
fwrite($stdin, "Ada\n");
rewind($stdin);

$output = new Output(STDOUT, $stdin);
$output->ask('Name?'); // "Ada"
```
