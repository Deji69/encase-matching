<?php
namespace Encase\Matching;

class CaseContinue implements CaseResultable
{
	/** @var array */
	protected $captures = [];

	/**
	 * Construct a case result from a bound argument.
	 *
	 * @param string[] $captures
	 */
	public function __construct($captures)
	{
		foreach ($captures as $capture) {
			$this->captures[] = Matcher::parseBindingString($capture);
		}
	}

	public function getValue($matcher, $captures)
	{
		$args = [];
		foreach ($this->captures as $capture) {
			$args[] = Matcher::resolveCallBindings($capture['args'], $capture['offsets'], $captures);
		}
		return $matcher->match(...$args);
	}
}
