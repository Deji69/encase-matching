<?php
namespace Encase\Matching\Patterns;

use ArrayIterator;

class BoolPattern extends Pattern
{
	public function __construct(bool $value)
	{
		parent::__construct($value);
	}

	public function match(ArrayIterator $argIterator)
	{
		return (bool)$argIterator->current() == $this->value;
	}
}
