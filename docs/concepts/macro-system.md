# Macro System

The Syn macro system is the core component that enables custom syntax transformations in PHP. This document explains how the macro system works, its architecture, and how to effectively use it.

## Overview

The macro system transforms custom syntax into valid PHP code through a series of well-defined steps:

1. **Macro Definition**: Patterns and their transformations are defined
2. **Macro Loading**: Definitions are parsed and loaded into memory
3. **Pattern Matching**: Input tokens are matched against macro patterns
4. **Transformation**: Matched patterns are replaced with their transformations
5. **Code Generation**: Final PHP code is produced

## Architecture

### Core Components

```php
// Macro definition and storage
MacroRegistry        // Stores and retrieves macro definitions
MacroDefinition      // Represents a single macro

// Pattern processing
PatternTokenizer     // Tokenizes macro patterns
TokenNormalizer      // Normalizes token formats

// Matching and capturing
SequenceMatcher      // Matches token sequences with captures
BalancedCapture      // Handles balanced delimiter content
DelimiterMatcher     // Finds matching delimiters

// Loading and parsing
MacroParser          // Parses macro definitions from text
FileLoader           // Loads macros from individual files
DirectoryLoader      // Loads macros from directories
MacroLoader          // Main loader coordinating all sources
```

### Data Flow

```
Macro Files (.syn) 
    ↓
MacroParser → MacroDefinition
    ↓
MacroRegistry (storage)
    ↓
Transformer (applies macros)
    ↓
Generated PHP Code
```

## Macro Definition Structure

### Basic Syntax

```syn
$(macro) { pattern } >> { replacement }
```

### Components

- **`$(macro)`**: Macro declaration keyword
- **`pattern`**: The syntax to match in source code
- **`>>`**: Transformation operator
- **`replacement`**: The PHP code to generate

### Example

```syn
$(macro) { $-> } >> { $this-> }
```

This transforms `$->method()` into `$this->method()`.

## Pattern Types

### Simple Patterns

Match exact token sequences:

```syn
$(macro) { __debug } >> { var_dump }
```

### Token Patterns

Match specific token types:

```syn
$(macro) { debug($(T_VARIABLE)) } >> { var_dump($(T_VARIABLE)) }
```

### Layer Patterns

Capture complex nested structures:

```syn
$(macro) { unless ($(layer() as condition)) { $(layer() as body) } } >> { if (!($(condition))) { $(body) } }
```

## Capture Mechanisms

### Named Captures

Capture and reuse parts of the pattern:

```syn
$(macro) { get $(T_STRING as property) } >> { $this->get$(property)() }
```

### Layer Captures

Capture balanced expressions:

```syn
$(macro) { safe($(layer() as expr)) } >> { try { $(expr) } catch (Exception $e) { null } }
```

### Multiple Captures

Use multiple captures in one pattern:

```syn
$(macro) { 
    $(T_VARIABLE as var) = $(layer() as value) or $(layer() as default) 
} >> { 
    $(var) = isset($(value)) ? $(value) : $(default) 
}
```

## Processing Pipeline

### 1. Tokenization

Source code is broken into PHP tokens:

```php
$tokens = token_get_all($sourceCode);
```

### 2. Pattern Matching

Each macro pattern is compared against token sequences:

```php
foreach ($macros as $macro) {
    $matches = $this->findMatches($tokens, $macro->getPattern());
    // Process matches...
}
```

### 3. Capture Extraction

When a pattern matches, captures are extracted:

```php
$captures = $this->extractCaptures($tokens, $pattern, $match);
```

### 4. Replacement Generation

Captured values are substituted into the replacement:

```php
$replacement = $this->generateReplacement($macro->getReplacement(), $captures);
```

### 5. Token Substitution

Original tokens are replaced with generated ones:

```php
$tokens = $this->replaceTokens($tokens, $match, $replacement);
```

## Advanced Features

### Recursive Macros

Macros can expand to include other macro calls:

```syn
$(macro) { chain $(layer() as expr) } >> { $(expr)->andThen() }
```

### Conditional Expansion

Macros can include conditional logic:

```syn
$(macro) { 
    optional $(T_VARIABLE as var) 
} >> { 
    isset($(var)) ? $(var) : null 
}
```

### Nested Patterns

Complex nested structures:

```syn
$(macro) { 
    with ($(layer() as bindings)) { 
        $(layer() as body) 
    } 
} >> { 
    (function() use ($(bindings)) { 
        $(body) 
    })() 
}
```

## Error Handling

### Pattern Validation

Patterns are validated during loading:

```php
class PatternValidator
{
    public function validate(string $pattern): array
    {
        $errors = [];
        
        // Check balanced delimiters
        if (!$this->hasBalancedDelimiters($pattern)) {
            $errors[] = 'Unbalanced delimiters in pattern';
        }
        
        // Check capture syntax
        if (!$this->hasValidCaptures($pattern)) {
            $errors[] = 'Invalid capture syntax';
        }
        
        return $errors;
    }
}
```

### Runtime Errors

Handle errors during transformation:

```php
try {
    $result = $this->applyMacro($tokens, $macro);
} catch (MacroTransformationException $e) {
    // Log error and continue with next macro
    $this->logger->error('Macro transformation failed', [
        'macro' => $macro->getName(),
        'error' => $e->getMessage()
    ]);
}
```

## Performance Optimization

### Macro Caching

Cache compiled macro patterns:

```php
class MacroCache
{
    public function get(string $key): ?CompiledMacro
    {
        return $this->cache[$key] ?? null;
    }
    
    public function set(string $key, CompiledMacro $macro): void
    {
        $this->cache[$key] = $macro;
    }
}
```

### Pattern Optimization

Optimize patterns for faster matching:

```php
class PatternOptimizer
{
    public function optimize(Pattern $pattern): OptimizedPattern
    {
        // Convert to finite state automaton
        // Precompile regular expressions
        // Build lookup tables
        return new OptimizedPattern($pattern);
    }
}
```

### Lazy Loading

Load macros only when needed:

```php
class LazyMacroLoader
{
    public function getMacro(string $name): MacroDefinition
    {
        if (!isset($this->loaded[$name])) {
            $this->loaded[$name] = $this->loadMacro($name);
        }
        
        return $this->loaded[$name];
    }
}
```

## Testing Macros

### Unit Testing

Test individual macro transformations:

```php
class MacroTest extends TestCase
{
    public function testDebugMacro(): void
    {
        $macro = new MacroDefinition(
            'debug($(layer() as expr))',
            'var_dump($(expr))'
        );
        
        $input = ['debug', '(', '$variable', ')'];
        $expected = ['var_dump', '(', '$variable', ')'];
        
        $result = $this->transformer->apply($input, $macro);
        
        $this->assertEquals($expected, $result);
    }
}
```

### Integration Testing

Test complete transformation pipelines:

```php
class TransformationTest extends TestCase
{
    public function testCompleteTransformation(): void
    {
        $source = '<?php debug($user);';
        $expected = '<?php var_dump($user);';
        
        $result = $this->processor->process($source);
        
        $this->assertEquals($expected, $result);
    }
}
```

## Best Practices

### Macro Design

1. **Keep patterns specific**: Avoid overly broad patterns that might match unintended code
2. **Use meaningful names**: Name captures clearly to make replacement readable
3. **Handle edge cases**: Consider what happens with nested structures or edge cases
4. **Test thoroughly**: Test macros with various input combinations

### Performance

1. **Order macros strategically**: Place most commonly used macros first
2. **Use simple patterns when possible**: Complex patterns are slower to match
3. **Cache compiled macros**: Avoid recompiling the same macros repeatedly
4. **Profile macro usage**: Identify bottlenecks in macro processing

### Debugging

1. **Use verbose output**: Enable verbose logging to see macro applications
2. **Preserve line numbers**: Keep line number information for debugging
3. **Test incrementally**: Add macros one at a time to isolate issues
4. **Validate patterns**: Check pattern syntax before using in production

## Common Patterns

### Code Generation

```syn
$(macro) { 
    property $(T_STRING as type) $(T_VARIABLE as name) 
} >> { 
    private $(type) $(name);
    
    public function get$(name)(): $(type) {
        return $this->$(name);
    }
    
    public function set$(name)($(type) $(name)): self {
        $this->$(name) = $(name);
        return $this;
    }
}
```

### Control Structures

```syn
$(macro) { 
    foreach ($(layer() as iterable) as $(T_VARIABLE as key) => $(T_VARIABLE as value)) { 
        $(layer() as body) 
    } 
} >> { 
    foreach ($(iterable) as $(key) => $(value)) { 
        $(body) 
    } 
}
```

### Functional Programming

```syn
$(macro) { $(layer() as left) |> $(layer() as right) } >> { ($(right))($(left)) }
```

## See Also

- [Parser Combinators](parser-combinators.md)
- [Transformation Pipeline](transformation-pipeline.md)
- [Macro DSL Reference](../macro-dsl.md)
- [Best Practices](../best-practices/macro-design.md) 