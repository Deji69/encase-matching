<?php
namespace Encase\Matching\Patterns;

use Encase\Matching\Exceptions\MatchBuilderException;

class ListPattern extends Pattern
{
	/** @var Pattern[] */
	protected $list;

	/** @var int */
	protected $patternsLeftOfRest;

	public function __construct($list, $patternsLeftOfRest = 0)
	{
		$this->list = $list;
		$this->patternsLeftOfRest = $patternsLeftOfRest;
	}

	public function match($value)
	{
		$captures = [];
		$value = (array)$value;

		$argIt = new \ArrayIterator($value);

		foreach ($this->list as $pattern) {
			if (!$argIt->valid()) {
				return false;
			}

			$capture = $pattern->matchIterator($argIt, $this->patternsLeftOfRest);

			if ($capture === false) {
				return false;
			}

			if (\is_array($capture)) {
				$captures = \array_merge($captures, $capture);
			}
		}

		// invalidate if there are unmatched elements left in the list
		if ($argIt->valid()) {
			return false;
		}

		return $captures;
	}
}
