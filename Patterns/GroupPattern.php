<?php
namespace Encase\Matching\Patterns;

use Encase\Matching\Matchable;
use Encase\Matching\PatternBuilder;

class GroupPattern extends Pattern
{
	/** @var string */
	protected $connective;

	/** @var (Matchable|mixed)[] */
	protected $patterns = [];

	/** @var bool */
	protected $patternsAreExact = false;

	public function __construct($patterns = [], $connective = 'and')
	{
		$this->connective = $connective === 'or' ? 'or' : 'and';

		$exactValues = [];
		$builtPatterns = [];

		foreach ($patterns as $pattern) {
			if (!\is_object($pattern)) {
				$exactValues[] = $pattern;
			} else {
				if (!empty($exactValues)) {
					if (\count($exactValues) > 1) {
						$builtPatterns[] = new static($exactValues, $this->connective);
					} else {
						$builtPatterns[] = $exactValues[0];
					}
				}

				$builtPatterns[] = $pattern;
			}
		}

		if (!empty($exactValues)) {
			if (empty($builtPatterns) && $this->connective === 'or') {
				$builtPatterns = $exactValues;
				$this->patternsAreExact = true;
			} else {
				$builtPatterns[] = new static($exactValues, $this->connective);
			}
		}

		$this->patterns = $builtPatterns;
	}

	public function getConnective(): string
	{
		return $this->connective;
	}

	public function getPatterns(): array
	{
		return $this->patterns;
	}

	/**
	 * @inheritDoc
	 */
	public function matchValue($value, array $captures = [])
	{
		$result = true;

		if ($this->connective === 'or') {
			if ($this->patternsAreExact) {
				return \in_array($value, $this->patterns);
			}

			$connect = function ($match) use (&$result) {
				$result = $match;
				return $match !== false;
			};
		} else {
			$connect = function ($match) use (&$result, &$captures) {
				if (\is_array($match)) {
					if (\is_array($result)) {
						$result = \array_merge($result, $match);
					} else {
						$result = $match;
					}

					$captures = \array_merge($captures, $result);
				}
				return $match !== false;
			};
		}

		foreach ($this->patterns as &$pattern) {
			if (!$pattern instanceof Matchable) {
				$pattern = PatternBuilder::buildArg($pattern, $this->getBindNames());
			}

			if ($connect($pattern->match($value, $captures))) {
				if ($this->connective === 'or') {
					return $result;
				}
			} elseif ($this->connective === 'and') {
				return false;
			}
		}

		return $this->connective === 'or' ? false : $result;
	}
}
