<?php
namespace Encase\Matching\Patterns;

class RestPattern extends Pattern
{
	public function matchArgs($argIt)
	{
		if ($this->bindName) {
			$args = [];

			do {
				$args[] = $argIt->current();
				$argIt->next();
			} while ($argIt->valid());

			return [$this->bindName => $args];
		}

		$argIt->seek($argIt->count() - 1);
		return true;
	}

	public function match($value)
	{
		return true;
	}
}
