<?php
namespace Encase\Matching;

use Closure;
use Encase\Regex\Regex;
use Encase\Functional\Func;
use Encase\Functional\Type;
use Encase\Matching\Patternable;
use const Encase\Matching\Support\_;
use Encase\Matching\Patterns\Pattern;
use function Encase\Functional\typeOf;
use Encase\Matching\Patterns\ListPattern;
use Encase\Matching\Patterns\RestPattern;
use Encase\Matching\Patterns\TypePattern;
use Encase\Matching\Patterns\AssocPattern;
use Encase\Matching\Patterns\ExactPattern;
use Encase\Matching\Patterns\GroupPattern;
use Encase\Matching\Patterns\RegexPattern;
use Encase\Matching\Patterns\ObjectPattern;
use Encase\Matching\Patterns\ClosurePattern;
use Encase\Matching\Patterns\WildcardPattern;
use Encase\Matching\Exceptions\PatternException;
use Encase\Matching\Patterns\DestructurePattern;
use Encase\Regex\Patternable as RegexPatternable;
use Encase\Matching\Exceptions\PatternBuilderException;

class PatternBuilder
{
	/**
	 * Build a pattern from multiple arguments.
	 *
	 * @param  array $args
	 * @param  string[] $bindNames
	 * @param  bool $exact If TRUE, exact patterns are preferred.
	 * @return Pattern
	 */
	public static function buildArgs(array $args, array $bindNames = [], bool $exact = false): Pattern
	{
		$numArgs = \count($args);

		if ($numArgs === 0) {
			throw new PatternBuilderException(
				'No arguments to build pattern.'
			);
		} elseif ($numArgs === 1) {
			$arg = \reset($args);

			if ($pattern = static::buildArg($arg, $bindNames, $exact)) {
				return $pattern;
			}
		} elseif ($numArgs === 2) {
			if (\is_string($args[0]) && (\is_array($args[1]) || \is_object($args[1]))) {
				$type = Type::object($args[0]);

				if (!empty($args[1])) {
					return static::buildMap($args[1], $type, $bindNames);
				}
				return static::buildArg($type, $bindNames);
			}
		}

		return static::buildAll($args);
	}

	/**
	 * Build a pattern from a single argument.
	 *
	 * @param  mixed $arg
	 * @param  array $bindNames
	 * @param  bool $exact
	 * @return Pattern|null
	 */
	public static function buildArg($arg, array $bindNames = [], bool $exact = false): Pattern
	{
		if ($exact) {
			return new ExactPattern($arg);
		}

		switch (typeOf($arg)) {
			default:
			case 'null':
			case 'int':
			case 'float':
			case 'bool':
				return new ExactPattern($arg);

			case 'object':
				if ($built = static::buildObjectArg($arg, $bindNames)) {
					return $built;
				}
				return null;

			case 'array':
				if ($built = static::buildList($arg, $bindNames)) {
					return $built;
				}
				return null;

			case 'string':
				return static::buildStringArg($arg, $bindNames);
		}
	}

	/**
	 * Build a pattern from a string argument.
	 *
	 * @param  string $arg
	 * @param  string[] $bindNames
	 * @return Pattern|null
	 */
	public static function buildStringArg(string $arg, array $bindNames = []): ?Pattern
	{
		if (empty($arg)) {
			return new ExactPattern($arg);
		}

		if ($arg === _ || $arg === '_') {
			return new WildcardPattern();
		}

		if (\in_array($arg, $bindNames, true)) {
			return new WildcardPattern($arg);
		}

		if (\class_exists(Regex::class) && Regex::isRegexString($arg)) {
			return new RegexPattern(new Regex($arg));
		}

		return static::parseStringPattern($arg, $bindNames);
	}

	/**
	 * Build a pattern from an object argument.
	 *
	 * @param  object $arg
	 * @param  string[] $bindNames
	 * @return Pattern
	 */
	public static function buildObjectArg(object $arg, array $bindNames = [])
	{
		if ($arg instanceof At) {
			return static::buildAt($arg, $bindNames);
		}

		if ($arg instanceof Closure || $arg instanceof Func) {
			return new ClosurePattern($arg);
		}

		if ($arg instanceof Matchable) {
			return $arg;
		}

		if ($arg instanceof Patternable) {
			return $arg->getPattern($bindNames);
		}

		if ($arg instanceof RegexPatternable) {
			return new RegexPattern($arg);
		}

		if ($arg instanceof Type) {
			return new TypePattern($arg);
		}

		return static::buildMap($arg, Type::of($arg), $bindNames);
	}

	public static function buildAt(At $at)
	{
		return new DestructurePattern($at);
	}

	/**
	 * Parse a pattern from a string argument.
	 *
	 * @param  string $arg
	 * @param  string[] $bindNames
	 * @return ExactPattern|RestPattern
	 */
	public static function parseStringPattern(string $arg, $bindNames = []): Pattern
	{
		if (!empty($arg)) {
			if ($arg[0] === '*') {
				$varName = \substr($arg, 1);

				if (empty($varName)) {
					return new RestPattern();
				} elseif (\in_array($varName, $bindNames)) {
					return new RestPattern($varName);
				}
			}
		}
		return new ExactPattern($arg);
	}

	/**
	 * Build an "any" pattern.
	 *
	 * @param  array $values Patterns or values of which at least one must
	 *                       match.
	 * @return GroupPattern
	 */
	public static function buildAll($values): GroupPattern
	{
		$connective = 'or';

		foreach ($values as $value) {
			if (\is_object($value) || \is_array($value)) {
				$connective = 'and';
				break;
			}
		}
		return new GroupPattern($values, $connective);
	}

	/**
	 * Build an object destructuring pattern.
	 *
	 * @param  array|object $map
	 * @param  \Encase\Functional\Type $type
	 * @param  string[] $bindNames
	 * @return ObjectPattern
	 */
	public static function buildMap($map, Type $type, array $bindNames = []): ObjectPattern
	{
		return new ObjectPattern($type, $map, $bindNames);
	}

	/**
	 * Build a list pattern.
	 *
	 * @param  array $args
	 * @param  string[] $bindNames
	 * @return ListPattern
	 */
	public static function buildList($args, array $bindNames = []): ListPattern
	{
		$list = [];
		$hasRestPattern = false;
		$isMapped = false;
		$leaveAfterRest = 0;

		foreach ($args as $k => $v) {
			$keyIsBind = false;

			if ($v instanceof Patternable) {
				$pattern = $v->getPattern($bindNames);
			} elseif ($v instanceof Matchable) {
				$pattern = $v;
			} else {
				$pattern = static::buildArg($v, $bindNames);
			}

			if (!\is_int($k)) {
				if ($key = KeyRepository::get($k)) {
					$pattern = new AssocPattern(
						$key->getPattern($bindNames),
						$pattern
					);
					$isMapped = true;
				} elseif (\in_array($k, $bindNames, true)) {
					$keyIsBind = true;
				} else {
					$pattern = new AssocPattern(
						static::buildArg($k, $bindNames),
						$pattern
					);
				}
			}

			if ($keyIsBind && $pattern instanceof MatchBindable) {
				$pattern->setBindName($k);
			}

			if ($pattern instanceof Pattern) {
				if ($pattern instanceof RestPattern) {
					if ($hasRestPattern) {
						throw new PatternException(
							'Only one \'*\' pattern allowed in a list.'
						);
					}

					$hasRestPattern = true;
					$leaveAfterRest = \count($args) - \count($list) - 1;
				}
			}

			$list[] = $pattern;
		}

		return new ListPattern($list, $leaveAfterRest, $isMapped, $bindNames);
	}
}
