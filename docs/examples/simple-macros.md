# Simple Macros

Simple macros are the foundation of Syn's transformation system. They provide straightforward token-to-token replacements and basic pattern matching. This guide covers the most common simple macro patterns and how to use them effectively.

## String Replacement

### Basic Token Replacement

The simplest macros replace one token with another:

```syn
$(macro) { $-> } >> { $this-> }
```

**Usage:**
```php
// Input
$->name = 'John';
$->getName();

// Output
$this->name = 'John';
$this->getName();
```

### Keyword Replacement

Replace custom keywords with PHP equivalents:

```syn
$(macro) { __debug } >> { var_dump }
```

**Usage:**
```php
// Input
__debug($user);

// Output
var_dump($user);
```

### Symbol Replacement

Create custom operators:

```syn
$(macro) { <> } >> { != }
```

**Usage:**
```php
// Input
if ($status <> 'active') {
    // ...
}

// Output
if ($status != 'active') {
    // ...
}
```

## Conditional Logic

### Unless Statement

Create an "unless" control structure:

```syn
$(macro) { unless ($(layer() as condition)) { $(layer() as body) } } >> { if (!($(condition))) { $(body) } }
```

**Usage:**
```php
// Input
unless ($user->isActive()) {
    throw new Exception('User not active');
}

// Output
if (!($user->isActive())) {
    throw new Exception('User not active');
}
```

### Null Coalescing Alternative

Custom null-safe operators:

```syn
$(macro) { $(T_VARIABLE as var) ?? $(layer() as default) } >> { isset($(var)) ? $(var) : $(default) }
```

**Usage:**
```php
// Input
$name = $user->name ?? 'Unknown';

// Output
$name = isset($user->name) ? $user->name : 'Unknown';
```

### Ternary Shorthand

Simplified ternary operator:

```syn
$(macro) { $(layer() as condition) ? $(layer() as value) } >> { $(condition) ? $(value) : null }
```

**Usage:**
```php
// Input
$result = $isValid ? $data;

// Output
$result = $isValid ? $data : null;
```

## Variable Operations

### Property Access Shorthand

Simplified property access:

```syn
$(macro) { @$(T_STRING as prop) } >> { $this->$(prop) }
```

**Usage:**
```php
// Input
return @name;

// Output
return $this->name;
```

### Variable Assignment Shorthand

Quick variable assignments:

```syn
$(macro) { let $(T_VARIABLE as var) = $(layer() as value) } >> { $(var) = $(value) }
```

**Usage:**
```php
// Input
let $total = $price * $quantity;

// Output
$total = $price * $quantity;
```

### Auto-increment Shorthand

Custom increment operators:

```syn
$(macro) { $(T_VARIABLE as var)++ } >> { $(var) = $(var) + 1 }
```

**Usage:**
```php
// Input
$counter++;

// Output
$counter = $counter + 1;
```

## Function Shortcuts

### Debug Helper

Enhanced debugging:

```syn
$(macro) { dd($(layer() as expr)) } >> { var_dump($(expr)); die() }
```

**Usage:**
```php
// Input
dd($user);

// Output
var_dump($user); die();
```

### Array Creation Shorthand

Simplified array syntax:

```syn
$(macro) { array[$(layer() as items)] } >> { [$(items)] }
```

**Usage:**
```php
// Input
$data = array[$name, $email, $phone];

// Output
$data = [$name, $email, $phone];
```

### Method Call Shorthand

Quick method calls:

```syn
$(macro) { call $(T_STRING as method) } >> { $this->$(method)() }
```

**Usage:**
```php
// Input
call save;
call validate;

// Output
$this->save();
$this->validate();
```

## String and Type Operations

### String Interpolation

Enhanced string interpolation:

```syn
$(macro) { str"$(layer() as content)" } >> { "$(content)" }
```

**Usage:**
```php
// Input
$message = str"Hello {$name}!";

// Output
$message = "Hello {$name}!";
```

### Type Casting Shortcuts

Quick type casting:

```syn
$(macro) { int $(layer() as expr) } >> { (int) $(expr) }
$(macro) { string $(layer() as expr) } >> { (string) $(expr) }
$(macro) { float $(layer() as expr) } >> { (float) $(expr) }
```

**Usage:**
```php
// Input
$age = int $userInput;
$name = string $value;

// Output
$age = (int) $userInput;
$name = (string) $value;
```

## Comparison Operations

### Equality Shortcuts

Simplified equality checks:

```syn
$(macro) { $(layer() as left) === $(layer() as right) } >> { $(left) === $(right) }
$(macro) { $(layer() as left) !== $(layer() as right) } >> { $(left) !== $(right) }
```

### Range Checks

Custom range operators:

```syn
$(macro) { $(layer() as value) in $(layer() as min)..$(layer() as max) } >> { $(value) >= $(min) && $(value) <= $(max) }
```

**Usage:**
```php
// Input
if ($age in 18..65) {
    // ...
}

// Output
if ($age >= 18 && $age <= 65) {
    // ...
}
```

## Error Handling

### Try-Catch Shorthand

Simplified error handling:

```syn
$(macro) { safe $(layer() as expr) } >> { try { $(expr) } catch (Exception $e) { null } }
```

**Usage:**
```php
// Input
$result = safe parseJson($data);

// Output
$result = try { parseJson($data) } catch (Exception $e) { null };
```

### Assert Macro

Quick assertions:

```syn
$(macro) { assert $(layer() as condition) } >> { if (!($(condition))) { throw new AssertionError('Assertion failed') } }
```

**Usage:**
```php
// Input
assert $user->isValid();

// Output
if (!($user->isValid())) { 
    throw new AssertionError('Assertion failed'); 
}
```

## Best Practices

### Naming Conventions

1. **Use clear prefixes**: `__debug`, `@property`, `str"..."`
2. **Avoid conflicts**: Don't use existing PHP keywords
3. **Be consistent**: Use the same pattern across related macros

### Pattern Design

1. **Keep it simple**: Simple macros should do one thing well
2. **Make it readable**: The macro should make code more readable, not less
3. **Consider context**: Think about where the macro will be used

### Testing Simple Macros

```php
class SimpleMacroTest extends TestCase
{
    public function testArrowMacro(): void
    {
        $source = '<?php $->test();';
        $expected = '<?php $this->test();';
        
        $result = $this->processor->process($source);
        
        $this->assertEquals($expected, $result);
    }
    
    public function testDebugMacro(): void
    {
        $source = '<?php __debug($var);';
        $expected = '<?php var_dump($var);';
        
        $result = $this->processor->process($source);
        
        $this->assertEquals($expected, $result);
    }
}
```

## Common Patterns

### Collection of Useful Simple Macros

```syn
# Object property access
$(macro) { $-> } >> { $this-> }

# Debug helpers
$(macro) { __debug } >> { var_dump }
$(macro) { __dump } >> { print_r }

# Null safety
$(macro) { $(T_VARIABLE as var) ?? $(layer() as default) } >> { isset($(var)) ? $(var) : $(default) }

# Type shortcuts
$(macro) { int $(layer() as expr) } >> { (int) $(expr) }
$(macro) { str $(layer() as expr) } >> { (string) $(expr) }

# Comparison shortcuts
$(macro) { <> } >> { != }
$(macro) { === } >> { === }

# Method shortcuts
$(macro) { call $(T_STRING as method) } >> { $this->$(method)() }
```

## Performance Considerations

### Efficient Patterns

- Simple token replacements are fastest
- Avoid complex nested patterns in simple macros
- Use specific matches rather than broad patterns

### Optimization Tips

1. **Order matters**: Place most commonly used macros first
2. **Cache compiled patterns**: Reuse compiled macro patterns
3. **Profile usage**: Measure which macros are used most frequently

## Debugging

### Verbose Output

Use verbose mode to see macro applications:

```bash
./bin/sync input.syn.php --macro-file=simple.syn --out=output.php --verbose
```

### Pattern Testing

Test patterns incrementally:

```php
// Test file
<?php
$->test();
__debug($var);
```

### Common Issues

1. **Pattern too broad**: Matches unintended code
2. **Replacement conflicts**: Multiple macros match same pattern
3. **Invalid syntax**: Generated code is not valid PHP

---

## Navigation

- **Previous:** [Macro System](../concepts/macro-system.md)
- **Next:** [Advanced Macros](advanced-macros.md)
- **Index:** [Documentation Index](../index.md)

## See Also

- [Advanced Macros](advanced-macros.md)
- [Macro DSL Reference](../macro-dsl.md)
- [Best Practices](../best-practices/macro-design.md)
- [For Comprehension](for-comprehension.md) 