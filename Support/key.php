<?php
namespace Encase\Matching\Support;

use Encase\Matching\KeyBinder;

/**
 * Create a bindable key pattern.
 *
 * @param  array $args
 * @return KeyBinder
 */
function key(...$args)
{
	return new KeyBinder($args);
}
