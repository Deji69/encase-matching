<?php
namespace Encase\Matching;

interface MultiMatchable
{
	/**
	 * Match as many arguments from the iterator as possible.
	 *
	 * @param  \ArrayIterator $argIt  Argument iterator to increment per match.
	 * @param  array $captures  Array of existing captures which can be used
	 *                         in pattern matching.
	 * @param  int $leave  Min number of arguments to leave unmatched at the end.
	 * @return bool|array  FALSE if the pattern(s) didn't match, TRUE or array
	 *                     containing captures if they did match match.
	 */
	public function matchIterator(\ArrayIterator $argIt, array $captures = [], int $leave = 0);
}
