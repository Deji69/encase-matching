<?php
namespace Encase\Matching;

use Encase\Functional\Func;
use Encase\Matching\CaseResultable;

class CaseCall implements CaseResultable
{
	/** @var Func */
	protected $callable;

	/** @var array|null */
	protected $bindNames = null;

	/**
	 * Construct a case call object.
	 *
	 * @param callable $callable
	 */
	public function __construct($callable)
	{
		$this->callable = Func::new($callable);
	}

	/**
	 * Get the callable object.
	 *
	 * @return Func
	 */
	public function getCallable()
	{
		return $this->callable;
	}

	/**
	 * Get the value by calling the callable.
	 *
	 * @param  Matcher $matcher
	 * @param  array   $args
	 * @param  mixed   $value
	 * @return mixed
	 */
	public function getValue($matcher, $args, $value)
	{
		if (empty($args)) {
			if ($this->callable->getNumberOfRequiredParameters() > 0) {
				return ($this->callable)($value);
			}
		}
		return ($this->callable)(...$args);
	}

	/**
	 * @return string[]
	 */
	public function getBindNames()
	{
		if ($this->bindNames === null) {
			$this->bindNames = [];

			foreach ($this->callable->getReflection()->getParameters() as $param) {
				$this->bindNames[] = $param->getName();
			}
		}

		return $this->bindNames;
	}
}
