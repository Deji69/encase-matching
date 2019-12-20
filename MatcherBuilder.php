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
	 * Holds the pattern argument for the current case.
	 *
	 * @var CaseArg|null
	 */
	protected $arg = null;

	/**
	 * Indicates an arg has been provided.
	 *
	 * @var bool
	 */
	protected $hasArg = false;

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
	 * Set to `TRUE` when `continue` is used. Should be followed by a case result
	 *
	 * @var bool
	 */
	protected $continueClause = false;

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
		if ($this->hasArg) {
			if ($value instanceof \Closure) {
				$this->endCase(new CaseCall($value));
			} else {
				$this->endCase(new CaseValue($value));
			}
			return $this;
		}
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
		$this->assertHasArg();
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
		$this->assertHasArg('Match case must have at least one argument.');
		$this->endCase(new CaseArg($binding));
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
		$this->continueClause = new CaseContinue(\func_get_args());
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
		$this->assertNoExistingArg('Incomplete match case.');
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
		$this->arg = new PatternArgExact($arg);
		$this->hasArg = true;
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
			$this->assertNoExistingArg('Missing pattern argument.');
			$this->arg = null;
			$this->hasArg = true;
		} else {
			$this->arg = new PatternArg($args);
			$this->hasArg = true;
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
		$this->assertHasArg();

		if ($this->elseClause === true) {
			$this->elseClause = $caseResult;
		} else {
			$case = new MatchCase();
			$case->arg = $this->arg;
			$case->conditions = $this->conditions;
			$case->result = $caseResult;

			if ($this->elseClause instanceof CaseResultable) {
				$case->elseResult = $this->elseClause;
			}

			$this->cases[] = $case;
			$this->arg = null;
			$this->hasArg = false;
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
		if ($this->hasArg && $this->arg === null) {
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
		if (!empty($this->cases) && \end($this->cases)->arg === null) {
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
	protected function assertHasArg($message = 'Match case must have at least one argument.')
	{
		if (!$this->hasArg) {
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
	protected function assertNoExistingArg($message)
	{
		if ($this->hasArg) {
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
