<?php
namespace Encase\Matching;

use Encase\Matching\Patterns\Patternable;

class PatternArgExact implements Patternable
{
	public $arg;

	public function __construct($arg)
	{
		$this->arg = $arg;
	}

	public function match($value)
	{
		return $this->arg === $value;
	}
}
