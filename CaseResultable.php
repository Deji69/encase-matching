<?php
namespace Encase\Matching;

interface CaseResultable
{
	/**
	 * Get the case result.
	 *
	 * @param  Matcher $matcher
	 * @param  array   $captures
	 * @param  mixed   $value
	 * @return mixed
	 */
	public function getValue(Matcher $matcher, array $captures, $value);
}
