<?php
namespace Encase\Matching\Support;

use Encase\Matching\Patterns\GroupPattern;

/**
 * Creates a pattern that matches if any of the sub-patterns match.
 */
function any(...$expressions)
{
	return new GroupPattern($expressions, 'or');
}
