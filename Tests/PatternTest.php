<?php
namespace Encase\Matching\Tests;

use Encase\Matching\Matcher;

use function Encase\Matching\Support\match;
use function Encase\Matching\Support\pattern;

class PatternTest extends TestCase
{
	public function testBuildMatcher()
	{
		$pattern = pattern(['foo' => 'bar']);
		$this->assertInstanceOf(Matcher::class, $pattern);
	}

	public function testUseBuiltMatcherWithMatch()
	{
		$pattern = pattern(['foo' => 'bar']);
		$result = match('foo', $pattern);
		$this->assertSame('bar', $result);
	}
}
