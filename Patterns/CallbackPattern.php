<?php
namespace Encase\Matching\Patterns;

use Encase\Functional\Func;
use Encase\Matching\Matcher;

class CallbackPattern extends Pattern
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
