<?php
namespace Encase\Matching;

use Closure;
use TypeError;
use function Encase\Functional\map;
use function Encase\Functional\each;
use function Encase\Functional\union;
use function Encase\Functional\reduce;
use Encase\Matching\Exceptions\MatchException;
use Encase\Matching\Exceptions\PatternException;
use Encase\Matching\Exceptions\MatchCaseException;
use Encase\Matching\Exceptions\DestructureException;

class Matcher implements CaseResultable, Matchable
{
	/** @var MatchCase[] */
	protected $cases = [];

	/** @var string[]|null */
	protected $bindNameCache = null;

	/**
	 * Build up the cases for the matcher. Leave patterns unbuilt to allow them
	 * to be built lazily.
	 *
	 * @param MatchCase[] $cases An array containing at least one MatchCase.
	 */
	public function __construct($cases)
	{
		if (empty($cases)) {
			throw new PatternException('Matcher must have at least one case.');
		}

		foreach ($cases as $patternKey => &$result) {
			$caseResult = (function () use (&$result) {
				if ($result instanceof Closure) {
					return new CaseCall($result);
				} elseif (\is_array($result)) {
					return new Matcher($result);
				}
				return new CaseValue($result);
			})();

			$this->cases[] = new MatchCase(
				$this,
				WhenRepository::get($patternKey) ?? $patternKey,
				$caseResult
			);
		}
	}

	/**
	 * Invoke `$this->match()` with the given arguments.
	 *
	 * @param  mixed $arg
	 * @param  array $captures
	 * @return mixed
	 */
	public function __invoke($arg, array $captures = [])
	{
		return $this->match($arg, $captures);
	}

	/**
	 * Match the argument to a pattern case and get the result.
	 *
	 * @inheritDoc
	 * @return mixed Returns the result of the matching case.
	 * @throws \Encase\Matching\Exceptions\MatchException
	 *         Thrown if no case matched the argument.
	 */
	public function match($arg, array $captures = ['@' => []])
	{
		$errors = [];
		$resultErrors = [];

		foreach ($this->cases as $caseKey => $case) {
			$result = false;

			try {
				$result = $case->match($arg, $captures);
			} catch (DestructureException|MatchCaseException|TypeError $e) {
				$errors[$caseKey] = $e;
			}

			if ($result !== false) {
				if (\is_array($result)) {
					$captures = \array_merge($captures, $result);
				}
				try {
					return $case->getValue($this, $captures, $arg);
				} catch (DestructureException|MatchCaseException|TypeError $e) {
					$resultErrors[$caseKey] = $e;
				} catch (MatchException $e) {
					$resultErrors[$caseKey] = $e;
				}
			}
		}

		$cases = map($this->cases, fn($case, $i) => [
			'case' => $case,
			'error' => $errors[$i] ?? null,
			'resultError' => $resultErrors[$i] ?? null,
		]);

		throw MatchException::new($arg, $cases);
	}

	public function getBindNames(): array
	{
		$this->bindNameCache ??= reduce(
			$this->cases,
			fn($array, $case) => union($array, $case->getBindNames()),
			[]
		);
		return $this->bindNameCache;
	}

	public function getValue(Matcher $matcher, array $captures, $value)
	{
		return $this->match($value, $captures);
	}

	/**
	 * Use the map of params to capture names to build an argument list.
	 *
	 * @param  string[] $paramArgMap
	 * @param  mixed $value
	 * @param  array $captures
	 * @return array
	 */
	public static function mapCapturesToArgs($paramArgMap, $value, $captures)
	{
		$args = map($paramArgMap, function ($param) use ($captures) {
			return $captures[$param['bindName']];
		});
		return empty($args) ? [$value] : $args;
	}

	/**
	 * @param  string[] $bindNames
	 * @param  array $captures
	 * @return array
	 */
	public static function getParamArgMappingForCall($bindNames, $captures)
	{
		$i = 0;
		$params = [];

		each(
			$bindNames,
			function ($bindName) use ($captures, &$params, &$i) {
				if (empty($bindName)) {
					return;
				}

				if (isset($captures[$bindName])) {
					$params[] = [
						'bindName' => $bindName
					];
					return;
				}

				if (!isset($captures[$i])) {
					return false;
				}

				$params[] = [
					'bindName' => $i++
				];
			}
		);
		return $params;
	}
}
