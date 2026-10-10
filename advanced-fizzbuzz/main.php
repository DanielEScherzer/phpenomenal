<?php

/**
 * Name of the command being run
 *
 * New PHP 8.6 features:
 * - in __debugInfo():
 *   - https://wiki.php.net/rfc/debugable-enums
 *   - https://wiki.php.net/rfc/partial_function_application_v2
 *   - https://wiki.php.net/rfc/trim_form_feed
 */
enum CommandName {
	/** Error, Operation::COMMAND was not overridden */
	case Error;

	/** Print out the numbers 1-100 with a delay */
	case NumberPrinter;

	/**
	 * Run the number printer, polling the result and converting it to
	 * FizzBuzz output before printing out that FizzBuzz content
	 */
	case FizzBuzzPrinter;

	/** Print usage instructions */
	case Help;

	public function __debugInfo(): array {
		$rawComment = new ReflectionEnumUnitCase(__CLASS__, $this->name)->getDocComment();
		// Add some form feeds that will be removed by the final trim()
		$comment = "\f$rawComment\f"
			|> explode("\n", ?)
			|> array_map(trim(?, "/* \t"), ?)
			|> implode(" ", ?)
			|> trim(?);
		return [
			'operation' => $this->name,
			'description' => $comment,
		];
	}
}

/**
 * Counter stored in a constant but still mutable
 *
 * New PHP 8.6 feature: https://wiki.php.net/rfc/const_object_property_write
 */
class HoldsNumber {
	public int $number = 0;
}

define('NEXT_NUMBER_HOLDER', new HoldsNumber());

/**
 * Name of the command being run
 *
 * New PHP 8.6 features:
 * - for the $showCommand property:
 *   - https://wiki.php.net/rfc/readonly_property_defaults
 * - in run():
 *   - https://wiki.php.net/rfc/debugable-enums
 */
abstract class Operation {
	public const CommandName COMMAND = CommandName::Error;

	protected readonly bool $showCommand = true;

	public function run(array $args) {
		if ($this->showCommand) {
			var_dump(static::COMMAND);
			echo "\n";
		}
		$this->doRun($args);
	}

	abstract protected function doRun(array $args);
}

/**
 * In this thread, print out the numbers 1 through 100, sleeping between each
 * one.
 *
 * New PHP 8.6 features:
 * - for the COMMAND constant:
 *   - https://wiki.php.net/rfc/override_constants
 * - in doRun():
 *   - https://wiki.php.net/rfc/clamp_v2
 *   - https://wiki.php.net/rfc/duration_class
 *   - https://wiki.php.net/rfc/const_object_property_write
 *   - https://wiki.php.net/rfc/sort_direction_enum
 */
class NumberGenerator extends Operation {
	#[\Override]
	public const CommandName COMMAND = CommandName::NumberPrinter;

	protected function doRun(array $args) {
		$delay = (int)$args['delay'];
		// Clamp the millisecond delay between 50 and 500
		$delay = clamp($delay, 50, 500);
		$sleepDuration = \Time\Duration::fromMilliseconds($delay);
		$order = $args['direction'];
		while (true) {
			// Write to constant property
			$next = ++NEXT_NUMBER_HOLDER->number;
			if ($next > 100) {
				break;
			}
			$to_print = match ($order) {
				SortDirection::Ascending => $next,
				SortDirection::Descending => 101 - $next,
			};
			echo $to_print . "\n";
			usleep($sleepDuration->nanoseconds / 1000);
		}
	}
}

/**
 * In this thread, create the number generator, listen to it, and map the results
 *
 * New PHP 8.6 features:
 * - for the COMMAND constant:
 *   - https://wiki.php.net/rfc/override_constants
 * - in doRun():
 *   - https://wiki.php.net/rfc/sort_direction_enum
 *   - https://wiki.php.net/rfc/poll_api
 * - in isMultipleOf():
 *   - https://wiki.php.net/rfc/parameter-doccomments
 * - in handleGeneratedNumber():
 *   - https://wiki.php.net/rfc/partial_function_application_v2
 */
class FizzBuzzPrinter extends Operation {
	#[\Override]
	public const CommandName COMMAND = CommandName::FizzBuzzPrinter;

	protected function doRun(array $args) {
		$cmd = PHP_BINARY . " " . __FILE__ . " --operation=runNumberGenerator";
		$cmd .= ' --delay=' . $args['delay'];
		$cmd .= ' --direction=' . match ($args['direction']) {
			SortDirection::Ascending => 'ASC',
			SortDirection::Descending => 'DESC',
		};

		$descriptors = [
			['pipe', 'r'], // stdin
			['pipe', 'w'], // stdout
			['file', '/dev/null', 'a'], //stderr
		];
		$pipes = [];
		$process = proc_open($cmd, $descriptors, $pipes);

		$poll = new \Io\Poll\Context;
		$handle = new StreamPollHandle($pipes[1]);
		$poll->add($handle, [\Io\Poll\Event::Read]);
		while (true) {
			$watchers = $poll->wait();
			foreach ($watchers as $watcher) {
				foreach ($watcher->getTriggeredEvents() as $event) {
					switch ($event) {
						case \Io\Poll\Event::Read:
							$content = trim(fread($pipes[1], 8192));
							// Handle the first entry including the output of
							// the command by splitting
							explode("\n", $content)
								|> array_filter(?, is_numeric(?))
								|> array_map(self::handleGeneratedNumber(?), ?);
							break;
						case \Io\Poll\Event::HangUp:
							break 4;
					}
				}
			}
		}
	}
	
	/**
	 * Check if an input number is a multiple of some other divisor
	 */
	private static function isMultipleOf(
		/** The number to check for being a multiple of another number */
		int $number,
		/**
		 * The divisor to check against, expected to be one of 15, 3, or 5, for
		 * detecting if 'FizzBuzz', 'Fizz', or 'Buzz' should be printed
		 */
		int $divisor
	): bool {
		return ($number % $divisor === 0);
	}

	/**
	 * Handle a generated number
	 */
	private static function handleGeneratedNumber(string $value) {
		$printFizzBuzz = self::isMultipleOf(?, 15);
		$printFizz = self::isMultipleOf(?, 3);
		$printBuzz = self::isMultipleOf(?, 5);
		$number = (int)$value;
		$result = match (true) {
			$printFizzBuzz($number) => "FizzBuzz",
			$printFizz($number) => "Fizz",
			$printBuzz($number) => "Buzz",
			default => $number,
		};
		echo "$result\n";
	}

}

/**
 * Show help information
 *
 * New PHP 8.6 features:
 * - for the COMMAND constant:
 *   - https://wiki.php.net/rfc/override_constants
 * - for the $showCommand property:
 *   - https://wiki.php.net/rfc/readonly_property_defaults
 */
class HelpOperation extends Operation {
	#[\Override]
	public const CommandName COMMAND = CommandName::Help;

	#[\Override]
	protected readonly bool $showCommand = false;

	protected function doRun(array $args) {
		$usedHelpArg = isset($args['help']);
		echo "PHP 8.6 Advanced FizzBuzz implementation\n";
		echo "Options:\n";
		echo "\t[--help]\t\t\t\tShow this help information\n";
		echo "\t[--operation=<operation>]\t\tOperation (default: runFizzBuzz)\n";
		echo "\t[--delay=<delay>]\t\t\tMillisecond delay between values (default: 200)\n";
		echo "\t[--direction={ASC | DESC}]\t\tSorting direction (default: ASC)\n";
		echo "\n";
		echo "Known operations:\n";
		echo "1. runFizzBuzz: Run the FizzBuzz generator [default] \n";
		echo "2. runNumberGenerator: Run the base number generator (internal)\n";
		if (isset($args['_rest'])) {
			echo "\n";
			echo 'Error: unhandled arguments provided: ' . implode(' ', $args['_rest']) . "\n";
		} else if (!isset($args['help'])) {
			echo "\n";
			echo 'Error: unknown operation `' . $args['operation'] . "` requested\n";
		}
		echo "\n";
		$path = $_SERVER['argv'][0];
		echo "Example usage (standard FizzBuzz):\t";
		echo PHP_BINARY . " $path \n";
		echo "Faster version:\t\t\t\t";
		echo PHP_BINARY . " $path --delay=50\n";
		echo "Advanced usage (reverse FizzBuzz):\t";
		echo PHP_BINARY . " $path --direction=DESC \n";
	}
}

$options = getopt('', ["operation::", "delay::", "direction::", "help"]);

if (isset($options['help'])) {
	new HelpOperation()->run($options);
	return;
}

$options['direction'] = match ($options['direction'] ?? false) {
	'ASC' => SortDirection::Ascending,
	'DESC' => SortDirection::Descending,
	false => SortDirection::Ascending,
	default => throw new ValueError("If provided, --direction must be ASC or DESC"),
};

$options['delay'] = $options['delay'] ?? '200';

$operation = match ($options['operation'] ?? false) {
	'runNumberGenerator' => new NumberGenerator(),
	false, 'runFizzBuzz' => new FizzBuzzPrinter(),
	default => new HelpOperation(),
};
$operation->run($options);
