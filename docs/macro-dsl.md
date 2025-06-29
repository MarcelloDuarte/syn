# Macro DSL Reference

The Syn Macro DSL (Domain Specific Language) provides a powerful syntax for defining transformation rules. This reference covers all aspects of macro definition and usage.

## Basic Syntax

### Macro Structure

Every macro follows this basic structure:

```syn
$(macro) { pattern } >> { replacement }
```

- **`$(macro)`**: Declares this as a macro definition
- **`pattern`**: The syntax to match in input files
- **`>>`**: Separates pattern from replacement
- **`replacement`**: The code to generate when pattern matches

### Simple Example

```syn
$(macro) { $-> } >> { $this-> }
```

This macro transforms `$->` into `$this->`.

## Pattern Matching

### Literals

Literal text matches exactly:

```syn
$(macro) { hello } >> { echo "Hello World"; }
```

Matches the exact token `hello`.

### Token Types

Match specific PHP token types:

```syn
$(macro) { $(T_VARIABLE) } >> { $this->$(T_VARIABLE) }
```

Common token types:
- `T_VARIABLE`: Variables like `$foo`
- `T_STRING`: Identifiers like `function_name`
- `T_LNUMBER`: Integer literals like `42`
- `T_DNUMBER`: Float literals like `3.14`
- `T_CONSTANT_ENCAPSED_STRING`: String literals like `"hello"`

### Layers

Layers capture complex nested structures:

```syn
$(macro) { debug($(layer() as expr)) } >> { var_dump($(expr)) }
```

#### Basic Layer Syntax

- **`$(layer())`**: Captures any balanced expression
- **`$(layer() as name)`**: Captures and names the expression for reuse

#### Layer Examples

```syn
# Capture function arguments
$(macro) { call($(layer() as args)) } >> { $this->call($(args)) }

# Capture block content
$(macro) { block { $(layer() as content) } } >> { { $(content) } }

# Multiple captures
$(macro) { 
    if ($(layer() as condition)) { 
        $(layer() as body) 
    } 
} >> { 
    if ($(condition)) { 
        $(body) 
    } 
}
```

### Lists

Capture multiple items separated by delimiters:

```syn
$(macro) { array[$(list(,) as items)] } >> { [$(items)] }
```

- **`$(list(,))`**: Matches comma-separated items
- **`$(list(;))`**: Matches semicolon-separated items
- **`$(list(|))`**: Matches pipe-separated items

### Sequences

Match specific token sequences:

```syn
$(macro) { $(T_VARIABLE) -> $(T_STRING) } >> { $(T_VARIABLE)->$(T_STRING)() }
```

## Transformation Rules

### Variable Substitution

Use captured variables in replacements:

```syn
$(macro) { get $(T_STRING as prop) } >> { $this->get$(prop)() }
```

Input: `get name`
Output: `$this->getName()`

### Complex Substitutions

```syn
$(macro) { 
    unless ($(layer() as condition)) { 
        $(layer() as body) 
    } 
} >> { 
    if (!($(condition))) { 
        $(body) 
    } 
}
```

### Nested Replacements

Replacements can contain other macro calls:

```syn
$(macro) { safe $(layer() as expr) } >> { try { $(expr) } catch (Exception $e) { null } }
```

## Macro Hygiene

### Variable Scoping

Macros preserve variable scoping:

```syn
$(macro) { 
    with ($(T_VARIABLE as var) = $(layer() as value)) { 
        $(layer() as body) 
    } 
} >> { 
    (function() use ($(var)) { 
        $(var) = $(value); 
        $(body) 
    })() 
}
```

### Collision Avoidance

Use unique variable names to avoid collisions:

```syn
$(macro) { 
    temp $(layer() as expr) 
} >> { 
    (function() { 
        $_syn_temp_var = $(expr); 
        return $_syn_temp_var; 
    })() 
}
```

## Advanced Patterns

### Conditional Patterns

Match patterns based on conditions:

```syn
$(macro) { 
    $(T_VARIABLE as var) when $(layer() as condition) 
} >> { 
    ($(condition)) ? $(var) : null 
}
```

### Recursive Patterns

Macros can be recursive:

```syn
$(macro) { 
    chain $(layer() as first) $(layer() as rest) 
} >> { 
    $(first)->chain($(rest)) 
}
```

### Multiple Patterns

Define multiple patterns in one macro:

```syn
$(macro) { 
    optional $(T_VARIABLE as var) 
} >> { 
    isset($(var)) ? $(var) : null 
}

$(macro) { 
    optional $(layer() as expr) 
} >> { 
    (function() { 
        try { 
            return $(expr); 
        } catch (Exception $e) { 
            return null; 
        } 
    })() 
}
```

## Pattern Matching Examples

### Simple Token Replacement

```syn
# Replace arrow functions
$(macro) { $(T_VARIABLE) => $(layer() as body) } >> { function($(T_VARIABLE)) { return $(body); } }

# Usage
$callback = $x => $x * 2;
# Becomes
$callback = function($x) { return $x * 2; };
```

### Control Structure Creation

```syn
# Create switch expression
$(macro) { 
    switch ($(layer() as expr)) { 
        $(list(;) as cases) 
    } 
} >> { 
    (function() use ($(expr)) { 
        switch ($(expr)) { 
            $(cases) 
        } 
    })() 
}
```

### Method Chaining

```syn
# Fluent interface helper
$(macro) { 
    $(T_VARIABLE as obj) :: $(T_STRING as method) ( $(layer() as args) ) 
} >> { 
    $(obj)->$(method)($(args))->$(obj) 
}
```

## Debugging Macros

### Verbose Output

Use the `--verbose` flag to see macro application:

```bash
./bin/sync input.syn.php --macro-file=macros.syn --out=output.php --verbose
```

### Preserve Line Numbers

Keep original line numbers for debugging:

```bash
./bin/sync input.syn.php --macro-file=macros.syn --out=output.php --preserve-line-numbers
```

### Testing Macros

Create test files to verify macro behavior:

```php
// test.syn.php
<?php
$-> test();
debug($variable);
unless ($condition) {
    echo "false";
}
```

## Performance Considerations

### Macro Complexity

- Simple token replacements are fastest
- Layer captures require more processing
- Nested patterns increase complexity

### Optimization Tips

1. **Use specific patterns**: Avoid overly broad matches
2. **Minimize nesting**: Deep nesting slows processing
3. **Cache macro files**: Compile macros once, use many times
4. **Profile usage**: Use `--verbose` to identify bottlenecks

## Error Handling

### Common Errors

#### Pattern Mismatch
```
Error: Pattern 'debug($(layer()))' does not match token sequence
```

**Solution**: Check pattern syntax and ensure it matches input exactly.

#### Invalid Replacement
```
Error: Invalid replacement syntax '$(unknown_var)'
```

**Solution**: Ensure all variables in replacement are captured in pattern.

#### Circular Dependencies
```
Error: Circular macro dependency detected
```

**Solution**: Remove recursive macro calls that don't terminate.

### Best Practices

1. **Start simple**: Begin with basic token replacements
2. **Test incrementally**: Test each macro addition
3. **Use meaningful names**: Name captures clearly
4. **Document macros**: Comment complex patterns
5. **Validate syntax**: Check generated PHP is valid

## Examples by Use Case

### Code Generation

```syn
# Generate getters/setters
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

### DSL Creation

```syn
# Create query builder syntax
$(macro) { 
    select $(list(,) as fields) from $(T_STRING as table) where $(layer() as condition) 
} >> { 
    Query::select([$(fields)])
        ->from('$(table)')
        ->where($(condition)) 
}
```

### Functional Programming

```syn
# Pipe operator
$(macro) { $(layer() as left) |> $(layer() as right) } >> { ($(right))($(left)) }

# Usage
$result = $data |> array_filter |> array_values;
# Becomes
$result = (array_values)((array_filter)($data));
```

---

## Navigation

- **Previous:** [Getting Started](getting-started.md)
- **Next:** [CLI Reference](cli.md)
- **Index:** [Documentation Index](index.md)

## See Also

- [Parser Combinators](concepts/parser-combinators.md)
- [Examples](examples/)
- [Best Practices](best-practices/macro-design.md)
- [Troubleshooting](troubleshooting/common-issues.md) 