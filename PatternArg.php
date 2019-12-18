<?php
namespace Encase\Matching;

class PatternArg
{
	public $args;

	public function __construct($args)
	{
		$this->args = (array)$args;
	}
}
