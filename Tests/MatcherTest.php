<?php
namespace Encase\Matching\Tests;

use Encase\Matching\Matcher;
use function Encase\Matching\Support\match;
use Encase\Matching\Exceptions\PatternException;

class MatcherTest extends TestCase
{
	public function testInvoke()
	{
		$matcher = new Matcher([
			'foo' => 'bar'
		]);
		$this->assertSame('bar', $matcher('foo'));
	}

	public function testThrowsPatternExceptionWithNoCases()
	{
		$this->expectException(PatternException::class);
		$this->expectExceptionMessage('Matcher must have at least one case.');
		match(null, []);
	}
}
