<?php
// phpcs:disable
namespace Encase\Matching\Tests;

use Encase\Functional\Type;
use const Encase\Matching\Support\_;
use function Encase\Matching\Support\_;
use function Encase\Matching\Support\any;
use function Encase\Matching\Support\match;
use function Encase\Matching\Support\pattern;

/**
 * TODO: Examples of this helping to do common PHP tasks, where applicable:
 *  - Input validation
 *  - Routing
 */

class MatcherTest extends TestCase
{
	public function casesMatchType()
	{
		return [
			[[], 'array'],
			[true, 'bool'],
			[1.0, 'float'],
			[1, 'int'],
			[null, 'null'],
			[$this, 'MatcherTest'],
			[(object)[], 'object'],
			['', 'string'],
		];
	}

	/** @dataProvider casesMatchType */
	public function testMatchType($value, $expect)
	{
		$pattern = pattern()
			(Type::array())     ['array']
			(Type::bool())      ['bool']
			(Type::float())     ['float']
			(Type::int())       ['int']
			(Type::null())      ['null']
			(Type::object(MatcherTest::class)) ['MatcherTest']
			(Type::object())    ['object']
			(Type::string())    ['string']
		;
		$result = $pattern->match($value);
		$this->assertSame($expect, $result);
	}

	public function testDestructureIndexedArray()
	{
		$array = [
			'Frank Drebin',
			42,
			['Inception', 'Matrix', 'One Flew Over the Arrays Nest'],
		];
		$result = pattern()
			('name', 'age', 'films') [
				fn($name, $age, $films) => "Name: $name, Age: $age, Films: ".\implode(', ', $films)
			]
		->match($array);
		$this->assertSame(
			'Name: Frank Drebin, Age: 42, Films: Inception, Matrix, One Flew Over the Arrays Nest',
			$result
		);
	}

	public function testDestructureObject()
	{
		$object = (object)[
			'name' => 'Frank',
			'age' => 42,
			'mass' => 123.4
		];
		$result = pattern()
			(['mass' => 'weight', 'age' => _(Type::int())->age])->f(
				fn($age, $weight) => "Age: $age, Weight: $weight"
			)
		->match($object);
		$this->assertSame('Age: 42, Weight: 123.4', $result);
	}

	public function testDestructureList()
	{
		// find a number in the list which isn't the sum of the last two
		$findUnfitNumber = pattern()
			('a', 'b', 'c', _('*')->t)
				->if(fn($a, $b, $c) => $c !== ($a + $b))
				->else->continue('b,c,t')
				->ret('c')
			() [null]
		;
		$this->assertSame(
			null,
			$findUnfitNumber->match([1, 2, 3, 5, 8, 13, 21, 34, 55, 89])
		);
		$this->assertSame(
			22,
			$findUnfitNumber->match([1, 2, 3, 5, 8, 13, 22, 34, 55, 89])
		);
	}

	public function testPalindromeFunc()
	{
		$same = fn($h, $t) => $h === $t;
		$isPalindrome = pattern()
			// if head and tail match, recurse, else return false
			('h', '*m', 't')-> if($same)-> continue('m')-> else [false]
			// end of odd-length palindrome:
			(_) ['odd']
			// default case - not a palindrome:
			()  ['even']
		;

		$this->assertSame('even', $isPalindrome('aabbaa'));
		$this->assertSame('odd', $isPalindrome(\explode('', 'aabaa')));
		$this->assertFalse($isPalindrome('aabcaa'));
		$this->assertTrue([1, 2, 2, 1]);
		$this->assertTrue([1, 2, 3, 2, 1]);
		$this->assertFalse([1, 2, 3, 4, 2, 1]);
	}

	public function testMatchStringAgainstStringCases()
	{
		$result = pattern()
			['a'] [1]
			['b'] [2]
			['c'] [3]
			['d'] [4]
		->match('c');
		$this->assertSame(3, $result);
	}

	public function testMatchCasesFallToDefault()
	{
		$result = pattern()
			['a'] [1]
			['b'] [2]
			()    [42]
		->match('c');
		$this->assertSame(42, $result);
	}

	public function matchAny()
	{
		$pattern = pattern()
			(any(1, 2, 3))  ['a']
			()              ['b']
		->get();
		$this->assertSame('a', $pattern(1));
		$this->assertSame('a', $pattern(2));
		$this->assertSame('a', $pattern(3));
		$this->assertSame('b', $pattern(4));
	}

	public function testMatchToDetermineEmptyType()
	{
		$fn = function () {};
		$pattern = pattern()
			[[]]            ['empty array']
			['']            ['empty string']
			[(object)[]]    ['empty object']
		->get();
		$this->assertSame('empty array', $pattern([]));
		$this->assertSame('empty string', $pattern(''));
		$this->assertSame('empty object', $pattern((object)[]));
	}

	public function testMatchArrayExact()
	{
		$result = pattern()
			[[1, 2]] ['ok']
		->match([1, 2]);
		$this->assertSame('ok', $result);
	}

	public function testMatchEmptyArray()
	{
		$result = match([], pattern()
			[[]] ['ok']
		);
		$this->assertSame('ok', $result);
	}

	public function testMatchIndexedArrayWithWildcard()
	{
		$result = pattern()
			(['a', 4]) [fn($a) => "ok: $a"]
			()         [false]
		->match([2, 4]);
		$this->assertSame('ok: 2', $result);
	}

	public function testRegexStringPatternWithNamedCaptures()
	{
		$checkIpDigit = fn($digit) => $digit >= 0 && $digit <= 255;
		$ipValidatorPattern = pattern()
			('/\A(?P<ip1>\d{1,3})\.(?P<ip2>\d{1,3})\.(?P<ip3>\d{1,3})\.(?P<ip4>\d{1,3})\z/')
				->if(fn($ip1, $ip2, $ip3, $ip4) =>
					$checkIpDigit($ip1) &&
					$checkIpDigit($ip2) &&
					$checkIpDigit($ip3) &&
					$checkIpDigit($ip4)
				) [true]
			() [false]
		;
		$this->assertFalse(match('abc', $ipValidatorPattern));
		$this->assertFalse(match('255.255.255.256', $ipValidatorPattern));
		$this->assertTrue(match('1.22.255.123', $ipValidatorPattern));
	}

	public function testRegexStringPatternWithAutomaticCaptures()
	{
		$checkIpDigit = fn($digit) => $digit >= 0 && $digit <= 255;
		$ipValidatorPattern = pattern()
			('/\A(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})\z/')
				->if(fn($ip) =>
					$checkIpDigit($ip[1]) &&
					$checkIpDigit($ip[2]) &&
					$checkIpDigit($ip[3]) &&
					$checkIpDigit($ip[4])
				) [true]
			() [false]
		;
		$this->assertFalse(match('abc', $ipValidatorPattern));
		$this->assertFalse(match('255.255.255.256', $ipValidatorPattern));
		$this->assertTrue(match('1.22.255.123', $ipValidatorPattern));
	}
}
