<?php
namespace Encase\Matching;

class KeyBinder
{
	/** @var array */
	public $keyArgs;

	/** @var string|null */
	public $keyBindName = null;

	/** @var array|null */
	public $valArgs = null;

	/**
	 * Create a key binding pattern object.
	 *
	 * @param array $args
	 */
	public function __construct($args)
	{
		$this->keyArgs = $args;
	}

	/**
	 * Set the bind name.
	 *
	 * @param  string $bindName
	 * @return Key
	 */
	public function __get(string $bindName)
	{
		return new Key($this->keyArgs, $this->valArgs, $bindName);
	}

	/**
	 * Set the bind name and value pattern arguments.
	 *
	 * @param  string $bindName
	 * @param  array $args
	 * @return Key
	 */
	public function __call(string $bindName, array $args)
	{
		return new Key($this->keyArgs, $args, $bindName);
	}
}
