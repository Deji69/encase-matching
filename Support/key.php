<?php
namespace Encase\Matching\Support;

use Encase\Matching\Key;
use Encase\Matching\KeyRepository;

/**
 * Create a bindable key pattern.
 *
 * @param  array $args
 * @return string Unique object ID for the key.
 */
function key(...$args)
{
	$key = new Key($args);
	return KeyRepository::add($key);
}
