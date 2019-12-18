<?php
namespace Encase\Matching;

use ArrayAccess;
use BadMethodCallException;
use Encase\Matching\Matcher;
use Encase\Matching\Exceptions\MatchBuilderException;

class MatcherBuilder implements ArrayAccess
{
	/**
	 * Holds completed case definitions.
	 *
	 * @var MatchCase[]
	 */
	protected $cases = [];

	/**
	 * Holds the pattern arguments for the current case.
	 *
	 * @var array
	 */
	protected $args = [];

	/**
	 * Holds the conditions as added by ->if(). Cleared when the case result is
	 * provided.
	 *
	 * @var array
	 */
	protected $conditions = [];

	/**
	 * Set to `TRUE` when `else` is used. Should be followed by an alternative
	 * case result for when `if()` does not pass. Then the case result object
	 * is assigned. Once the main case result is provided, this is set back to
	 * `FALSE`.
	 *
	 * @var CaseResultable|bool
	 */
	protected $elseClause = false;

	/**
	 * Add a pattern match argument to the current case.
	 *
	 * @return $this|self
	 * @throws MatchBuilderException
	 */
	public function __invoke(...$args)
	{
		return $this->whenPattern($args);
	}

	/**
	 * @param  string $name
	 * @return $this|self
	 * @throws MatchBuilderException
	 */
	public function __get($name)
	{
		switch ($name) {
			case 'else':
				$this->assertHasConditions('No matching if() for else clause.');

				if ($this->elseClause) {
					$this->assertNotInElseClause();
				}

				$this->elseClause = true;
				break;

			default:
				throw new MatchBuilderException('Invalid clause "'.$name.'"');
		}
		return $this;
	}

	/**
	 * Add an exact match argument to the current case.
	 *
	 * @param  mixed $value The argument to strict match against.
	 * @return $this|self
	 * @throws MatchBuilderException
	 */
	public function offsetGet($value)
	{
		return $this->whenExact($value);
	}

	/**
	 * Pose a condition for the case to be handled.
	 *
	 * @param  callable $func The callback to handle the condition.
	 * @return $this
	 * @throws MatchBuilderException Thrown if preconditions not met.
	 */
	public function if($func)
	{
		$this->assertNotInElseClause('Missing case result for \'else\' clause.');
		$this->assertHasArgs();
		$this->conditions[] = $func;
		return $this;
	}

	/**
	 * Bind a capture to the current case.
	 *
	 * @param  string $binding
	 * @return $this
	 * @throws MatchBuilderException
	 */
	public function ret($binding)
	{
		$this->assertHasArgs('Match case must have at least one argument.');
		$this->endCase(new CaseArg($binding));
		return $this;
	}

	/**
	 * Bind a function call to the current case.
	 *
	 * @param  callable $fn Function to call if the case was matched.
	 * @return $this
	 * @throws MatchBuilderException Thrown if there were no case args.
	 */
	public function f($fn)
	{
		$this->endCase(new CaseCall($fn));
		return $this;
	}

	/**
	 * Bind a value result to the current case.
	 *
	 * @param  mixed $value
	 * @return $this
	 * @throws MatchBuilderException Thrown if there were no case args.
	 */
	public function v($value)
	{
		$this->endCase(new CaseValue($value));
		return $this;
	}

	/**
	 * Continue the match recursively with the specified captures.
	 *
	 * @param  string ...$args Captures to pass to the next match iteration.
	 * @return $this
	 * @throws MatchBuilderException Thrown if there were no case args.
	 */
	public function continue()
	{
		$this->endCase(new CaseContinue(\func_get_args()));
		return $this;
	}

	/**
	 * Get a unique instance of the built immutable pattern match object.
	 *
	 * @return \Encase\Matching\Matcher
	 * @throws MatchBuilderException
	 */
	public function get()
	{
		$this->assertNotInElseClause('Missing case result for \'else\' clause.');
		$this->assertNoExistingArgs('Incomplete match case.');
		$this->assertHasCases('Match has no cases.');
		return new Matcher($this->cases);
	}

	/**
	 * Try to match one or more arguments to the match cases.
	 * Convenient equivalent to `->get()->match(...)`.
	 *
	 * @param  mixed ...$args
	 * @return mixed
	 * @throws MatchBuilderException
	 */
	public function match(...$args)
	{
		return $this->get()->match(...$args);
	}

	public function offsetExists($offset)
	{
		throw new BadMethodCallException('Call to offsetExists on pattern match argument');
	}

	public function offsetSet($offset, $value)
	{
		throw new BadMethodCallException('Call to offsetSet on pattern match argument');
	}

	public function offsetUnset($offset)
	{
		throw new BadMethodCallException('Call to offsetUnset on pattern match argument');
	}

	/**
	 * Create a new MatcherBuilder object.
	 *
	 * @return static
	 */
	public static function new()
	{
		return new static();
	}

	/**
	 * Add a strict match argument to the current match case.
	 *
	 * @param  mixed $arg The argument to match.
	 * @return $this
	 */
	protected function whenExact($arg)
	{
		$this->assertNotInElseClause('Missing case result for \'else\' clause.');
		$this->assertNoExistingConditions('if() cannot be followed by more arguments');
		$this->assertLastCaseIsNotDefault('Cannot have another case following the default case.');
		$this->assertNotDefaultCase('Default case can only have one argument.');
		$this->args[] = new PatternArgExact($arg);
		return $this;
	}

	/**
	 * Add a pattern argument to the current match case.
	 *
	 * @param  array $args The arguments to build a pattern.
	 * @return $this
	 * @throws MatchBuilderException Thrown if preconditions aren't met.
	 */
	protected function whenPattern(array $args)
	{
		$this->assertNotInElseClause('Missing case result for \'else\' clause.');
		$this->assertNoExistingConditions('if() cannot be followed by more arguments');
		$this->assertLastCaseIsNotDefault('Cannot have another case following the default case.');
		$this->assertNotDefaultCase('Default case can only only have one argument.');

		if (empty($args)) {
			$this->assertNoExistingArgs('Missing pattern argument.');
			$this->args[] = null;
		} else {
			$this->args[] = new PatternArg($args);
		}
		return $this;
	}

	/**
	 * Finish building the current match case by adding it to the cases list
	 * and resetting the argument and condition lists for the next case.
	 *
	 * @param  CaseResultable $caseResult
	 * @return void
	 * @throws MatchBuilderException Thrown if a precondition is not met.
	 */
	protected function endCase($caseResult)
	{
		$this->assertHasArgs();

		if ($this->elseClause === true) {
			$this->elseClause = $caseResult;
		} else {
			$case = new MatchCase();
			$case->args = $this->args;
			$case->conditions = $this->conditions;
			$case->result = $caseResult;

			if ($this->elseClause instanceof CaseResultable) {
				$case->elseResult = $this->elseClause;
			}

			$this->cases[] = $case;
			$this->args = [];
			$this->conditions = [];
			$this->elseClause = false;
		}
	}

	/**
	 * Assert that we're not currently in an 'else' clause.
	 *
	 * @param  string $message
	 * @return void
	 * @throws MatchBuilderException Thrown if currently in an 'else' clause.
	 */
	protected function assertNotInElseClause($message = 'Expected case result in else clause.')
	{
		if ($this->elseClause === true) {
			throw new MatchBuilderException($message);
		}
	}

	/**
	 * Assert that the current match case is not the default case.
	 *
	 * @param  string $message Exception message.
	 * @return void
	 * @throws MatchBuilderException Thrown if assertion isn't met.
	 */
	protected function assertNotDefaultCase($message)
	{
		if (!empty($this->args) && \end($this->args) === null) {
			throw new MatchBuilderException($message);
		}
	}

	/**
	 * Assert that the last match case was not a default case.
	 *
	 * @param  string $message Exception message.
	 * @return void
	 * @throws MatchBuilderException Thrown if assertion isn't met.
	 */
	protected function assertLastCaseIsNotDefault($message)
	{
		if (!empty($this->cases) && \end($this->cases)->args[0] === null) {
			throw new MatchBuilderException($message);
		}
	}

	/**
	 * Assert that the current match case has at least one argument.
	 *
	 * @param  string $message Exception message.
	 * @return void
	 * @throws MatchBuilderException Thrown if assertion isn't met.
	 */
	protected function assertHasArgs($message = 'Match case must have at least one argument.')
	{
		if (empty($this->args)) {
			throw new MatchBuilderException($message);
		}
	}

	/**
	 * Assert that there are no incomplete case arguments.
	 *
	 * @param  string $message Exception message.
	 * @return void
	 * @throws MatchBuilderException Thrown if assertion isn't met.
	 */
	protected function assertNoExistingArgs($message)
	{
		if (!empty($this->args)) {
			throw new MatchBuilderException($message);
		}
	}

	/**
	 * Assert that conditions exist for the current case.
	 *
	 * @param  string $message
	 * @return void
	 * @throws MatchBuilderException Thrown if assertion isn't met.
	 */
	protected function assertHasConditions($message)
	{
		if (empty($this->conditions)) {
			throw new MatchBuilderException($message);
		}
	}

	/**
	 * Assert that no existing conditions exist for the current case.
	 *
	 * @param  string $message
	 * @return void
	 * @throws MatchBuilderException Thrown if assertion isn't met.
	 */
	protected function assertNoExistingConditions($message)
	{
		if (!empty($this->conditions)) {
			throw new MatchBuilderException($message);
		}
	}

	/**
	 * Assert that there are no incomplete case arguments.
	 *
	 * @param  string $message Exception message.
	 * @return void
	 * @throws MatchBuilderException Thrown if assertion isn't met.
	 */
	protected function assertHasCases($message)
	{
		if (empty($this->cases)) {
			throw new MatchBuilderException($message);
		}
	}
}
