<?php
namespace Encase\Matching\Tests;

use stdClass;
use Encase\Functional\Type;
use PHPUnit\Framework\TestCase;
use const Encase\Matching\Support\_;
use function Encase\Functional\type;
use function Encase\Matching\Support\all;
use function Encase\Matching\Support\when;
use function Encase\Matching\Support\match;
use Encase\Matching\Exceptions\MatchException;

class MatchExceptionTest extends TestCase
{
	public function testDidNotMatchExactValues()
	{
		$this->assertMatchException([
			"No case matched int(99):\n",
			"  did not match exact values: 1, 3, 5, 'foo'"
		], function () {
			match(99, [
				1 => 1,
				3 => 3,
				5 => 5,
				'foo' => 'bar'
			]);
		});
	}

	public function testDidNotMatchAnyAll()
	{
		$this->assertMatchException([
			"No case matched null:\n",
			"  did not match any: 1, 2 or 3",
			"  did not match all: int and fn(\$n)"
		], function() {
			match(null, [
				when(1, 2, 3) => '123',
				when(all(Type::int(), fn($n) => $n % 2)) => '456',
			]);
		});
	}

	public function testDidNotMatchCallback()
	{
		$this->assertMatchException([
			"No case matched null:\n",
			"  did not match: fn(int \$v), fn(float \$v), fn(\$v)",
			"    Exception: Argument 1 passed to .* must be of the type int, null given",
			"    Exception: Argument 1 passed to .* must be of the type float, null given"
		], function () {
			match(null, [
				when(fn(int $v) => true) => 'int',
				when(fn(float $v) => true) => 'float',
				when(fn($v) => false) => 'other',
			]);
		});
	}

	public function testDidNotMatchTypes()
	{
		$this->assertMatchException([
			"No case matched string('foo'):\n",
			"  did not match types: int, float, stdClass"
		], function () {
			match('foo', [
				when(type('int')) => 'int',
				when(type('float')) => 'float',
				when(type(stdClass::class)) => 'stdClass'
			]);
		});
	}

	public function testResultTypeError()
	{
		$this->assertMatchException([
			"No case matched string('a'):\n",
			"  did not match exact values: 0\n",
			"  matched with _:\n",
			"    Exception: Arg 1 (\$n) must be of type int, string('a') given",
		], function () {
			match('a', [
				0 => 1,
				_ => fn(int $n) => $n,
			]);
		});
	}

	public function testMatchGuardDidNotMatch()
	{
		$this->assertMatchException([
			"No case matched int(0):\n",
			"  matched with type: int\n",
			"    Exception: No case matched int(0):\n",
			"      did not match exact values: 1, 2",
		], function () {
			match(0, [
				when(Type::int()) => [
					1 => 1,
					2 => 2,
				],
			]);
		});
	}

	protected function assertMatchException($messageParts, $fn)
	{
		$thrown = false;

		try {
			$fn();
		} catch (MatchException $e) {
			$thrown = true;
			$message = $e->getMessage();

			foreach ($messageParts as $part) {
				$part = \addcslashes($part, '()/$');
				$this->assertRegExp('/'.$part.'/', $message);
			}
		}

		$this->assertTrue($thrown);
	}
}
