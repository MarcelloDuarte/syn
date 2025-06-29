# Macro Design Best Practices

This guide covers best practices for designing effective, maintainable, and performant macros in Syn. Following these principles will help you create macros that are easy to use, understand, and maintain.

## Design Principles

### 1. Single Responsibility Principle

Each macro should have one clear, well-defined purpose:

**Good:**
```syn
$(macro) { debug $(layer() as expr) } >> { 
    var_dump($(expr)); 
}

$(macro) { log $(T_STRING as level) $(layer() as message) } >> { 
    error_log("[$(level)] $(message)"); 
}
```

**Bad:**
```syn
$(macro) { debug_and_log $(layer() as expr) } >> { 
    var_dump($(expr)); 
    error_log($(expr)); 
    file_put_contents('debug.log', $(expr)); 
}
```

### 2. Principle of Least Surprise

Macros should behave intuitively and consistently:

**Good:**
```syn
$(macro) { unless ($(layer() as condition)) { $(layer() as body) } } >> { 
    if (!($(condition))) { $(body) } 
}
```

**Bad:**
```syn
$(macro) { unless ($(layer() as condition)) { $(layer() as body) } } >> { 
    while (!($(condition))) { $(body); break; } 
}
```

### 3. Composability

Design macros to work well together:

**Good:**
```syn
$(macro) { safe $(layer() as expr) } >> { 
    try { $(expr) } catch (Exception $e) { null; } 
}

$(macro) { retry $(T_LNUMBER as times) { $(layer() as body) } } >> { 
    for ($i = 0; $i < $(times); $i++) { 
        try { $(body); break; } catch (Exception $e) { 
            if ($i === $(times) - 1) throw $e; 
        } 
    } 
}

// These can be composed:
// retry 3 { safe someRiskyOperation() }
```

## Naming Conventions

### Macro Names

Use clear, descriptive names that indicate the macro's purpose:

**Good:**
```syn
$(macro) { pipe $(layer() as input) through $(layer() as transform) } >> { ... }
$(macro) { cache $(layer() as expr) for $(T_LNUMBER as seconds) } >> { ... }
$(macro) { validate $(T_VARIABLE as data) against $(T_STRING as rules) } >> { ... }
```

**Bad:**
```syn
$(macro) { p $(layer() as a) t $(layer() as b) } >> { ... }
$(macro) { c $(layer() as e) f $(T_LNUMBER as s) } >> { ... }
$(macro) { v $(T_VARIABLE as d) a $(T_STRING as r) } >> { ... }
```

### Capture Names

Use meaningful names for captured content:

**Good:**
```syn
$(macro) { 
    foreach ($(layer() as iterable) as $(T_VARIABLE as item)) { 
        $(layer() as body) 
    } 
} >> { 
    foreach ($(iterable) as $(item)) { $(body) } 
}
```

**Bad:**
```syn
$(macro) { 
    foreach ($(layer() as x) as $(T_VARIABLE as y)) { 
        $(layer() as z) 
    } 
} >> { 
    foreach ($(x) as $(y)) { $(z) } 
}
```

## Pattern Design

### Use Appropriate Token Types

Choose the most specific token type for your patterns:

**Good:**
```syn
$(macro) { 
    enum $(T_STRING as name) { $(layer() as cases) } 
} >> { 
    class $(name) { $(cases) } 
}
```

**Bad:**
```syn
$(macro) { 
    enum $(layer() as name) { $(layer() as cases) } 
} >> { 
    class $(name) { $(cases) } 
}
```

### Balance Flexibility and Specificity

Make patterns flexible enough to be useful, but specific enough to be safe:

**Good:**
```syn
$(macro) { 
    route $(T_STRING as method) $(T_STRING as path) => $(T_STRING as handler) 
} >> { 
    Route::$(method)('$(path)', '$(handler)'); 
}
```

**Acceptable (more flexible):**
```syn
$(macro) { 
    route $(layer() as method) $(T_STRING as path) => $(layer() as handler) 
} >> { 
    Route::$(method)('$(path)', $(handler)); 
}
```

### Avoid Overly Complex Patterns

Break complex patterns into smaller, composable macros:

**Bad:**
```syn
$(macro) { 
    api $(T_STRING as version) { 
        middleware $(list(,) as middleware) { 
            resource $(T_STRING as name) { 
                $(layer() as routes) 
            } 
        } 
    } 
} >> { ... }
```

**Good:**
```syn
$(macro) { 
    api $(T_STRING as version) { $(layer() as content) } 
} >> { 
    Route::group(['prefix' => '$(version)'], function() { $(content) }); 
}

$(macro) { 
    middleware $(list(,) as middleware) { $(layer() as routes) } 
} >> { 
    Route::group(['middleware' => [$(middleware)]], function() { $(routes) }); 
}

$(macro) { 
    resource $(T_STRING as name) { $(layer() as routes) } 
} >> { 
    Route::group(['prefix' => '$(name)'], function() { $(routes) }); 
}
```

## Error Handling

### Provide Clear Error Messages

Include context and suggestions in error messages:

**Good:**
```syn
$(macro) { 
    validate $(T_VARIABLE as data) { $(layer() as rules) } 
} >> { 
    if (!is_array($(data))) { 
        throw new ValidationException('Data must be an array, ' . gettype($(data)) . ' given'); 
    } 
    $(rules) 
}
```

### Fail Fast

Validate inputs early and provide immediate feedback:

**Good:**
```syn
$(macro) { 
    cache $(layer() as expr) for $(T_LNUMBER as seconds) 
} >> { 
    if ($(seconds) <= 0) { 
        throw new InvalidArgumentException('Cache duration must be positive'); 
    } 
    Cache::remember(md5('$(expr)'), $(seconds), function() { return $(expr); }); 
}
```

### Graceful Degradation

When possible, provide fallback behavior:

**Good:**
```syn
$(macro) { 
    optional $(layer() as expr) 
} >> { 
    try { 
        $(expr) 
    } catch (Exception $e) { 
        null; 
    } 
}
```

## Performance Considerations

### Minimize Generated Code

Generate only necessary code:

**Good:**
```syn
$(macro) { 
    memoize $(layer() as expr) 
} >> { 
    (function() { 
        static $cache; 
        $key = md5('$(expr)'); 
        return $cache[$key] ??= $(expr); 
    })() 
}
```

**Bad:**
```syn
$(macro) { 
    memoize $(layer() as expr) 
} >> { 
    (function() { 
        static $cache = []; 
        static $initialized = false; 
        if (!$initialized) { 
            $cache = []; 
            $initialized = true; 
        } 
        $key = md5('$(expr)'); 
        if (!array_key_exists($key, $cache)) { 
            $cache[$key] = $(expr); 
        } 
        return $cache[$key]; 
    })() 
}
```

### Avoid Deep Nesting

Keep generated code readable and performant:

**Good:**
```syn
$(macro) { 
    chain $(layer() as value) { $(layer() as operations) } 
} >> { 
    (function($value) { 
        $(operations) 
        return $value; 
    })($(value)) 
}

$(macro) { 
    then $(layer() as operation) 
} >> { 
    $value = ($(operation))($value); 
}
```

### Consider Compile-Time vs Runtime

Prefer compile-time computation when possible:

**Good (compile-time):**
```syn
$(macro) { 
    const_add $(T_LNUMBER as a) $(T_LNUMBER as b) 
} >> { 
    $(php: $a + $b) 
}
```

**Acceptable (runtime):**
```syn
$(macro) { 
    add $(layer() as a) $(layer() as b) 
} >> { 
    ($(a) + $(b)) 
}
```

## Documentation and Testing

### Document Macro Behavior

Provide clear documentation for each macro:

```syn
/**
 * Creates a retry mechanism that attempts an operation multiple times
 * 
 * @param int $times Number of retry attempts
 * @param callable $operation Operation to retry
 * @throws Exception The last exception if all retries fail
 * 
 * Example:
 *   retry 3 { riskyDatabaseOperation() }
 */
$(macro) { 
    retry $(T_LNUMBER as times) { $(layer() as operation) } 
} >> { 
    for ($i = 0; $i < $(times); $i++) { 
        try { 
            $(operation); 
            break; 
        } catch (Exception $e) { 
            if ($i === $(times) - 1) throw $e; 
        } 
    } 
}
```

### Write Comprehensive Tests

Test both positive and negative cases:

```php
class RetryMacroTest extends MacroTestCase
{
    public function testSuccessfulOperation(): void
    {
        $source = '<?php retry 3 { return "success"; }';
        $result = $this->processor->process($source);
        
        $this->assertStringContains('for ($i = 0; $i < 3; $i++)', $result);
        $this->assertStringContains('return "success"', $result);
    }
    
    public function testRetryWithException(): void
    {
        $source = '<?php retry 2 { throw new Exception("fail"); }';
        $result = $this->processor->process($source);
        
        $this->assertStringContains('catch (Exception $e)', $result);
        $this->assertStringContains('if ($i === 2 - 1) throw $e', $result);
    }
    
    public function testInvalidRetryCount(): void
    {
        $this->expectException(MacroException::class);
        $source = '<?php retry -1 { doSomething(); }';
        $this->processor->process($source);
    }
}
```

## Versioning and Compatibility

### Semantic Versioning

Use semantic versioning for macro libraries:

- **Major**: Breaking changes to macro behavior
- **Minor**: New macros or backward-compatible enhancements
- **Patch**: Bug fixes and performance improvements

### Deprecation Strategy

Provide clear deprecation paths:

```syn
/**
 * @deprecated Use `cache` macro instead
 * @see cache
 */
$(macro) { 
    memoize $(layer() as expr) 
} >> { 
    trigger_error('memoize macro is deprecated, use cache instead', E_USER_DEPRECATED); 
    Cache::remember(md5('$(expr)'), 3600, function() { return $(expr); }); 
}
```

### Backward Compatibility

Maintain backward compatibility within major versions:

```syn
// Old version
$(macro) { log $(layer() as message) } >> { error_log($(message)); }

// New version (backward compatible)
$(macro) { log $(layer() as message) } >> { Logger::info($(message)); }
$(macro) { log $(T_STRING as level) $(layer() as message) } >> { Logger::$(level)($(message)); }
```

## Security Considerations

### Input Validation

Always validate macro inputs:

```syn
$(macro) { 
    sql $(T_STRING as query) with $(layer() as params) 
} >> { 
    if (strpos('$(query)', ';') !== false) { 
        throw new SecurityException('Multiple statements not allowed'); 
    } 
    $pdo->prepare('$(query)')->execute($(params)); 
}
```

### Avoid Code Injection

Be careful with dynamic code generation:

**Dangerous:**
```syn
$(macro) { 
    eval $(T_STRING as code) 
} >> { 
    eval('$(code)'); 
}
```

**Safe:**
```syn
$(macro) { 
    call $(T_STRING as function) with $(layer() as args) 
} >> { 
    if (!function_exists('$(function)')) { 
        throw new InvalidArgumentException('Function $(function) does not exist'); 
    } 
    $(function)($(args)); 
}
```

### Sanitize Output

Ensure generated code is safe:

```syn
$(macro) { 
    html $(T_STRING as tag) { $(layer() as content) } 
} >> { 
    echo '<$(tag)>' . htmlspecialchars($(content)) . '</$(tag)>'; 
}
```

## Common Anti-Patterns

### 1. God Macros

Avoid macros that do too much:

**Bad:**
```syn
$(macro) { 
    web_framework { $(layer() as everything) } 
} >> { 
    // Hundreds of lines of generated code
}
```

### 2. Cryptic Syntax

Avoid overly clever or obscure syntax:

**Bad:**
```syn
$(macro) { $(T_VARIABLE as x) ~> $(layer() as f) } >> { $(f)($(x)) }
```

**Good:**
```syn
$(macro) { pipe $(T_VARIABLE as value) through $(layer() as function) } >> { 
    $(function)($(value)) 
}
```

### 3. Side Effects

Avoid macros with unexpected side effects:

**Bad:**
```syn
$(macro) { 
    get $(T_VARIABLE as var) 
} >> { 
    global $debug_log; 
    $debug_log[] = '$(var)'; 
    $(var) 
}
```

### 4. Tight Coupling

Avoid macros that depend on specific frameworks or libraries:

**Bad:**
```syn
$(macro) { 
    model $(T_STRING as name) 
} >> { 
    class $(name) extends Illuminate\Database\Eloquent\Model { ... } 
}
```

**Good:**
```syn
$(macro) { 
    model $(T_STRING as name) extends $(T_STRING as base) 
} >> { 
    class $(name) extends $(base) { ... } 
}
```

## Migration Strategies

### Gradual Migration

When changing macro behavior, provide migration paths:

```syn
// Version 1.x
$(macro) { cache $(layer() as expr) } >> { 
    Cache::get(md5('$(expr)')) ?? Cache::set(md5('$(expr)'), $(expr)); 
}

// Version 2.x
$(macro) { cache $(layer() as expr) } >> { 
    Cache::remember(md5('$(expr)'), 3600, function() { return $(expr); }); 
}

// Migration macro
$(macro) { old_cache $(layer() as expr) } >> { 
    trigger_error('old_cache is deprecated, use cache instead', E_USER_DEPRECATED); 
    Cache::get(md5('$(expr)')) ?? Cache::set(md5('$(expr)'), $(expr)); 
}
```

### Automated Refactoring

Provide tools to help users migrate:

```php
class MacroMigrationTool
{
    public function migrate(string $source): string
    {
        // Replace old macro usage with new syntax
        $source = preg_replace('/old_cache\s+(.+)/', 'cache $1', $source);
        
        return $source;
    }
}
```

## Conclusion

Following these best practices will help you create macros that are:

- **Maintainable**: Easy to understand and modify
- **Reliable**: Behave consistently and predictably
- **Performant**: Generate efficient code
- **Secure**: Safe from common vulnerabilities
- **Composable**: Work well with other macros
- **Testable**: Can be thoroughly validated

Remember that good macro design is an iterative process. Start simple, gather feedback, and refine your designs based on real-world usage.

---

## Navigation

- **Previous:** [Configuration](../configuration/project-setup.md)
- **Next:** [Performance Optimization](performance.md)
- **Index:** [Documentation Index](../index.md)

## See Also

- [Macro DSL Reference](../macro-dsl.md)
- [Advanced Macros](../examples/advanced-macros.md)
- [Testing Macros](testing.md)
- [Error Handling](error-handling.md) 