<?php
namespace Encase\Matching;

use Encase\Matching\Support\_;

class At extends _
{
	/** @var mixed& */
	public $£var = null;

	/** @var array|null */
	public $£patternArgs = null;

	/** @var int */
	public $£destructureCallCount = 0;

	/** @var string|null */
	public $£bindName = null;

	/** @var mixed& */
	protected $£src;

	/** @var bool */
	protected $£hasSrc = false;

	public function __construct(string $bindName = null)
	{
		$this->£bindName = $bindName;
		$this->£hasSrc = \func_num_args() > 0;
	}

	public function __toString()
	{
		$str = '';

		foreach ($this->£calls as $call) {
			switch ($call[0]) {
				case '__get':
					$str .= '->'.$call[1][0];
					break;
				case '__set':
					$str = '('.$str.'->'.$call[1][0].' = ...)';
					break;
				case '__call':
					$str .= '->'.$call[1][0].'(...)';
					break;
				default:
					$str .= '->'.$call[0][0].'(...)';
			}
		}
		return $str;
	}

	/**
	 * @param  string $name
	 * @param  array $args
	 * @return self|static
	 */
	public static function& __callStatic($name, $args)
	{
		$at = new static($name);
		$at->£patternArgs = $args;
		$at->£var =& $at;
		return $at->£var;
	}
}
