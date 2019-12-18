<?php
namespace Encase\Matching\Support;

use Encase\Matching\Wildcard;

const ___ = '\0\0\0';

function ___($bindName)
{
	return new Wildcard($bindName);
}
