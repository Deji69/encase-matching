<?php
namespace Encase\Matching\Support;

use Encase\Matching\MatcherBuilder;

/**
 * Begin building a Matcher.
 *
 * @return \Encase\Matching\MatcherBuilder
 */
function pattern()
{
	return new MatcherBuilder;
}
