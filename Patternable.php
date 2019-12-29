<?php
namespace Encase\Matching;

interface Patternable
{
	/**
	 * Get/create the pattern with the given bindable names.
	 *
	 * @param  string[] $bindNames
	 * @return Matchable
	 */
	public function getPattern(array $bindNames = []): Matchable;
}
