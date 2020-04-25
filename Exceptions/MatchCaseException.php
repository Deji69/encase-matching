<?php
namespace Encase\Matching\Exceptions;

use Exception;
use Encase\Matching\MatchCase;

class MatchCaseException extends Exception
{
	/** @var MatchCase $case */
	public $case;

	public function __construct(MatchCase $case)
	{
		parent::__construct('Match case exception');
		$this->case = $case;
	}
}
