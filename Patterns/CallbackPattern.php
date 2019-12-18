<?php
namespace Encase\Matching\Patterns;

class CallbackPattern extends Pattern
{
	public function __construct($fn)
	{
		parent::__construct($fn);
	}

	public function match($value)
	{
		return ($this->value)($value);
	}
}
