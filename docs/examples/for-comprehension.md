# For Comprehension

For comprehension is a powerful syntactic construct that allows you to write monadic computations in a more readable and intuitive way. Instead of manually chaining `flatMap` and `map` operations, you can use a declarative syntax similar to list comprehensions in other functional programming languages.

## Overview

For comprehension transforms nested monadic operations into a more readable syntax that resembles imperative code. This is particularly useful when working with:

- Optional values (`Maybe`/`Option` types)
- Error handling (`Either` types)
- Asynchronous operations (`Promise`/`Future` types)
- Collections and streams
- Any monadic container type

## Basic Syntax

The basic syntax follows this pattern:

```php
for {
    $variable1 <- monadicExpression1
    $variable2 <- monadicExpression2
    // ... more bindings
} yield expression
```

## Simple Example

### Input (Syn syntax)

```php
for {
    $a <- Some(42)
    $b <- Some($a + 1)
} yield $b;
```

### Output (Generated PHP)

```php
Some(42)->flatMap(function($a) {
    return Some($a + 1)->map(function($b) use ($a) {
        return $b;
    });
});
```

## How It Works

The for comprehension macro:

1. **Parses the bindings**: Each line with `<-` creates a variable binding from a monadic value
2. **Generates nested functions**: Each binding becomes a `flatMap` call (except the last, which becomes `map`)
3. **Handles variable scoping**: Variables from previous bindings are automatically included in `use` clauses
4. **Transforms the yield**: The final expression after `yield` becomes the return value of the innermost `map`

## Advanced Examples

### Multiple Bindings with Complex Operations

```php
// Syn syntax
for {
    $user <- getUserById($id)
    $profile <- getProfileByUserId($user->id)
    $settings <- getSettingsByProfileId($profile->id)
} yield [
    'user' => $user,
    'profile' => $profile,
    'settings' => $settings
];

// Generated PHP
getUserById($id)->flatMap(function($user) use ($id) {
    return getProfileByUserId($user->id)->flatMap(function($profile) use ($user, $id) {
        return getSettingsByProfileId($profile->id)->map(function($settings) use ($user, $profile, $id) {
            return [
                'user' => $user,
                'profile' => $profile,
                'settings' => $settings
            ];
        });
    });
});
```

### With Filtering (Guard Clauses)

```php
// Syn syntax
for {
    $x <- range(1, 10)
    $y <- range(1, 10)
    if $x + $y == 10
} yield [$x, $y];

// Generated PHP
range(1, 10)->flatMap(function($x) {
    return range(1, 10)->filter(function($y) use ($x) {
        return $x + $y == 10;
    })->map(function($y) use ($x) {
        return [$x, $y];
    });
});
```

### Nested For Comprehensions

```php
// Syn syntax
for {
    $outer <- Some(getValue())
    $inner <- for {
        $a <- Some($outer * 2)
        $b <- Some($a + 5)
    } yield $a + $b
} yield $inner;

// Generated PHP
Some(getValue())->flatMap(function($outer) {
    return Some($outer * 2)->flatMap(function($a) use ($outer) {
        return Some($a + 5)->map(function($b) use ($a, $outer) {
            return $a + $b;
        });
    })->map(function($inner) use ($outer) {
        return $inner;
    });
});
```

## Error Handling Example

For comprehensions shine when dealing with error-prone operations:

```php
// Syn syntax
for {
    $data <- parseJson($input)
    $validated <- validateData($data)
    $processed <- processData($validated)
} yield $processed;

// This creates a chain where if any step fails (returns None/Left),
// the entire computation short-circuits and returns the error
```

## Comparison with Manual Chaining

### Without For Comprehension

```php
getUserById($id)
    ->flatMap(function($user) use ($id) {
        return getProfileByUserId($user->id)
            ->flatMap(function($profile) use ($user, $id) {
                return getSettingsByProfileId($profile->id)
                    ->map(function($settings) use ($user, $profile, $id) {
                        return new UserData($user, $profile, $settings);
                    });
            });
    });
```

### With For Comprehension

```php
for {
    $user <- getUserById($id)
    $profile <- getProfileByUserId($user->id)
    $settings <- getSettingsByProfileId($profile->id)
} yield new UserData($user, $profile, $settings);
```

## Macro Definition

The for comprehension is implemented using Syn's macro system. Here's the macro definition:

```syn
$(macro) { 
    for { 
        $(layer() as binding) <- $(layer() as monad) 
        $(layer() as rest_binding) <- $(layer() as rest_monad) 
    } yield $(layer() as yield_expr) 
} >> { 
    $(monad)->flatMap(function($(binding)) {
        return $(rest_monad)->map(function($(rest_binding)) use ($(binding)) {
            return $(yield_expr);
        });
    }) 
}
```

## Usage in Your Project

1. **Create the macro file**: Save the macro definition as `for_comprehension.syn`
2. **Include in your build**: Use the Syn CLI to process files with this macro
3. **Write comprehensions**: Use the for comprehension syntax in your `.syn.php` files

## Best Practices

### Do Use For Comprehensions When:
- You have multiple monadic operations that depend on each other
- The sequential nature of operations is important
- You want to improve readability of complex monadic chains
- Error propagation should short-circuit the entire computation

### Don't Use For Comprehensions When:
- You only have a single monadic operation (use `map` directly)
- Operations are independent (use `applicative` style instead)
- Performance is critical and you can't afford the function call overhead

## Type Requirements

For comprehensions work with any type that implements the monadic interface:

- `flatMap(callable $f): Monad`
- `map(callable $f): Monad`

Common PHP libraries that support this pattern:
- [Phunkie](https://github.com/phunkie/phunkie) - Functional programming library
- [Widmogrod/php-functional](https://github.com/widmogrod/php-functional) - Functional programming primitives
- Custom implementations of Option, Either, Try, etc.

## Limitations

- Currently supports up to 2 bindings in the basic implementation
- Guard clauses (`if` conditions) require additional macro definitions
- Nested comprehensions need careful parenthesization
- Variable scoping is determined lexically, not dynamically

---

## Navigation

- **Previous:** [Advanced Macros](advanced-macros.md)
- **Next:** [Real-World Examples](real-world.md)
- **Index:** [Documentation Index](../index.md)

## See Also

- [Advanced Macros](advanced-macros.md) - More complex macro examples
- [Macro DSL Reference](../macro-dsl.md) - Complete macro syntax guide
- [Parser Combinators](../concepts/parser-combinators.md) - Understanding the underlying parsing
- [Real-World Examples](real-world.md) - Practical use cases 