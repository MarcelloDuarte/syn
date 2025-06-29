# Real-World Examples

This section demonstrates practical applications of Syn macros in real-world scenarios, showing how to solve common programming challenges and implement useful patterns.

## Enum Implementation

PHP doesn't have native enums (before PHP 8.1), but we can create them using macros:

```syn
$(macro) { 
    enum $(T_STRING as name) { 
        $(layer() as cases) 
    } 
} >> { 
    class $(name) { 
        $(cases) 
        
        private function __construct() {} 
        
        public static function values(): array { 
            return [$(layer() as values)]; 
        } 
    } 
}

$(macro) { 
    case $(T_STRING as name) = $(layer() as value) 
} >> { 
    public const $(name) = $(value); 
}
```

**Usage:**
```php
// Input
enum Status {
    case ACTIVE = 'active'
    case INACTIVE = 'inactive'  
    case PENDING = 'pending'
}

// Output
class Status {
    public const ACTIVE = 'active';
    public const INACTIVE = 'inactive';
    public const PENDING = 'pending';
    
    private function __construct() {}
    
    public static function values(): array {
        return ['active', 'inactive', 'pending'];
    }
}
```

### Advanced Enum with Methods

```syn
$(macro) { 
    enum $(T_STRING as name) : $(T_STRING as type) { 
        $(layer() as cases) 
        
        $(layer() as methods) 
    } 
} >> { 
    class $(name) { 
        private $(type) $value; 
        
        $(cases) 
        
        private function __construct($(type) $value) { 
            $this->value = $value; 
        } 
        
        public function getValue(): $(type) { 
            return $this->value; 
        } 
        
        $(methods) 
    } 
}

$(macro) { 
    case $(T_STRING as name)($(layer() as value)) 
} >> { 
    public static function $(name)(): self { 
        return new self($(value)); 
    } 
}
```

**Usage:**
```php
// Input
enum HttpStatus : int {
    case OK(200)
    case NOT_FOUND(404)
    case SERVER_ERROR(500)
    
    public function isError(): bool {
        return $this->value >= 400;
    }
}

// Usage
$status = HttpStatus::NOT_FOUND();
echo $status->getValue(); // 404
echo $status->isError(); // true
```

## Builder Pattern

Create fluent builder patterns with macros:

```syn
$(macro) { 
    builder $(T_STRING as name) { 
        $(layer() as properties) 
    } 
} >> { 
    class $(name)Builder { 
        $(properties) 
        
        public function build(): $(name) { 
            return new $(name)( 
                $(layer() as constructor_args) 
            ); 
        } 
    } 
}

$(macro) { 
    property $(T_STRING as type) $(T_VARIABLE as name) 
} >> { 
    private $(type) $(name); 
    
    public function with$(name)($(type) $(name)): self { 
        $this->$(name) = $(name); 
        return $this; 
    } 
}
```

**Usage:**
```php
// Input
builder User {
    property string $name
    property string $email
    property int $age
}

// Output
class UserBuilder {
    private string $name;
    private string $email;
    private int $age;
    
    public function withName(string $name): self {
        $this->name = $name;
        return $this;
    }
    
    public function withEmail(string $email): self {
        $this->email = $email;
        return $this;
    }
    
    public function withAge(int $age): self {
        $this->age = $age;
        return $this;
    }
    
    public function build(): User {
        return new User($this->name, $this->email, $this->age);
    }
}

// Usage
$user = (new UserBuilder())
    ->withName('John Doe')
    ->withEmail('john@example.com')
    ->withAge(30)
    ->build();
```

## Validation Macros

Create comprehensive validation systems:

```syn
$(macro) { 
    validator $(T_STRING as name) { 
        $(layer() as rules) 
    } 
} >> { 
    class $(name)Validator { 
        private array $errors = []; 
        
        public function validate(array $data): ValidationResult { 
            $this->errors = []; 
            $(rules) 
            return new ValidationResult($this->errors); 
        } 
        
        private function addError(string $field, string $message): void { 
            $this->errors[$field][] = $message; 
        } 
    } 
}

$(macro) { 
    rule $(T_STRING as field) : $(layer() as validation) 
} >> { 
    if (!$(validation)) { 
        $this->addError('$(field)', '$(field) validation failed'); 
    } 
}

$(macro) { 
    required $(T_VARIABLE as value) 
} >> { 
    (!empty($(value))) 
}

$(macro) { 
    email $(T_VARIABLE as value) 
} >> { 
    (filter_var($(value), FILTER_VALIDATE_EMAIL) !== false) 
}

$(macro) { 
    min_length $(T_VARIABLE as value) $(T_LNUMBER as length) 
} >> { 
    (strlen($(value)) >= $(length)) 
}
```

**Usage:**
```php
// Input
validator UserValidator {
    rule name: required $data['name'] && min_length $data['name'] 3
    rule email: required $data['email'] && email $data['email']
    rule age: required $data['age'] && $data['age'] >= 18
}

// Output
class UserValidator {
    private array $errors = [];
    
    public function validate(array $data): ValidationResult {
        $this->errors = [];
        
        if (!((!empty($data['name'])) && (strlen($data['name']) >= 3))) {
            $this->addError('name', 'name validation failed');
        }
        
        if (!((!empty($data['email'])) && (filter_var($data['email'], FILTER_VALIDATE_EMAIL) !== false))) {
            $this->addError('email', 'email validation failed');
        }
        
        if (!((!empty($data['age'])) && $data['age'] >= 18)) {
            $this->addError('age', 'age validation failed');
        }
        
        return new ValidationResult($this->errors);
    }
    
    private function addError(string $field, string $message): void {
        $this->errors[$field][] = $message;
    }
}
```

## Database Query Builder

Create a type-safe query builder:

```syn
$(macro) { 
    query $(T_STRING as table) { 
        $(layer() as clauses) 
    } 
} >> { 
    QueryBuilder::table('$(table)') 
        $(clauses) 
}

$(macro) { 
    select $(list(,) as fields) 
} >> { 
    ->select([$(fields)]) 
}

$(macro) { 
    where $(layer() as condition) 
} >> { 
    ->where($(condition)) 
}

$(macro) { 
    join $(T_STRING as table) on $(layer() as condition) 
} >> { 
    ->join('$(table)', $(condition)) 
}

$(macro) { 
    order by $(layer() as field) $(T_STRING as direction) 
} >> { 
    ->orderBy($(field), '$(direction)') 
}

$(macro) { 
    limit $(T_LNUMBER as count) 
} >> { 
    ->limit($(count)) 
}
```

**Usage:**
```php
// Input
$users = query users {
    select id, name, email, created_at
    join profiles on users.id = profiles.user_id
    where active = 1 AND created_at > '2023-01-01'
    order by created_at DESC
    limit 10
};

// Output
$users = QueryBuilder::table('users')
    ->select(['id', 'name', 'email', 'created_at'])
    ->join('profiles', 'users.id = profiles.user_id')
    ->where('active = 1 AND created_at > \'2023-01-01\'')
    ->orderBy('created_at', 'DESC')
    ->limit(10);
```

## Event System

Create a simple event system:

```syn
$(macro) { 
    event $(T_STRING as name) { 
        $(layer() as properties) 
    } 
} >> { 
    class $(name)Event { 
        $(properties) 
        
        public function __construct($(layer() as params)) { 
            $(layer() as assignments) 
        } 
        
        public function getName(): string { 
            return '$(name)'; 
        } 
    } 
}

$(macro) { 
    property $(T_STRING as type) $(T_VARIABLE as name) 
} >> { 
    public $(type) $(name); 
}

$(macro) { 
    listen $(T_STRING as event) { 
        $(layer() as handler) 
    } 
} >> { 
    EventDispatcher::listen('$(event)', function($event) { 
        $(handler) 
    }); 
}

$(macro) { 
    emit $(T_STRING as event) with $(layer() as data) 
} >> { 
    EventDispatcher::emit(new $(event)Event($(data))); 
}
```

**Usage:**
```php
// Input
event UserRegistered {
    property User $user
    property DateTime $timestamp
}

listen UserRegistered {
    $this->sendWelcomeEmail($event->user);
    $this->logRegistration($event->user, $event->timestamp);
}

// Later in code
emit UserRegistered with $user, new DateTime();

// Output
class UserRegisteredEvent {
    public User $user;
    public DateTime $timestamp;
    
    public function __construct(User $user, DateTime $timestamp) {
        $this->user = $user;
        $this->timestamp = $timestamp;
    }
    
    public function getName(): string {
        return 'UserRegistered';
    }
}

EventDispatcher::listen('UserRegistered', function($event) {
    $this->sendWelcomeEmail($event->user);
    $this->logRegistration($event->user, $event->timestamp);
});

// Later in code
EventDispatcher::emit(new UserRegisteredEvent($user, new DateTime()));
```

## Configuration DSL

Create a configuration syntax:

```syn
$(macro) { 
    config $(T_STRING as name) { 
        $(layer() as settings) 
    } 
} >> { 
    return [ 
        '$(name)' => [ 
            $(settings) 
        ] 
    ]; 
}

$(macro) { 
    $(T_STRING as key) = $(layer() as value) 
} >> { 
    '$(key)' => $(value), 
}

$(macro) { 
    group $(T_STRING as name) { 
        $(layer() as items) 
    } 
} >> { 
    '$(name)' => [ 
        $(items) 
    ], 
}
```

**Usage:**
```php
// Input
config database {
    host = 'localhost'
    port = 3306
    
    group connections {
        default = 'mysql'
        mysql = [
            'driver' => 'mysql',
            'host' => env('DB_HOST', 'localhost'),
            'database' => env('DB_DATABASE'),
        ]
    }
}

// Output
return [
    'database' => [
        'host' => 'localhost',
        'port' => 3306,
        'connections' => [
            'default' => 'mysql',
            'mysql' => [
                'driver' => 'mysql',
                'host' => env('DB_HOST', 'localhost'),
                'database' => env('DB_DATABASE'),
            ],
        ],
    ]
];
```

## Testing DSL

Create a BDD-style testing syntax:

```syn
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

$(macro) { 
    expect $(layer() as actual) to equal $(layer() as expected) 
} >> { 
    $this->assertEquals($(expected), $(actual)); 
}

$(macro) { 
    expect $(layer() as actual) to be true 
} >> { 
    $this->assertTrue($(actual)); 
}

$(macro) { 
    expect $(layer() as actual) to contain $(layer() as expected) 
} >> { 
    $this->assertStringContains($(expected), $(actual)); 
}
```

**Usage:**
```php
// Input
describe "Calculator" {
    it "should add two numbers correctly" {
        $calculator = new Calculator();
        $result = $calculator->add(2, 3);
        expect $result to equal 5
    }
    
    it "should handle negative numbers" {
        $calculator = new Calculator();
        $result = $calculator->add(-2, 3);
        expect $result to equal 1
    }
}

// Output
class CalculatorTest extends TestCase {
    public function testShouldAddTwoNumbersCorrectly(): void {
        $calculator = new Calculator();
        $result = $calculator->add(2, 3);
        $this->assertEquals(5, $result);
    }
    
    public function testShouldHandleNegativeNumbers(): void {
        $calculator = new Calculator();
        $result = $calculator->add(-2, 3);
        $this->assertEquals(1, $result);
    }
}
```

## API Route Definition

Create a clean API routing syntax:

```syn
$(macro) { 
    api $(T_STRING as prefix) { 
        $(layer() as routes) 
    } 
} >> { 
    Route::group(['prefix' => '$(prefix)', 'middleware' => 'api'], function() { 
        $(routes) 
    }); 
}

$(macro) { 
    get $(T_STRING as path) => $(T_STRING as controller)@$(T_STRING as method) 
} >> { 
    Route::get('$(path)', '$(controller)@$(method)'); 
}

$(macro) { 
    post $(T_STRING as path) => $(T_STRING as controller)@$(method) 
} >> { 
    Route::post('$(path)', '$(controller)@$(method)'); 
}

$(macro) { 
    resource $(T_STRING as name) => $(T_STRING as controller) 
} >> { 
    Route::resource('$(name)', '$(controller)'); 
}

$(macro) { 
    middleware $(list(,) as middleware) { 
        $(layer() as routes) 
    } 
} >> { 
    Route::group(['middleware' => [$(middleware)]], function() { 
        $(routes) 
    }); 
}
```

**Usage:**
```php
// Input
api v1 {
    get users => UserController@index
    post users => UserController@store
    
    middleware auth, throttle:60,1 {
        get users/{id} => UserController@show
        resource posts => PostController
    }
}

// Output
Route::group(['prefix' => 'v1', 'middleware' => 'api'], function() {
    Route::get('users', 'UserController@index');
    Route::post('users', 'UserController@store');
    
    Route::group(['middleware' => ['auth', 'throttle:60,1']], function() {
        Route::get('users/{id}', 'UserController@show');
        Route::resource('posts', 'PostController');
    });
});
```

## Performance Considerations

### Macro Caching

```syn
$(macro) { 
    cached $(layer() as expr) for $(T_LNUMBER as seconds) 
} >> { 
    Cache::remember(md5('$(expr)'), $(seconds), function() { 
        return $(expr); 
    }) 
}
```

### Lazy Loading

```syn
$(macro) { 
    lazy $(T_VARIABLE as var) = $(layer() as expr) 
} >> { 
    $(var) = function() use (&$(var)) { 
        if ($(var) instanceof Closure) { 
            $(var) = ($(expr)); 
        } 
        return $(var); 
    }; 
}
```

## Best Practices for Real-World Usage

### 1. Keep Macros Simple and Focused
- Each macro should solve one specific problem
- Avoid overly complex transformations
- Make the generated code readable

### 2. Document Your Macros
- Include usage examples
- Document edge cases and limitations
- Provide migration guides for breaking changes

### 3. Test Thoroughly
- Unit test macro transformations
- Integration test generated code
- Performance test complex macros

### 4. Version Your Macros
- Use semantic versioning for macro libraries
- Maintain backward compatibility when possible
- Provide deprecation warnings

### 5. Error Handling
- Validate macro inputs
- Provide helpful error messages
- Graceful degradation when possible

---

## Navigation

- **Previous:** [For Comprehension](for-comprehension.md)
- **Next:** [Plugin System](../plugins.md)
- **Index:** [Documentation Index](../index.md)

## See Also

- [Advanced Macros](advanced-macros.md)
- [Best Practices](../best-practices/macro-design.md)
- [Macro DSL Reference](../macro-dsl.md)
- [Testing Macros](../best-practices/testing.md) 