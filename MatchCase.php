<?php
namespace Encase\Matching;

class MatchCase
{
	/** @var array */
	public $args;

	/** @var CaseResultable|array */
	public $result;

	/** @var CaseResultable|array|null */
	public $elseResult = null;

	/** @var array */
	public $conditions = [];

	/** @var array */
	protected $resultCaptureMapCache = [];

	/** @var array */
	protected $elseResultCaptureMatchCache = [];

	/**
	 * Undocumented function
	 *
	 * @param  Matcher $matcher
	 * @param  bool    $ifResult The boolean result of the `if` clause.
	 * @param  array   $captures The array of captures.
	 * @return mixed
	 */
	public function getValue($matcher, bool $ifResult, array $captures)
	{
		if ($ifResult) {
			$result = $this->result;
			$captureCache = &$this->resultCaptureMapCache;
		} else {
			$result = $this->elseResult;
			$captureCache = &$this->elseResultCaptureMatchCache;
		}

		if ($result instanceof CaseCall) {
			if (empty($captureCache)) {
				$captureCache = Matcher::getParamArgMappingForCall(
					$result->getCallable(),
					$captures
				);
			}

			$captures = Matcher::mapCapturesToArgs($captureCache, $captures);
		}

		return $result->getValue($matcher, $captures);
	}
}
