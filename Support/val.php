<?php
namespace Encase\Matching\Support;

use Encase\Matching\PatternBuilder;
use Encase\Matching\Patterns\ExactPattern;

/**
 * Create an exact value pattern.
 *
 * @param  mixed $value
 * @return ExactPattern
 */
function val(...$values)
{
	return PatternBuilder::buildArgs($values);
}
