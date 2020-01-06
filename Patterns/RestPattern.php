<?php
namespace Encase\Matching\Patterns;

use ArrayIterator;
use Encase\Matching\MultiMatchable;

class RestPattern extends Pattern implements MultiMatchable
{
	/**
	 * Match as many elements of the iterator as possible.
	 *
	 * @param  \ArrayIterator $argIt Advanced with each match made.
	 * @param  int $leave Number of elements from the end to stop at.
	 * @param  array $captures Captured elements.
	 * @return bool|array
	 */
	public function matchIterator(\ArrayIterator $argIt, array $captures = [], int $leave = 0)
	{
		if ($this->bindName) {
			$args = [];

			if ($leave <= 0) {
				while ($argIt->valid()) {
					$args[] = $argIt->current();
					$argIt->next();
				}
			} else {
				$argItTemp = clone $argIt;
				$argItTemp->seek($argItTemp->count() - 1 - $leave);
				$endKey = $argItTemp->key();

				for ($i = 0; $argIt->valid(); ++$i) {
					$key = $argIt->key();
					$args[] = $argIt->current();
					$argIt->next();

					if ($key === $endKey) {
						break;
					}
				}
			}

			return [$this->bindName => $args];
		}

		$argIt->seek($argIt->count() - 1 - $leave);
		$argIt->next();
		return true;
	}

	public function matchValue($value, array $bindNames = [])
	{
		return true;
	}
}
