<?php
namespace Encase\Matching;

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
		return $this->value;
	}
}
