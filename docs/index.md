# Syn Documentation

Welcome to the Syn documentation! Syn is a high-level parser combinator based PHP preprocessor that allows you to extend PHP with custom syntax through macros.

## Table of Contents

### Getting Started
- [Installation](getting-started.md#installation)
- [Quick Start Guide](getting-started.md#quick-start-guide)
- [Basic Concepts](getting-started.md#basic-concepts)
- [Your First Macro](getting-started.md#your-first-macro)

### Core Concepts
- [Parser Combinators](concepts/parser-combinators.md)
- [Macro System](concepts/macro-system.md)
- [AST Extensions](concepts/ast-extensions.md)
- [Transformation Pipeline](concepts/transformation-pipeline.md)

### Macro DSL Reference
- [Basic Syntax](macro-dsl.md#basic-syntax)
- [Pattern Matching](macro-dsl.md#pattern-matching)
  - [Literals](macro-dsl.md#literals)
  - [Token Types](macro-dsl.md#token-types)
  - [Layers](macro-dsl.md#layers)
  - [Lists](macro-dsl.md#lists)
  - [Sequences](macro-dsl.md#sequences)
- [Transformation Rules](macro-dsl.md#transformation-rules)
- [Macro Hygiene](macro-dsl.md#macro-hygiene)
- [Advanced Patterns](macro-dsl.md#advanced-patterns)

### CLI Tool (sync)
- [Installation](cli.md#installation)
- [Basic Usage](cli.md#basic-usage)
- [Command Options](cli.md#command-options)
- [Configuration](cli.md#configuration)
- [Error Handling](cli.md#error-handling)
- [Debugging](cli.md#debugging)

### Plugin System
- [Plugin Architecture](plugins.md#plugin-architecture)
- [Creating Plugins](plugins.md#creating-plugins)
- [Plugin Interface](plugins.md#plugin-interface)
- [Plugin Registration](plugins.md#plugin-registration)
- [Best Practices](plugins.md#best-practices)

### Examples
- [Simple Macros](examples/simple-macros.md)
  - [String Replacement](examples/simple-macros.md#string-replacement)
  - [Conditional Logic](examples/simple-macros.md#conditional-logic)
  - [Variable Operations](examples/simple-macros.md#variable-operations)
- [Advanced Macros](examples/advanced-macros.md)
  - [Custom Control Structures](examples/advanced-macros.md#custom-control-structures)
  - [Domain-Specific Languages](examples/advanced-macros.md#domain-specific-languages)
  - [Code Generation](examples/advanced-macros.md#code-generation)
- [Real-World Examples](examples/real-world.md)
  - [Enum Implementation](examples/real-world.md#enum-implementation)
  - [Builder Pattern](examples/real-world.md#builder-pattern)
  - [Validation Macros](examples/real-world.md#validation-macros)

### API Reference
- [Core Classes](api/core.md)
  - [Parser](api/core.md#parser)
  - [Macro](api/core.md#macro)
  - [Transformer](api/core.md#transformer)
- [Parser Combinators](api/combinators.md)
  - [Basic Combinators](api/combinators.md#basic-combinators)
  - [Token Combinators](api/combinators.md#token-combinators)
  - [Sequence Combinators](api/combinators.md#sequence-combinators)
- [AST Extensions](api/ast.md)
  - [Custom Nodes](api/ast.md#custom-nodes)
  - [Node Visitors](api/ast.md#node-visitors)

### Configuration
- [Composer Integration](configuration/composer.md)
- [Project Setup](configuration/project-setup.md)
- [Macro Discovery](configuration/macro-discovery.md)
- [Plugin Configuration](configuration/plugins.md)

### Best Practices
- [Macro Design](best-practices/macro-design.md)
- [Performance Optimization](best-practices/performance.md)
- [Error Handling](best-practices/error-handling.md)
- [Testing Macros](best-practices/testing.md)
- [Debugging Techniques](best-practices/debugging.md)

### Troubleshooting
- [Common Issues](troubleshooting/common-issues.md)
- [Error Messages](troubleshooting/error-messages.md)
- [Performance Problems](troubleshooting/performance.md)
- [Debugging Tools](troubleshooting/debugging-tools.md)

### Contributing
- [Development Setup](contributing/development-setup.md)
- [Code Style](contributing/code-style.md)
- [Testing](contributing/testing.md)
- [Pull Request Process](contributing/pull-requests.md)

### Architecture
- [System Overview](architecture/overview.md)
- [Parser Combinators](architecture/parser-combinators.md)
- [Macro Engine](architecture/macro-engine.md)
- [Transformation Pipeline](architecture/transformation-pipeline.md)
- [Plugin System](architecture/plugin-system.md)

### Migration
- [From Yay](migration/from-yay.md)
- [Version Compatibility](migration/version-compatibility.md)
- [Breaking Changes](migration/breaking-changes.md)

---

## Quick Navigation

- **New to Syn?** Start with [Getting Started](getting-started.md)
- **Want to write macros?** Check out [Macro DSL Reference](macro-dsl.md)
- **Need examples?** Browse [Examples](examples/)
- **Building plugins?** See [Plugin Development](plugins.md)
- **CLI usage?** Read [CLI Reference](cli.md)

## Support

- [GitHub Issues](https://github.com/marcelloduarte/syn/issues)
- [GitHub Discussions](https://github.com/marcelloduarte/syn/discussions)
- [Stack Overflow](https://stackoverflow.com/questions/tagged/syn-php)

## License

This documentation is licensed under the MIT License - see the [LICENSE](../LICENSE) file for details. 