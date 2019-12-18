<?php
namespace Encase\Matching\Support;

use Encase\Matching\Matcher;
use InvalidArgumentException;
use Encase\Matching\MatcherBuilder;

/**
 * Matches arguments against a Matcher.
 *
 * All arguments but the last are treated as match arguments.
 * The last argument given should be a Matcher or MatcherBuilder.
 *
 * @param  ...$args
 * @param  Matcher|MatcherBuilder
 * @return mixed Match result.
 */
function match(...$args)
{
	$matcher = \array_pop($args);

	if ($matcher instanceof MatcherBuilder) {
		$matcher = $matcher->get();
	} else {
		throw new InvalidArgumentException(
			'Last argument of match() call must be a pattern'
		);
	}

	return $matcher->match(...$args);
}
