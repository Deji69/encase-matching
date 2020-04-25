<?php
namespace Encase\Matching;

use TypeError;
use Encase\Functional\Func;
use Encase\Functional\Type;
use Encase\Matching\CaseResultable;
use Encase\Matching\Exceptions\MatchException;

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
					$args = [$value];
				}
			}
			return ($this->callable)(...$args);
		} catch (TypeError $e) {
			throw new MatchException($this->getFunctionCallExceptionMessage($args), 0, $e);
		}
	}

	protected function getFunctionCallExceptionMessage($args): string
	{
		$numArgs = \count($args);
		$minNumArgs = $this->callable->getNumberOfRequiredParameters();

		if ($numArgs < $minNumArgs) {
			return "Too few arguments, $numArgs passed and $minNumArgs required.";
		}

		$refl = $this->callable->getReflection();
		$params = $refl->getParameters();

		for ($i = 0; $i < $numArgs; ++$i) {
			$param = $params[$i];
			$paramType = $param->getType();

			if ($paramType === null) {
				continue;
			}

			$arg = $args[$i];
			$argType = Type::of($arg);
			$paramPos = $i + 1;
			$paramName = $param->getName();
			$paramTypeName = $paramType->getName();
			$orNull = $param->allowsNull() ? ' or null' : '';

			if ($orNull && $argType->type === 'null') {
				continue;
			}

			$argMustBe = "Arg $paramPos (\$$paramName) must be";
			$paramTypeOrNull = "$paramTypeName$orNull";
			$annotatedArg = $argType->annotate($arg);
			$given = $argType->class !== null ? "instance of $annotatedArg given" : "$annotatedArg given";

			if ($paramType->isBuiltin()) {
				if ($argType->type !== $paramTypeName) {
					return "$argMustBe of type $paramTypeOrNull, $given";
				}
			} elseif ($argType->class !== $paramTypeName) {
				return "$argMustBe an instance of $paramTypeOrNull, $given";
			}
		}

		return 'Invalid arg type in case call result.';
	}
}
