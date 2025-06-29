# Transformation Pipeline

The Syn transformation pipeline is the core engine that processes source code through multiple stages to apply macro transformations and generate the final PHP output. Understanding this pipeline is crucial for advanced usage and debugging.

## Pipeline Overview

The transformation pipeline consists of several distinct stages:

```
Source Code → Tokenization → Parsing → Macro Loading → Transformation → Code Generation → Output
```

### Stage Details

1. **Tokenization**: Convert source code into tokens
2. **Parsing**: Build Abstract Syntax Tree (AST) from tokens  
3. **Macro Loading**: Load and register macro definitions
4. **Transformation**: Apply macro transformations to AST
5. **Code Generation**: Convert transformed AST back to PHP code
6. **Output**: Write final code to destination

## Stage 1: Tokenization

### Token Types

Syn recognizes all standard PHP tokens plus custom macro tokens:

```php
// Standard PHP tokens
T_VARIABLE, T_STRING, T_LNUMBER, T_DNUMBER, T_CONSTANT_ENCAPSED_STRING

// Macro-specific tokens  
T_MACRO, T_LAYER, T_CAPTURE, T_PATTERN_MATCH

// Custom tokens
T_UNLESS, T_PIPE_OPERATOR, T_ARROW_FUNCTION
```

### Tokenizer Implementation

```php
class SynTokenizer
{
    private array $customTokens = [
        'unless' => T_UNLESS,
        '|>' => T_PIPE_OPERATOR,
        '$->' => T_ARROW_FUNCTION,
    ];
    
    public function tokenize(string $source): array
    {
        // First pass: PHP tokenization
        $tokens = token_get_all($source);
        
        // Second pass: Custom token recognition
        return $this->recognizeCustomTokens($tokens);
    }
    
    private function recognizeCustomTokens(array $tokens): array
    {
        $result = [];
        
        foreach ($tokens as $token) {
            if ($this->isCustomToken($token)) {
                $result[] = $this->convertToCustomToken($token);
            } else {
                $result[] = $token;
            }
        }
        
        return $result;
    }
}
```

## Stage 2: Parsing

### AST Construction

The parser builds an AST that represents both PHP constructs and macro expansions:

```php
class SynParser
{
    private Parser $phpParser;
    private MacroPatternMatcher $macroMatcher;
    
    public function parse(array $tokens): Node
    {
        // Build basic PHP AST
        $ast = $this->phpParser->parse($tokens);
        
        // Enhance with macro nodes
        return $this->enhanceWithMacros($ast, $tokens);
    }
    
    private function enhanceWithMacros(Node $ast, array $tokens): Node
    {
        $visitor = new MacroDetectionVisitor($this->macroMatcher);
        $traverser = new NodeTraverser();
        $traverser->addVisitor($visitor);
        
        return $traverser->traverse([$ast])[0];
    }
}
```

### Macro Detection

During parsing, the system identifies potential macro expansions:

```php
class MacroDetectionVisitor extends NodeVisitorAbstract
{
    public function enterNode(Node $node)
    {
        if ($this->couldBeMacro($node)) {
            return $this->createMacroNode($node);
        }
        
        return null;
    }
    
    private function couldBeMacro(Node $node): bool
    {
        // Check for macro patterns
        if ($node instanceof FunctionCall) {
            return $this->macroRegistry->hasMacro($node->name);
        }
        
        if ($node instanceof StaticCall) {
            return $this->macroRegistry->hasMacro($node->class . '::' . $node->name);
        }
        
        return false;
    }
}
```

## Stage 3: Macro Loading

### Discovery Process

Macros are discovered and loaded from multiple sources:

```php
class MacroDiscovery
{
    public function discover(Configuration $config): array
    {
        $macros = [];
        
        // Load from files
        foreach ($config->getMacroFiles() as $file) {
            $macros = array_merge($macros, $this->loadFromFile($file));
        }
        
        // Load from directories
        foreach ($config->getMacroDirectories() as $dir) {
            $macros = array_merge($macros, $this->loadFromDirectory($dir));
        }
        
        // Load from plugins
        foreach ($config->getPlugins() as $plugin) {
            $macros = array_merge($macros, $plugin->getMacros());
        }
        
        return $macros;
    }
}
```

### Macro Registration

```php
class MacroRegistry
{
    private array $macros = [];
    private array $patterns = [];
    
    public function register(MacroDefinition $macro): void
    {
        $this->macros[$macro->getName()] = $macro;
        
        if ($macro->hasPattern()) {
            $this->patterns[] = $macro->getCompiledPattern();
        }
    }
    
    public function findMatches(array $tokens): array
    {
        $matches = [];
        
        foreach ($this->patterns as $pattern) {
            $matches = array_merge($matches, $pattern->match($tokens));
        }
        
        return $matches;
    }
}
```

## Stage 4: Transformation

### Transformation Engine

The core transformation engine applies macros to the AST:

```php
class TransformationEngine
{
    private MacroRegistry $registry;
    private int $maxIterations = 10;
    
    public function transform(Node $ast): Node
    {
        $iteration = 0;
        
        do {
            $changed = false;
            $visitor = new MacroExpansionVisitor($this->registry);
            $traverser = new NodeTraverser();
            $traverser->addVisitor($visitor);
            
            $newAst = $traverser->traverse([$ast])[0];
            
            if (!$this->astEquals($ast, $newAst)) {
                $ast = $newAst;
                $changed = true;
            }
            
            $iteration++;
        } while ($changed && $iteration < $this->maxIterations);
        
        if ($iteration >= $this->maxIterations) {
            throw new MaxIterationsExceededException();
        }
        
        return $ast;
    }
}
```

### Macro Expansion

Individual macros are expanded through pattern matching and replacement:

```php
class MacroExpansionVisitor extends NodeVisitorAbstract
{
    public function enterNode(Node $node)
    {
        if ($node instanceof MacroExpansionNode) {
            return $this->expandMacro($node);
        }
        
        return null;
    }
    
    private function expandMacro(MacroExpansionNode $node): Node
    {
        $macro = $this->registry->getMacro($node->macroName);
        
        if (!$macro) {
            throw new MacroNotFoundException($node->macroName);
        }
        
        // Apply pattern matching and replacement
        return $macro->expand($node->arguments, $node->captureGroups);
    }
}
```

## Stage 5: Code Generation

### AST to Code Conversion

The final AST is converted back to PHP code:

```php
class SynCodeGenerator
{
    private PrettyPrinter $printer;
    
    public function generate(Node $ast): string
    {
        // Convert AST back to PHP code
        $code = $this->printer->prettyPrint([$ast]);
        
        // Apply post-processing
        return $this->postProcess($code);
    }
    
    private function postProcess(string $code): string
    {
        // Clean up whitespace
        $code = $this->normalizeWhitespace($code);
        
        // Fix indentation
        $code = $this->fixIndentation($code);
        
        // Remove unnecessary parentheses
        $code = $this->removeUnnecessaryParentheses($code);
        
        return $code;
    }
}
```

### Code Formatting

```php
class CodeFormatter
{
    public function format(string $code): string
    {
        // Apply consistent formatting rules
        $code = $this->formatBraces($code);
        $code = $this->formatSpacing($code);
        $code = $this->formatIndentation($code);
        
        return $code;
    }
    
    private function formatIndentation(string $code): string
    {
        $lines = explode("\n", $code);
        $indentLevel = 0;
        $result = [];
        
        foreach ($lines as $line) {
            $trimmed = trim($line);
            
            if (str_ends_with($trimmed, '{')) {
                $result[] = str_repeat('    ', $indentLevel) . $trimmed;
                $indentLevel++;
            } elseif ($trimmed === '}') {
                $indentLevel--;
                $result[] = str_repeat('    ', $indentLevel) . $trimmed;
            } else {
                $result[] = str_repeat('    ', $indentLevel) . $trimmed;
            }
        }
        
        return implode("\n", $result);
    }
}
```

## Pipeline Configuration

### Configuration Options

```php
class PipelineConfiguration
{
    private bool $enableOptimizations = true;
    private bool $preserveComments = true;
    private int $maxIterations = 10;
    private array $enabledStages = ['tokenize', 'parse', 'transform', 'generate'];
    
    public function configureStage(string $stage, array $options): void
    {
        $this->stageOptions[$stage] = $options;
    }
    
    public function getStageOptions(string $stage): array
    {
        return $this->stageOptions[$stage] ?? [];
    }
}
```

### Custom Pipeline Stages

```php
interface PipelineStage
{
    public function getName(): string;
    public function process($input, PipelineConfiguration $config);
    public function getDependencies(): array;
}

class CustomOptimizationStage implements PipelineStage
{
    public function getName(): string
    {
        return 'optimization';
    }
    
    public function process($input, PipelineConfiguration $config)
    {
        if (!$config->isOptimizationEnabled()) {
            return $input;
        }
        
        return $this->optimize($input);
    }
    
    public function getDependencies(): array
    {
        return ['transform'];
    }
}
```

## Error Handling

### Pipeline Errors

```php
class PipelineErrorHandler
{
    public function handleError(\Throwable $error, string $stage, $input): void
    {
        $context = [
            'stage' => $stage,
            'error' => $error->getMessage(),
            'input_type' => gettype($input),
        ];
        
        if ($error instanceof MacroException) {
            $this->handleMacroError($error, $context);
        } elseif ($error instanceof ParseException) {
            $this->handleParseError($error, $context);
        } else {
            $this->handleGenericError($error, $context);
        }
    }
    
    private function handleMacroError(MacroException $error, array $context): void
    {
        $message = "Macro error in {$context['stage']}: {$error->getMessage()}";
        
        if ($error->hasSourceLocation()) {
            $message .= " at line {$error->getLine()}, column {$error->getColumn()}";
        }
        
        throw new PipelineException($message, $error->getCode(), $error);
    }
}
```

### Recovery Strategies

```php
class ErrorRecoveryStrategy
{
    public function recover(\Throwable $error, string $stage, $input)
    {
        switch ($stage) {
            case 'tokenize':
                return $this->recoverFromTokenizationError($error, $input);
                
            case 'parse':
                return $this->recoverFromParseError($error, $input);
                
            case 'transform':
                return $this->recoverFromTransformError($error, $input);
                
            default:
                throw $error; // Cannot recover
        }
    }
    
    private function recoverFromTransformError(\Throwable $error, Node $ast): Node
    {
        // Replace problematic nodes with error placeholders
        $visitor = new ErrorRecoveryVisitor($error);
        $traverser = new NodeTraverser();
        $traverser->addVisitor($visitor);
        
        return $traverser->traverse([$ast])[0];
    }
}
```

## Performance Optimization

### Caching

```php
class PipelineCache
{
    private string $cacheDir;
    
    public function getCachedResult(string $source, string $stage): ?string
    {
        $key = $this->generateCacheKey($source, $stage);
        $file = $this->cacheDir . '/' . $key . '.cache';
        
        if (file_exists($file)) {
            return file_get_contents($file);
        }
        
        return null;
    }
    
    public function cacheResult(string $source, string $stage, string $result): void
    {
        $key = $this->generateCacheKey($source, $stage);
        $file = $this->cacheDir . '/' . $key . '.cache';
        
        file_put_contents($file, $result);
    }
    
    private function generateCacheKey(string $source, string $stage): string
    {
        return md5($source . $stage . $this->getConfigHash());
    }
}
```

### Parallel Processing

```php
class ParallelPipeline
{
    private int $workerCount = 4;
    
    public function processFiles(array $files): array
    {
        $chunks = array_chunk($files, ceil(count($files) / $this->workerCount));
        $processes = [];
        
        foreach ($chunks as $chunk) {
            $processes[] = $this->startWorker($chunk);
        }
        
        return $this->collectResults($processes);
    }
    
    private function startWorker(array $files): Process
    {
        $process = new Process(['php', 'worker.php', json_encode($files)]);
        $process->start();
        
        return $process;
    }
}
```

## Debugging the Pipeline

### Pipeline Tracer

```php
class PipelineTracer
{
    private array $trace = [];
    
    public function traceStage(string $stage, $input, $output): void
    {
        $this->trace[] = [
            'stage' => $stage,
            'input_hash' => md5(serialize($input)),
            'output_hash' => md5(serialize($output)),
            'timestamp' => microtime(true),
            'memory_usage' => memory_get_usage(true),
        ];
    }
    
    public function getTrace(): array
    {
        return $this->trace;
    }
    
    public function dumpTrace(): string
    {
        $output = "Pipeline Trace:\n";
        
        foreach ($this->trace as $entry) {
            $output .= sprintf(
                "Stage: %s | Time: %.4fs | Memory: %s\n",
                $entry['stage'],
                $entry['timestamp'],
                $this->formatBytes($entry['memory_usage'])
            );
        }
        
        return $output;
    }
}
```

### Visual Pipeline Inspector

```php
class PipelineInspector
{
    public function generateDiagram(array $trace): string
    {
        $dot = "digraph Pipeline {\n";
        $dot .= "  rankdir=LR;\n";
        
        $prevStage = null;
        foreach ($trace as $entry) {
            $stage = $entry['stage'];
            $dot .= "  {$stage} [label=\"{$stage}\\n{$this->formatMemory($entry)}\"];\n";
            
            if ($prevStage) {
                $dot .= "  {$prevStage} -> {$stage};\n";
            }
            
            $prevStage = $stage;
        }
        
        $dot .= "}\n";
        
        return $dot;
    }
}
```

## Testing the Pipeline

### Unit Testing Stages

```php
class PipelineStageTest extends TestCase
{
    public function testTokenizationStage(): void
    {
        $tokenizer = new SynTokenizer();
        $source = '<?php unless ($x) { echo "hello"; }';
        
        $tokens = $tokenizer->tokenize($source);
        
        $this->assertContains([T_UNLESS, 'unless'], $tokens);
    }
    
    public function testTransformationStage(): void
    {
        $engine = new TransformationEngine($this->getMockRegistry());
        $ast = $this->createMockAST();
        
        $result = $engine->transform($ast);
        
        $this->assertInstanceOf(Node::class, $result);
        $this->assertNotSame($ast, $result);
    }
}
```

### Integration Testing

```php
class PipelineIntegrationTest extends TestCase
{
    public function testFullPipeline(): void
    {
        $pipeline = new TransformationPipeline();
        $source = file_get_contents('fixtures/complex-macro.syn.php');
        
        $result = $pipeline->process($source);
        
        $this->assertStringContains('if (!', $result);
        $this->assertStringNotContains('unless', $result);
    }
}
```

---

## Navigation

- **Previous:** [AST Extensions](ast-extensions.md)
- **Next:** [Simple Macros](../examples/simple-macros.md)
- **Index:** [Documentation Index](../index.md)

## See Also

- [Parser Combinators](parser-combinators.md)
- [Macro System](macro-system.md)
- [AST Extensions](ast-extensions.md)
- [Performance Optimization](../best-practices/performance.md) 