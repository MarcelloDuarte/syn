# CLI Reference

The `sync` command is Syn's command-line interface for processing PHP files with custom syntax using macro definitions.

## Installation

### Local Installation

```bash
composer require marcelloduarte/syn
./vendor/bin/sync --help
```

### Global Installation

```bash
composer global require marcelloduarte/syn
sync --help
```

### From Source

```bash
git clone https://github.com/marcelloduarte/syn.git
cd syn
composer install
./bin/sync --help
```

## Basic Usage

### Process Single File

```bash
./bin/sync input.syn.php --out=output.php
```

### Process Directory

```bash
./bin/sync src/ --out=compiled/
```

### With Macro Directory

```bash
./bin/sync src/ --macro-dir=macros/ --out=compiled/
```

## Command Options

### Required Arguments

#### `input`
Path to input file or directory to process.

```bash
./bin/sync src/Example.syn.php --out=compiled/Example.php
./bin/sync src/ --out=compiled/
```

### Options

#### `--out, -o`
Output file or directory (required).

```bash
sync input.syn.php --out=output.php
sync src/ --out=compiled/
```

#### `--macro-dir, -m`
Directory containing macro definitions (`.syn` files).

```bash
sync src/ --macro-dir=macros/ --out=compiled/
```

Can be used multiple times:
```bash
sync src/ --macro-dir=macros/ --macro-dir=vendor/macros/ --out=compiled/
```

#### `--macro-file, -f`
Specific macro file to use.

```bash
sync src/ --macro-file=my-macros.syn --out=compiled/
```

Can be used multiple times:
```bash
sync src/ --macro-file=arrows.syn --macro-file=debug.syn --out=compiled/
```

#### `--preserve-line-numbers, -p`
Preserve line numbers in output (for debugging).

```bash
sync src/ --macro-dir=macros/ --out=compiled/ --preserve-line-numbers
```

#### `--verbose, -v`
Enable verbose output.

```bash
sync src/ --macro-dir=macros/ --out=compiled/ --verbose
```

Verbose levels:
- `-v`: Basic verbose output
- `-vv`: More detailed output
- `-vvv`: Debug level output

#### `--config, -c`
Configuration file path.

```bash
sync src/ --config=syn.config.php --out=compiled/
```

## Configuration

### Configuration File

Create `syn.config.php`:

```php
<?php

return [
    'macro_directories' => ['macros/', 'vendor/syn-macros/'],
    'macro_files' => ['custom.syn'],
    'preserve_line_numbers' => false,
    'verbose' => false,
];
```

### Environment Variables

Set environment variables for common options:

```bash
export SYN_MACRO_DIR="macros/"
export SYN_VERBOSE=1
sync src/ --out=compiled/
```

## Examples

### Basic Processing

```bash
# Single file
./bin/sync example.syn.php --out=example.php

# Directory
./bin/sync src/ --out=compiled/
```

### With Macros

```bash
# Using macro directory
./bin/sync src/ --macro-dir=macros/ --out=compiled/

# Using specific macro files
./bin/sync src/ --macro-file=arrows.syn --macro-file=debug.syn --out=compiled/
```

### Development Workflow

```bash
# Development with verbose output
./bin/sync src/ --macro-dir=macros/ --out=compiled/ --verbose --preserve-line-numbers

# Production build
./bin/sync src/ --macro-dir=macros/ --out=dist/
```

### Configuration File Usage

```bash
# Using configuration file
./bin/sync src/ --config=syn.config.php --out=compiled/

# Override config options
./bin/sync src/ --config=syn.config.php --out=compiled/ --verbose
```

## Error Handling

### Common Exit Codes

- `0`: Success
- `1`: General error
- `2`: Configuration error
- `3`: Parse error
- `4`: File system error

### Error Messages

#### Missing Output Path
```
ERROR: Output path is required. Use --out option.
```

**Solution**: Always specify `--out` option.

#### Non-existent Input
```
ERROR: Input file or directory does not exist: src/
```

**Solution**: Verify input path exists and is readable.

#### Invalid Macro File
```
ERROR: Cannot parse macro file: macros/invalid.syn
```

**Solution**: Check macro file syntax.

#### Permission Errors
```
ERROR: Cannot write to output directory: /protected/
```

**Solution**: Check write permissions on output directory.

## Debugging

### Verbose Output

Use verbose flags to debug processing:

```bash
# Show which files are processed
./bin/sync src/ --macro-dir=macros/ --out=compiled/ -v

# Show macro applications
./bin/sync src/ --macro-dir=macros/ --out=compiled/ -vv

# Show detailed parsing information
./bin/sync src/ --macro-dir=macros/ --out=compiled/ -vvv
```

### Preserve Line Numbers

Keep original line numbers for easier debugging:

```bash
./bin/sync src/ --macro-dir=macros/ --out=compiled/ --preserve-line-numbers
```

### Dry Run Mode

Test configuration without writing files:

```bash
./bin/sync src/ --macro-dir=macros/ --out=/dev/null --verbose
```

## Integration

### Composer Scripts

Add to `composer.json`:

```json
{
    "scripts": {
        "build": "./bin/sync src/ --macro-dir=macros/ --out=compiled/",
        "build-dev": "./bin/sync src/ --macro-dir=macros/ --out=compiled/ --verbose --preserve-line-numbers",
        "watch": "./bin/sync src/ --macro-dir=macros/ --out=compiled/ --watch"
    }
}
```

Run with:
```bash
composer build
composer build-dev
```

### Make Integration

Create `Makefile`:

```makefile
.PHONY: build clean

build:
	./bin/sync src/ --macro-dir=macros/ --out=compiled/

clean:
	rm -rf compiled/

watch:
	./bin/sync src/ --macro-dir=macros/ --out=compiled/ --watch

.DEFAULT_GOAL := build
```

### CI/CD Integration

#### GitHub Actions

```yaml
name: Build
on: [push, pull_request]

jobs:
  build:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.1'
      - run: composer install
      - run: composer build
      - run: composer test
```

#### Docker

```dockerfile
FROM php:8.1-cli

COPY . /app
WORKDIR /app

RUN composer install
RUN composer build

CMD ["php", "compiled/app.php"]
```

## Performance

### Large Projects

For large projects, consider:

```bash
# Process in parallel (if supported)
sync src/ --macro-dir=macros/ --out=compiled/ --parallel

# Use specific macro files instead of directories
sync src/ --macro-file=essential.syn --out=compiled/

# Profile performance
time sync src/ --macro-dir=macros/ --out=compiled/ --verbose
```

### Memory Usage

Monitor memory usage for large files:

```bash
# Increase memory limit
php -d memory_limit=1G ./bin/sync src/ --macro-dir=macros/ --out=compiled/
```

## Advanced Usage

### Custom PHP Binary

```bash
# Use specific PHP version
/usr/bin/php8.1 ./bin/sync src/ --macro-dir=macros/ --out=compiled/
```

### Multiple Input Directories

```bash
# Process multiple source directories
sync src/ lib/ --macro-dir=macros/ --out=compiled/
```

### Output Structure

Control output directory structure:

```bash
# Preserve directory structure
sync src/ --macro-dir=macros/ --out=compiled/ --preserve-structure

# Flatten output
sync src/ --macro-dir=macros/ --out=compiled/ --flatten
```

## Troubleshooting

### Debug Steps

1. **Verify installation**:
   ```bash
   ./bin/sync --version
   ```

2. **Test with verbose output**:
   ```bash
   ./bin/sync input.syn.php --out=output.php --verbose
   ```

3. **Check macro syntax**:
   ```bash
   ./bin/sync input.syn.php --macro-file=test.syn --out=output.php --verbose
   ```

4. **Validate input files**:
   ```bash
   php -l input.syn.php
   ```

### Common Issues

#### Macro Not Applied
- Verify macro file path
- Check macro syntax
- Use `--verbose` to see loaded macros

#### Permission Denied
- Check output directory permissions
- Verify input file readability
- Run with appropriate user permissions

#### Out of Memory
- Increase PHP memory limit
- Process files in smaller batches
- Optimize macro complexity

## See Also

- [Getting Started](getting-started.md)
- [Macro DSL Reference](macro-dsl.md)
- [Configuration](configuration/)
- [Troubleshooting](troubleshooting/) 