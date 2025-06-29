# Plugin System

The Syn plugin system allows you to extend the preprocessor with custom functionality, transformations, and integrations. Plugins can hook into various stages of the processing pipeline to provide additional features.

## Plugin Architecture

Syn uses an event-driven plugin architecture where plugins can:

- **Hook into processing stages**: Before/after parsing, transformation, output
- **Register custom macros**: Add domain-specific macro libraries
- **Modify configuration**: Dynamically adjust processing settings
- **Provide custom transformers**: Add specialized transformation logic
- **Integrate with external tools**: Connect with IDEs, build systems, etc.

### Plugin Lifecycle

```
1. Plugin Discovery    → Find and load plugins
2. Plugin Registration → Register hooks and services
3. Processing Hooks    → Execute during processing
4. Cleanup            → Clean up resources
```

## Creating Plugins

### Basic Plugin Structure

All plugins must implement the `PluginInterface`:

```php
<?php

namespace Syn\Plugin;

interface PluginInterface
{
    /**
     * Get the plugin name
     */
    public function getName(): string;
    
    /**
     * Get the plugin version
     */
    public function getVersion(): string;
    
    /**
     * Register the plugin with the plugin manager
     */
    public function register(PluginManager $manager): void;
    
    /**
     * Boot the plugin (called after all plugins are registered)
     */
    public function boot(): void;
}
```

### Simple Plugin Example

```php
<?php

namespace MyApp\Plugins;

use Syn\Plugin\PluginInterface;
use Syn\Plugin\PluginManager;

class LoggingPlugin implements PluginInterface
{
    public function getName(): string
    {
        return 'logging';
    }
    
    public function getVersion(): string
    {
        return '1.0.0';
    }
    
    public function register(PluginManager $manager): void
    {
        $manager->addHook('before_parse', [$this, 'beforeParse']);
        $manager->addHook('after_transform', [$this, 'afterTransform']);
    }
    
    public function boot(): void
    {
        // Initialize logging system
        error_log('Syn Logging Plugin initialized');
    }
    
    public function beforeParse(string $source, string $filename): void
    {
        error_log("Parsing file: {$filename}");
    }
    
    public function afterTransform(string $result, string $filename): void
    {
        error_log("Transformed file: {$filename} ({$this->getStats($result)})");
    }
    
    private function getStats(string $code): string
    {
        $lines = substr_count($code, "\n") + 1;
        $chars = strlen($code);
        return "{$lines} lines, {$chars} characters";
    }
}
```

## Plugin Interface

### Available Hooks

Plugins can hook into these processing stages:

#### Pre-Processing Hooks
- `before_parse` - Before source code is parsed
- `before_load_macros` - Before macros are loaded
- `before_transform` - Before transformation begins

#### Processing Hooks
- `on_macro_match` - When a macro is matched
- `on_token_process` - When processing individual tokens
- `on_error` - When an error occurs

#### Post-Processing Hooks
- `after_transform` - After transformation completes
- `after_output` - After output is generated
- `before_write` - Before writing to file

### Hook Parameters

Each hook receives specific parameters:

```php
// before_parse(string $source, string $filename)
$manager->addHook('before_parse', function($source, $filename) {
    echo "Parsing {$filename}\n";
});

// on_macro_match(MacroDefinition $macro, array $tokens, int $position)
$manager->addHook('on_macro_match', function($macro, $tokens, $position) {
    echo "Matched macro: {$macro->getName()}\n";
});

// on_error(Exception $error, string $context)
$manager->addHook('on_error', function($error, $context) {
    error_log("Error in {$context}: {$error->getMessage()}");
});
```

## Plugin Registration

### Manual Registration

```php
use Syn\Plugin\PluginManager;
use MyApp\Plugins\LoggingPlugin;

$pluginManager = new PluginManager();
$pluginManager->register(new LoggingPlugin());
```

### Automatic Discovery

Plugins can be auto-discovered from directories:

```php
$pluginManager->discoverFrom([
    __DIR__ . '/plugins',
    __DIR__ . '/vendor/*/syn-plugins',
]);
```

### Composer Integration

Add to your `composer.json`:

```json
{
    "extra": {
        "syn": {
            "plugins": [
                "MyApp\\Plugins\\LoggingPlugin",
                "MyApp\\Plugins\\CachePlugin"
            ]
        }
    }
}
```

## Advanced Plugin Examples

### Macro Library Plugin

```php
<?php

namespace MyApp\Plugins;

use Syn\Plugin\PluginInterface;
use Syn\Plugin\PluginManager;
use Syn\Macro\MacroLoader;

class ReactivePlugin implements PluginInterface
{
    private MacroLoader $macroLoader;
    
    public function getName(): string
    {
        return 'reactive';
    }
    
    public function getVersion(): string
    {
        return '2.1.0';
    }
    
    public function register(PluginManager $manager): void
    {
        $this->macroLoader = $manager->getMacroLoader();
        
        $manager->addHook('before_load_macros', [$this, 'loadReactiveMacros']);
    }
    
    public function boot(): void
    {
        // Plugin is ready
    }
    
    public function loadReactiveMacros(): void
    {
        // Observable pattern
        $this->macroLoader->loadFromString('
            $(macro) { 
                observable $(T_VARIABLE as var) = $(layer() as initial) 
            } >> { 
                $(var) = new Observable($(initial)); 
            }
        ');
        
        // Reactive computations
        $this->macroLoader->loadFromString('
            $(macro) { 
                computed $(T_VARIABLE as var) = $(layer() as expr) 
            } >> { 
                $(var) = new Computed(function() { return $(expr); }); 
            }
        ');
        
        // Event handlers
        $this->macroLoader->loadFromString('
            $(macro) { 
                on $(layer() as event) { $(layer() as handler) } 
            } >> { 
                EventBus::on($(event), function($data) { $(handler) }); 
            }
        ');
    }
}
```

### IDE Integration Plugin

```php
<?php

namespace MyApp\Plugins;

use Syn\Plugin\PluginInterface;
use Syn\Plugin\PluginManager;

class IDEPlugin implements PluginInterface
{
    private string $ideSocketPath;
    
    public function __construct(string $ideSocketPath = '/tmp/syn-ide.sock')
    {
        $this->ideSocketPath = $ideSocketPath;
    }
    
    public function getName(): string
    {
        return 'ide-integration';
    }
    
    public function getVersion(): string
    {
        return '1.0.0';
    }
    
    public function register(PluginManager $manager): void
    {
        $manager->addHook('on_error', [$this, 'sendErrorToIDE']);
        $manager->addHook('after_transform', [$this, 'sendCompletionToIDE']);
    }
    
    public function boot(): void
    {
        $this->connectToIDE();
    }
    
    public function sendErrorToIDE(\Exception $error, string $context): void
    {
        $this->sendToIDE([
            'type' => 'error',
            'message' => $error->getMessage(),
            'file' => $error->getFile(),
            'line' => $error->getLine(),
            'context' => $context
        ]);
    }
    
    public function sendCompletionToIDE(string $result, string $filename): void
    {
        $this->sendToIDE([
            'type' => 'completion',
            'file' => $filename,
            'size' => strlen($result)
        ]);
    }
    
    private function connectToIDE(): void
    {
        // Connect to IDE via socket, HTTP, or other protocol
    }
    
    private function sendToIDE(array $data): void
    {
        // Send data to IDE
        file_put_contents($this->ideSocketPath, json_encode($data) . "\n", FILE_APPEND);
    }
}
```

### Performance Profiling Plugin

```php
<?php

namespace MyApp\Plugins;

use Syn\Plugin\PluginInterface;
use Syn\Plugin\PluginManager;

class ProfilerPlugin implements PluginInterface
{
    private array $timings = [];
    private array $stack = [];
    
    public function getName(): string
    {
        return 'profiler';
    }
    
    public function getVersion(): string
    {
        return '1.0.0';
    }
    
    public function register(PluginManager $manager): void
    {
        $manager->addHook('before_parse', [$this, 'startTiming']);
        $manager->addHook('before_transform', [$this, 'startTiming']);
        $manager->addHook('after_transform', [$this, 'endTiming']);
        $manager->addHook('after_output', [$this, 'endTiming']);
        $manager->addHook('on_macro_match', [$this, 'recordMacroUsage']);
    }
    
    public function boot(): void
    {
        register_shutdown_function([$this, 'outputProfile']);
    }
    
    public function startTiming(string $stage): void
    {
        $this->stack[] = [
            'stage' => $stage,
            'start' => hrtime(true)
        ];
    }
    
    public function endTiming(): void
    {
        if (empty($this->stack)) return;
        
        $timing = array_pop($this->stack);
        $duration = (hrtime(true) - $timing['start']) / 1000000; // Convert to milliseconds
        
        $this->timings[$timing['stage']] = ($this->timings[$timing['stage']] ?? 0) + $duration;
    }
    
    public function recordMacroUsage($macro, $tokens, $position): void
    {
        $macroName = $macro->getName();
        $this->timings["macro:{$macroName}"] = ($this->timings["macro:{$macroName}"] ?? 0) + 1;
    }
    
    public function outputProfile(): void
    {
        echo "\n=== Syn Performance Profile ===\n";
        
        foreach ($this->timings as $stage => $value) {
            if (strpos($stage, 'macro:') === 0) {
                echo sprintf("%-30s %d uses\n", $stage, $value);
            } else {
                echo sprintf("%-30s %.2f ms\n", $stage, $value);
            }
        }
        
        echo "================================\n";
    }
}
```

## Best Practices

### Plugin Design

1. **Single Responsibility**: Each plugin should have one clear purpose
2. **Minimal Dependencies**: Avoid heavy dependencies when possible
3. **Error Handling**: Handle errors gracefully without breaking the pipeline
4. **Performance**: Be mindful of performance impact on processing

### Configuration

```php
class ConfigurablePlugin implements PluginInterface
{
    private array $config;
    
    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'enabled' => true,
            'log_level' => 'info',
            'output_format' => 'json'
        ], $config);
    }
    
    public function register(PluginManager $manager): void
    {
        if (!$this->config['enabled']) {
            return; // Plugin disabled
        }
        
        // Register hooks based on configuration
    }
}
```

### Testing Plugins

```php
class PluginTest extends TestCase
{
    public function testPluginRegistration(): void
    {
        $manager = new PluginManager();
        $plugin = new LoggingPlugin();
        
        $manager->register($plugin);
        
        $this->assertTrue($manager->hasPlugin('logging'));
        $this->assertEquals('1.0.0', $manager->getPlugin('logging')->getVersion());
    }
    
    public function testPluginHooks(): void
    {
        $manager = new PluginManager();
        $plugin = new LoggingPlugin();
        
        $manager->register($plugin);
        
        ob_start();
        $manager->executeHook('before_parse', 'test.php', 'test source');
        $output = ob_get_clean();
        
        $this->assertStringContains('Parsing file: test.php', $output);
    }
}
```

## Plugin Distribution

### Composer Package

Create a `composer.json` for your plugin:

```json
{
    "name": "vendor/syn-plugin-name",
    "description": "Description of your Syn plugin",
    "type": "syn-plugin",
    "require": {
        "marcelloduarte/syn": "^1.0"
    },
    "autoload": {
        "psr-4": {
            "Vendor\\SynPluginName\\": "src/"
        }
    },
    "extra": {
        "syn": {
            "plugin-class": "Vendor\\SynPluginName\\Plugin"
        }
    }
}
```

### Plugin Marketplace

Submit your plugins to the Syn plugin registry:

1. Create a GitHub repository
2. Tag releases with semantic versioning
3. Submit to the plugin registry
4. Include comprehensive documentation

## Configuration

### Global Plugin Configuration

```php
// syn.config.php
return [
    'plugins' => [
        'enabled' => ['logging', 'profiler'],
        'disabled' => ['experimental'],
        'config' => [
            'logging' => [
                'level' => 'debug',
                'output' => 'file'
            ],
            'profiler' => [
                'enabled' => true,
                'detailed' => false
            ]
        ]
    ]
];
```

### Per-Project Configuration

```json
{
    "syn": {
        "plugins": {
            "MyApp\\Plugins\\CustomPlugin": {
                "enabled": true,
                "custom_option": "value"
            }
        }
    }
}
```

---

## Navigation

- **Previous:** [Real-World Examples](examples/real-world.md)
- **Next:** [Configuration](configuration/project-setup.md)
- **Index:** [Documentation Index](index.md)

## See Also

- [Plugin Interface](api/plugins.md)
- [Configuration](configuration/)
- [Best Practices](best-practices/)
- [Examples](examples/) 