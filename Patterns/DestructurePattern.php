<?php
namespace Encase\Matching\Patterns;

use Encase\Matching\At;

use function Encase\Functional\slice;

/**
 * Destructure pattern matches objects and captures properties.
 */
class DestructurePattern extends Pattern
{
	/** @var At */
	protected $at;

	/** @var string[] */
	protected $propPath;

	/** @var string|null */
	protected $var = null;

	/**
	 * Create a pattern for destructuring objects.
	 *
	 * @param At $at
	 */
	public function __construct(At &$at)
	{
		$this->at =& $at;
	}

	public function matchValue($value, array $captures = [])
	{
		$bindName = null;

		for ($i = 0; $i < $this->at->£destructureCallCount; ++$i) {
			$call = $this->at->£calls[$i];

			if ($call[0] === '__get') {
				$bindName = $call[1][0];
			}

			if (!static::destructureCall($call[0], $call[1], $value)) {
				return false;
			}
		}

		$this->at->£var = $value;

		$captures[$this->at->£bindName ?? $bindName] = $value;
		$captures['@'][] = $this->at;

		return $captures;
	}

	public static function destructureValue(At $at)
	{
		$value = $at->£var;

		for ($i = $at->£destructureCallCount; $i < \count($at->£calls); ++$i) {
			$call = $at->£calls[$i];

			if (!static::destructureCall($call[0], $call[1], $value)) {
				return false;
			}
		}

		$at->£calls = slice($at->£calls, 0, $at->£destructureCallCount);
		$at->£var = $value;
		return true;
	}

	public static function destructureCall($methodName, $args, &$value)
	{
		$getArrayElement = function () use ($args, &$value) {
			if (!$value instanceof \ArrayAccess || !$value->offsetExists($args[0])) {
				if (!\is_array($value) || !\array_key_exists($args[0], $value)) {
					return false;
				}
			}

			$value = $value[$args[0]];
			return true;
		};

		switch ($methodName) {
			case '__get': {
				if (!\is_object($value)) {
					if (\is_array($value)) {
						if ($getArrayElement()) {
							break;
						}
					}
					return false;
				}

				if (!\property_exists($value, $args[0])
				 && !\method_exists($value, '__get')) {
					return false;
				}

				$value = $value->{$args[0]} ?? null;
				break;
			}
			case 'offsetGet': {
				if ($getArrayElement()) {
					break;
				}
				return false;
			}
			case '__call':
			default: {
				if (!\is_object($value)) {
					return false;
				}

				if (!\method_exists($value, $args[0])
				 && !\method_exists($value, '__call')) {
					return false;
				}

				$value = $value->{$args[0]}(...$args[1]);
				break;
			}
		}
		return true;
	}
}
