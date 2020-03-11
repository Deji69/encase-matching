<?php
namespace Encase\Matching\Tests;

use Encase\Matching\At;

class AtTest extends TestCase
{
	public function testToString()
	{
		$at = new At();
		$_ignore = $at->foo;
		$this->assertSame('->foo', (string)$at);

		$at = new At();
		$_ignore = $at->foo->bar;
		$this->assertSame('->foo->bar', (string)$at);

		$at = new At();
		$_ignore = $at->call();
		$this->assertSame('->call(...)', (string)$at);

		$at = new At();
		$_ignore = $at->foo->bar = 'cat';
		$this->assertSame('(->foo->bar = ...)', (string)$at);

		$at = new At();
		$_ignore = $at->call()->get;
		$this->assertSame('->call(...)->get', (string)$at);
	}
}
