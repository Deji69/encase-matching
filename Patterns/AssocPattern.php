<?php
namespace Encase\Matching\Patterns;

use Encase\Matching\KeyMatchable;

class AssocPattern extends Pattern implements KeyMatchable
{
	/** @var Pattern */
	protected $keyPattern;

	/** @var Pattern|null */
	protected $valPattern = null;

	/** @var array|null */
	protected $bindNamesCache = null;

	public function __construct(Pattern $keyPattern, Pattern $valPattern = null)
	{
		$this->keyPattern = $keyPattern;
		$this->valPattern = $valPattern;
	}

	/**
	 * Match an associative element.
	 */
	public function match($map, array $captures = [])
	{
		$result = [];
		$matchValue = $this->keyPattern instanceof WildcardPattern;

		if ($this->keyPattern instanceof ExactPattern) {
			$key = $this->keyPattern->getValue();

			if (!\array_key_exists($key, $map)) {
				return false;
			}

			$val = $map[$key];
			$result = $this->keyPattern->bindResult($key);
		} else {
			foreach ($map as $key => $val) {
				if ($matchValue) {
					$result = $this->valPattern->match($val, $captures);
				} else {
					$result = $this->keyPattern->match($key, $captures);
				}

				if ($result !== false) {
					if ($matchValue) {
						$keyResult = $this->keyPattern->match($key, $captures);

						if ($keyResult === false) {
							return false;
						}

						$result = \array_merge($result, $keyResult);
					}
					break;
				}

				$key = null;
			}

			if ($key === null) {
				return false;
			}
		}

		if (!$matchValue && $this->valPattern !== null) {
			$valResult = $this->valPattern->match($val, $captures);

			if ($valResult === false) {
				return false;
			}

			$result = \array_merge($result, $valResult);
		}

		if ($this->bindName) {
			$result = \array_merge($result, [$this->bindName => $val]);
		}

		return $result;
	}

	/**
	 * Get the binding names for the patterns.
	 *
	 * @return string[]
	 */
	public function getBindNames(): array
	{
		if ($this->bindNamesCache === null) {
			$this->bindNamesCache = \array_merge(
				$this->keyPattern->getBindNames(),
				$this->valPattern->getBindNames()
			);
		}
		return $this->bindNamesCache;
	}
}
