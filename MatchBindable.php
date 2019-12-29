<?php
namespace Encase\Matching;

interface MatchBindable
{
	/**
	 * Get the name with which to bind values matched by this pattern.
	 *
	 * @return string
	 */
	public function getBindName(): string;

	/**
	 * Set the name with which to bindvalues matched by this pattern.
	 *
	 * @param  string $bindName
	 * @return void
	 */
	public function setBindName(?string $bindName);
}
