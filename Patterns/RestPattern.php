<?php
namespace Encase\Matching\Patterns;

use ArrayIterator;

class RestPattern extends Pattern
{
	/**
	 * Match as many elements of the iterator as possible.
	 *
	 * @param  \ArrayIterator $argIt Advanced with each match made.
	 * @param  int $limit Max number of elements to match.
	 * @return bool|array
	 */
	public function matchIterator($argIt, $limit = 0)
	{
		if ($this->bindName) {
			$args = [];

			if ($limit <= 0) {
				while ($argIt->valid()) {
					$args[] = $argIt->current();
					$argIt->next();
				}
			} else {
				for ($i = 0; $i < $limit && $argIt->valid(); ++$i) {
					$args[] = $argIt->current();
					$argIt->next();
				}
			}

			return [$this->bindName => $args];
		}

		$argIt->seek($argIt->count());
		return true;
	}

	public function match($value)
	{
		return true;
	}
}
