<?php
namespace Encase\Matching;

use Encase\Matching\Patterns\ExactPattern;

class CaseValue implements CaseResultable
{
	protected $value;

	public function __construct(&$value)
	{
		$this->value =& $value;
	}

	/**
	 * Get the result value.
	 *
	 * @param  Matcher $matcher
	 * @param  array $args
	 * @param  mixed $value
	 * @return mixed
	 */
	public function getValue($matcher, $args, $value)
	{
		if ($this->value instanceof ExactPattern) {
			return $this->value->getValue();
		}
		return $this->value;
	}
}
