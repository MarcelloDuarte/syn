# Syn

[![Build Status](https://github.com/marcelloduarte/syn/workflows/CI/badge.svg)](https://github.com/marcelloduarte/syn/actions)
[![Latest Stable Version](https://poser.pugx.org/marcelloduarte/syn/v/stable)](https://packagist.org/packages/marcelloduarte/syn)
[![License](https://poser.pugx.org/marcelloduarte/syn/license)](https://packagist.org/packages/marcelloduarte/syn)

**Syn** is a high-level parser combinator based PHP preprocessor that allows developers to augment PHP with custom syntax through macros. Language features can be distributed as Composer packages, as long as the macro-based implementations can be expressed in pure PHP code and are fast enough.

## Features

- **Parser Combinator Based**: Built on robust parser combinators for reliable syntax parsing
- **Macro System**: Define custom syntax rules that transform into valid PHP
- **Composer Integration**: Distribute language features as packages
- **Plugin System**: Extend Syn with custom rule evaluators
- **Line Number Preservation**: Maintain debugging information through transformations
- **Clear Error Messages**: Helpful diagnostics for invalid syntax
- **CLI Tool**: Command-line interface for preprocessing files

## Installation

```bash
composer require marcelloduarte/syn
```

## Quick Start

### 1. Create a Macro File

Create a `.syn` file with your custom syntax rules:

```php
// macros.syn
$(macro) {
    unless ($(layer() as condition)) { $(layer() as body) }
} >> {
    if (!($(condition))) {
        $(body)
    }
}

$(macro) {
    __swap($(T_VARIABLE as a), $(T_VARIABLE as b))
} >> {
    (list($(a), $(b)) = [$(b), $(a)])
}
```

### 2. Use Custom Syntax

Write PHP files with your custom syntax:

```php
// example.syn.php
<?php

class Example {
    public function test() {
        unless ($x === 1) {
            echo "x is not 1";
        }
        
        __swap($foo, $bar);
    }
}
```

### 3. Compile with Sync

```bash
sync example.syn.php --macro-dir=./macros --out=./output
```

This will generate valid PHP code in the output directory.

## CLI Usage

The `sync` command-line tool provides various options for preprocessing files:

```bash
# Basic usage
sync input.syn.php --out=output.php

# Process directory with macro definitions
sync src/ --macro-dir=macros/ --out=compiled/

# Use specific macro file
sync src/ --macro-file=my-macros.syn --out=compiled/

# Verbose output for debugging
sync src/ --macro-dir=macros/ --out=compiled/ --verbose

# Show help
sync --help
```

## Macro DSL

Syn provides a powerful DSL for defining macros:

### Basic Syntax

```php
$(macro) {
    // pattern to match
} >> {
    // transformation
}
```

### Pattern Matching

- **Literals**: `unless`, `__swap`, `$`
- **Token Types**: `$(T_VARIABLE as name)`, `$(T_STRING as class)`
- **Layers**: `$(layer() as expression)` for balanced parentheses/braces
- **Lists**: `$(ls(label() as item, token(',')) as items)`
- **Sequences**: `$(chain(token1, token2, token3))`

### Examples

#### Simple Replacement

```php
$(macro) {
    $ // literal '$' token
} >> {
    $this
}
```

#### Conditional Logic

```php
$(macro) {
    unless ($(layer() as condition)) { $(layer() as body) }
} >> {
    if (!($(condition))) {
        $(body)
    }
}
```

#### Variable Swapping

```php
$(macro) {
    __swap($(T_VARIABLE as a), $(T_VARIABLE as b))
} >> {
    (list($(a), $(b)) = [$(b), $(a)])
}
```

## Plugin System

Extend Syn with custom rule evaluators:

```php
// CustomPlugin.php
class CustomPlugin implements Syn\Plugin\PluginInterface
{
    public function getName(): string
    {
        return 'custom';
    }
    
    public function getMacros(): array
    {
        return [
            // your custom macros
        ];
    }
}
```

Register plugins in your composer.json:

```json
{
    "extra": {
        "syn-plugins": [
            "MyNamespace\\CustomPlugin"
        ]
    }
}
```

## Architecture

Syn is built on top of [Nikic's PHP-Parser](https://github.com/nikic/PHP-Parser) and extends it with:

- **Parser Combinators**: For reliable and composable syntax parsing
- **AST Extensions**: Support for custom token types defined in macros
- **Macro Engine**: Pattern matching and transformation system
- **Plugin Architecture**: Extensible macro system

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests
5. Submit a pull request

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Acknowledgments

- Inspired by [Yay](https://github.com/marcioAlmada/yay) by Márcio Almada
- Built on [PHP-Parser](https://github.com/nikic/PHP-Parser) by Nikita Popov
- Parser combinator concepts from functional programming

## Documentation

For detailed documentation, see the [docs/](docs/) directory:

- [Getting Started](docs/getting-started.md)
- [Macro DSL Reference](docs/macro-dsl.md)
- [Plugin Development](docs/plugins.md)
- [CLI Reference](docs/cli.md)
- [Examples](docs/examples.md) 