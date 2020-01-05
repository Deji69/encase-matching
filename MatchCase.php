<?php
namespace Encase\Matching;

use Encase\Matching\Patternable;
use function Encase\Functional\slice;
use function Encase\Functional\union;
use Encase\Matching\Exceptions\MatchException;
use Encase\Matching\Patterns\DestructurePattern;

class MatchCase
{
	/** @var Patternable|string */
	public $pattern;

	/** @var CaseResultable|array|null */
	public $result = null;

	/** @var Matcher */
	protected $matcher;

	/** @var array */
	protected $argNames = [];

	/** @var array */
	protected $resultCaptureMapCache = [];

	/** @var array|null */
	protected $bindNameCache = null;

	/**
	 * @param Patternable|string $pattern
	 * @param CaseResultable|array|null $result
	 */
	public function __construct(Matcher $matcher, $pattern, $result)
	{
		$this->matcher = $matcher;
		$this->pattern = $pattern;
		$this->result = $result;
	}

	/**
	 * Match the value with the pattern.
	 *
	 * @param  mixed $value
	 * @param  string[] $bindNames
	 * @return array|bool
	 */
	public function match($value, array $captures = [])
	{
		$pattern = $this->getPattern();
		$result = $pattern->match($value, $captures);
		return $result;
	}

	/**
	 * Get the pattern object.
	 *
	 * @return Matchable
	 */
	public function getPattern()
	{
		if (!$this->pattern instanceof Matchable) {
			$bindNames = $this->getResultBindNames();

			if ($this->pattern instanceof Patternable) {
				$this->pattern = $this->pattern->getPattern($bindNames);
			} else {
				$this->pattern = PatternBuilder::buildArg($this->pattern, $bindNames);
			}
		}
		return $this->pattern;
	}

	/**
	 * Get the match case result value.
	 *
	 * @param  Matcher $matcher The Matcher instance.
	 * @param  array   $captures The array of captures.
	 * @param  mixed   $value The value being matched.
	 * @return mixed
	 */
	public function getValue(Matcher $matcher, array $captures, $value)
	{
		$args = [];

		if ($this->result instanceof CaseCall) {
			if (empty($this->resultCaptureMapCache)) {
				$this->resultCaptureMapCache = Matcher::getParamArgMappingForCall(
					$this->getResultBindNames(),
					$captures
				);
			}

			$args = Matcher::mapCapturesToArgs($this->resultCaptureMapCache, $value, $captures);
			$result = $this->result->getValue($matcher, $args, $value);
		} else {
			$result = $this->result->getValue($matcher, $captures, $value);
		}

		if ($result instanceof Matcher) {
			$result = $result->getValue($matcher, $captures, $value);
		}

		if ($result instanceof At) {
			$val = $result->£var;
			$count = $result->£destructureCallCount;

			for ($i = $count; $i < \count($result->£calls); ++$i) {
				$call = $result->£calls[$i];

				if (!DestructurePattern::destructureCall($call[0], $call[1], $val)) {
					$newAt = clone $result;
					$newAt->£calls = slice($newAt->£calls, $result->£destructureCallCount);
					throw new MatchException('Failed to destructure case result '.$newAt);
				}
			}
			return $val;
		}
		return $result;
	}

	/**
	 * Get the required bindings.
	 *
	 * @return string[]
	 */
	public function getBindNames()
	{
		if ($this->bindNameCache === null) {
			$bindNames = $this->getResultBindNames();
			$bindNames = union($bindNames, $this->getPatternBindNames($bindNames));
			$this->bindNameCache = $bindNames;
		}
		return $this->bindNameCache;
	}

	/**
	 * Get the binding variable names required for the pattern.
	 *
	 * @param  string[] $bindNames
	 * @return string[]
	 */
	public function getPatternBindNames($bindNames = [])
	{
		return $this->getPattern($bindNames)->getBindNames();
	}

	/**
	 * Get the binding variable names required for the result.
	 *
	 * @return string[]
	 */
	public function getResultBindNames()
	{
		if ($this->result instanceof CaseCall) {
			return $this->result->getBindNames();
		} elseif ($this->result instanceof Matcher) {
			return $this->result->getBindNames();
		}
		return [];
	}

	protected function clearBindNameCache()
	{
		$this->bindNameCache = null;
	}
}
