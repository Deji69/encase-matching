<?php
namespace Encase\Matching;

interface Matchable
{
	/**
	 * Match an argument to the pattern.
	 *
	 * @param  mixed $value Value to match with.
	 * @param  array $captures The destructured captures.
	 * @return bool|array  FALSE if the pattern didn't match, or array
	 *                     containing captures if it did match.
	 */
	public function match($value, array $captures = []);

	/**
	 * Get the list of bind names to capture for this pattern.
	 *
	 * @return string[]
	 */
	public function getBindNames(): array;
}
