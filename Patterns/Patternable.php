<?php
namespace Encase\Matching\Patterns;

interface Patternable
{
	/**
	 * Match an argument to the pattern.
	 *
	 * @param  mixed $value Value to match with.
	 * @return bool|array  FALSE if the pattern doesn't match, TRUE or array
	 *                     containing bindings if it does match.
	 */
	public function match($value);
}
