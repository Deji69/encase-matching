<?php
namespace Encase\Matching\Patterns;

class ArrayPattern extends Pattern
{
	/** @var array */
	protected $array = [];

	public function __construct($array = [])
	{
		$this->array = $array;
	}

	public function match($array)
	{
		$patterns = [];
		$exactPatterns = [];

		foreach ($this->array as $key => $value) {
			$patterns[] = Pattern::new($key)->match();
		}
	}
}
