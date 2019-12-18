<?php
namespace Encase\Matching;

use Encase\Regex\Regex;
use Encase\Functional\Func;
use Encase\Functional\Type;
use Mockery\Matcher\Closure;
use Encase\Matching\PatternArg;
use const Encase\Matching\Support\_;
use Encase\Matching\Patterns\Pattern;
use function Encase\Functional\typeOf;
use Encase\Matching\Patterns\BoolPattern;
use Encase\Matching\Patterns\ListPattern;
use Encase\Matching\Patterns\Patternable;
use Encase\Matching\Patterns\RestPattern;
use Encase\Matching\Patterns\TypePattern;
use Encase\Matching\Patterns\ExactPattern;
use Encase\Matching\Patterns\RegexPattern;
use Encase\Matching\Patterns\CallbackPattern;
use Encase\Matching\Patterns\WildcardPattern;
use Encase\Regex\Patternable as RegexPatternable;
use Encase\Matching\Exceptions\PatternBuilderException;
use Encase\Matching\Exceptions\PatternException;

class PatternBuilder
{
	public static function build(PatternArg $pattern): Pattern
	{
		return static::buildArgs($pattern->args);
	}

	public static function buildArgs(array $args): Pattern
	{
		$numArgs = \count($args);

		if ($numArgs === 0) {
			throw new PatternBuilderException(
				'No arguments to build pattern.'
			);
		} elseif ($numArgs === 1) {
			$arg = \reset($args);

			if ($pattern = static::buildArg($arg)) {
				return $pattern;
			}
		}

		return static::buildList($args);
	}

	public static function buildArg($arg)
	{
		switch (typeOf($arg)) {
			case 'null':
				return new ExactPattern(null);

			case 'int':
			case 'float':
				return new ExactPattern($arg);

			case 'bool':
				return new BoolPattern($arg);

			case 'object':
				if ($built = static::buildObjectArg($arg)) {
					return $built;
				}
				break;

			case 'array':
				if ($built = static::buildMapArg($arg)) {
					return $built;
				}
				break;

			case 'string':
				return static::buildStringArg($arg);
		}

		if (\is_callable($arg)) {
			return new CallbackPattern($arg);
		}

		return null;
	}

	public static function buildMapArg($arg)
	{
		foreach ($arg as $k => $v) {
		}
	}

	public static function buildStringArg($arg)
	{
		if (empty($arg)) {
			return null;
		}

		if ($arg === _ || $arg[0] === '_') {
			return new WildcardPattern();
		}

		if (Regex::isRegexString($arg)) {
			return new RegexPattern(new Regex($arg));
		}

		if (\preg_match('/[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*/', $arg)) {
			return new WildcardPattern($arg);
		}

		return static::parseStringPattern($arg);
	}

	public static function buildObjectArg($arg)
	{
		if ($arg instanceof Type) {
			return new TypePattern($arg);
		}

		if ($arg instanceof Closure || $arg instanceof Func) {
			return new CallbackPattern($arg);
		}

		if ($arg instanceof Patternable) {
			return $arg;
		}

		if ($arg instanceof RegexPatternable) {
			return new RegexPattern($arg);
		}

		if ($arg instanceof Wildcard) {
			$pattern = static::buildArgs($arg->getArgs());
			$pattern->setBindName($arg->getBinding());
			return $pattern;
		}

		return null;
	}

	public static function parseStringPattern($arg)
	{
		if ($arg === '*') {
			return new RestPattern();
		}

		throw new PatternException('Unable to parse string pattern: '.$arg);
	}

	public static function buildList($args)
	{
		$list = [];

		foreach ($args as $arg) {
			$list[] = static::buildArg($arg);
		}

		return new ListPattern($list);
	}
}
