<?php
namespace Encase\Matching\Tests;

use Encase\Matching\Support\_;
use function Encase\Matching\Support\_;

class _Test extends TestCase
{
	public function testHelper()
	{
		$_ = _('test');
		$this->assertInstanceOf(_::class, $_);
		$this->assertSame(['test'], $_->£args);
	}

	public function testGet()
	{
		$_ = new _();
		$_->test;
		$this->assertSame([['__get', ['test']]], $_->£calls);
	}

	public function testSet()
	{
		$_ = new _();
		$_->test = 123;
		$this->assertSame([['__set', ['test', 123]]], $_->£calls);
	}

	public function testCall()
	{
		$_ = new _();
		$_->test(1, 2.3);
		$this->assertSame([['test', [1, 2.3]]], $_->£calls);
	}

	public function testOffsetGet()
	{
		$_ = new _();
		$_['test'];
		$this->assertSame([['offsetGet', ['test']]], $_->£calls);
	}

	public function testOffsetSet()
	{
		$_ = new _();
		$_['test'] = 'foo';
		$this->assertSame([['offsetSet', ['test', 'foo']]], $_->£calls);
	}

	public function testOffsetUnset()
	{
		$_ = new _();
		unset($_['test']);
		$this->assertSame([['offsetUnset', ['test']]], $_->£calls);
	}

	public function testCallStatic()
	{
		$_ = _::test('foo', 1);
		$this->assertSame('test', $_->£staticMethod);
		$this->assertSame(['foo', 1], $_->£args);
	}
}
