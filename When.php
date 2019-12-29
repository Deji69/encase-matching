<?php
namespace Encase\Matching;

use Encase\Matching\Patternable;
use Encase\Matching\Patterns\Pattern;

class When implements Patternable
{
	/**
	 * The pattern to check for.
	 *
	 * @var Pattern|null
	 */
	public $pattern = null;

	/** @var array */
	public $args = [];

	/**
	 * Create a When object with a pattern to check for.
	 *
	 * @param array $args
	 */
	public function __construct(array $args)
	{
		$this->args = $args;
	}

	/**
	 * Get the built pattern.
	 *
	 * @param  string[] $bindNames
	 * @return Pattern
	 */
	public function getPattern($bindNames = []): Pattern
	{
		$this->pattern ??= PatternBuilder::buildArgs($this->args, $bindNames);
		return $this->pattern;
	}
}
