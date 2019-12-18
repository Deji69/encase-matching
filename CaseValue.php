<?php
namespace Encase\Matching;

class CaseValue implements CaseResultable
{
	protected $value;

	public function __construct($value)
	{
		$this->value = $value;
	}

	public function getValue($matcher, $args)
	{
		return $this->value;
	}
}
