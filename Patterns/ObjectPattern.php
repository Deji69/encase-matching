<?php
namespace Encase\Matching\Patterns;

use Encase\Matching\Matchable;
use Encase\Matching\Patternable;
use Encase\Matching\PatternBuilder;

class ObjectPattern extends Pattern
{
	/** @var array */
	protected $patterns;

	/** @var \Encase\Functional\Type */
	protected $type;

	/** @var array */
	protected $bindNames = [];

	/**
	 * @param \Encase\Functional\Type $type
	 * @param array $destructurePatterns
	 * @param string[] $bindNames
	 */
	public function __construct($type, $destructurePatterns = [], array $bindNames = [])
	{
		$this->patterns = $destructurePatterns;
		$this->bindNames = $bindNames;
		$this->type = $type;
	}

	/**
	 * @inheritDoc
	 */
	public function matchValue($value, array $captures = [])
	{
		if ($this->type->check($value)) {
			$captures = [];

			foreach ($this->patterns as $k => &$v) {
				$valuePattern = null;

				if (\is_numeric($k)) {
					$propName = $v;
				} else {
					$propName = $k;
					$valuePattern = &$v;
				}

				if (!\property_exists($value, $propName)) {
					return false;
				}

				$result = true;

				if ($valuePattern !== null) {
					if ($valuePattern instanceof Patternable) {
						$valuePattern = $valuePattern->getPattern($this->getBindNames());
					} elseif (!$valuePattern instanceof Matchable) {
						$valuePattern = PatternBuilder::buildArg($valuePattern, $this->getBindNames());
					}

					$result = $valuePattern->match($value->$propName);

					if ($result === false) {
						return false;
					}
				}

				if (\in_array($propName, $this->getBindNames(), true)) {
					$captures[$propName] = $value->$propName;
				}

				if (\is_array($result)) {
					$captures = \array_merge($captures, $result);
				}
			}

			return empty($captures) ? true : $captures;
		}
		return false;
	}

	/**
	 * @inheritDoc
	 */
	public function getBindNames(): array
	{
		return $this->bindNames;
	}
}
