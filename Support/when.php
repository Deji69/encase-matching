<?php
namespace Encase\Matching\Support;

use Encase\Matching\When;
use Encase\Matching\WhenRepository;

/**
 * Create and serialise a "when" pattern case to use in arrays.
 *
 * @return string
 */
function when(...$args)
{
	return WhenRepository::add(new When($args));
}
