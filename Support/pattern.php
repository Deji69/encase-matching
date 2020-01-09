<?php
namespace Encase\Matching\Support;

use Encase\Matching\Matcher;

/**
 * Construct a pattern matcher.
 */
function pattern(array $cases)
{
	return new Matcher($cases);
}
