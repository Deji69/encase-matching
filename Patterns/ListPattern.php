<?php
namespace Encase\Matching\Patterns;

use Encase\Matching\KeyMatchable;
use Encase\Matching\MultiMatchable;

class ListPattern extends Pattern
{
	/** @var Pattern[] */
	protected $list;

	/** @var int */
	protected $leaveRightOfRest;

	/** @var string[] */
	protected $bindNames;

	/**
	 * @param array $list
	 * @param int $leaveRightOfRest
	 * @param string[] $bindNames
	 */
	public function __construct($list, $leaveRightOfRest = 0, $bindNames = [])
	{
		$this->list = $list;
		$this->bindNames = $bindNames;
		$this->leaveRightOfRest = $leaveRightOfRest;
	}

	/**
	 * @inheritDoc
	 */
	public function matchValue($value, array $captures = [])
	{
		$captures = [];
		$value = (array)$value;

		$argIt = new \ArrayIterator($value);
		$isMapped = false;

		foreach ($this->list as &$pattern) {
			$mapByKey = $pattern instanceof KeyMatchable;

			if (!$argIt->valid() && !$mapByKey) {
				return false;
			}

			if ($mapByKey) {
				$isMapped = true;
				$result = $pattern->match($value, $captures);
			} elseif ($pattern instanceof MultiMatchable) {
				$result = $pattern->matchIterator($argIt, $captures, $this->leaveRightOfRest);
			} else {
				$result = $pattern->match($argIt->current(), $captures);

				if ($result !== false) {
					$argIt->next();
				}
			}

			if ($result === false) {
				return false;
			}

			if (\is_array($result)) {
				$captures = \array_merge($captures, $result);
			}
		}

		// invalidate if there are unmatched elements left in the list
		if ($argIt->valid() && !$isMapped) {
			return false;
		}

		return $captures;
	}

	public function getBindNames(): array
	{
		return $this->bindNames;
	}
}
