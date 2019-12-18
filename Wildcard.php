<?php
namespace Encase\Matching;

class Wildcard
{
	/** @var array */
	protected $args;

	/** @var string */
	protected $binding;

	/**
	 * Construct a Wildcard object, symbolising the intent of a placeholder.
	 *
	 * The use of this type is entirely semantic and contextual.
	 *
	 * @param mixed ...$args Arguments which may be used in context.
	 */
	public function __construct(...$args)
	{
		$this->args = $args;
	}

	/**
	 * Get the arguments used to build the wildcard.
	 *
	 * @return array
	 */
	public function getArgs()
	{
		return $this->args;
	}

	/**
	 * Get the binding name for the wildcard.
	 *
	 * @return string
	 */
	public function getBinding()
	{
		return $this->binding;
	}

	/**
	 * Set a binding name for the wildcard.
	 *
	 * @param  string $name
	 * @return $this
	 */
	public function __get($name)
	{
		$this->binding = $name;
		return $this;
	}
}
