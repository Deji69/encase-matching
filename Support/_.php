<?php
namespace Encase\Matching\Support;

use Encase\Matching\Wildcard;

const _ = '\0\0';

function _(...$patterns)
{
	return new Wildcard(...$patterns);
}
