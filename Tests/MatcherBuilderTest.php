<?php
namespace Encase\Matching\Tests;

use Encase\Matching\Matcher;
use Encase\Matching\MatcherBuilder;
use Encase\Matching\Exceptions\MatchBuilderException;

class MatcherBuilderTest extends TestCase
{
	public function testInvokeReturnsSameObject()
	{
		$match = new MatcherBuilder();
		$result = $match('a');
		$this->assertSame($match, $result);
	}

	public function testGetImmutablePatternMatchObject()
	{
		$builder = MatcherBuilder::new()('a')->v(1);
		$result1 = $builder->get();
		$this->assertInstanceOf(Matcher::class, $result1);
		$result2 = $builder->get();
		$this->assertInstanceOf(Matcher::class, $result2);
		$this->assertNotSame($result1, $result2);
	}

	public function testMatchExactToValue()
	{
		$result = MatcherBuilder::new()
			{'a'}->v(42)
			->match('a');
		$this->assertSame(42, $result);
	}

	public function testArgumentFollowingDefaultCaseThrows()
	{
		$this->expectException(MatchBuilderException::class);
		MatcherBuilder::new()(){'a'};
	}

	public function testDefaulCaseFollowingArgumentThrows()
	{
		$this->expectException(MatchBuilderException::class);
		MatcherBuilder::new(){'a'}();
	}

	public function testBuildingWithoutCasesThrows()
	{
		$this->expectException(MatchBuilderException::class);
		MatcherBuilder::new()->get();
	}

	public function testBuildingWithIncompleteCaseThrows()
	{
		$this->expectException(MatchBuilderException::class);
		$result = MatcherBuilder::new()
			{'a'}->v(1)
			{'b'}
			->get();
	}

	public function testAddingCaseAfterDefaultCaseThrows()
	{
		$this->expectException(MatchBuilderException::class);
		MatcherBuilder::new()
			()    ->v(1)
			{'a'} ->v(2);
	}
}
