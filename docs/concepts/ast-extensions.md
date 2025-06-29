# AST Extensions

Syn extends PHP's Abstract Syntax Tree (AST) capabilities to support custom syntax transformations and macro expansions. This document explains how AST extensions work and how to create custom AST nodes.

## Understanding AST in Syn

### What is an AST?

An Abstract Syntax Tree represents the structure of source code in a tree format where:
- **Nodes** represent language constructs (statements, expressions, etc.)
- **Edges** represent relationships between constructs
- **Leaves** contain actual values (literals, identifiers, etc.)

### PHP AST vs Syn AST

```php
// PHP Code
$x = 1 + 2;

// PHP AST (simplified)
Assignment(
    Variable('x'),
    BinaryOp(
        '+',
        Literal(1),
        Literal(2)
    )
)

// Syn Enhanced AST
Assignment(
    Variable('x'),
    MacroExpansion(
        'add',
        [Literal(1), Literal(2)]
    )
)
```

## AST Node Types

### Core Node Types

Syn supports all standard PHP AST nodes plus custom extensions:

#### Expression Nodes
- `MacroExpansion` - Represents a macro call
- `CaptureGroup` - Represents captured content in patterns
- `LayerExpression` - Represents layered content matching
- `TokenSequence` - Represents a sequence of tokens

#### Statement Nodes
- `MacroDefinition` - Represents a macro definition
- `ConditionalMacro` - Represents conditional macro expansion
- `LoopMacro` - Represents iterative macro expansion

#### Utility Nodes
- `TokenNode` - Wraps individual tokens
- `PatternNode` - Represents pattern matching constructs
- `TransformationNode` - Represents transformation rules

## Custom AST Nodes

### Creating Custom Nodes

```php
<?php

namespace Syn\AST\Node;

use PhpParser\Node;

class MacroExpansionNode extends Node
{
    public string $macroName;
    public array $arguments;
    public ?array $captureGroups;
    
    public function __construct(
        string $macroName,
        array $arguments = [],
        ?array $captureGroups = null,
        array $attributes = []
    ) {
        parent::__construct($attributes);
        
        $this->macroName = $macroName;
        $this->arguments = $arguments;
        $this->captureGroups = $captureGroups;
    }
    
    public function getType(): string
    {
        return 'MacroExpansion';
    }
    
    public function getSubNodeNames(): array
    {
        return ['macroName', 'arguments', 'captureGroups'];
    }
}
```

### Node Visitors

Use visitors to traverse and transform AST nodes:

```php
<?php

namespace Syn\AST\Visitor;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use Syn\AST\Node\MacroExpansionNode;

class MacroExpansionVisitor extends NodeVisitorAbstract
{
    private MacroRegistry $macroRegistry;
    
    public function __construct(MacroRegistry $macroRegistry)
    {
        $this->macroRegistry = $macroRegistry;
    }
    
    public function enterNode(Node $node)
    {
        if ($node instanceof MacroExpansionNode) {
            return $this->expandMacro($node);
        }
        
        return null;
    }
    
    private function expandMacro(MacroExpansionNode $node): ?Node
    {
        $macro = $this->macroRegistry->getMacro($node->macroName);
        
        if (!$macro) {
            throw new MacroNotFoundException($node->macroName);
        }
        
        return $macro->expand($node->arguments, $node->captureGroups);
    }
}
```

## AST Transformation Pipeline

### Processing Stages

```
1. Lexical Analysis    → Tokens
2. Syntax Analysis     → Basic AST
3. Macro Detection     → Enhanced AST with macro nodes
4. Macro Expansion     → Transformed AST
5. Code Generation     → PHP Code
```

### Example Transformation

```php
// Input: Syn code with macro
unless ($condition) {
    doSomething();
}

// Stage 1: Tokens
[T_STRING('unless'), '(', T_VARIABLE('$condition'), ')', '{', ...]

// Stage 2: Basic AST
UnlessStatement(
    condition: Variable('condition'),
    body: Block([
        FunctionCall('doSomething', [])
    ])
)

// Stage 3: Macro Detection
MacroExpansionNode(
    macroName: 'unless',
    arguments: [Variable('condition')],
    captureGroups: [
        'condition' => Variable('condition'),
        'body' => Block([FunctionCall('doSomething', [])])
    ]
)

// Stage 4: Macro Expansion
IfStatement(
    condition: UnaryOp('!', Variable('condition')),
    body: Block([
        FunctionCall('doSomething', [])
    ])
)

// Stage 5: Code Generation
if (!$condition) {
    doSomething();
}
```

## Advanced AST Features

### Conditional Nodes

```php
class ConditionalMacroNode extends Node
{
    public Node $condition;
    public Node $thenMacro;
    public ?Node $elseMacro;
    
    public function expand(array $context): Node
    {
        $conditionValue = $this->evaluateCondition($this->condition, $context);
        
        return $conditionValue 
            ? $this->thenMacro->expand($context)
            : $this->elseMacro?->expand($context) ?? new NullNode();
    }
    
    private function evaluateCondition(Node $condition, array $context): bool
    {
        // Evaluate condition at compile time
        return (new ConditionEvaluator())->evaluate($condition, $context);
    }
}
```

### Loop Nodes

```php
class LoopMacroNode extends Node
{
    public Node $iterable;
    public string $itemVariable;
    public Node $bodyMacro;
    
    public function expand(array $context): Node
    {
        $items = $this->evaluateIterable($this->iterable, $context);
        $expandedStatements = [];
        
        foreach ($items as $item) {
            $itemContext = array_merge($context, [$this->itemVariable => $item]);
            $expandedStatements[] = $this->bodyMacro->expand($itemContext);
        }
        
        return new Block($expandedStatements);
    }
}
```

### Pattern Matching Nodes

```php
class PatternMatchNode extends Node
{
    public array $patterns;
    public Node $value;
    
    public function expand(array $context): Node
    {
        $valueToMatch = $this->evaluateValue($this->value, $context);
        
        foreach ($this->patterns as $pattern) {
            if ($pattern->matches($valueToMatch)) {
                return $pattern->getResult()->expand($context);
            }
        }
        
        throw new NoPatternMatchedException($valueToMatch);
    }
}
```

## AST Optimization

### Dead Code Elimination

```php
class DeadCodeEliminationVisitor extends NodeVisitorAbstract
{
    public function leaveNode(Node $node)
    {
        // Remove unreachable code after return statements
        if ($node instanceof Block) {
            return $this->removeDeadCode($node);
        }
        
        return null;
    }
    
    private function removeDeadCode(Block $block): Block
    {
        $statements = [];
        
        foreach ($block->stmts as $stmt) {
            $statements[] = $stmt;
            
            if ($stmt instanceof Return_) {
                break; // Remove everything after return
            }
        }
        
        return new Block($statements);
    }
}
```

### Constant Folding

```php
class ConstantFoldingVisitor extends NodeVisitorAbstract
{
    public function leaveNode(Node $node)
    {
        if ($node instanceof BinaryOp\Plus) {
            return $this->foldAddition($node);
        }
        
        return null;
    }
    
    private function foldAddition(BinaryOp\Plus $node): ?Node
    {
        if ($node->left instanceof LNumber && $node->right instanceof LNumber) {
            return new LNumber($node->left->value + $node->right->value);
        }
        
        return null;
    }
}
```

## Error Handling in AST

### AST Validation

```php
class ASTValidator extends NodeVisitorAbstract
{
    private array $errors = [];
    
    public function enterNode(Node $node)
    {
        if ($node instanceof MacroExpansionNode) {
            $this->validateMacroExpansion($node);
        }
        
        return null;
    }
    
    private function validateMacroExpansion(MacroExpansionNode $node): void
    {
        if (empty($node->macroName)) {
            $this->errors[] = new ASTError('Macro name cannot be empty', $node);
        }
        
        if (!$this->isValidMacroName($node->macroName)) {
            $this->errors[] = new ASTError('Invalid macro name format', $node);
        }
    }
    
    public function getErrors(): array
    {
        return $this->errors;
    }
}
```

### Error Recovery

```php
class ErrorRecoveryVisitor extends NodeVisitorAbstract
{
    public function enterNode(Node $node)
    {
        try {
            return $this->processNode($node);
        } catch (MacroException $e) {
            // Replace problematic node with error placeholder
            return new ErrorPlaceholderNode($e->getMessage(), $node);
        }
    }
}
```

## Performance Considerations

### Node Caching

```php
class CachedASTBuilder
{
    private array $cache = [];
    
    public function buildAST(string $source): Node
    {
        $hash = md5($source);
        
        if (isset($this->cache[$hash])) {
            return clone $this->cache[$hash];
        }
        
        $ast = $this->parseSource($source);
        $this->cache[$hash] = $ast;
        
        return clone $ast;
    }
}
```

### Lazy Evaluation

```php
class LazyASTNode extends Node
{
    private ?\Closure $builder = null;
    private ?Node $builtNode = null;
    
    public function __construct(\Closure $builder)
    {
        $this->builder = $builder;
    }
    
    public function getBuiltNode(): Node
    {
        if ($this->builtNode === null) {
            $this->builtNode = ($this->builder)();
            $this->builder = null; // Free memory
        }
        
        return $this->builtNode;
    }
}
```

## Debugging AST

### AST Dumper

```php
class ASTDumper
{
    public function dump(Node $node, int $indent = 0): string
    {
        $spaces = str_repeat('  ', $indent);
        $result = $spaces . $node->getType();
        
        if ($node instanceof MacroExpansionNode) {
            $result .= " (macro: {$node->macroName})";
        }
        
        $result .= "\n";
        
        foreach ($node->getSubNodeNames() as $name) {
            $value = $node->$name;
            
            if ($value instanceof Node) {
                $result .= $spaces . "  {$name}:\n";
                $result .= $this->dump($value, $indent + 2);
            } elseif (is_array($value)) {
                $result .= $spaces . "  {$name}: [\n";
                foreach ($value as $item) {
                    if ($item instanceof Node) {
                        $result .= $this->dump($item, $indent + 2);
                    }
                }
                $result .= $spaces . "  ]\n";
            } else {
                $result .= $spaces . "  {$name}: " . var_export($value, true) . "\n";
            }
        }
        
        return $result;
    }
}
```

### Visual AST Explorer

```php
class ASTVisualizer
{
    public function generateDot(Node $node): string
    {
        $dot = "digraph AST {\n";
        $nodeId = 0;
        
        $dot .= $this->generateDotNode($node, $nodeId);
        $dot .= "}\n";
        
        return $dot;
    }
    
    private function generateDotNode(Node $node, int &$nodeId): string
    {
        $currentId = $nodeId++;
        $label = $node->getType();
        
        $dot = "  node{$currentId} [label=\"{$label}\"];\n";
        
        foreach ($node->getSubNodeNames() as $name) {
            $value = $node->$name;
            
            if ($value instanceof Node) {
                $childId = $nodeId;
                $dot .= $this->generateDotNode($value, $nodeId);
                $dot .= "  node{$currentId} -> node{$childId} [label=\"{$name}\"];\n";
            }
        }
        
        return $dot;
    }
}
```

## Testing AST Extensions

### Unit Testing Nodes

```php
class MacroExpansionNodeTest extends TestCase
{
    public function testMacroExpansionCreation(): void
    {
        $node = new MacroExpansionNode(
            'testMacro',
            [new Variable('x')],
            ['arg1' => new Literal(42)]
        );
        
        $this->assertEquals('testMacro', $node->macroName);
        $this->assertCount(1, $node->arguments);
        $this->assertArrayHasKey('arg1', $node->captureGroups);
    }
    
    public function testMacroExpansion(): void
    {
        $registry = new MacroRegistry();
        $registry->register(new TestMacro());
        
        $visitor = new MacroExpansionVisitor($registry);
        $node = new MacroExpansionNode('testMacro', [new Variable('x')]);
        
        $result = $visitor->enterNode($node);
        
        $this->assertInstanceOf(IfStatement::class, $result);
    }
}
```

### Integration Testing

```php
class ASTIntegrationTest extends TestCase
{
    public function testFullTransformation(): void
    {
        $source = '<?php unless ($x) { echo "hello"; }';
        
        $parser = new SynParser();
        $ast = $parser->parse($source);
        
        $transformer = new ASTTransformer();
        $transformedAST = $transformer->transform($ast);
        
        $generator = new CodeGenerator();
        $result = $generator->generate($transformedAST);
        
        $expected = '<?php if (!$x) { echo "hello"; }';
        $this->assertEquals($expected, $result);
    }
}
```

---

## Navigation

- **Previous:** [Macro System](macro-system.md)
- **Next:** [Transformation Pipeline](transformation-pipeline.md)
- **Index:** [Documentation Index](../index.md)

## See Also

- [Parser Combinators](parser-combinators.md)
- [Macro System](macro-system.md)
- [API Reference](../api/ast.md)
- [Best Practices](../best-practices/macro-design.md) 