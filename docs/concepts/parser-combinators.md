# Parser Combinators

Syn is built on parser combinators, a functional programming technique for building parsers by combining smaller parsing functions. This document explains how parser combinators work in Syn and how they enable powerful macro transformations.

## What are Parser Combinators?

Parser combinators are higher-order functions that take parsers as input and return new parsers as output. They allow you to build complex parsers from simple building blocks in a compositional way.

### Key Concepts

- **Parser**: A function that takes input and returns a parse result
- **Combinator**: A function that combines parsers to create new parsers
- **Composition**: Building complex parsers from simpler ones
- **Backtracking**: Trying alternative parsing strategies when one fails

## Syn's Parser Architecture

### Core Parser Interface

```php
interface ParserInterface
{
    public function parse(array $tokens, int $position): ParseResult;
}
```

### Parse Result

```php
class ParseResult
{
    public function __construct(
        private bool $success,
        private mixed $value,
        private int $position,
        private ?string $error = null
    ) {}
    
    public function isSuccess(): bool;
    public function getValue(): mixed;
    public function getPosition(): int;
    public function getError(): ?string;
}
```

## Basic Combinators

### Token Parser

Matches specific tokens:

```php
// Match a variable token
$variableParser = new TokenParser(T_VARIABLE);

// Match specific text
$arrowParser = new TokenParser(T_DOUBLE_ARROW, '->');
```

### Sequence Parser

Combines multiple parsers in sequence:

```php
// Match: $variable -> method
$sequenceParser = new SequenceParser([
    new TokenParser(T_VARIABLE),
    new TokenParser(T_OBJECT_OPERATOR, '->'),
    new TokenParser(T_STRING)
]);
```

### Choice Parser

Tries multiple parsers, returning the first successful match:

```php
// Match either a variable or a string
$choiceParser = new ChoiceParser([
    new TokenParser(T_VARIABLE),
    new TokenParser(T_CONSTANT_ENCAPSED_STRING)
]);
```

### Many Parser

Matches zero or more occurrences:

```php
// Match multiple statements
$manyParser = new ManyParser(
    new StatementParser()
);
```

### Optional Parser

Matches zero or one occurrence:

```php
// Optional type hint
$optionalParser = new OptionalParser(
    new TokenParser(T_STRING)
);
```

## Advanced Combinators

### Map Parser

Transforms the result of a successful parse:

```php
// Parse a number and convert to integer
$numberParser = new MapParser(
    new TokenParser(T_LNUMBER),
    fn($value) => (int) $value
);
```

### Filter Parser

Filters results based on a predicate:

```php
// Only accept even numbers
$evenNumberParser = new FilterParser(
    $numberParser,
    fn($value) => $value % 2 === 0
);
```

## Macro Pattern Parsing

### Layer Parsing

Layers capture balanced expressions using combinators:

```php
class LayerParser implements ParserInterface
{
    public function parse(array $tokens, int $position): ParseResult
    {
        // Find balanced delimiters
        $depth = 0;
        $start = $position;
        
        while ($position < count($tokens)) {
            $token = $tokens[$position];
            
            if (in_array($token, ['(', '{', '['])) {
                $depth++;
            } elseif (in_array($token, [')', '}', ']'])) {
                $depth--;
                if ($depth === 0) {
                    break;
                }
            }
            
            $position++;
        }
        
        return new ParseResult(
            true,
            array_slice($tokens, $start, $position - $start),
            $position
        );
    }
}
```

### Pattern Matching

Macro patterns use combinators to match complex structures:

```php
// Match: unless (condition) { body }
$unlessParser = new SequenceParser([
    new TokenParser(T_STRING, 'unless'),
    new TokenParser('('),
    new LayerParser(), // condition
    new TokenParser(')'),
    new TokenParser('{'),
    new LayerParser(), // body
    new TokenParser('}')
]);
```

## Monadic Operations

Parser combinators form a monad, enabling powerful composition patterns.

### FlatMap

Chain parsers where the second depends on the first:

```php
class FlatMapParser implements ParserInterface
{
    public function __construct(
        private ParserInterface $parser,
        private callable $fn
    ) {}
    
    public function parse(array $tokens, int $position): ParseResult
    {
        $result = $this->parser->parse($tokens, $position);
        
        if (!$result->isSuccess()) {
            return $result;
        }
        
        $nextParser = ($this->fn)($result->getValue());
        return $nextParser->parse($tokens, $result->getPosition());
    }
}
```

### Usage Example

```php
// Parse variable, then parse method calls on that variable
$chainParser = new FlatMapParser(
    new TokenParser(T_VARIABLE),
    fn($var) => new ManyParser(
        new SequenceParser([
            new TokenParser(T_OBJECT_OPERATOR, '->'),
            new TokenParser(T_STRING)
        ])
    )
);
```

## Error Handling

### Error Recovery

Combinators can implement sophisticated error recovery:

```php
class RecoveryParser implements ParserInterface
{
    public function parse(array $tokens, int $position): ParseResult
    {
        $result = $this->primaryParser->parse($tokens, $position);
        
        if ($result->isSuccess()) {
            return $result;
        }
        
        // Try recovery strategy
        return $this->recoveryParser->parse($tokens, $position);
    }
}
```

### Error Messages

Provide meaningful error messages:

```php
class ExpectedParser implements ParserInterface
{
    public function parse(array $tokens, int $position): ParseResult
    {
        $result = $this->parser->parse($tokens, $position);
        
        if (!$result->isSuccess()) {
            return new ParseResult(
                false,
                null,
                $position,
                "Expected {$this->expected} at position {$position}"
            );
        }
        
        return $result;
    }
}
```

## Performance Considerations

### Memoization

Cache parse results to avoid recomputation:

```php
class MemoizedParser implements ParserInterface
{
    private array $cache = [];
    
    public function parse(array $tokens, int $position): ParseResult
    {
        $key = $this->getCacheKey($tokens, $position);
        
        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }
        
        $result = $this->parser->parse($tokens, $position);
        $this->cache[$key] = $result;
        
        return $result;
    }
}
```

### Left Recursion

Handle left-recursive grammars:

```php
class LeftRecursiveParser implements ParserInterface
{
    public function parse(array $tokens, int $position): ParseResult
    {
        $result = $this->baseParser->parse($tokens, $position);
        
        while ($result->isSuccess()) {
            $nextResult = $this->recursiveParser->parse(
                $tokens, 
                $result->getPosition()
            );
            
            if (!$nextResult->isSuccess()) {
                break;
            }
            
            $result = new ParseResult(
                true,
                $this->combineResults($result->getValue(), $nextResult->getValue()),
                $nextResult->getPosition()
            );
        }
        
        return $result;
    }
}
```

## Building Custom Combinators

### Creating New Combinators

```php
class SeparatedByParser implements ParserInterface
{
    public function __construct(
        private ParserInterface $elementParser,
        private ParserInterface $separatorParser
    ) {}
    
    public function parse(array $tokens, int $position): ParseResult
    {
        $elements = [];
        $currentPos = $position;
        
        // Parse first element
        $result = $this->elementParser->parse($tokens, $currentPos);
        if (!$result->isSuccess()) {
            return $result;
        }
        
        $elements[] = $result->getValue();
        $currentPos = $result->getPosition();
        
        // Parse separator + element pairs
        while (true) {
            $sepResult = $this->separatorParser->parse($tokens, $currentPos);
            if (!$sepResult->isSuccess()) {
                break;
            }
            
            $elemResult = $this->elementParser->parse($tokens, $sepResult->getPosition());
            if (!$elemResult->isSuccess()) {
                break;
            }
            
            $elements[] = $elemResult->getValue();
            $currentPos = $elemResult->getPosition();
        }
        
        return new ParseResult(true, $elements, $currentPos);
    }
}
```

## Real-World Examples

### Parsing Macro Definitions

```php
// Parse: $(macro) { pattern } >> { replacement }
$macroParser = new SequenceParser([
    new TokenParser(T_VARIABLE, '$(macro)'),
    new TokenParser('{'),
    new LayerParser(), // pattern
    new TokenParser('}'),
    new TokenParser(T_SR, '>>'), // >>
    new TokenParser('{'),
    new LayerParser(), // replacement
    new TokenParser('}')
]);
```

### Parsing For Comprehensions

```php
// Parse: for { bindings } yield expression
$forComprehensionParser = new SequenceParser([
    new TokenParser(T_STRING, 'for'),
    new TokenParser('{'),
    new SeparatedByParser(
        new BindingParser(), // $var <- expr
        new TokenParser(T_WHITESPACE)
    ),
    new TokenParser('}'),
    new TokenParser(T_STRING, 'yield'),
    new ExpressionParser()
]);
```

## Best Practices

### Combinator Design

1. **Keep combinators small**: Each combinator should do one thing well
2. **Make them composable**: Combinators should work well together
3. **Handle errors gracefully**: Provide meaningful error messages
4. **Consider performance**: Use memoization for expensive parsers
5. **Test thoroughly**: Combinators are building blocks, they must be reliable

### Parser Organization

```php
class SynParser
{
    private function buildMacroParser(): ParserInterface
    {
        return new SequenceParser([
            $this->macroKeyword(),
            $this->pattern(),
            $this->separator(),
            $this->replacement()
        ]);
    }
    
    private function pattern(): ParserInterface
    {
        return new ChoiceParser([
            $this->layerPattern(),
            $this->tokenPattern(),
            $this->literalPattern()
        ]);
    }
}
```

## See Also

- [Macro System](macro-system.md)
- [AST Extensions](ast-extensions.md)
- [Transformation Pipeline](transformation-pipeline.md)
- [Examples](../examples/) 