<?php
namespace Encase\Matching\Patterns;

use Encase\Regex\Patternable as RegexPatternable;

use function Encase\Functional\accumulate;
use function Encase\Functional\isIndexedArray;

class RegexPattern extends Pattern
{
	/**
	 * Construct a Regex pattern.
	 *
	 * @param \Encase\Regex\Patternable $pattern
	 */
	public function __construct(RegexPatternable $pattern)
	{
		parent::__construct($pattern->getPattern());
	}

	public function match($value)
	{
		$matches = [];

		if (\is_string($value)) {
			if (\preg_match($this->value, $value, $matches)) {
				if (isIndexedArray($matches)) {
					return [$matches];
				}
				return accumulate($matches, [], function ($res, $val, $key) {
					if (\is_string($key)) {
						$res[$key] = $val;
					}
					return $res;
				});
			}
		}
		return false;
	}
}
