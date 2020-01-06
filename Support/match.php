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
function match($value, $cases = null)
{
	if (\func_num_args() === 1) {
		$matcher = new Matcher($value);
		return $matcher;
	}

	$matcher = new Matcher($cases);
	return $matcher->match($value);
}
