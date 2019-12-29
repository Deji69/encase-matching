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

	public function __construct($fn)
	{
		$this->func = Func::new($fn);
	}

	public function matchValue($value, $captures = [])
	{
		if (!isset($this->captureMapCache)) {
			$this->captureMapCache = Matcher::getParamArgMappingForCall(
				$this->getBindNames(),
				$captures
			);
		}

		$args = Matcher::mapCapturesToArgs($this->captureMapCache, $captures);

		if (empty($args)) {
			return ($this->func)($value);
		}

		return ($this->func)(...$args);
	}

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
}
