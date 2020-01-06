<?php
namespace Encase\Matching;

use Encase\Functional\Func;
use Encase\Matching\CaseResultable;
use Encase\Matching\Exceptions\MatchException;
use TypeError;

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
		return $this->callFunction($args, $value);
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

	protected function callFunction($args, $value)
	{
		try {
			if (empty($args)) {
				if ($this->callable->getNumberOfRequiredParameters() > 0) {
					return ($this->callable)($value);
				}
			}
			return ($this->callable)(...$args);
		} catch (TypeError $e) {
			throw new MatchException('Invalid arg type in case call result.', 0, $e);
		}
	}
}
