<?php
namespace Encase\Matching\Patterns;

use Encase\Matching\Matchable;
use Encase\Matching\MatchBindable;

use const Encase\Matching\Support\_;

abstract class Pattern implements Matchable, MatchBindable
{
	/** @var string|null */
	protected $bindName = null;

	/**
	 * Match a value with this pattern.
	 *
	 * @param  mixed $value
	 * @param  array $captures
	 * @return bool|array
	 */
	public function matchValue($value, array $captures = [])
	{
		return false;
	}

	/**
	 * @param string|null $bindName Variable name to capture.
	 */
	public function __construct(string $bindName = null)
	{
		$this->bindName = !empty($bindName) ? $bindName : null;
	}

	public function __get(string $bindName)
	{
		$this->setBindName($bindName);
		return $this;
	}

	/**
	 * @inheritDoc
	 */
	public function getBindName(): string
	{
		return $this->bindName;
	}

	/**
	 * @inheritDoc
	 */
	public function getBindNames(): array
	{
		return [];
	}

	/**
	 * @inheritDoc
	 */
	public function setBindName(?string $bindName)
	{
		$this->bindName = ($bindName !== _ && $bindName !== '_')
			? $bindName
			: null;
	}

	/**
	 * Bind result value using the bind name set for the pattern.
	 *
	 * @param  mixed $value
	 * @param  array|bool $result
	 * @return array|bool
	 */
	public function bindResult($value, $result = true)
	{
		if ($result === false) {
			return false;
		}
		if ($result === true) {
			if ($this->bindName !== null) {
				return [$this->bindName => $value];
			}
			return [];
		} elseif ($this->bindName !== null) {
			$result = \array_merge($result, [$this->bindName => $value]);
		}
		return $result;
	}

	/**
	 * @inheritDoc
	 */
	public function match($value, array $captures = [])
	{
		$result = $this->matchValue($value, $captures);
		return $this->bindResult($value, $result);
	}

	/**
	 * Create a new Pattern instance.
	 *
	 * @param  mixed ...$args
	 * @return static
	 */
	public static function new(...$args)
	{
		return new static(...$args);
	}
}
