# Advanced Macros

Advanced macros go beyond simple token replacement to create powerful domain-specific languages, custom control structures, and sophisticated code generation patterns. This guide covers complex macro patterns and advanced techniques.

## Custom Control Structures

### Switch Expressions

Create switch expressions that return values:

```syn
$(macro) { 
    switch ($(layer() as expr)) { 
        $(layer() as cases) 
    } 
} >> { 
    (function() use ($(expr)) { 
        switch ($(expr)) { 
            $(cases) 
        } 
    })() 
}
```

**Usage:**
```php
// Input
$result = switch ($status) {
    case 'active': return 'User is active';
    case 'inactive': return 'User is inactive';
    default: return 'Unknown status';
};

// Output
$result = (function() use ($status) {
    switch ($status) {
        case 'active': return 'User is active';
        case 'inactive': return 'User is inactive';
        default: return 'Unknown status';
    }
})();
```

### Pattern Matching

Implement pattern matching for PHP:

```syn
$(macro) { 
    match ($(layer() as expr)) { 
        $(layer() as patterns) 
    } 
} >> { 
    (function() use ($(expr)) {
        $__match_value = $(expr);
        $(patterns)
        throw new MatchException('No pattern matched');
    })()
}

$(macro) { 
    case $(layer() as pattern) => $(layer() as result) 
} >> { 
    if ($__match_value === $(pattern)) return $(result); 
}
```

**Usage:**
```php
// Input
$message = match ($code) {
    case 200 => 'Success';
    case 404 => 'Not Found';
    case 500 => 'Server Error';
};

// Output
$message = (function() use ($code) {
    $__match_value = $code;
    if ($__match_value === 200) return 'Success';
    if ($__match_value === 404) return 'Not Found';
    if ($__match_value === 500) return 'Server Error';
    throw new MatchException('No pattern matched');
})();
```

### Async/Await Simulation

Create async/await syntax for PHP:

```syn
$(macro) { 
    async $(layer() as body) 
} >> { 
    (function() { 
        return new Promise(function($resolve, $reject) { 
            try { 
                $result = $(body); 
                $resolve($result); 
            } catch (Exception $e) { 
                $reject($e); 
            } 
        }); 
    })() 
}

$(macro) { 
    await $(layer() as promise) 
} >> { 
    $(promise)->wait() 
}
```

## Domain-Specific Languages

### Query Builder DSL

Create a fluent query syntax:

```syn
$(macro) { 
    query { 
        select $(list(,) as fields) 
        from $(T_STRING as table) 
        where $(layer() as condition) 
        order by $(layer() as order) 
    } 
} >> { 
    Query::select([$(fields)])
        ->from('$(table)')
        ->where($(condition))
        ->orderBy($(order))
}
```

**Usage:**
```php
// Input
$users = query {
    select name, email, created_at
    from users
    where active = 1
    order by created_at DESC
};

// Output
$users = Query::select(['name', 'email', 'created_at'])
    ->from('users')
    ->where('active = 1')
    ->orderBy('created_at DESC');
```

### Validation DSL

Create a validation syntax:

```syn
$(macro) { 
    validate $(T_VARIABLE as var) { 
        $(layer() as rules) 
    } 
} >> { 
    Validator::make($(var), [ 
        $(rules) 
    ])->validate() 
}

$(macro) { 
    $(T_STRING as field) : $(layer() as rule) 
} >> { 
    '$(field)' => '$(rule)', 
}
```

**Usage:**
```php
// Input
validate $data {
    name: required|string|max:255
    email: required|email|unique:users
    age: integer|min:18
}

// Output
Validator::make($data, [
    'name' => 'required|string|max:255',
    'email' => 'required|email|unique:users',
    'age' => 'integer|min:18',
])->validate();
```

### HTML Template DSL

Create an HTML templating syntax:

```syn
$(macro) { 
    html { 
        $(layer() as content) 
    } 
} >> { 
    (function() { 
        ob_start(); 
        $(content) 
        return ob_get_clean(); 
    })() 
}

$(macro) { 
    @if ($(layer() as condition)) { 
        $(layer() as body) 
    } 
} >> { 
    if ($(condition)) { 
        $(body) 
    } 
}

$(macro) { 
    @foreach ($(layer() as iterable) as $(T_VARIABLE as item)) { 
        $(layer() as body) 
    } 
} >> { 
    foreach ($(iterable) as $(item)) { 
        $(body) 
    } 
}
```

## Code Generation

### Property Generation

Generate getters, setters, and constructors:

```syn
$(macro) { 
    properties { 
        $(layer() as props) 
    } 
} >> { 
    $(props) 
    
    public function __construct($(layer() as constructor_params)) { 
        $(layer() as constructor_body) 
    } 
}

$(macro) { 
    $(T_STRING as type) $(T_VARIABLE as name) 
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

### Event System

Generate event handling code:

```syn
$(macro) { 
    event $(T_STRING as name) { 
        $(layer() as properties) 
    } 
} >> { 
    class $(name)Event extends Event { 
        $(properties) 
        
        public function __construct($(layer() as params)) { 
            $(layer() as assignments) 
        } 
    } 
}

$(macro) { 
    on $(T_STRING as event) { 
        $(layer() as handler) 
    } 
} >> { 
    EventDispatcher::listen('$(event)', function($event) { 
        $(handler) 
    }); 
}
```

### API Route Generation

Generate REST API routes:

```syn
$(macro) { 
    api $(T_STRING as resource) { 
        $(layer() as routes) 
    } 
} >> { 
    Route::group(['prefix' => '$(resource)'], function() { 
        $(routes) 
    }); 
}

$(macro) { 
    get $(T_STRING as path) => $(T_STRING as controller)@$(T_STRING as method) 
} >> { 
    Route::get('$(path)', '$(controller)@$(method)'); 
}

$(macro) { 
    post $(T_STRING as path) => $(T_STRING as controller)@$(T_STRING as method) 
} >> { 
    Route::post('$(path)', '$(controller)@$(method)'); 
}
```

## Advanced Pattern Techniques

### Recursive Macros

Macros that can call themselves:

```syn
$(macro) { 
    deep $(layer() as expr) 
} >> { 
    (function() { 
        $result = $(expr); 
        return is_array($result) ? array_map(function($item) { 
            return deep $item; 
        }, $result) : $result; 
    })() 
}
```

### Contextual Macros

Macros that behave differently based on context:

```syn
$(macro) { 
    auto $(T_VARIABLE as var) = $(layer() as value) 
} >> { 
    $(var) = $(value); 
    // Type inference could be added here 
}
```

### Macro Composition

Combining multiple macros:

```syn
$(macro) { 
    pipeline $(layer() as input) { 
        $(layer() as steps) 
    } 
} >> { 
    (function() { 
        $__pipeline_value = $(input); 
        $(steps) 
        return $__pipeline_value; 
    })() 
}

$(macro) { 
    step $(layer() as transform) 
} >> { 
    $__pipeline_value = ($(transform))($__pipeline_value); 
}
```

## Performance Considerations

### Macro Optimization

1. **Minimize token generation**: Generate only necessary tokens
2. **Cache complex patterns**: Store compiled patterns for reuse
3. **Avoid deep nesting**: Limit macro recursion depth
4. **Profile macro usage**: Identify performance bottlenecks

### Memory Management

```syn
$(macro) { 
    memory_efficient $(layer() as expr) 
} >> { 
    (function() { 
        $result = $(expr); 
        gc_collect_cycles(); 
        return $result; 
    })() 
}
```

## Testing Advanced Macros

### Unit Testing

```php
class AdvancedMacroTest extends TestCase
{
    public function testSwitchExpression(): void
    {
        $source = '<?php $result = switch ($status) { case "active": return "ok"; };';
        $result = $this->processor->process($source);
        
        $this->assertStringContains('function() use ($status)', $result);
        $this->assertStringContains('switch ($status)', $result);
    }
    
    public function testQueryBuilder(): void
    {
        $source = '<?php query { select name from users where active = 1 };';
        $result = $this->processor->process($source);
        
        $this->assertStringContains('Query::select', $result);
        $this->assertStringContains('->from(\'users\')', $result);
    }
}
```

### Integration Testing

```php
class MacroIntegrationTest extends TestCase
{
    public function testComplexMacroChain(): void
    {
        $source = file_get_contents('fixtures/complex-macro-example.syn.php');
        $expected = file_get_contents('fixtures/complex-macro-expected.php');
        
        $result = $this->processor->process($source);
        
        $this->assertEquals($expected, $result);
    }
}
```

## Best Practices

### Design Principles

1. **Make it readable**: The macro should improve code readability
2. **Keep it focused**: Each macro should have a single responsibility
3. **Provide escape hatches**: Allow users to bypass the macro when needed
4. **Document thoroughly**: Complex macros need extensive documentation

### Error Handling

```syn
$(macro) { 
    safe_macro $(layer() as expr) 
} >> { 
    try { 
        $(expr) 
    } catch (MacroException $e) { 
        trigger_error('Macro error: ' . $e->getMessage(), E_USER_WARNING); 
        null; 
    } 
}
```

### Debugging

```syn
$(macro) { 
    debug_macro $(layer() as expr) 
} >> { 
    (function() { 
        $__debug_start = microtime(true); 
        $__debug_result = $(expr); 
        $__debug_time = microtime(true) - $__debug_start; 
        error_log("Macro execution time: {$__debug_time}s"); 
        return $__debug_result; 
    })() 
}
```

## Real-World Examples

### Framework Integration

```syn
# Laravel-style dependency injection
$(macro) { 
    inject $(T_STRING as service) as $(T_VARIABLE as var) 
} >> { 
    $(var) = app($(service)::class); 
}

# Symfony-style routing
$(macro) { 
    route $(T_STRING as path) $(T_STRING as name) $(T_STRING as method) 
} >> { 
    $routes->add('$(name)', new Route('$(path)', ['_controller' => '$(method)'])); 
}
```

### Testing Utilities

```syn
# RSpec-style testing
$(macro) { 
    describe $(T_STRING as description) { 
        $(layer() as tests) 
    } 
} >> { 
    class $(description)Test extends TestCase { 
        $(tests) 
    } 
}

$(macro) { 
    it $(T_STRING as should) { 
        $(layer() as test) 
    } 
} >> { 
    public function test$(should)(): void { 
        $(test) 
    } 
}
```

---

## Navigation

- **Previous:** [Simple Macros](simple-macros.md)
- **Next:** [For Comprehension](for-comprehension.md)
- **Index:** [Documentation Index](../index.md)

## See Also

- [Macro DSL Reference](../macro-dsl.md)
- [Parser Combinators](../concepts/parser-combinators.md)
- [Best Practices](../best-practices/macro-design.md)
- [Real-World Examples](real-world.md) 