<?php
namespace Encase\Matching\Patterns;

use Encase\Matching\At;
use Encase\Matching\Exceptions\DestructureException;

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

			$value = static::destructureCall($call[0], $call[1], $value);
		}

		$this->at->£var = $value;

		$captures[$this->at->£bindName ?? $bindName] = $value;
		$captures['@'][] = $this->at;

		return $captures;
	}

	/**
	 * Destructure a value by applying a method call on the value.
	 *
	 * @param  string $methodName __get, offsetGet or non-magic method name.
	 * @param  array $args The arguments for the method call.
	 * @param  mixed $value The object, array or callable to destructure.
	 * @return mixed The result of the accessed property or function.
	 */
	public static function destructureCall($methodName, $args, $value)
	{
		$getArrayElement = function ($value, $key) {
			if (!$value instanceof \ArrayAccess || !$value->offsetExists($key)) {
				if (!\is_array($value) || !\array_key_exists($key, $value)) {
					throw new DestructureException('Failed to destructure array');
				}
			}

			return $value[$key];
		};
		$getProperty = function ($value, $name) use ($getArrayElement) {
			if (\is_object($value)) {
				if (\property_exists($value, $name)
				 || \method_exists($value, '__get')) {
					return $value->{$name} ?? null;
				}
			}
			return $getArrayElement($value, $name);
		};

		switch ($methodName) {
			case '__get': {
				return $getProperty($value, $args[0]);
			}
			case 'offsetGet': {
				return $getArrayElement($value, $args[0]);
			}
			default: {
				if (\is_object($value) && \method_exists($value, $methodName)) {
					return $value->{$args[0]}(...$args[1]);
				}

				$value = $getProperty($value, $methodName);

				if (\is_callable($value)) {
					return $value(...$args);
				}

				break;
			}
		}

		throw new DestructureException('Failed to destructure call');
	}
}
