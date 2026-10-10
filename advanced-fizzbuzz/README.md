# Advanced FizzBuzz

An advanced version of the FizzBuzz challenge, with support for reporting
results slower or faster, or in reverse order, or just showing the original
numbers (if you really want to).

Everything is implemented in a single file (`main.php`) for easy of testing.

Run with `--help` to see usage instructions.

## PHP 8.6 Features

- Debuggable enums: when running an operation, the command chosen is reported
based on the `CommandName` enum variant, using `CommandName::__debugInfo()` to
get the information.
- `trim()` including form feeds by default: `CommandName::__debugInfo()` adds
some extra form feeds to the extracted enum case documentation comment, knowing
that they will be removed by a later `trim()` call.
- `#[\Override]` for class constants: the command for an operation is indicated
by the `Operation` subclass overridding the `COMMAND` class constant, and those
overrides are marked with `#[\Override]`.
- `readonly` property defaults: the operation details are only reported if
`Operation::$showCommand` is not overridden to set the value to `false`; it
is a read-only property with the value set as the default in the property
declaration.
- Partial function application: multiple places make use of partial function
application to simplify the code and chain together callbacks.
- Constant object property writes: the inner thread that reports the raw numbers
tracks the next number in an instance of the `HoldsNumber` class, which is
stored in the global `NEXT_NUMBER_HOLDER` constant. The next number is updated
using `++NEXT_NUMBER_HOLDER->number`.
- The `\\SortDirection` enum: the output can be either ascending ("1, 2, Fizz") or
descending ("Buzz, Fizz, 98"); internally, the direction is tracked using the
new `\\SortDirection` enum.
- Polling API: internally, the main process starts a new process that just
prints out the numbers from 1 to 100 (or from 100 to 1), and listens to that
process using the new polling API.
- The `\\Time\\Duration` class: the speed at which values are printed is controlled
by making `usleep()` calls between different values; the duration of the sleep
is calculated using the new `\\Time\\Duration` class.
- The `clamp()` function: the duration of the sleep between printing values is
restricted to being at least 50 milliseconds and at most 500 milliseconds using
the new `clamp()` function.
- Parameter documentation comments: `FizzBuzzPrinter::isMultipleOf()` uses the
support for documentation comments to annotate the two parameters, though that
documentation is not currently actually read.
