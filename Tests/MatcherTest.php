<?php
// phpcs:disable
namespace Encase\Matching\Tests;

use RuntimeException;
use Encase\Matching\At;
use Encase\Matching\Key;
use Encase\Functional\Type;
use const Encase\Matching\Support\_;
use function Encase\Functional\split;
use function Encase\Matching\Support\at;
use function Encase\Matching\Support\any;
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

	public function testConstantPatternPiExample()
	{
		$matcher = fn($val) => match($val, [
			1 => 'one',
			'2' => 'two',
			'3.14159' => 'pi',
			'boo' => 'hoo',
			_ => false,
		]);
		$this->assertFalse($matcher('1'));
		$this->assertFalse($matcher('2'));
		$this->assertFalse($matcher(3.14159));
		$this->assertSame('one', $matcher(1));
		$this->assertSame('two', $matcher(2));
		$this->assertSame('pi', $matcher('3.14159'));
		$this->assertSame('hoo', $matcher('boo'));
	}

	public function testConstantMatchZeroesExample()
	{
		$matcher = fn($val) => match($val, [
			when(0) => 'zero',
			when('') => 'empty string',
			when(null) => 'null',
			when(false) => 'false',
		]);
		$this->assertSame('zero', $matcher(0));
		$this->assertSame('false', $matcher(false));
		$this->assertSame('null', $matcher(null));
	}

	public function testConstantMatchOnesExample()
	{
		$matcher = fn($val) => match($val, [
			when(1) => 'one',
			when(true) => 'true'
		]);
		$this->assertSame('one', $matcher(1));
		$this->assertSame('true', $matcher(true));
	}

	public function testExampleMatchWithBind()
	{
		$result = [];
		$inputs = [0, 1, 2, 3, false, '2', '0'];

		foreach ($inputs as $input) {
			$result[] = match($input, [
				0 => 'zero',
				when(Type::int()) => [
					when(fn($n) => $n % 2 !== 0) => 'odd',
					when(fn($n) => $n % 2 === 0) => 'even'
				],
				_ => 'NaN',
			]);
		}
		$this->assertSame('zero,odd,even,odd,NaN,NaN,NaN', \implode(',', $result));
	}

	public function testFizzBuzzExample()
	{
		$result = [];

		for ($i = 0; $i < 31; ++$i) {
			$result[] = match($i, [
				when(Type::int()) => [
					when(fn($n) => $n <= 0) => '<=0',
					when(fn($n) => $n % 3 == 0) => [
						when(fn($n) => $n % 5 == 0) => 'Fizz Buzz',
						_ => 'Fizz'
					],
					when(fn($n) => $n % 5 == 0) => 'Buzz',
					_ => $i,
				],
				_ => function() {
					throw new RuntimeException('input was not an int');
				}
			]);
		}

		$this->assertSame(
			'<=0, 1, 2, Fizz, 4, Buzz, Fizz, 7, 8, Fizz, Buzz, 11, Fizz, 13, 14, Fizz Buzz, '
			.'16, 17, Fizz, 19, Buzz, Fizz, 22, 23, Fizz, Buzz, 26, Fizz, 28, 29, Fizz Buzz',
			\implode(', ', $result)
		);
	}

	public function testFizzBuzzBetterExample()
	{
		$result = '';

		for ($i = 1; $i < 31; ++$i) {
			if ($result) {
				$result .= ' ';
			}

			$result .= match($i, [
				when(fn($n) => $n % 15 == 0) => 'fizzbuzz',
				when(fn($n) => $n % 5 == 0) => 'buzz',
				when(fn($n) => $n % 3 == 0) => 'fizz',
				_ => $i
			]);
		}

		$this->assertSame(
			'1 2 fizz 4 buzz fizz 7 8 fizz buzz 11 fizz 13 14 fizzbuzz 16 17 fizz 19 buzz fizz 22 23 fizz buzz 26 fizz 28 29 fizzbuzz',
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
			(object)['x' => 'z', 'y'],
		];
		foreach ($objects as $object) {
			$result[] = match($object, [
				when(['x', val('y')]) => [
					when(fn($x) => $x > 100) => 'x is out of bounds',
					when(fn($obj, $y = 0) => $y > 100) => 'y is out of bounds',
				],
				when((object)['x' => val('z')]) => fn($x) => 'x is z',
				when(['x', 'y']) => fn($x) => "x = $x",
				_ => 'error',
			]);
		}
		$this->assertSame(['x = 10', 'error', 'x is out of bounds', 'x is z'], $result);
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

	public function testParityBindingExample()
	{
		$getParity = fn($val) => match($val, [
			when(Type::int()) => [
				when(fn($v) => $v % 2 === 0) => 'even',
				_ => 'odd',
			],
			'n' => fn($n) => "$n is not an integer",
		]);
		$this->assertSame('odd', $getParity(5));
		$this->assertSame('even', $getParity(8));
		$this->assertSame('foo is not an integer', $getParity('foo'));
	}

	public function testTicTacToeListExample()
	{
		$getTicTacToeRowResult = fn($list) => match($list, [
			when(['x', 'y', 'z']) => [
				when(fn($x, $y, $z) => $x == $y && $y == $z) => [
					when(fn($x) => $x == 'x') => 'crosses wins!',
					when(fn($x) => $x == 'o') => 'naughts wins!',
				],
			],
			when(['x', 'o', 'x']) => fn($xox) => \implode(',', $xox).'!'
		]);
		$this->assertSame('naughts wins!', $getTicTacToeRowResult(['o', 'o', 'o']));
		$this->assertSame('crosses wins!', $getTicTacToeRowResult(['x', 'x', 'x']));
		$this->assertSame('x,o,x!', $getTicTacToeRowResult(['x', 'o', 'x']));
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

	public function testHunterPreyListExample()
	{
		$result = match(['dog' => 'cat', 'a' => 'b'], [
			when(['hunter' => 'cat']) => fn($hunter) => "cat gets chased by $hunter",
			when(['hunter' => 'cat', 'a' => 'b']) => fn($hunter) => $hunter,
		]);
		$this->assertSame('cat', $result);
	}

	public function testHunterPreyMapExample()
	{
		// try removing key => val pairs from the input and see the effect on output
		$hunt = fn($input) => match($input, [
			when([key('cat') => 'prey'])
				=> fn($prey) => "cat chases $prey",
			when([key::hunter() => 'cat'])
				=> fn($hunter) => "cat gets chased by $hunter",
			when([key('mouse') => 'other'])
				=> fn($other) => "mouse hides, $other rests",
		]);
		$this->assertSame(
			'mouse hides, dog rests',
			$hunt(['mouse' => 'dog', 'a' => 'b'])
		);
		$this->assertSame(
			'cat chases mouse',
			$hunt(['cat' => 'mouse', 'a' => 'b'])
		);
		$this->assertSame(
			'cat gets chased by dog',
			$hunt(['dog' => 'cat', 'a' =>'b'])
		);
		//$this->assertSame('a mouse scuttles around', $hunterPrey(['mouse' => 'cheese']));
	}

	public function testScopelessDestructureForValueReselt()
	{
		$value = (object)['x' => (object)['y' => (object)['z' => 'bingo']]];
		$result = match($value, [
			when($a = at()->x->y) => $a->z,
		]);
		$this->assertSame('bingo', $result);
	}

	public function testScopelessDestructureForClosureResult()
	{
		$value = (object)['x' => (object)['y' => (object)['z' => 'bingo']]];
		$result = match($value, [
			when($a = at()->x->y) => fn() => $a->z,
		]);
		$this->assertSame('bingo', $result);
	}

	public function testScopelessDestructureForWhen()
	{
		$value = (object)['x' => (object)['y' => (object)['z' => 'foo']]];
		$result = match($value, [
			when($a = at::a()->x->y, fn($a) => $a->z === 'foo') => $a->z,
		]);
		$this->assertSame('foo', $result);
	}

	public function testScopelessDestructureForSubCase()
	{
		$value = (object)['x' => (object)['y' => (object)['z' => 'foo']]];
		$result = match($value, [
			when($a = at::a()->x->y, fn($a) => $a->z === 'foo') => [
				when(fn($a) => $a->z === 'foo') => $a->z,
			]
		]);
		$this->assertSame('foo', $result);
	}

	public function testScopelessDestructureArray()
	{
		$value = ['x' => ['y' => ['z' => 'foo']]];
		$result = match($value, [
			when($a = at::a()['x']['y'], fn($a) => $a['z'] === 'foo') => [
				when(fn($a) => $a['z'] === 'foo') => $a['z']
			]
		]);
		$this->assertSame('foo', $result);
	}

	public function testBindAsIdentifier()
	{
		$values = [
			['no x/y', (object)['x' => null]],
			['no x/y', ['y' => null]],
			['x missed', (object)['x' => 2, 'y' => 5]],
			['x missed', ['x' => 4, 'y' => 5]],
			['y missed', (object)['x' => 3, 'y' => 8]],
			['y missed', ['x' => 3, 'y' => 4]],
			['hit', (object)['x' => 3, 'y' => 5]],
			['hit', ['x' => 3, 'y' => 5]],
		];

		foreach ($values as $data) {
			$expect = $data[0];
			$value = $data[1];

			$result = match($value, [
				when(at::a()->x, at::b()->y) => [
					when(fn($a) => $a == 3) => [
						when(fn($b) => $b == 5) => 'hit',
						_ => 'y missed',
					],
					_ => 'x missed',
				],
				_ => 'no x/y',
			]);
			$this->assertSame($expect, $result);
		}
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
				'Job' => 'P.I.',
				key ('Name') => at::name('Frank'),
				key::ageKey () => any(42, 66),
				key::abcKey (Type::int()) => 'abc',
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

	public function testMatchGuardAltSyntax()
	{
		$square = (object)['x' => 5, 'y' => 5];
		$rect = (object)['x' => 5, 'y' => 10];
		$pattern = [
			when(at()->x, at()->y) => [
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
		$pattern = fn($n) => match($n, [
			when(1, 2, 3) => 'a',
			_ => 'b',
		]);
		$this->assertSame('a', $pattern(1));
		$this->assertSame('a', $pattern(2));
		$this->assertSame('a', $pattern(3));
		$this->assertSame('b', $pattern(4));
	}

	public function testMatchAll()
	{
		$pattern = fn($n) => match($n, [
			when(Type::int(), fn() => $n < 5) => 'int under 5',
			when(fn($n) => $n > 0, fn() => $n < 5) => '0-5',
		]);
		$this->assertSame('int under 5', $pattern(2));
		$this->assertSame('0-5', $pattern('2'));
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

class MatcherTest_Shape
{
}
