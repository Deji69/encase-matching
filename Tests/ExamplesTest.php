<?php
namespace Encase\Matching\Tests;

use RuntimeException;
use Encase\Matching\Key;
use Encase\Functional\Type;
use const Encase\Matching\Support\_;
use function Encase\Matching\Support\key;
use function Encase\Matching\Support\val;
use function Encase\Matching\Support\when;
use function Encase\Matching\Support\match;

class ExamplesTest extends TestCase
{

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

	public function testFactorialExample()
	{
		$factorial = function ($i) use (&$factorial) {
			return match($i, [
				0 => 1,
				_ => fn(int $n) => $n * $factorial($n - 1),
			]);
		};

		$this->assertSame(1, $factorial(0));
		$this->assertSame(1, $factorial(1));
		$this->assertSame(2, $factorial(2));
		$this->assertSame(24, $factorial(4));
	}
}
