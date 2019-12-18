<?php
namespace Encase\Matching\Patterns;

class WildcardPattern extends Pattern
{
	public function __construct($bindName = null)
	{
		parent::__construct(null, $bindName);
	}

	public function match($value)
	{
		if ($this->bindName !== null) {
			return [$this->bindName => $value];
		}
		return true;
	}
}
