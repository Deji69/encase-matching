<?php
namespace Encase\Matching\Support;

use Encase\Matching\Patterns\GroupPattern;

/**
 * Creates a pattern that matches if all of the sub-patterns match.
 */
function all(...$expressions)
{
	return new GroupPattern($expressions, 'and');
}
