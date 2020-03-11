<?php declare(strict_types = 1);
// phpcs:disable
namespace Encase\Matching\Tests;

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
use Encase\Matching\Exceptions\MatchException;

class MatchTest extends TestCase
{
	public function casesMatchType()
	{
		return [
			[[], 'array'],
			[true, 'bool'],
			[1.0, 'float'],
			[1, 'int'],
			[null, 'null'],
			[$this, 'MatchTest'],
			[(object)[], 'object'],
			['', 'string'],
		];
	}

	public function casesMatchPointObject()
	{
		return [
			[new MatchTest_Point(0, 5), 'Y: 5'],
			[new MatchTest_Point(6, 0), 'X: 6'],
			[new MatchTest_Point(7, 8), 'X,Y: 7,8'],
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
			when(MatchTest::class, []) => 'MatchTest',
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

	public function testMatchExceptionOnExhaustedCases()
	{
		$this->expectException(MatchException::class);
		match(3, [
			1 => null,
			2 => null,
		]);
	}

	public function testMatchExceptionOnWhenCallTypeError()
	{
		$this->expectException(MatchException::class);
		$this->expectExceptionMessageMatches(
			'/Invalid arg type in call pattern: Argument 1.*'.
			'must be of the type int, string given/'
		);
		match('b', [
			when(fn(int $a) => true) => 0,
		]);
	}

	public function testMatchExceptionOnResultCallTypeError()
	{
		$this->expectException(MatchException::class);
		$factorial = function ($i) use (&$factorial) {
			return match($i, [
				0 => 1,
				_ => fn(int $n) => $n * $factorial($n - 1),
			]);
		};
		$factorial('a');
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

	public function testScopelessDestructureForResultWithCall()
	{
		$value = (object)['x' => (object)['y' => fn($a) => $a === 'foo' ? 'bingo' : 'nope']];
		$result = match($value, [
			when($a = at()->x) => $a->y('foo')
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

	public function testArrayExactValueReturn()
	{
		$result = match(2, [
			1 => false,
			2 => val([]),
		]);
		$this->assertSame([], $result);
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

	public function testGetOddKeyedElements()
	{
		$matcher = function ($list, $result = []) use (&$matcher) {
			return match($list, [
				when([key::k(fn(int $k) => $k % 2 !== 0) => 'v'])
					=> function ($k, $v) use (&$list, &$result, $matcher) {
						unset($list[$k]);
						$result[] = $v;
						return $matcher($list, $result);
					},
				_ => fn() => $result,
			]);
		};

		$list = [1, 2, 3, 4, 5, 6, 7, 8];
		$this->assertSame([2, 4, 6, 8], $matcher($list));
	}

	/** @dataProvider casesMatchPointObject */
	public function testMatchPointObject($object, $expect)
	{
		$result = match($object, [
			when(MatchTest_Point::class, ['x', 'y' => 0]) => fn($x) => "X: $x",
			when(MatchTest_Point::class, ['x' => 0, 'y']) => fn($y) => "Y: $y",
			when(MatchTest_Point::class, ['x', 'y']) => fn($x, $y) => "X,Y: $x,$y",
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
}

class MatchTest_Point
{
	public $x;
	public $y;

	public function __construct($x, $y)
	{
		$this->x = $x;
		$this->y = $y;
	}
}

class MatchTest_Shape
{
}
