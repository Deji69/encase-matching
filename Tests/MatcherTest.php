<?php
// phpcs:disable
namespace Encase\Matching\Tests;

use Encase\Functional\Type;
use const Encase\Matching\Support\_;
use function Encase\Matching\Support\_;
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
			(Type::array())     ->v('array')
			(Type::bool())      ->v('bool')
			(Type::float())     ->v('float')
			(Type::int())       ->v('int')
			(Type::null())      ->v('null')
			(Type::object(MatcherTest::class)) ->v('MatcherTest')
			(Type::object())    ->v('object')
			(Type::string())    ->v('string')
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
			('name', 'age', 'films')->f(
				fn($name, $age, $films) => "Name: $name, Age: $age, Films: ".\implode(', ', $films)
			)
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
			()->v(null)
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
			// head and tail match, recurse
			('h', '*m', 't') ->if($same) ->continue('m')
			// end of even-length palindrome:
			('h', 't')       ->if($same) ->v('even')
			// end of odd-length palindrome:
			(_) ->v('odd')
			// default case - not a palindrome:
			()  ->v(false)
		;

		$this->assertSame('even', $isPalindrome('aabbaa'));
		$this->assertSame('odd', $isPalindrome('aabaa'));
		$this->assertFalse($isPalindrome('aabcaa'));
		$this->assertTrue([1, 2, 2, 1]);
		$this->assertTrue([1, 2, 3, 2, 1]);
		$this->assertFalse([1, 2, 3, 4, 2, 1]);
	}

	public function testMatchStringAgainstStringCases()
	{
		$result = pattern()
			['a'] ->v(1)
			['b'] ->v(2)
			['c'] ->v(3)
			['d'] ->v(4)
		->match('c');
		$this->assertSame(3, $result);
	}

	public function testMatchMultipleStringsAgainstStringCases()
	{
		$result = pattern()
			['x'] ['y'] ['z'] ->v(1)
			['1'] ['2'] ['3'] ->v(2)
			['a'] ['b'] ['c'] ->v(3)
			['f'] ['o'] ['o'] ->v(4)
			()                ->f(function () { return 'default'; })
		->match('a', 'b', 'c');
		$this->assertSame(3, $result);
	}

	public function testMatchCasesFallToDefault()
	{
		$result = pattern()
			['a'] ->v(1)
			['b'] ->v(2)
			()    ->v(42)
		->match('c');
		$this->assertSame(42, $result);
	}

	public function matchAny()
	{
		$pattern = pattern()
			(all(1, 2, 3))  ->v('a')
			()              ->v('b');
		$this->assertSame('a', $pattern->match(1));
		$this->assertSame('a', $pattern->match(2));
		$this->assertSame('a', $pattern->match(3));
		$this->assertSame('b', $pattern->match(4));
	}

	public function testMatchToDetermineEmptyType()
	{
		$fn = function () {};
		$pattern = pattern()
			[[]]            ->v('empty array')
			['']            ->v('empty string')
			[(object)[]]    ->v('empty object')
			->get();
		$this->assertSame('empty array', $pattern([]));
		$this->assertSame('empty string', $pattern(''));
		$this->assertSame('empty object', $pattern((object)[]));
	}

	public function testMatchArrayExact()
	{
		$result = pattern()
			[[1, 2]] ->v('ok')
		->match([1, 2]);
		$this->assertSame('ok', $result);
	}

	public function testMatchEmptyArray()
	{
		$result = match([], pattern()
			[[]] ->v('ok')
		);
		$this->assertSame('ok', $result);
	}

	public function testMatchIndexedArrayWithWildcard()
	{
		$result = pattern()
			([_('n')->a, 4]) ->f(fn($a) => "ok: $a")
			()               ->v(false)
		->match([2, 4]);
		$this->assertSame('ok: 2', $result);
	}

	public function testRegexStringPatternWithNamedCaptures()
	{
		$checkIpDigit = fn($digit) => $digit >= 0 && $digit <= 255;
		$ipValidatorPattern = pattern()
			('/\A(?P<ip1>\d{1,3})\.(?P<ip2>\d{1,3})\.(?P<ip3>\d{1,3})\.(?P<ip4>\d{1,3})\z/')
			    ->if(fn($ip1, $ip2, $ip3, $ip4) => $checkIpDigit($ip1) &&
			                                       $checkIpDigit($ip2) &&
			                                       $checkIpDigit($ip3) &&
			                                       $checkIpDigit($ip4))
			    ->v(true)
			()  ->v(false)
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
				->if(fn($ip) => $checkIpDigit($ip[1]) &&
				                $checkIpDigit($ip[2]) &&
				                $checkIpDigit($ip[3]) &&
				                $checkIpDigit($ip[4]))
				->v(true)
			()  ->v(false)
		;
		$this->assertFalse(match('abc', $ipValidatorPattern));
		$this->assertFalse(match('255.255.255.256', $ipValidatorPattern));
		$this->assertTrue(match('1.22.255.123', $ipValidatorPattern));
	}
}
