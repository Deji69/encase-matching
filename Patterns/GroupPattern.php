<?php
namespace Encase\Matching\Patterns;

class GroupPattern implements Patternable
{
	/** @var string */
	protected $connective;

	/** @var Patternable[] */
	protected $patterns = [];

	public function __construct($patterns = [], $connective = 'and')
	{
		$this->patterns = $patterns;
		$this->connective = $connective === 'or' ? 'or' : 'and';
	}

	public function match($argIt)
	{
		$result = true;

		if ($this->connective === 'or') {
			$connect = function ($match) use (&$result) {
				if ($match !== false) {
					$result = $match;
					return true;
				}
				return false;
			};
		} else {
			$connect = function ($match) use (&$result) {
				if ($match !== false) {
					if (\is_array($match)) {
						if (\is_array($result)) {
							$result = \array_merge($result, $match);
						} else {
							$result = $match;
						}
					}
				}
				return false;
			};
		}

		foreach ($this->patterns as $pattern) {
			if ($connect($pattern->match($argIt))) {
				return $result;
			}
		}

		return $this->connective === 'or' ? false : $result;
	}
}
