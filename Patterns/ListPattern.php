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

	/** @var bool */
	protected $isMapped = false;

	/**
	 * @param array $list
	 * @param int $restLeave
	 * @param string[] $binds
	 */
	public function __construct($list, $restLeave = 0, bool $isMapped = false, $binds = [])
	{
		$this->list = $list;
		$this->bindNames = $binds;
		$this->leaveRightOfRest = $restLeave;
		$this->isMapped = $isMapped;
	}

	/**
	 * @inheritDoc
	 */
	public function matchValue($value, array $captures = [])
	{
		$captures = [];
		$value = (array)$value;

		$argIt = new \ArrayIterator($value);

		foreach ($this->list as &$pattern) {
			$mapByKey = $pattern instanceof KeyMatchable;

			if (!$argIt->valid() && !$mapByKey) {
				return false;
			}

			if ($mapByKey && $this->isMapped) {
				$result = $pattern->match($value, $captures);
			} elseif ($pattern instanceof MultiMatchable) {
				$result = $pattern->matchIterator($argIt, $captures, $this->leaveRightOfRest);
			} else {
				if ($mapByKey && !$this->isMapped
				 && $pattern instanceof KeyMatchable) {
					$result = $pattern->match(
						[$argIt->key() => $argIt->current()],
						$captures
					);
				} else {
					$result = $pattern->match($argIt->current(), $captures);
				}

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
		if ($argIt->valid() && !$this->isMapped) {
			return false;
		}

		return $captures;
	}

	public function getBindNames(): array
	{
		return $this->bindNames;
	}
}
