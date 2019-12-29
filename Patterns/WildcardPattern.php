<?php
namespace Encase\Matching\Patterns;

class WildcardPattern extends Pattern
{
	public function __construct($bindName = null)
	{
		parent::__construct($bindName);
	}

	public function matchValue($value, array $bindNames = [])
	{
		return true;
	}
}
