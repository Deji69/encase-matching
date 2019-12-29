<?php
namespace Encase\Matching\Patterns;

use Encase\Matching\Matchable;

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
			if (!$pattern instanceof Matchable) {
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
				if ($match !== false) {
					$result = $match;
					return true;
				}
				return false;
			};
		} else {
			$connect = function ($match) use (&$result) {
				if ($match !== false) {
					if (\is_array($match)) {
						if (\is_array($result)) {
							$result = \array_merge($result, $match);
						} else {
							$result = $match;
						}
					}
				}
				return false;
			};
		}

		foreach ($this->patterns as $pattern) {
			if ($connect($pattern->match($value))) {
				return $result;
			}
		}

		return $this->connective === 'or' ? false : $result;
	}
}
