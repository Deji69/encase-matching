Encase Pattern Matching Library
===============================
*Functional-style pattern matching for those with a syntactic sweet tooth.*

- [Encase Pattern Matching Library](#encase-pattern-matching-library)
- [Overview](#overview)
  - [Syntax](#syntax)
    - [Matcher Syntax](#matcher-syntax)
    - [Case Conditions](#case-conditions)
    - [Case Results](#case-results)
    - [Match Guarding](#match-guarding)
  - [Pattern Overview](#pattern-overview)
- [Patterns](#patterns)
  - [Constant Pattern](#constant-pattern)
  - [Type Pattern](#type-pattern)
  - [Any Pattern](#any-pattern)
  - [Wildcard Pattern](#wildcard-pattern)
  - [Binding Pattern](#binding-pattern)
  - [List Pattern](#list-pattern)
  - [Array Pattern](#array-pattern)
  - [Regex Pattern](#regex-pattern)
- [Further Examples](#further-examples)
  - [FizzBuzz](#fizzbuzz)
  - [Factorial](#factorial)
  - [Extract Array Elements Having Odd Keys](#extract-array-elements-having-odd-keys)
  
# Overview

Pattern Matching is a popular tool which has been increasingly implemented in many non-functional languages. PHP has no such feature and userland implementation of it can be tricky due to its relatively tight syntax restraints and limited metaprogramming capabilities. At least one attempt has been made to bring this feature to PHP in a library ([functional-php/pattern-matching](https://github.com/functional-php/pattern-matching)). This library attempts to offer powerful userland pattern matching a syntax which fits in with PHP better.

```php
$result = match(3, [
    0 => 'zero',
    when(Type::int()) => [
        when(fn($n) => $n % 2 !== 0) => 'odd',
        when(fn($n) => $n % 2 === 0) => 'even',
    ],
    _ => 'not a number!'
]); // odd
```

If no cases match the given arguments, a `MatcherException` exception is thrown. Default cases are made using `_` or `'_'`, and will be called if no other case matches instead of throwing an exception.

## Syntax

Two free standing functions exist for building and matching patterns: `match()` and `when()`. The `match()` function takes 2 arguments, the first is the value to be matched and the second is an array containing match cases. A match case looks like a free-standing, single pattern argument or one or more pattern arguments surrounded by `when`, followed by `=>` and then the result on the right...

```php
$matcher = match(3, [
    when(1, 2, 3) => 'one, two or three',
    when(4) => 'four',
    5 => 'five',
    _ => 'something else',
]);
```

### Matcher Syntax

```php
// matcher-syntax
    match(/* var */, /* match-array */);
// match-array
    [/* match-case[, match-case...] */]
// match-case:
    /* pattern-arg */ => /* case-result */
    when(/* pattern-arg[, pattern-arg...] */) => /* case-result */
// case-result: value|function|match-array
```

### Case Conditions

Inside the array for a match statement, case conditions make up the of the array. Since PHP arrays only accept string and integer keys (and note that numeric strings are converted to integers for array keys), a `when()` helper function is provided to wrap more complex pattern arguments. This allows patterns to be built using more than one argument, and using non array key compatible types such as arrays and objects (including closures). See [Pattern Overview](#pattern-overview) for more information on the patterns available and the argument types used to build them. Under the hood, the `when()` function returns a string representing the pattern object to be built with the provided arguments which can be used as an array key, and looked up later to retrieve the pattern object.

```php
arg1 => ...                 // one string/int argument used to build pattern
when(arg1, ...) => ...      // one or more arguments used to build pattern, wrapped in when()
```

Single string and integer arguments are treated the same (aside from PHP's own key handling) no matter whether they are wrapped in `when()`, thus omitting `when()` for single arguments can be largely treated as a shortened syntax. However, using `when()` is still better for type safety due to PHP's array key handling and may also be slightly faster if the match is performed more than once due to the pattern object already being built.

Note that single string arguments which match names of parameters in related closure case conditions and case result closures are treated as bind names to capture rather than the exact strings themselves. If you need strings which match var names in case results and sub-case patterns and results, wrap the string in `Encase\Matching\Support\val()`.

```php
$result = match((object)['x' => 10, 'y'], [
    when(['x', val('y')]) => [
        when(fn($x) => $x > 100) => 'x is out of bounds',
        when(fn($obj, $y = 0) => $y > 100) => 'y is out of bounds',
    ],
    when(['x', 'y']) => fn($x) => "x = $x",
    _ => 'error',
]); // result: x = 10
```

Note that if `x` was originally assigned a value higher than 100, `'x is out of bounds'` would instead be the result as both outer patterns match the object being destructured. If the `'y'` value in the object was changed to `'z'`, `'error'` would instead be the result as neither patterns match. If `val()` was not used around `'y'`, there would be an error as the object would be checked for the `y` property as there is a `$y` parameter in the sub-case parameter lists.

In the 2nd outer `when`, `'y'` is already only used to match the value in the object. `val()` doesn't need to be used because none of the closures within the same scope are using that as a parameter name.

### Case Results

The result comes on the right of the `=>` which follows the [Pattern Arguments](#pattern-arguments). Here a value can be provided which will be the result if the case is matched. Alternatively, it can be a closure which is to be called if the case is matched. It can also be an array containing additional "sub-cases", explained in [Match Guarding](#match-guarding).

```php
... => null,                     // return null
... => fn() => echo 'hello',     // say hello
... => [...],                    // match guarding
```

### Match Guarding

Match guarding can be performed with no real additional syntax. Match guarding comes as a product of the recursive ability of this pattern matching implementation. If a case result is an array, it will be treated as a set of sub-cases to recurse to if the case condition passes. These sub-cases can have results as normal, but can too have their own sub-cases, making up what is traditionally a complex if-elseif-else structure.

```php
echo match($i, [
    when(Type::int()) => [
        when(fn($n) => $n <= 0) => '',
        when(fn($n) => $n % 3 == 0) => [
            when(fn($n) => $n % 5 == 0) => 'fizzbuzz',
            _ => 'fizz',
        ],
        when(fn($n) => $n % 5 == 0) => 'buzz',
        _ => $i,
    ],
    _ => function() {
        throw new RuntimeException('input was not an int');
    }
]);
echo "\n";
```

If a `when()` pattern arg is a closure, it is either given previously destructured values inherited from the parent case, or the matched value itself.

## Pattern Overview

The following table lists the types of patterns supported by this library and their syntax. The links will direct you to further information about a particular type of pattern.

| Name                   | Description            | Example                         |
| ---------------------- |----------------------- | ------------------------------- |
 [Constant Pattern](#constant-pattern) | Matches exact values | `"str"` or `3.5` or `5` or `when(val($var))`
 [Type Pattern](#type-pattern) | Matches values by type | `when(Type::int())` or `when(Type::object(MyClass::class))` or `when(MyClass::class, [])`
 [Any Pattern](#any-pattern) | Matches if any value matches | `when(1, 2, 3)` or `when(1, 'one')` or `when(any(...))`
 [Wildcard Pattern](#wildcard-pattern) | Matches anything | `_` or `'_'`
 [Binding Pattern](#binding-pattern) | Matches on a pattern and captures the value | `'n'` or `'n' => ...`
 [List Pattern](#list-pattern) | Matches a list of elements | `when(['first', '*', 'last'])`
 [Array Pattern](#array-pattern) | Matches key-value pairs in an associative array. | `key('foo') => 'bar'` or `key::oddKey(Type::int(), fn ($n) => $n % 2 === 0) => _`
 [Regex Pattern](#regex-pattern) | Matches strings to regular expressions | `'/[A-Z]*/i'` or `when('/(?P<num>[0-9])/')`

# Patterns

## Constant Pattern

Constant patterns are values values to be matched exactly. Comparisons are performed using the strict equality (`===`) operator. Not that due to the way PHP handles array keys, converting numeric strings to integers, only integers and non-numeric strings can be matched to.

```php
$matcher = fn($val) => match($val, [
    1 => 'one',
    '2' => 'two',
    '3.14159' => 'pi',
    'boo' => 'hoo',
    _ => false,
]);
$matcher('1');          // false
$matcher(1);            // 'one'
$matcher('2');          // false
$matcher(2);            // 'two'
$matcher('3.14159');    // 'pi'
$matcher('boo');        // 'hoo'
```

This is sufficient as a less verbose way to strictly match integers and strings. If you want values to avoid the issues and limitations of PHP array keys, use `Encase\Matching\Support\when()`:

```php
$matcher = fn($val) => match($val, [
    when(0) => 'zero (int)',
    when(1) => 'one (int)',
    when('') => 'empty string',
    when(null) => 'null',
    when(true) => 'true (bool)',
    when(false) => 'false (bool)',
]);

$matcher(0);        // zero (int)
$matcher(1);        // one (int)
$matcher('');       // empty string
$matcher(null);     // null
$matcher(true);     // true
$matcher(false);    // false
```

The pattern builder may use certain arguments as non-literal patterns. For example, the string `'foo'` may be interpreted as a binding name if there exists a `$foo` parameter argument in the same match case. You can use `val()` if you prefer parameters to be interpreted as exact values.

## Type Pattern

Values can be matched based on their type using the `Encase\Functional\Type` class type representation from the Encase\Functional library. If simply matching objects of a certain type, then `(className, [])` can instead be used, where `className` is the fully-qualified class name of the class.

```php
use Encase\Functional\Type;

$pattern = fn($val) => match($val, [
    when(Type::null()) => 'null found',
    when(Type::int()) => 'int found',
    when(Type::float()) => 'float found',
    when(Type::string()) => 'string found',
    when(Type::object(\stdClass::class)) => 'stdClass object found',
    when(Type::object()) => 'object found',
    when(Type::class, []) => 'Type object found'.
]);
$pattern(42); // result: int found
$pattern((object)['a' => 'stdClass']); // result: 'stdClass object found'
$pattern(new MyClass); // result: 'object found'.
$pattern(Type::int()); // result: 'Type object found'
```

## Any Pattern

Multiple [constant pattern](#constant-pattern)s make up a pattern group which matches if any one of the patterns, much like a logical OR operation.

```php
match('1,000', [
    when(1, 2, 3) => '1, 2 or 3 (int)',
    when('1,000', 1000) => 'one thousand',
]); // one thousand
```

Note that if any one of the arguments is an array or object, the group instead matches *all* patterns. Use `Encase\Matching\Support\any()` to force an any pattern in these cases. Likewise, you can ensure an all pattern with `Encase\Matching\Support\all()`.

## Wildcard Pattern

Wildcards can be used with the `Encase\Matching\Support\_` constant or `'_'`. A `_` on its own is treated as equivalent to `'_'`. Wildcards ignore arguments and elements and do not bind to parameters. For binding, see [Binding Pattern](#binding-pattern).

```php
use Encase\Matching\Support\_;

match(['a', 12], [
    when([]) => '0 items',
    when([_]) => '1 item',
    when([_, _]) => '2 items',
    when([_, _, _]) => '3 items',
]); // result: 2 items
```

## Binding Pattern

Arguments can be bound and used in results, sub-case conditions and sub-case results, etc.

```php
$getParity = fn($val) => match($val, [
    when(Type::int()) => [
        when(fn($v) => $v % 2 == 0) => 'even',
        _ => 'odd',
    ]
    'n' => fn($n) => "$n is not an integer",
]);

$getParity(5);      // result: 'odd'
$getParity(8);      // result: 'even'
$getParity('foo');  // result: 'foo is not an integer'
```

Note that the existence of the `$n` parameter itself makes `'n'` a bind name rather than a plain string. Use `Encase\Matching\Support\val()` instead for an exact value based match.

## List Pattern

Arguments within `when([...])` will match lists of values (ordered array values), unless `Encase\Matching\Support\key()` is used for one of them, in which case it becomes an [Array Pattern](#array-pattern).

```php
$getTicTacToeRowResult = fn($list) => match($list, [
    when(['x', 'y', 'z']) => [
        when(fn($x, $y, $z) => $x == $y && $y == $z) => [
            when(fn($x) => $x == 'x') => 'crosses wins!',
            when(fn($x) => $x == 'o') => 'naughts wins!',
        ],
    ],
    when(['x', 'o', 'x']) => fn($xox) => \implode(',', $xox).'!'
]);
$getTicTacToeRowResult(['o', 'o', 'o']); // naughts wins!
$getTicTacToeRowResult(['x', 'o', 'x']); // x,o,x!
```

Within a list pattern, you can match the "remaining" arguments with `'*'`, or for example `'*param'` if you want to bind to a `$param` parameter in the case result.

```php
$getPalindromeType = function($list) use (&$isPalindrome) {
    return match($list, [
        when(['h', '*m', 't']) => [
            when(fn($h, $t) => $h === $t) => fn($m) => $getPalindromeType($m),
        ],
        when(['head', 'tail']) => [
            when(fn($head, $tail) => $head === $tail) => 'even',
        ],
        when([_]) => 'odd',
        _ => false,
    ]);
};
```

To match a value by pattern, yet also bind it, use `'paramName' => pattern...`.

```php
$getReservedSeat = fn($seat) => match($seat, [
    when(['row' => Type::int(), 'seat' => '/\A[A-C]\z/'])
        => fn($row, $seat) => "You are seated at $row-$seat",
    _ => 'Seat allocation is invalid',
]);
$getReservedSeat([22, 'B']);  // 'You are seated at 22-B'
$getReservedSeat([16, 'C']);  // 'You are seated at 16-C'
$getReservedSeat([14, 'D']);  // 'Seat allocation is invalid'
```

## Array Pattern

Using the basic syntax of the [List Pattern](#list-pattern) along with the `Encase\Matching\Support\key()` helper function, you can also match associative elements in arrays. Note that use of `key()` within the array will turn a list pattern into an array pattern. A vital difference is that a list pattern is exhaustive and won't match if elements are left unmatched. However with an array pattern, unmatched elements are ignored.

For illustration, lets first look at how list patterns can and cannot match associative arrays:

```php
// list pattern cannot bind keys, can only match in-order, and must match exhaustively
match(['dog' => 'cat', 'a' => 'b'], [
    when(['hunter' => 'cat']) => fn($hunter) => "cat gets chased by $hunter",
    when(['hunter' => 'cat', 'a' => 'b']) => fn($hunter) => $hunter;
]); // cat
```

Note how `'hunter'` in `'hunter' => 'cat'` is treated as a binding name rather than a key, asserting the value *must* be `'cat'` and will be bound to `$hunter`. The `'a' => 'b'` pair however is treated as a key-value pair, to be matched exactly with the element occurring at the 2nd position in the array. The matching is still exhaustive even though a key was matched.

We can use `key()` to match key values with exact values and patterns *and* make the pattern a non-exhaustive array pattern rather than a list pattern:

```php
// PHPs unambiguous, case-insensitive syntax rules means we can use both the
// `key` class and function as `key`
use Encase\Matching\Support\Key;
use function Encase\Matching\Support\key;

// use key('key') to match keys and key::bindName(...) to bind keys matching a
// pattern
$hunt = fn($map) => match($map, [
    when([key('cat') => 'prey']..)
        => fn($prey) => "cat chases $prey",
    when([key::hunter() => 'cat'])
        => fn($hunter) => "cat gets chased by $hunter",
    when([key('mouse') => 'other'])
        => fn($other) => "mouse hides, $other rests",
]);

$hunt(['mouse' => 'dog', 'a' => 'b']);  // mouse hides, dog rests
$hunt(['cat' => 'mouse', 'a' => 'b']);  // cat chases mouse
$hunt(['dog' => 'cat', 'a' =>'b']);     // cat gets chased by dog
```

Using `key()` with a single string or integer argument matches exact element keys. Pattern arguments can be used to match the first element where the key matches the pattern. Using `key::hunter() => 'cat'` binds *any* key to the `$hunter` parameter, and matches so long as the value is `'cat'`.

The single argument of `key()` or `key::param()` is an exact key name or pattern to match. For example you can use `key(Type::string())` to match any string key or `key::param(Type::int())` to match any integer key and bind it to `$param`.

## Regex Pattern

The pattern parser identifies strings starting and ending with a `/` character (the end `/` character may also be followed by valid PCRE flags). These are automatically used to match and validate strings against the given regex. Any named captures are passed to functions where the parameter names match, essentially destructuring the string. Unnamed captures are passed as an array to the first other parameter.

```php
$checkIpDigit = fn($digit) => $digit >= 0 && $digit <= 255;
$ipValidator = fn($ip) => match($ip, [
    '/\A(?P<ip1>\d{1,3})\.(?P<ip2>\d{1,3})\.(?P<ip3>\d{1,3})\.(?P<ip4>\d{1,3})\z/' => [
        when(fn($ip1, $ip2, $ip3, $ip4) => $checkIpDigit($ip1)
            && $checkIpDigit($ip2)
            && $checkIpDigit($ip3)
            && $checkIpDigit($ip4)
        ) => true
    ]
    _ => false
]);
$ipValidator('abc');              // returns: false
$ipValidator('255.255.255.256');  // returns: false
$ipValidator('1.22.255.123')      // returns: true
```

# Further Examples

Most of the examples given so far can probably be further improved in certain ways. Here are some examples that show more optimal approaches to classic tasks pattern matching can solve.

## FizzBuzz

```php
$fizzBuzz = fn($i) => match([$i % 3, $i % 5], [
    when([0, 0]) => 'Fizz Buzz',
    when([0, _]) => 'Fizz',
    when([_, 0]) => 'Buzz',
    _ => $i,
]);
```

## Factorial

```php
$factorial = function ($i) use (&$factorial) {
    return match($i, [
        0 => 1,
        _ => fn(int $n) => $n * $factorial($n - 1),
    ]);
};
```

## Extract Array Elements Having Odd Keys

```php
$matcher = function ($list, $result = []) use (&$matcher) {
    return match($list, [
        when([key::k (fn(int $k) => $k % 2 !== 0) => 'v'])
            => function ($k, $v) use (&$list, &$result, $matcher) {
                unset($list[$k]);
                $result[] = $v;
                return $matcher($list, $result);
            },
        _ => fn() => $result,
    ]);
};

$matcher([1, 2, 3, 4, 5, 6, 7, 8]);     // [2, 4, 6, 8]
```