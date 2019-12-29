<?php
namespace Encase\Matching\Patterns;

class ExactPattern extends Pattern
{
	/** @var mixed */
	protected $value;

	/**
	 * Create a pattern for matching the exact value.
	 *
	 * @param mixed $value
	 */
	public function __construct($value)
	{
		$this->value = $value;
	}

	/**
	 * Get the exact match value.
	 *
	 * @return mixed
	 */
	public function getValue()
	{
		return $this->value;
	}

	/**
	 * @inheritDoc
	 */
	public function matchValue($value, $bindNames = [])
	{
		return $value === $this->value;
	}
}
