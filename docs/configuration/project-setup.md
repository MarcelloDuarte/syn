# Project Setup

This guide covers how to set up Syn in your PHP projects, from basic installation to advanced configuration options.

## Installation

### Composer Installation

The recommended way to install Syn is via Composer:

```bash
composer require marcelloduarte/syn --dev
```

### Global Installation

For system-wide usage:

```bash
composer global require marcelloduarte/syn
```

Make sure your global Composer bin directory is in your PATH:

```bash
export PATH="$PATH:$HOME/.composer/vendor/bin"
```

### Manual Installation

Download the latest release from GitHub:

```bash
wget https://github.com/marcelloduarte/syn/releases/latest/download/syn.phar
chmod +x syn.phar
mv syn.phar /usr/local/bin/syn
```

## Basic Project Setup

### Directory Structure

Organize your project with a clear separation between source and processed files:

```
project/
├── src/               # Original .syn.php files
├── build/             # Processed PHP files
├── macros/            # Custom macro definitions
├── syn.config.php     # Syn configuration
└── composer.json      # Project dependencies
```

### Configuration File

Create a `syn.config.php` file in your project root:

```php
<?php

return [
    'source_directories' => [
        'src/',
        'app/',
    ],
    
    'output_directory' => 'build/',
    
    'macro_directories' => [
        'macros/',
        'vendor/*/syn-macros/',
    ],
    
    'file_extensions' => [
        '.syn.php',
        '.syn',
    ],
    
    'exclude_patterns' => [
        '*/tests/*',
        '*/vendor/*',
        '*.test.syn.php',
    ],
    
    'preprocessing' => [
        'preserve_comments' => true,
        'optimize_output' => true,
        'generate_source_maps' => false,
    ],
    
    'plugins' => [
        // Plugin configurations
    ],
];
```

### Composer Integration

Add Syn to your `composer.json`:

```json
{
    "require-dev": {
        "marcelloduarte/syn": "^1.0"
    },
    "scripts": {
        "syn:build": "syn build",
        "syn:watch": "syn watch",
        "pre-autoload-dump": "syn build"
    },
    "extra": {
        "syn": {
            "config": "syn.config.php",
            "auto-build": true
        }
    }
}
```

## Advanced Configuration

### Environment-Specific Configuration

Create different configurations for different environments:

```php
// syn.config.php
<?php

$baseConfig = [
    'source_directories' => ['src/'],
    'output_directory' => 'build/',
];

$environment = $_ENV['APP_ENV'] ?? 'development';

switch ($environment) {
    case 'production':
        return array_merge($baseConfig, [
            'preprocessing' => [
                'optimize_output' => true,
                'remove_debug_macros' => true,
                'minify_output' => true,
            ],
        ]);
        
    case 'testing':
        return array_merge($baseConfig, [
            'source_directories' => ['src/', 'tests/'],
            'preprocessing' => [
                'generate_source_maps' => true,
                'preserve_debug_info' => true,
            ],
        ]);
        
    default: // development
        return array_merge($baseConfig, [
            'preprocessing' => [
                'preserve_comments' => true,
                'generate_source_maps' => true,
            ],
        ]);
}
```

### Custom File Processing

Configure how different file types are processed:

```php
return [
    'file_processors' => [
        '*.syn.php' => [
            'processor' => 'php',
            'options' => [
                'strict_types' => true,
                'php_version' => '8.1',
            ],
        ],
        
        '*.syn.js' => [
            'processor' => 'javascript',
            'options' => [
                'target' => 'es2020',
                'module' => 'commonjs',
            ],
        ],
        
        '*.syn.ts' => [
            'processor' => 'typescript',
            'options' => [
                'strict' => true,
                'target' => 'es2020',
            ],
        ],
    ],
];
```

### Macro Discovery Configuration

Control how macros are discovered and loaded:

```php
return [
    'macro_discovery' => [
        'auto_discovery' => true,
        'cache_macros' => true,
        'cache_ttl' => 3600, // 1 hour
        
        'sources' => [
            [
                'type' => 'directory',
                'path' => 'macros/',
                'recursive' => true,
                'pattern' => '*.syn',
            ],
            [
                'type' => 'file',
                'path' => 'config/macros.syn',
            ],
            [
                'type' => 'composer',
                'package_type' => 'syn-macro-library',
            ],
            [
                'type' => 'url',
                'url' => 'https://example.com/macros.syn',
                'cache' => true,
            ],
        ],
    ],
];
```

## Build Integration

### NPM Scripts Integration

For projects using NPM, add Syn to your build process:

```json
{
    "scripts": {
        "build": "npm run syn:build && npm run webpack",
        "syn:build": "syn build",
        "syn:watch": "syn watch --daemon",
        "dev": "concurrently 'npm run syn:watch' 'npm run webpack:dev'"
    },
    "devDependencies": {
        "concurrently": "^7.0.0"
    }
}
```

### Webpack Integration

Create a Webpack plugin for Syn:

```javascript
// webpack.syn.plugin.js
const { spawn } = require('child_process');

class SynPlugin {
    constructor(options = {}) {
        this.options = options;
    }
    
    apply(compiler) {
        compiler.hooks.beforeCompile.tapAsync('SynPlugin', (params, callback) => {
            const syn = spawn('syn', ['build'], {
                stdio: 'inherit'
            });
            
            syn.on('close', (code) => {
                if (code === 0) {
                    callback();
                } else {
                    callback(new Error(`Syn build failed with code ${code}`));
                }
            });
        });
    }
}

module.exports = SynPlugin;
```

```javascript
// webpack.config.js
const SynPlugin = require('./webpack.syn.plugin');

module.exports = {
    plugins: [
        new SynPlugin(),
    ],
};
```

### GitHub Actions Integration

Automate Syn processing in CI/CD:

```yaml
# .github/workflows/build.yml
name: Build

on: [push, pull_request]

jobs:
  build:
    runs-on: ubuntu-latest
    
    steps:
    - uses: actions/checkout@v2
    
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.1'
        tools: composer
    
    - name: Install dependencies
      run: composer install --no-dev --optimize-autoloader
    
    - name: Build with Syn
      run: composer run syn:build
    
    - name: Run tests
      run: vendor/bin/phpunit
    
    - name: Upload build artifacts
      uses: actions/upload-artifact@v2
      with:
        name: build-files
        path: build/
```

## IDE Integration

### VSCode Configuration

Create a `.vscode/settings.json`:

```json
{
    "files.associations": {
        "*.syn.php": "php",
        "*.syn": "php"
    },
    "php.validate.executablePath": "/usr/bin/php",
    "syn.autoProcess": true,
    "syn.configFile": "syn.config.php"
}
```

Create a `.vscode/tasks.json`:

```json
{
    "version": "2.0.0",
    "tasks": [
        {
            "label": "Syn: Build",
            "type": "shell",
            "command": "syn",
            "args": ["build"],
            "group": {
                "kind": "build",
                "isDefault": true
            },
            "presentation": {
                "echo": true,
                "reveal": "always",
                "focus": false,
                "panel": "shared"
            }
        },
        {
            "label": "Syn: Watch",
            "type": "shell",
            "command": "syn",
            "args": ["watch"],
            "isBackground": true,
            "group": "build"
        }
    ]
}
```

### PhpStorm Configuration

Add a custom file type for `.syn.php` files:

1. Go to File → Settings → Editor → File Types
2. Add a new file type or associate with PHP
3. Add `*.syn.php` and `*.syn` patterns

Create an external tool:

1. Go to File → Settings → Tools → External Tools
2. Add new tool:
   - Name: Syn Build
   - Program: syn
   - Arguments: build
   - Working directory: $ProjectFileDir$

## Docker Integration

### Dockerfile

```dockerfile
FROM php:8.1-cli

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install Syn
RUN composer global require marcelloduarte/syn

# Add Composer global bin to PATH
ENV PATH="${PATH}:/root/.composer/vendor/bin"

WORKDIR /app

# Copy project files
COPY . .

# Install dependencies
RUN composer install --no-dev --optimize-autoloader

# Build with Syn
RUN syn build

# Set up entry point
CMD ["php", "-S", "0.0.0.0:8000", "-t", "build/"]
```

### Docker Compose

```yaml
# docker-compose.yml
version: '3.8'

services:
  app:
    build: .
    ports:
      - "8000:8000"
    volumes:
      - .:/app
      - /app/vendor
    environment:
      - APP_ENV=development
    command: syn watch --daemon
    
  web:
    image: nginx:alpine
    ports:
      - "80:80"
    volumes:
      - ./build:/var/www/html
      - ./nginx.conf:/etc/nginx/nginx.conf
    depends_on:
      - app
```

## Performance Optimization

### Caching Configuration

```php
return [
    'cache' => [
        'enabled' => true,
        'driver' => 'file', // file, redis, memcached
        'path' => sys_get_temp_dir() . '/syn-cache',
        'ttl' => 3600,
        
        'strategies' => [
            'macro_definitions' => true,
            'parsed_ast' => true,
            'transformed_output' => false, // Usually disabled in development
        ],
    ],
];
```

### Parallel Processing

```php
return [
    'processing' => [
        'parallel' => true,
        'max_processes' => 4, // Number of CPU cores
        'chunk_size' => 10,   // Files per process
        
        'memory_limit' => '256M',
        'time_limit' => 300, // 5 minutes
    ],
];
```

### File Watching

```php
return [
    'watch' => [
        'enabled' => true,
        'polling_interval' => 1000, // milliseconds
        'debounce_delay' => 500,    // milliseconds
        
        'ignore_patterns' => [
            '*/node_modules/*',
            '*/vendor/*',
            '*/.git/*',
            '*/build/*',
        ],
        
        'extensions' => ['.syn.php', '.syn', '.php'],
    ],
];
```

## Testing Configuration

### PHPUnit Integration

```xml
<!-- phpunit.xml -->
<phpunit bootstrap="vendor/autoload.php">
    <listeners>
        <listener class="Syn\Testing\PHPUnit\SynTestListener" />
    </listeners>
    
    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
        <testsuite name="Integration">
            <directory>tests/Integration</directory>
        </testsuite>
        <testsuite name="Syn">
            <directory>tests/Syn</directory>
        </testsuite>
    </testsuites>
    
    <php>
        <env name="SYN_CONFIG" value="syn.test.config.php"/>
        <env name="SYN_AUTO_BUILD" value="true"/>
    </php>
</phpunit>
```

### Test-Specific Configuration

```php
// syn.test.config.php
<?php

return [
    'source_directories' => [
        'tests/fixtures/',
    ],
    
    'output_directory' => 'tests/build/',
    
    'preprocessing' => [
        'preserve_comments' => true,
        'generate_source_maps' => true,
        'optimize_output' => false,
    ],
    
    'macro_directories' => [
        'tests/macros/',
        'macros/',
    ],
];
```

## Troubleshooting Setup

### Common Issues

1. **Permission Errors**
   ```bash
   chmod +x vendor/bin/syn
   ```

2. **Memory Limit Issues**
   ```php
   // syn.config.php
   return [
       'processing' => [
           'memory_limit' => '512M',
       ],
   ];
   ```

3. **Path Resolution Issues**
   ```php
   return [
       'source_directories' => [
           __DIR__ . '/src/',
       ],
       'output_directory' => __DIR__ . '/build/',
   ];
   ```

### Debug Configuration

```php
return [
    'debug' => [
        'enabled' => true,
        'log_level' => 'debug', // error, warning, info, debug
        'log_file' => 'syn.log',
        'dump_ast' => false,
        'dump_tokens' => false,
        'profile_performance' => true,
    ],
];
```

### Validation

Validate your configuration:

```bash
syn config:validate
syn config:dump
syn config:test
```

---

## Navigation

- **Previous:** [Plugin System](../plugins.md)
- **Next:** [Composer Integration](composer.md)
- **Index:** [Documentation Index](../index.md)

## See Also

- [CLI Reference](../cli.md)
- [Configuration Options](../configuration/)
- [Best Practices](../best-practices/)
- [Troubleshooting](../troubleshooting/) 