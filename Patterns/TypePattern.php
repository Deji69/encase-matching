<?php
namespace Encase\Matching\Patterns;

use Encase\Functional\Type;

class TypePattern extends Pattern
{
	public function __construct(Type $type)
	{
		parent::__construct($type);
	}

	public function match($value)
	{
		return $this->value->is($value);
	}
}
