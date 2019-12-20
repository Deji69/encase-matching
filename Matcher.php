<?php
namespace Encase\Matching;

use ArrayObject;
use ReflectionFunction;
use ReflectionParameter;
use function Encase\Functional\map;
use function Encase\Functional\each;
use Encase\Matching\Patterns\Pattern;
use Encase\Matching\Patterns\Patternable;
use Encase\Matching\Exceptions\MatchException;
use Encase\Matching\Exceptions\PatternException;

class Matcher
{
	/** @var MatchCase[] */
	protected $cases = [];

	/**
	 * @param MatchCase[] $cases An array containing at least one MatchCase.
	 */
	public function __construct($cases)
	{
		if (empty($cases)) {
			throw new PatternException('Matcher must have at least one case.');
		}

		$this->cases = $cases;
	}

	/**
	 * Invoke `$this->match()` with the given arguments.
	 *
	 * @param  mixed ...$args
	 * @return mixed
	 */
	public function __invoke(...$args)
	{
		return $this->match(...$args);
	}

	/**
	 * Match the given arguments.
	 *
	 * @param  mixed ...$args
	 * @return mixed
	 * @throws \Encase\Matching\Exceptions\MatchException
	 *         Thrown if no case matched the arguments.
	 */
	public function match($arg)
	{
		foreach ($this->cases as $case) {
			$captures = [];
			$pattern = &$case->arg;
			$result = $pattern !== null ? self::matchArg($case->arg, $arg) : true;

			if ($result !== false) {
				if (\is_array($result)) {
					$captures = $result;
					$result = true;
				}

				if (!empty($case->conditions)) {
					if (self::checkConditions($case->conditions, $captures) === false) {
						if (!$case->elseResult) {
							continue;
						}
					}
				}
				return $case->getValue($this, $result, $captures);
			}
		}

		throw new MatchException('No cases matched the arguments.');
	}

	/**
	 * Undocumented function
	 *
	 * @param  Pattern|PatternArg $patternArg
	 * @param  mixed $arg
	 * @return bool|array
	 */
	protected static function matchArg(&$patternArg, $arg)
	{
		if ($patternArg instanceof Patternable) {
			return $patternArg->match($arg);
		}

		// Replace the PatternArg with the built pattern in order to save time
		// should we call upon this Matcher again.
		$patternArg = PatternBuilder::build($patternArg);
		return $patternArg->match($arg);
	}

	public static function mapCapturesToArgs($paramArgMap, $captures)
	{
		return map($paramArgMap, function ($parameter) use ($captures) {
			return $captures[$parameter];
		});
	}

	protected static function checkConditions(&$conditions, $captures)
	{
		return each($conditions, function (&$condition) use ($captures) {
			if (!\is_array($condition)) {
				$condition = [
					self::getParamArgMappingForCall(
						$condition,
						$captures
					),
					$condition
				];
			}

			$args = self::mapCapturesToArgs(
				$condition[0],
				$captures
			);

			if (!$condition[1](...$args)) {
				return false;
			}
		});
	}

	public static function getParamArgMappingForCall($func, $captures)
	{
		$refl = new ReflectionFunction($func);
		$i = 0;
		$params = [];
		$reflParams = $refl->getParameters();

		each(
			$reflParams,
			function ($parameter) use ($captures, &$params, &$i) {
				/** @var ReflectionParameter $parameter */
				if (isset($captures[$parameter->getName()])) {
					$params[] = $parameter->getName();
					return;
				}
				if (!isset($captures[$i])) {
					return false;
				}
				$params[] = $i++;
			}
		);
		return $params;
	}

	/**
	 * Undocumented function
	 *
	 * @param  string $str
	 * @return array
	 */
	public static function parseBindingString($str)
	{
		$seperated = explode(',', $str);
		$args = [];
		$argOffsets = [];

		foreach ($seperated as $arg) {
			$offsets = [];

			if ($bracketPos = \strpos($arg, '[')) {
				$subscript = \substr($arg, $bracketPos);
				$subscript = \preg_replace('/\[(\d+|\$?\w[\w\d]*)\]/', '$1.', $subscript);
				$offsets = \explode('.', $subscript);

				if (!empty($offsets)) {
					\array_pop($offsets);

					$arg = \substr($arg, 0, $bracketPos);
				}
			}

			$args[] = $arg;
			$argOffsets[] = $offsets;
		}

		return [
			'args' => $args,
			'offsets' => $argOffsets
		];
	}

	/**
	 * Undocumented function
	 *
	 * @param  string[] $args
	 * @param  array $argOffsets
	 * @param  array $captures
	 * @return mixed
	 */
	public static function resolveCallBindings($args, $argOffsets, $captures)
	{
		$array = [];

		$offsetArray = new ArrayObject($argOffsets);
		$offsetIt = $offsetArray->getIterator();

		foreach ($args as $arg) {
			$offsets = $offsetIt->current();
			$offsetIt->next();

			if (empty($arg)) {
				continue;
			}

			$arg = $captures[$arg];

			foreach ($offsets as $offset) {
				if ($offset[0] === '$') {
					$arg = $arg[$captures[\substr($offset, 1)]];
				} else {
					$arg = $arg[$offset];
				}
			}

			if (\is_array($arg)) {
				$array = \array_merge($array, $arg);
			} else {
				$array[] = $arg;
			}
		}

		if (\count($args) <= 1) {
			return empty($array) ? null : $array[0];
		}

		return $array;
	}
}
