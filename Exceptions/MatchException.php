<?php
namespace Encase\Matching\Exceptions;

use Closure;
use Exception;
use Throwable;
use ReflectionParameter;
use Encase\Functional\Func;
use Encase\Functional\Type;
use Encase\Matching\MatchCase;
use function Encase\Functional\map;
use function Encase\Functional\join;
use Encase\Matching\Patterns\Pattern;
use Encase\Matching\Patterns\TypePattern;
use Encase\Matching\Patterns\ExactPattern;
use Encase\Matching\Patterns\GroupPattern;
use Encase\Matching\Patterns\ClosurePattern;
use Encase\Matching\Patterns\WildcardPattern;

class MatchException extends Exception
{
	/**
	 * Create MatchException
	 *
	 * @param mixed $value
	 * @param array $caseResults
	 * @return self
	 */
	public static function new($value, $caseResults): self
	{
		$message = 'No case matched '.Type::annotate($value).':';

		$shiftCasesWithPatternType = function ($class) use (&$caseResults) {
			$values = [];
			$errors = [];

			while (!empty($caseResults)) {
				$caseResult = \reset($caseResults);
				/** @var MatchCase $case */
				$case = $caseResult['case'];
				/** @var Exception $error */
				$error = $caseResult['error'];
				$pattern = $case->getPattern();

				if ($error !== null) {
					$errors[] = $error;
				}

				if (\get_class($pattern) !== $class) {
					break;
				}

				\array_shift($caseResults);
				$values[] = static::representPattern($pattern);
			}

			return [$values, $errors];
		};

		while (!empty($caseResults)) {
			$caseResult = \array_shift($caseResults);

			/** @var MatchCase $case */
			$case = $caseResult['case'];

			/** @var Exception $error */
			$error = $caseResult['error'];

			/** @var Exception $resultError */
			$resultError = $caseResult['resultError'];

			$values = [];
			$errors = [];
			$pattern = $case->getPattern();

			$message .= $resultError === null ? "\n  did not match" : "\n  matched with";
			$skipPatterns = false;

			if ($pattern instanceof GroupPattern) {
				$connective = $pattern->getConnective() === 'and' ? 'all' : 'any';
				$message .= " $connective: ".static::representCasePatterns($case);
			} elseif ($pattern instanceof ExactPattern) {
				[$values, $errors] = $shiftCasesWithPatternType(ExactPattern::class);
				$message .= " exact values: ";
			} elseif ($pattern instanceof TypePattern) {
				[$values, $errors] = $shiftCasesWithPatternType(TypePattern::class);
				$typePlurality = \count($values) > 1 ? 'types' : 'type';
				$message .= " $typePlurality: ";
			} elseif ($pattern instanceof WildcardPattern) {
				$message .= " _:";
				$skipPatterns = true;
			} else {
				[$values, $errors] = $shiftCasesWithPatternType(\get_class($pattern));
				$message .= ": ";
			}

			if (!$skipPatterns) {
				\array_unshift($values, static::representPattern($pattern, $error));
				$message .= \implode(', ', $values);
			}

			\array_unshift($errors, $error);

			foreach ($errors as $error) {
				if ($error === null) {
					continue;
				}

				$message .= "\n    Exception: ".$error->getMessage();
			}

			if ($resultError !== null) {
				$subErrorMessage = \explode("\n", $resultError->getMessage());
				$subErrorMessage = \implode("\n    ", $subErrorMessage);
				$message .= "\n    Exception: $subErrorMessage";
			}
		}

		return new self($message);
	}

	protected static function representCasePatterns(MatchCase $case): string
	{
		$patterns = '';
		$pattern = $case->getPattern();

		if ($pattern instanceof GroupPattern) {
			$connective = $pattern->getConnective();
			$patterns = join(map($pattern->getPatterns(), function($p) {
				if ($p instanceof Pattern) {
					return static::representPattern($p);
				}
				return static::representValue($p);
			}), ', ', " $connective ");
		} else {
			$patterns = self::representPattern($pattern);
		}
		return $patterns;
	}

	protected static function representPattern(Pattern $pattern, Throwable $err = null): string
	{
		if ($pattern instanceof ExactPattern) {
			return static::representValue($pattern->getValue());
		} elseif ($pattern instanceof TypePattern) {
			$type = $pattern->getType();

			if ($type->type === 'object') {
				return (string)$type->class;
			}

			return (string)$type;
		} elseif ($pattern instanceof ClosurePattern) {
			return static::representValue($pattern->getFunction());
		}
		return \get_class($pattern);
	}

	protected static function representValue($value)
	{
		if (\is_string($value)) {
			$value = '\''.\addcslashes($value, '\'').'\'';
		}

		if (\is_object($value)) {
			if ($value instanceof Closure || $value instanceof Func) {
				$refl = Func::box($value)->getReflection();
				$params = join(map($refl->getParameters(), function(ReflectionParameter $param) {
					$name = $param->getName();
					$type = $param->hasType() ? $param->getType()->getName() : null;
					return ($type !== null ? "$type " : '')."\$$name";
				}), ', ');
				$ret = $refl->hasReturnType() ? (': '.$refl->getReturnType()) : '';
				return "fn($params)$ret";
			}
			return (string)Type::of($value);
		}

		return (string)$value;
	}
}
