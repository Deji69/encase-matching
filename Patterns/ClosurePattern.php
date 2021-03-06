<?php
namespace Encase\Matching\Patterns;

use TypeError;
use Encase\Functional\Func;
use Encase\Matching\Matcher;
use Encase\Matching\Exceptions\MatchException;

class ClosurePattern extends Pattern
{
	/** @var Func */
	protected $func;

	/** @var string[]|null */
	protected $bindNameCache = null;

	/** @var array|null */
	protected $captureMapCache = null;

	/**
	 * Construct a pattern to call a function and match based on the result.
	 *
	 * @param Func $fn
	 */
	public function __construct($fn)
	{
		$this->func = Func::new($fn);
	}

	/**
	 * @inheritDoc
	 */
	public function matchValue($value, $captures = [])
	{
		$args = Matcher::mapCapturesToArgs($this->getCaptureMap($captures), $value, $captures);
		return $this->callFunction($args);
	}

	public function getFunction(): Func
	{
		return $this->func;
	}

	/**
	 * @inheritDoc
	 */
	public function getBindNames(): array
	{
		if ($this->bindNameCache === null) {
			$refl = $this->func->getReflection();

			$this->bindNameCache = [];

			foreach ($refl->getParameters() as $param) {
				$this->bindNameCache[] = $param->getName();
			}
		}

		return $this->bindNameCache;
	}

	/**
	 * Call the function.
	 *
	 * @param  array $args
	 * @return bool
	 */
	protected function callFunction($args)
	{
		$this->func = new Func(clone $this->func->get());
		return (bool)($this->func)(...$args);
		try {
		} catch (TypeError $e) {
			$message = $e->getMessage();
			$pos = \strpos($message, 'given, called in');
			$message = \substr($message, 0, $pos !== false ? $pos + 5 : null);
			throw new MatchException(
				'Invalid arg type in call pattern: '.$message,
				0,
				$e
			);
		}
	}

	/**
	 * Get the map for converting capture arrays to arg lists.
	 *
	 * @param  array $captures
	 * @return array
	 */
	protected function getCaptureMap(array $captures): array
	{
		if (!isset($this->captureMapCache)) {
			$this->captureMapCache = Matcher::getParamArgMappingForCall(
				$this->getBindNames(),
				$captures
			);
		}
		return $this->captureMapCache;
	}
}
