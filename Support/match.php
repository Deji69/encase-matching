<?php
namespace Encase\Matching\Support;

use Encase\Matching\Matcher;

/**
 * Matches arguments against a set of cases.
 *
 * If `$cases` is a Matcher object (e.g. built with pattern()), that matcher is
 * invoked on `$value` and the result is returned. Otherwise `$cases` will be
 * used to build the Matcher to invoke.
 *
 * @param  mixed $value Value to match.
 * @param  array|Matcher $cases `When` cases to match against.
 * @return mixed Result of matched case.
 */
function match($value, $cases = null)
{
	if (\func_num_args() === 1) {
		$matcher = new Matcher($value);
		return $matcher;
	}

	$matcher = $cases instanceof Matcher ? $cases : new Matcher($cases);
	return $matcher->match($value);
}
