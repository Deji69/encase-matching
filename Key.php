<?php
namespace Encase\Matching;

use Encase\Matching\Patterns\Pattern;
use Encase\Matching\Patterns\AssocPattern;

class Key implements Patternable
{
	/** @var array */
	public $keyArgs = [];

	/** @var array|null */
	public $valArgs = null;

	/** @var string|null */
	public $bindName = null;

	/** @var Patternable|null */
	public $keyPattern = null;

	/** @var Patternable|null */
	public $valPattern = null;

	/**
	 * @param array $keyArgs
	 * @param array|null $valArgs
	 * @param string|null $bindName
	 */
	public function __construct(array $keyArgs, array $valArgs = null, string $bindName = null)
	{
		$this->keyArgs = $keyArgs;
		$this->valArgs = $valArgs;
		$this->bindName = $bindName;
	}

	/**
	 * Get the `AssocPattern` for the key.
	 *
	 * @param  string[] $bindNames
	 * @return AssocPattern
	 */
	public function getPattern(array $bindNames = []): AssocPattern
	{
		if ($this->keyPattern === null) {
			$this->keyPattern = PatternBuilder::buildArgs(
				$this->keyArgs,
				$bindNames
			);
			$this->keyPattern->setBindName($this->bindName);
		}

		if ($this->valPattern === null && !empty($this->valArgs)) {
			$this->valPattern = PatternBuilder::buildArgs(
				$this->valArgs,
				$bindNames
			);
		}

		return new AssocPattern($this->keyPattern, $this->valPattern);
	}
}
