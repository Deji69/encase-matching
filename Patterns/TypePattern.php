<?php
namespace Encase\Matching\Patterns;

use Encase\Functional\Type;

class TypePattern extends Pattern
{
	/** @var Type */
	protected $type;

	public function __construct(Type $type)
	{
		$this->type = $type;
	}

	public function getType(): Type
	{
		return $this->type;
	}

	public function matchValue($value, array $bindNames = [])
	{
		return $this->type->check($value);
	}
}
