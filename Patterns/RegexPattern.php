<?php
namespace Encase\Matching\Patterns;

use function Encase\Functional\reduce;
use function Encase\Functional\isIndexedArray;
use Encase\Regex\Patternable as RegexPatternable;

class RegexPattern extends Pattern
{
	/** @var string */
	protected $pattern;

	/**
	 * Construct a Regex pattern.
	 *
	 * @param \Encase\Regex\Patternable $pattern
	 */
	public function __construct(RegexPatternable $pattern)
	{
		$this->pattern = $pattern->getPattern();
	}

	/**
	 * @inheritDoc
	 */
	public function matchValue($value, array $captures = [])
	{
		$matches = [];

		if (\is_string($value)) {
			if (\preg_match($this->pattern, $value, $matches)) {
				if (isIndexedArray($matches)) {
					return [$matches];
				}
				return reduce($matches, [], function ($res, $val, $key) {
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
