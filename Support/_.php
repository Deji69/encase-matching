<?php
namespace Encase\Matching\Support;

use ArrayAccess;

const _ = '\0\0_';

/**
 * Create an _ instance with the args.
 *
 * @param  array $args
 * @return _
 */
function _(...$args)
{
	return new _(...$args);
}

/**
 * A context-independent placeholder object.
 *
 * A 'macro' of sorts, recording every action taken on itself.
 * Say you do `_(1, 2)->a['b']->method()` on an instance, you would be able to
 * print that exact code based on the data stored in this object.
 * Thus, you could repeat those actions or maybe do other ones to another
 * object based on the data in this macro.
 */
class _ implements ArrayAccess
{
	/**
	 * The __callStatic static magic method name used to instance the object
	 * or NULL if not instanced via the method.
	 *
	 * @var string|null
	 */
	public $£staticMethod = null;

	/**
	 * The list of arguments passed when the object was instanced.
	 *
	 * @var array
	 */
	public $£args = [];

	/**
	 * The methods invoked on the _ object in-order, including magic methods.
	 *
	 * Array form: [method_name, [args...]].
	 *
	 * @var array
	 */
	public $£calls = [];

	/**
	 * @param array  $args
	 * @param string $staticMethod
	 */
	public function __construct(...$args)
	{
		$this->£args = $args;
	}

	public function __get($name)
	{
		$this->£calls[] = ['__get', [$name]];
		return $this;
	}

	public function __set($name, $value)
	{
		$this->£calls[] = ['__set', [$name, $value]];
		return $this;
	}

	public function __call($name, $arguments)
	{
		$this->£calls[] = [$name, $arguments];
		return $this;
	}

	public function offsetExists($offset)
	{
		$this->£calls[] = ['offsetExists', [$offset]];
		return $this;
	}

	public function offsetGet($offset)
	{
		$this->£calls[] = ['offsetGet', [$offset]];
		return $this;
	}

	public function offsetSet($offset, $value)
	{
		$this->£calls[] = ['offsetSet', [$offset, $value]];
		return $this;
	}

	public function offsetUnset($offset)
	{
		$this->£calls[] = ['offsetUnset', [$offset]];
	}

	/**
	 * Create a new _ instance with the static method name and args.
	 *
	 * @param  string $name
	 * @param  array  $args
	 * @return self|static
	 */
	public static function __callStatic($name, $args)
	{
		$_ = new static(...$args);
		$_->£staticMethod = $name;
		return $_;
	}
}
