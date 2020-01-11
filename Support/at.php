<?php
namespace Encase\Matching\Support;

use Encase\Matching\At;

/**
 * Get an At binder for the given variable, or get the bound value  from an At
 * binder.
 *
 * @param array $pattern
 * @return At|mixed
 */
function& at(...$pattern)
{
	if (!empty($pattern)) {
		if ($pattern[0] instanceof At) {
			$at = clone $pattern[0];
			$value = $at->£var;
			$at->£var =& $value;
			return $value;
		}
	}

	$at = new At();
	$at->£patternArgs = $pattern ?? null;
	$at->£var =& $at;
	return $at->£var;
}
