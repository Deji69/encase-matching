<?php
namespace Encase\Matching\Support;

use Encase\Matching\Matcher;

/**
 * Matches arguments against a set of cases.
 *
 * @param  mixed $value Value to match.
 * @param  array $cases `When` cases to match against.
 * @return mixed Result of matched case.
 */
function match($value, $cases)
{
	$matcher = new Matcher($cases);
	return $matcher->match($value);
}
