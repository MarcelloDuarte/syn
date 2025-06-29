# Getting Started

Welcome to Syn! This guide will help you get up and running with Syn, a high-level parser combinator based PHP preprocessor that allows you to extend PHP with custom syntax through macros.

## Terminology

- **Syn** - The library and project name
- **sync** - The command-line tool for compiling `.syn.php` files to `.php` files  
- **`.syn`** - Macro definition files
- **`.syn.php`** - PHP files with custom syntax that get compiled to standard PHP

The `sync` command "synchronizes" your custom syntax files with standard PHP output.

## Installation

### Via Composer

```bash
composer require marcelloduarte/syn
```

### Global Installation

```bash
composer global require marcelloduarte/syn
```

### From Source

```bash
git clone https://github.com/marcelloduarte/syn.git
cd syn
composer install
```

## Quick Start Guide

### 1. Create Your First Macro

Create a macro file `macros.syn`:

```syn
$(macro) { $-> } >> { $this-> }
```

This simple macro transforms `$->` into `$this->` for cleaner object method calls.

### 2. Create a Syn File

Create a file `example.syn.php`:

```php
<?php

class Example 
{
    private $name;
    
    public function setName($name) 
    {
        $->name = $name;
        return $this;
    }
    
    public function getName() 
    {
        return $->name;
    }
}
```

### 3. Process the File

Using the CLI:

```bash
./bin/sync example.syn.php --macro-file=macros.syn --out=example.php
```

### 4. View the Result

The generated `example.php` will contain:

```php
<?php

class Example 
{
    private $name;
    
    public function setName($name) 
    {
        $this->name = $name;
        return $this;
    }
    
    public function getName() 
    {
        return $this->name;
    }
}
```

## Basic Concepts

### Macros

Macros are transformation rules that define how custom syntax should be converted to valid PHP. They consist of:

- **Pattern**: The syntax to match (left side of `>>`)
- **Replacement**: The PHP code to generate (right side of `>>`)

### Tokens

Syn works with PHP tokens, allowing precise control over syntax transformation:

- **Literals**: Exact text matches
- **Variables**: Token placeholders like `$(T_VARIABLE)`
- **Layers**: Complex nested structures like `$(layer())`

### Processing Pipeline

1. **Tokenization**: Source code is broken into PHP tokens
2. **Macro Loading**: Macro definitions are parsed and loaded
3. **Pattern Matching**: Tokens are matched against macro patterns
4. **Transformation**: Matched patterns are replaced with their transformations
5. **Code Generation**: Final PHP code is generated

### File Types

- **`.syn`**: Macro definition files
- **`.syn.php`**: PHP files with custom syntax
- **`.php`**: Generated standard PHP files

## Your First Macro

Let's create a more complex macro step by step.

### Simple Token Replacement

```syn
$(macro) { __debug($(layer() as expr)) } >> { var_dump($(expr)) }
```

This macro transforms `__debug($variable)` into `var_dump($variable)`.

### Usage in Code

```php
// Input (.syn.php)
__debug($user);
__debug($user->getName());

// Output (.php)
var_dump($user);
var_dump($user->getName());
```

### Complex Control Structures

```syn
$(macro) { unless ($(layer() as condition)) { $(layer() as body) } } >> { if (!($(condition))) { $(body) } }
```

This creates an `unless` control structure:

```php
// Input (.syn.php)
unless ($user->isActive()) {
    throw new Exception('User is not active');
}

// Output (.php)
if (!($user->isActive())) {
    throw new Exception('User is not active');
}
```

## Project Structure

### Recommended Directory Layout

```
project/
├── src/
│   ├── Example.syn.php
│   └── Another.syn.php
├── macros/
│   ├── arrows.syn
│   ├── debug.syn
│   └── control-structures.syn
├── compiled/
│   ├── Example.php
│   └── Another.php
├── composer.json
└── syn.config.php
```

### Configuration File

Create `syn.config.php`:

```php
<?php

return [
    'macro_directories' => ['macros/'],
    'macro_files' => [],
    'preserve_line_numbers' => false,
    'verbose' => false,
];
```

### Build Script

Add to `composer.json`:

```json
{
    "scripts": {
        "build": "sync src/ --macro-dir=macros/ --out=compiled/",
        "build-watch": "sync src/ --macro-dir=macros/ --out=compiled/ --watch"
    }
}
```

## CLI Usage

### Basic Commands

```bash
# Process single file
./bin/sync input.syn.php --out=output.php

# Process directory
./bin/sync src/ --out=compiled/

# With macro directory
./bin/sync src/ --macro-dir=macros/ --out=compiled/

# With specific macro file
./bin/sync src/ --macro-file=my-macros.syn --out=compiled/

# Verbose output
./bin/sync src/ --macro-dir=macros/ --out=compiled/ --verbose

# Preserve line numbers (for debugging)
./bin/sync src/ --macro-dir=macros/ --out=compiled/ --preserve-line-numbers
```

### Configuration File Usage

```bash
# Use configuration file
./bin/sync src/ --config=syn.config.php --out=compiled/
```

## IDE Integration

### VS Code

Create `.vscode/tasks.json`:

```json
{
    "version": "2.0.0",
    "tasks": [
        {
            "label": "Build Syn",
            "type": "shell",
            "command": "./bin/sync",
            "args": ["src/", "--macro-dir=macros/", "--out=compiled/"],
            "group": {
                "kind": "build",
                "isDefault": true
            }
        }
    ]
}
```

### PhpStorm

1. Go to File → Settings → Tools → External Tools
2. Add new tool:
   - Name: Syn Build
   - Program: `$ProjectFileDir$/bin/sync`
   - Arguments: `src/ --macro-dir=macros/ --out=compiled/`
   - Working directory: `$ProjectFileDir$`

## Next Steps

- Read the [Macro DSL Reference](macro-dsl.md) for complete syntax guide
- Explore [Examples](examples/) for real-world usage patterns
- Check out [Best Practices](best-practices/) for optimization tips
- Learn about [Parser Combinators](concepts/parser-combinators.md) for advanced usage

## Common Issues

### Macro Not Applied

- Check macro file syntax
- Ensure macro directory is specified correctly
- Use `--verbose` flag for debugging information

### Parse Errors

- Verify PHP syntax in `.syn.php` files
- Check for unmatched braces or parentheses
- Use `--preserve-line-numbers` for easier debugging

### Performance Issues

- Use specific macro files instead of large directories
- Consider macro complexity and nesting
- Profile with `--verbose` flag

## Support

- [GitHub Issues](https://github.com/marcelloduarte/syn/issues)
- [Documentation](index.md)
- [Examples](examples/) 