<?php
namespace Encase\Matching;

use Encase\Matching\Patterns\Pattern;
use Encase\Matching\Patterns\WildcardPattern;

class Key implements Patternable
{
	/** @var array */
	public $keyArgs = [];

	/** @var string|null */
	public $bindName = null;

	/** @var Pattern|null */
	public $keyPattern = null;

	/**
	 * @param array $keyArgs
	 * @param string|null $bindName
	 */
	public function __construct(array $keyArgs, string $bindName = null)
	{
		$this->keyArgs = $keyArgs;
		$this->bindName = $bindName;
	}

	/**
	 * Get the `AssocPattern` for the key.
	 *
	 * @param  string[] $bindNames
	 * @return Pattern|null
	 */
	public function getPattern(array $bindNames = []): Pattern
	{
		if ($this->keyPattern === null) {
			if (empty($this->keyArgs)) {
				$this->keyPattern = new WildcardPattern($this->bindName);
			} else {
				$this->keyPattern = PatternBuilder::buildArgs(
					$this->keyArgs,
					$bindNames
				);
				$this->keyPattern->setBindName($this->bindName);
			}
		}

		return $this->keyPattern;
	}

	public static function __callStatic($name, $args)
	{
		$key = new self($args, $name);
		return KeyRepository::add($key);
	}
}
