<?php
namespace Encase\Matching\Patterns;

class ListPattern extends Pattern
{
	/** @var Pattern[] */
	protected $list;

	public function __construct($list)
	{
		$this->list = $list;
	}

	public function match($value)
	{
		$captures = [];

		$argIt = new \ArrayIterator((array)$value);

		foreach ($this->list as $pattern) {
			if (!$argIt->valid()) {
				return false;
			}

			$capture = $pattern->matchArgs($argIt);

			if ($capture === false) {
				return false;
			}

			$argIt->next();

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
