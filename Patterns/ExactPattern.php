<?php
namespace Encase\Matching\Patterns;

use ArrayIterator;

class ExactPattern extends Pattern
{
	public function __construct($value)
	{
		parent::__construct($value);
	}

	public function match(ArrayIterator $argIt): bool
	{
		return $argIt->current() === $this->value;
	}
}
