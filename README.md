Encase Pattern Matching Library
===============================
*Functional-style pattern matching for those with a syntactic sweet tooth.*

- [Encase Pattern Matching Library](#encase-pattern-matching-library)
- [Overview](#overview)
	- [Design](#design)
		- [Quirks &amp; Limitations](#quirks-amp-limitations)
	- [Syntax](#syntax)
		- [Matcher Syntax](#matcher-syntax)
		- [Pattern Argument Syntax](#pattern-argument-syntax)
		- [If Clause](#if-clause)
		- [Else Clause](#else-clause)
			- [Syntax](#syntax-1)
			- [Example](#example)
		- [Result Syntax](#result-syntax)
			- [Return a Value](#return-a-value)
			- [Return a Binding](#return-a-binding)
			- [Call a Function](#call-a-function)
			- [Recurse the Matcher](#recurse-the-matcher)
	- [Pattern Overview](#pattern-overview)
- [Patterns](#patterns)
	- [Constant Pattern](#constant-pattern)
	- [Type Pattern](#type-pattern)
	- [Wildcard Pattern](#wildcard-pattern)
	- [Binding Pattern](#binding-pattern)
	- [Regex Pattern](#regex-pattern)
  
# Overview

Pattern Matching is a popular tool which has been increasingly implemented in many non-functional languages. PHP has no such feature and userland implementation of it can be tricky due to its relatively tight syntax restraints and limited metaprogramming capabilities. At least one attempt has been made to bring this feature to PHP in a library ([functional-php/pattern-matching](https://github.com/functional-php/pattern-matching)). This library tries to take a different aproach to (ab)using arrays and parsing strings by (ab)using what few PHP features exist that allow creation of new syntactic sugar...

```php
$result = match(3, pattern()
	[0]   ->v('zero')
	('n') ->if(fn($n) => $n % 2 !== 0) ->v('odd')
	('n') ->if(fn($n) => $n % 2 === 0) ->v('even')
	()    ->v('not a number!')
);
```

If no cases match the given arguments, a `MatcherException` exception is thrown. Default cases are made using `()`, and will be called if no other case matches instead of throwing an exception.

## Design

The thought process behind the design of this library is probably worth a mention, either for an understanding of the choices or just to satisfy any curiosity.

This library does not attempt to base its approach much on any one other languages implementation of pattern matching and instead provides something that fits better into PHP's existing syntax and that existing PHP developers with no experience in FP  languages are more likely to find approachable. I've tried to think more about what PHP problems can be solved with it than pretending that there'd be much use for Haskell style pattern matching in PHP.

### Quirks & Limitations

The array accessing syntax `[]` is used to match values against constants or other variables. Amazingly, PHP does not enforce the type of value used within square brackets. Prior to PHP 7.4, `{}` could be used, which was the original preference - this syntax however was deprecated in PHP 7.4, so cannot be used without deprecation notices. Some of the syntactical choices where made around the fact that you cannot use `[]` or `{}` with values that aren't integers or strings, and then immediately follow it up with a call `()`, as this is interpreted similarly to `->arg()` and the `__call` magic method enforces a string method name.

The necessary overhead incurred by building patterns at runtime is mitigated somewhat by caching where possible. Reflection is used to determine how to pass bindings to [If Clauses](#if-clause) and when [calling functions](#call-a-function), but the information is saved in a way which makes re-using the same matcher object work faster on repeated calls or when recursed. The pattern matching syntax is fairly well enforced at build time but some things may not be checkable until the matcher is first used - for example, handling whether the patterns cover all possible cases.

## Syntax

Two free standing functions exist for building and matching patterns: `match()` and `pattern()`. The `match()` function takes at least 1 argument, the last argument must be a matcher which will be invoked with the other arguments. The `pattern()` function begins construction of a matcher and also has a `match()` method to match arguments with. Thus, two pattern matching approaches exist, "pattern first" and "match first":

**Match First**
```php
$result = match($arg, pattern()
	// Patterns...
);
```

**Pattern First**
```php
$result = pattern()
	// Patterns...
->match($arg);
```

The result of `pattern()` including the pattern syntax is always an object on which `->match(...)` could be called, thus you can even save matchers in a variable to re-use later:

```php
$matcher = pattern()
	// Patterns...
;

$matcher->match(...);
match(..., $matcher);
```

### Matcher Syntax

A call to `pattern()` returns a MatcherBuilder object, allowing a unique syntax for building a pattern matcher.

```php
pattern()
	pattern-argument[, pattern-argument...]
		[
			->if(cond)
			[->else -> result-expression]
		]
		-> result-expression
```

See [Pattern Argument Syntax](#pattern-argument-syntax) for the syntax of `pattern-argument` and [Result Syntax](#result-syntax) for the syntax of `result-expression`. The `if` clause is optional and information can be found in [If Clause](#if-clause). If there is an `if` clause, an `else` can optionally be added and information on it can be found in [Else Clause](#else-clause).

### Pattern Argument Syntax

A pattern argument looks like `[...]` for exact value arguments or `(...)` for pattern arguments, where `...` is one or more (in thecaseof pattern arguments) arguments to build a pattern. However `(...)` will also fall back to matching exactly if `...` does not make up a valid pattern.

### If Clause

After the pattern arguments, an `if` clause can optionally be defined to further filter the case based on the matched patterns.

```php
->if(cond)
```

`cond` must be a function. On trying to match one or more arguments, the arguments are first matched to the pattern arguments and any resulting captures can be passed to the `cond` function. On the first call, reflection will be used to determine which captures to pass to the function, and this information will be cache'd for any future matches. Any named capture matching a paramter name are passed to that paramter. For any other parameters, unnamed captures are passed in-order to the function.

### Else Clause

After an [if clause](#if-clause), an `else` clause can optionally be defined. Usually, if an `if` clause fails, the matcher proceeds to the next pattern case. In some cases, you may still want to specify an action if the current pattern matched but the `if` clause failed. An `else` clause allows you to do this without creating another pattern case which matches the same arguments and omitting the `if` clause.

#### Syntax
```php
->if(cond)
->else -> result-expression
-> result-expression
```

Following an `if` clause, `else` is specified, which must be followed by a [result expression](#result-syntax). This result expression must also be followed by the result expression for the `if` clause.

#### Example

```php
pattern()
	('a') ->if(fn($a) => $a > 10)
	      ->v('it is over 10')
	('a') ->if(fn($a) => $a < 10)
	      ->else->v('it is 10')
	      ->v('it is under 10')
;
```

### Result Syntax

After all match arguments are given, and after the optional [if clause](#if-clause), the result must be specified after `->`.

There are 4 possibilities for each match cases result: return a value, return a binding, call a function or recurse the matcher.

#### Return a Value

```php
->v(5)
```

In this case, the value given will be returned as-is.

#### Return a Binding

```php
->ret('binding')
```

Where `'binding'` is a string with the name of any pny pattern argument binding, the bound value will be returned.

#### Call a Function

```php
->f(func)
```

Given `func` is a callable, it will be called and any bindings that match the parameters will be passed in the same manner as with the [if clause](#if-clause). The result of this call will be the result of the match expression.


#### Recurse the Matcher

```php
->continue(...)
```

The matcher will be re-invoked. `...` is an optional list of bindings to pass to the next iteration of the matcher. 

## Pattern Overview

The following table lists the types of patterns supported by this library and their syntax. The links will direct you to further information about a particular type of pattern.

| Name                   | Description            | Example                         |
| ---------------------- | ---------------------- | ------------------------------- |
 [Constant Pattern](#constant-pattern) | Matches exact values | `["str"]`, `[3.5]`, `[5]`, `[$var]`
 [Type Pattern](#type-pattern) | Matches values by type | `(Type::int())`, `(Type::object('MyClass'))`
 [Wildcard Pattern](#wildcard-pattern) | Matches anything | `(_)` or `('_')`
 [Binding Pattern](#binding-pattern) | Matches on a pattern and captures the value | `(_(...)->myBind)`
 [Regex Pattern](#regex-pattern) | Matches strings to regular expressions | `('/[A-Z]*/')`
 [List Pattern](#list-pattern) | Matches a list of elements | `('first', _('*')->rest)`

# Patterns

## Constant Pattern

Exact values can be matched by using square brackets `[]` around a constant or variable in a case:

```php
pattern()
	[1]     ->v('one')
	['a']   ->v('A')
	[3.14]  ->v('pi')
	[$var]  ->v('$var')
->match($var); // result: '$var'
```

## Type Pattern

Values can be matched based on their type using the `Encase\Functional\Type` class type representation from the Encase\Functional library.

```php
use Encase\Functional\Type;

$pattern = pattern()
	(Type::null())             ->v('null found')
	(Type::int())              ->v('int found')
	(Type::float())            ->v('float found')
	(Type::string())           ->v('string found')
	(Type::object('stdClass')) ->v('stdClass object found')
	(Type::object())           ->v('object found')
;
$pattern->match((object)['a' => 'stdClass']); // result: 'stdClass object found'
$pattern->match(new MyClass); // result: 'object found'
```

## Wildcard Pattern

Wildcards can be used with `_` or `'_'`. A `_` on its own is equivalent to `'_'`. Note that the `_` symbol can also be called as a function for a [Binding Pattern](#binding-pattern). A single `_` matches any single argument.

```php
pattern()
	(_)         ->v('1 arg')
	(_) (_)     ->v('2 args')
	(_) (_) (_) ->v('3 args')
->match('a', 123); // result: 2 args
```

## Binding Pattern

Arguments can be bound and used later in [if clauses](#if-clause) and [result expressions](#result-syntax). The syntax is `_(pattern-expr)->binding-name` where `pattern-expr` can be any pattern and `binding-name` is the name which shall refer to the matched argument later.

```php
$getParity = pattern()
	(_(Type::int())->v)
		->if(fn($v) => ($v % 2) == 0)
		->else->v('odd')
		->v('even')
	(_()->v)
		->f(fn($v) => '$v is not an integer')
->get();

$getParity(5);     // result: 'odd'
$getParity(8);     // result: 'even'
$getParity('ab');  // result: 'ab is not an integer'
```

In this pattern, we create a binding pattern for arguments matching `Type::int()` (see [Type Pattern](#type-pattern)), and associate it with the name `v`. We can now declare `$v` in parameter lists or use `'v'` in [result expressions](#result-syntax) to use and refer to this binding.

## Regex Pattern

The pattern parser identifies strings starting and ending with a `/` character (the end `/` character may also be followed by valid PCRE flags). These are automatically used to match and validate strings against the given regex. Any named captures are passed to functions where the parameter names match. Unnamed captures are passed in-order to the remaining parameters.

```php
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
match('abc', $ipValidatorPattern);              // returns: false
match('255.255.255.256', $ipValidatorPattern);  // returns: false
match('1.22.255.123', $ipValidatorPattern)      // returns: true
```