<?php
namespace Encase\Matching\Patterns;

use Encase\Matching\MultiMatchable;

class MapPattern extends Pattern implements MultiMatchable
{
	/** @var Pattern[] */
	protected $map;

	/** @var string[] */
	protected $bindNames = [];

	/**
	 * Create a map pattern for arrays.
	 *
	 * @param array $map
	 * @param string[] $bindNames
	 */
	public function __construct(array $map, array $bindNames = [])
	{
		$this->map = $map;
		$this->bindNames = $bindNames;
	}

	public function matchValue($value, array $captures = [])
	{
		$captures = [];

		foreach ($this->map as $k => $v) {
			if ($v instanceof AssocPattern) {
			}
		}

		return empty($captures) ? true : $captures;
	}
}
