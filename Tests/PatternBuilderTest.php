<?php
namespace Encase\Matching\Tests;

use Encase\Matching\PatternArg;
use Encase\Matching\PatternBuilder;
use Encase\Matching\Patterns\RegexPattern;
use Encase\Matching\Patterns\WildcardPattern;
use Encase\Regex\Regex;

use const Encase\Matching\Support\_;

use function Encase\Matching\Support\_;

class PatternBuilderTest extends TestCase
{
	public function testWildcard()
	{
		$pattern = PatternBuilder::build(new PatternArg(_));
		$this->assertInstanceOf(WildcardPattern::class, $pattern);

		$pattern = PatternBuilder::build(new PatternArg('_'));
		$this->assertInstanceOf(WildcardPattern::class, $pattern);

		$pattern = PatternBuilder::build(new PatternArg('test1@_'));
		$this->assertInstanceOf(WildcardPattern::class, $pattern);
		$this->assertSame('test1', $pattern->getBindName());

		$pattern = PatternBuilder::build(new PatternArg(_('test2')));
		$this->assertInstanceOf(WildcardPattern::class, $pattern);
		$this->assertSame('test2', $pattern->getBindName());
	}

	public function testRegex()
	{
		// Build from string
		$pattern = PatternBuilder::build(new PatternArg('/\w/'));
		$this->assertInstanceOf(RegexPattern::class, $pattern);
		// Build from Regex object
		$pattern = PatternBuilder::build(new PatternArg(new Regex('/\w/')));
		$this->assertInstanceOf(RegexPattern::class, $pattern);
	}
}
