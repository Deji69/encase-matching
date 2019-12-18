<?php
namespace Encase\Matching\Patterns;

abstract class Pattern implements Patternable
{
	/** @var mixed */
	protected $value = null;

	/** @var string|null */
	protected $bindName = null;

	/**
	 * @param string|null $bindName Variable name to capture.
	 */
	public function __construct($value = null, string $bindName = null)
	{
		$this->value = $value;
		$this->bindName = !empty($bindName) ? $bindName : null;
	}

	/**
	 * Get the binding name for the wildcard.
	 *
	 * @return string
	 */
	public function getBindName()
	{
		return $this->bindName;
	}

	/**
	 * Set the binding name for the wildcard.
	 *
	 * @param  string $bindName
	 * @return void
	 */
	public function setBindName(string $bindName)
	{
		$this->bindName = $bindName;
	}

	/**
	 * Match an argument to the pattern.
	 *
	 * @param  \ArrayIterator $argIt Will be incremented up to the last
	 *                               argument that matches.
	 * @return bool|array  FALSE if the pattern doesn't match, TRUE or array
	 *                     containing bindings if it does match.
	 */
	public function matchArgs($argIt)
	{
		$value = $argIt->current();
		return $this->match($value);
	}

	/**
	 * Create a new Pattern instance.
	 *
	 * @param  mixed ...$args
	 * @return static
	 */
	public static function new(...$args)
	{
		return new static(...$args);
	}
}
