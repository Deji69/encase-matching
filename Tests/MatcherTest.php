<?php
// phpcs:disable
namespace Encase\Matching\Tests;

use RuntimeException;
use Encase\Functional\Type;
use const Encase\Matching\Support\_;
use function Encase\Functional\split;
use function Encase\Matching\Support\key;
use function Encase\Matching\Support\val;
use function Encase\Matching\Support\when;
use function Encase\Matching\Support\match;

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

	public function casesMatchPointObject()
	{
		return [
			[new MatcherTest_Point(0, 5), 'Y: 5'],
			[new MatcherTest_Point(6, 0), 'X: 6'],
			[new MatcherTest_Point(7, 8), 'X,Y: 7,8'],
		];
	}

	public function casesMatchList()
	{
		return [
			[[1, 2, 3, 4, 5]],
			[['a', 'b', 'c', 'd', 'e']],
		];
	}

	public function casesNumbers()
	{
		return \array_map(fn($num) => [$num], \range(1, 6));
	}

	public function casesOddNumbers()
	{
		return \array_map(fn($num) => [$num], \range(1, 9, 2));
	}

	/** @dataProvider casesMatchType */
	public function testMatchType($value, $expect)
	{
		$result = match($value, [
			when(Type::array())  => 'array',
			when(Type::bool())   => 'bool',
			when(Type::float())  => 'float',
			when(Type::int())    => 'int',
			when(Type::null())   => 'null',
			when(Type::string()) => 'string',
			when(MatcherTest::class, []) => 'MatcherTest',
			when(Type::object()) => 'object',
		]);
		$this->assertSame($expect, $result);
	}

	/** @dataProvider casesOddNumbers */
	public function testMatchWithBind($number)
	{
		$result = match($number, [
			2 => 'Got 2',
			4 => 'Got 4',
			6 => 'Got 6',
			8 => 'Got 8',
			'n' => fn($n) => "Got $n",
		]);
		$this->assertSame("Got $number", $result);
	}

	public function testExampleMatchWithBind()
	{
		$result = match(3, [
			0 => 'zero',
			when(Type::int()) => [
				when(fn($n) => $n % 2 !== 0) => 'odd',
				when(fn($n) => $n % 2 === 0) => 'even'
			],
			_ => 'not a number!',
		]);
		$this->assertSame('odd', $result);
	}

	public function testFizzBuzzExample()
	{
		$result = '';
		for ($i = 0; $i < 31; ++$i) {
			if ($result) {
				$result .= ' ';
			}

			$result .= match($i, [
				when(Type::int()) => [
					when(fn($n) => $n % 3 == 0) => [
						when(fn($n) => $n % 15 == 0) => fn() => 'fizzbuzz',
						when(fn($n) => $n % 5 == 0) => 'fizz',
						_ => 'buzz',
					],
					_ => fn($n) => $n,
				],
				_ => function() {
					throw new RuntimeException('input was not an int');
				}
			]);
		}
		$this->assertSame(
			'fizzbuzz 1 2 buzz 4 5 buzz 7 8 buzz 10 11 buzz 13 14 fizzbuzz 16 17 buzz 19 20 buzz 22 23 buzz 25 26 buzz 28 29 fizzbuzz',
			$result
		);
	}

	public function testBindAvoidanceExample()
	{
		$result = [];
		$objects = [
			(object)['x' => 10, 'y'],
			(object)['x' => 101, 'z'],
			(object)['x' => 101, 'y'],
		];
		foreach ($objects as $object) {
			$result[] = match($object, [
				when(['x', val('y')]) => [
					when(fn($x) => $x > 100) => 'x is out of bounds',
					when(fn($obj, $y = 0) => $y > 100) => 'y is out of bounds',
				],
				when(['x', 'y']) => fn($x) => "x = $x",
				_ => 'error',
			]);
		}
		$this->assertSame(['x = 10', 'error', 'x is out of bounds'], $result);
	}

	public function testListElementCountExample()
	{
		$list = [];
		$results = [];

		for ($list = []; \count($list) < 4; $list[] = \count($list) + 1) {
			$results[] = match($list, [
				when([]) => '0 items',
				when([_]) => '1 item',
				when([_, _]) => '2 items',
				when([_, _, _]) => '3 items',
			]); // result: 2 items
		}

		$this->assertSame(['0 items', '1 item', '2 items', '3 items'], $results);
	}

	public function testSeatReservationExample()
	{
		$getReservedSeat = fn($seat) => match($seat, [
			when(['row', 'seat' => '/\A[A-C]\z/'])
				=> fn($row, $seat) => "You are seated at $row-$seat",
			_ => 'Seat allocation is invalid',
		]);
		$this->assertSame('You are seated at 22-B', $getReservedSeat([22, 'B']));
		$this->assertSame('You are seated at 16-C', $getReservedSeat([16, 'C']));
		$this->assertSame('Seat allocation is invalid', $getReservedSeat([14, 'D']));
	}

	/** @dataProvider casesNumbers */
	public function testMatchWithBindAndGuard($number)
	{
		$result = match($number, [
			when(Type::int()) => [
				when(fn($n) => $n % 2 == 0) => fn($n) => "Even: $n",
				when(fn($n) => $n % 2 == 1) => fn($n) => "Odd: $n",
			]
		]);

		if ($number % 2 == 0) {
			$this->assertSame("Even: $number", $result);
		} else {
			$this->assertSame("Odd: $number", $result);
		}
	}

	public function testDestructureList()
	{
		$array = [
			'Frank Drebin',
			'P.I.',
			42,
			['Inception', 'Matrix', 'One Flew Over the Arrays Nest'],
		];
		$result = match($array, [
			when(['Frank Drebin', 'job', 'age' => Type::int(), 'movies' => Type::array()]) =>
				fn($age, $job, $movies) => "Age: $age, Job: $job, Movies: ".\implode(', ', $movies)
		]);
		$this->assertSame(
			'Age: 42, Job: P.I., Movies: Inception, Matrix, One Flew Over the Arrays Nest',
			$result
		);
	}

	public function testDestructureMapKeys()
	{
		$map = [
			'Name' => 'Frank',
			'Job' => 'P.I.',
			'Age' => 42,
			5 => 'abc',
		];

		$result = match($map, [
			when([
				key('Job') -> _ ('P.I.'),
				key(_) -> ageKey (42, 66),
				'name' => key('Name') -> _ ('Frank'),
				'abc' => key(Type::int()) -> abcKey,
			]) => fn($name, $abc, $ageKey, $abcKey)
				=> [$name, $abc, $ageKey, $abcKey],
		]);

		$this->assertSame(['Frank', 'abc', 'Age', 5], $result);
	}

	/** @dataProvider casesMatchPointObject */
	public function testMatchPointObject($object, $expect)
	{
		$result = match($object, [
			when(MatcherTest_Point::class, ['x', 'y' => 0]) => fn($x) => "X: $x",
			when(MatcherTest_Point::class, ['x' => 0, 'y']) => fn($y) => "Y: $y",
			when(MatcherTest_Point::class, ['x', 'y']) => fn($x, $y) => "X,Y: $x,$y",
		]);
		$this->assertSame($expect, $result);
	}

	/** @dataProvider casesMatchList */
	public function testMatchList($list)
	{
		$result = match($list, [
			when(['first', _, 'third', '*']) => fn($first, $third) => [$first, $third],
		]);
		$this->assertSame([$list[0], $list[2]], $result);

		$result = match($list, [
			when(['first', '*', 'last']) => fn($first, $last) => [$first, $last],
		]);
		$this->assertSame([$list[0], \end($list)], $result);
	}

	public function testMatchGuard()
	{
		$square = (object)['x' => 5, 'y' => 5];
		$rect = (object)['x' => 5, 'y' => 10];
		$pattern = [
			when((object)['x', 'y']) => [
				when(fn($x, $y) => $x === $y) => 'Square',
				_ => 'Rectangle',
			],
			_ => 'Not a shape!',
		];
		$this->assertSame('Square', match($square, $pattern));
		$this->assertSame('Rectangle', match($rect, $pattern));
	}

	public function testDestructureListAndIgnoreParts()
	{
		// find a number in the list which isn't the sum of the last two
		$findUnfitNumber = function ($arg) use (&$findUnfitNumber) {
			return match($arg, [
				when(['a', 'b', 'c', '*t']) => [
					when(fn($a, $b, $c) => $c !== ($a + $b)) => fn($c) => $c,
					_ => fn($b, $c, $t) => $findUnfitNumber(\array_merge([$b, $c], $t)),
				],
				_ => null,
			]);
		};

		$this->assertSame(
			null,
			$findUnfitNumber([1, 2, 3, 5, 8, 13, 21, 34, 55, 89])
		);
		$this->assertSame(
			22,
			$findUnfitNumber([1, 2, 3, 5, 8, 13, 22, 34, 55, 89])
		);
	}

	public function testPalindromeFunc()
	{
		$isPalindrome = function($list) use (&$isPalindrome) {
			return match($list, [
				when(['h', '*m', 't']) => [
					when(fn($h, $t) => $h === $t) => fn($m) => $isPalindrome($m),
				],
				when(['h', 't']) => [
					when(fn($h, $t) => $h === $t) => 'even',
				],
				when([_]) => 'odd',
				_ => false,
			]);
		};

		$this->assertSame('even', $isPalindrome(split('aabbaa')));
		$this->assertSame('odd', $isPalindrome(split('aabaa')));
		$this->assertFalse($isPalindrome(split('aabcaa')));
	}

	public function testMatchStringAgainstStringCases()
	{
		$result = match('c', [
			'a' => 1,
			'b' => 2,
			'c' => 3,
			'd' => 4,
		]);
		$this->assertSame(3, $result);
	}

	public function testMatchCasesFallToDefault()
	{
		$result = match('c', [
			'a' => 1,
			'b' => 2,
			_ => 42
		]);
		$this->assertSame(42, $result);
	}

	public function testMatchAny()
	{
		$pattern = fn($n) => match($n, [when(1, 2, 3) => 'a', _ => 'b']);
		$this->assertSame('a', $pattern(1));
		$this->assertSame('a', $pattern(2));
		$this->assertSame('a', $pattern(3));
		$this->assertSame('b', $pattern(4));
	}

	public function testMatchArrayExact()
	{
		$result = match([1, 2], [
			when([1, 2]) => 'ok'
		]);
		$this->assertSame('ok', $result);
	}

	public function testMatchIndexedArrayWithWildcard()
	{
		$result = match([2, 4], [
			when(['a', 4]) => fn($a) => "ok: $a",
			_ => false,
		]);
		$this->assertSame('ok: 2', $result);
	}

	public function testRegexStringPatternWithNamedCaptures()
	{
		$checkIpDigit = fn($digit) => $digit >= 0 && $digit <= 255;
		$validateIp = fn($ip) => match($ip, [
			when('/\A(?P<ip1>\d{1,3})\.(?P<ip2>\d{1,3})\.(?P<ip3>\d{1,3})\.(?P<ip4>\d{1,3})\z/') => [
				when(fn($ip1, $ip2, $ip3, $ip4) =>
					$checkIpDigit($ip1) &&
					$checkIpDigit($ip2) &&
					$checkIpDigit($ip3) &&
					$checkIpDigit($ip4)
				) => true,
			],
			_ => false,
		]);
		$this->assertFalse($validateIp('abc'));
		$this->assertFalse($validateIp('255.255.255.256'));
		$this->assertTrue($validateIp('1.22.255.123'));
	}

	public function testRegexStringPatternWithAutomaticCaptures()
	{
		$checkIpDigit = fn($digit) => $digit >= 0 && $digit <= 255;
		$validateIp = fn($ip) => match($ip, [
			when('/\A(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})\z/') => [
				when(fn($ip) =>
					$checkIpDigit($ip[1]) &&
					$checkIpDigit($ip[2]) &&
					$checkIpDigit($ip[3]) &&
					$checkIpDigit($ip[4])
				) => true,
			],
			_ => false,
		]);
		$this->assertFalse($validateIp('abc'));
		$this->assertFalse($validateIp('255.255.255.256'));
		$this->assertTrue($validateIp('1.22.255.123'));
	}
}

class MatcherTest_Point
{
	public $x;
	public $y;

	public function __construct($x, $y)
	{
		$this->x = $x;
		$this->y = $y;
	}
}
