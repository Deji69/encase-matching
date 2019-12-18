<?php
namespace Encase\Matching;

use ArrayObject;

class CaseArg implements CaseResultable
{
	/** @var string[] */
	protected $args;

	/** @var array */
	protected $offsets = [];

	/**
	 * Construct a case result from a bound argument.
	 *
	 * @param string $arg
	 */
	public function __construct($arg)
	{
		$parsed = Matcher::parseBindingString($arg);
		$this->args = $parsed['args'];
		$this->offsets = $parsed['offsets'];
	}

	public function getValue($matcher, $captures)
	{
		return Matcher::resolveCallBindings($this->args, $this->offsets, $captures);
	}
}
