<?php

declare(strict_types=1);

namespace Syn\Tests\Unit\Parser;

use PHPUnit\Framework\TestCase;
use Syn\Parser\Parser;
use Syn\Parser\ParserException;
use Syn\Parser\MacroDefinition;

class ParserTest extends TestCase
{
    private Parser $parser;

    protected function setUp(): void
    {
        $this->parser = new Parser();
    }

    public function testParseSimplePHPCode(): void
    {
        $code = '<?php echo "Hello, World!";';
        
        $ast = $this->parser->parse($code);
        
        $this->assertIsArray($ast);
        $this->assertNotEmpty($ast);
        $this->assertInstanceOf(\PhpParser\Node\Stmt\Echo_::class, $ast[0]);
    }

    public function testParseComplexPHPCode(): void
    {
        $code = '<?php
        class TestClass {
            public function testMethod($param) {
                if ($param > 0) {
                    return $param * 2;
                }
                return 0;
            }
        }';
        
        $ast = $this->parser->parse($code);
        
        $this->assertIsArray($ast);
        $this->assertNotEmpty($ast);
        $this->assertInstanceOf(\PhpParser\Node\Stmt\Class_::class, $ast[0]);
    }

    public function testParseEmptyCode(): void
    {
        $code = '<?php';
        
        $ast = $this->parser->parse($code);
        
        $this->assertIsArray($ast);
        $this->assertEmpty($ast);
    }

    public function testParseInvalidPHPCode(): void
    {
        $code = '<?php echo "unclosed string;';
        
        $this->expectException(ParserException::class);
        $this->expectExceptionMessage('PHP parsing error:');
        
        $this->parser->parse($code);
    }

    public function testTokenizeSimpleCode(): void
    {
        $code = '<?php $variable = 42;';
        
        $tokens = $this->parser->tokenize($code);
        
        $this->assertIsArray($tokens);
        $this->assertNotEmpty($tokens);
        
        // Check for expected token types
        $tokenTypes = array_map(function($token) {
            return is_array($token) ? $token[0] : $token;
        }, $tokens);
        
        $this->assertContains(T_OPEN_TAG, $tokenTypes);
        $this->assertContains(T_VARIABLE, $tokenTypes);
        $this->assertContains('=', $tokenTypes);
        $this->assertContains(T_LNUMBER, $tokenTypes);
    }

    public function testTokenizeComplexCode(): void
    {
        $code = '<?php
        function test($param) {
            return $param->method();
        }';
        
        $tokens = $this->parser->tokenize($code);
        
        $this->assertIsArray($tokens);
        $this->assertNotEmpty($tokens);
        
        $tokenTypes = array_map(function($token) {
            return is_array($token) ? $token[0] : $token;
        }, $tokens);
        
        $this->assertContains(T_FUNCTION, $tokenTypes);
        $this->assertContains(T_VARIABLE, $tokenTypes);
        $this->assertContains(T_OBJECT_OPERATOR, $tokenTypes);
        $this->assertContains(T_RETURN, $tokenTypes);
    }

    public function testTokenizeEmptyCode(): void
    {
        $code = '';
        
        $tokens = $this->parser->tokenize($code);
        
        $this->assertIsArray($tokens);
        $this->assertEmpty($tokens);
    }

    public function testParseWithLineNumbers(): void
    {
        $code = '<?php
        echo "line 2";
        echo "line 3";
        if (true) {
            echo "line 5";
        }';
        
        $ast = $this->parser->parseWithLineNumbers($code);
        
        $this->assertIsArray($ast);
        $this->assertNotEmpty($ast);
        
        // Check that line numbers are preserved
        foreach ($ast as $node) {
            if ($node->hasAttribute('startLine')) {
                $this->assertIsInt($node->getAttribute('startLine'));
                $this->assertGreaterThan(0, $node->getAttribute('startLine'));
            }
        }
    }

    public function testGetTokensReturnsEmptyInitially(): void
    {
        $tokens = $this->parser->getTokens();
        
        $this->assertIsArray($tokens);
        $this->assertEmpty($tokens);
    }

    public function testParseVariousStatements(): void
    {
        $testCases = [
            '<?php $var = 123;' => \PhpParser\Node\Stmt\Expression::class,
            '<?php if (true) { echo "test"; }' => \PhpParser\Node\Stmt\If_::class,
            '<?php for ($i = 0; $i < 10; $i++) { }' => \PhpParser\Node\Stmt\For_::class,
            '<?php while (true) { break; }' => \PhpParser\Node\Stmt\While_::class,
            '<?php function test() { }' => \PhpParser\Node\Stmt\Function_::class,
            '<?php class Test { }' => \PhpParser\Node\Stmt\Class_::class,
        ];

        foreach ($testCases as $code => $expectedClass) {
            $ast = $this->parser->parse($code);
            $this->assertInstanceOf($expectedClass, $ast[0], "Failed for code: $code");
        }
    }

    public function testParseWithComments(): void
    {
        $code = '<?php
        // This is a comment
        echo "test"; // Another comment
        /* Block comment */
        $var = 42;';
        
        $ast = $this->parser->parse($code);
        
        $this->assertIsArray($ast);
        $this->assertCount(2, $ast); // Should have 2 statements (echo and assignment)
    }

    public function testParseWithNamespaces(): void
    {
        $code = '<?php
        namespace Test\Example;
        
        use Another\Class as AliasedClass;
        
        class MyClass extends AliasedClass {
            public function method() {
                return "test";
            }
        }';
        
        $ast = $this->parser->parse($code);
        
        $this->assertIsArray($ast);
        $this->assertInstanceOf(\PhpParser\Node\Stmt\Namespace_::class, $ast[0]);
    }

    public function testParseWithTraits(): void
    {
        $code = '<?php
        trait TestTrait {
            public function traitMethod() {
                return "trait";
            }
        }
        
        class TestClass {
            use TestTrait;
        }';
        
        $ast = $this->parser->parse($code);
        
        $this->assertIsArray($ast);
        $this->assertInstanceOf(\PhpParser\Node\Stmt\Trait_::class, $ast[0]);
        $this->assertInstanceOf(\PhpParser\Node\Stmt\Class_::class, $ast[1]);
    }

    public function testParseWithAnonymousClasses(): void
    {
        $code = '<?php
        $obj = new class {
            public function method() {
                return "anonymous";
            }
        };';
        
        $ast = $this->parser->parse($code);
        
        $this->assertIsArray($ast);
        $this->assertNotEmpty($ast);
    }

    public function testParseWithClosures(): void
    {
        $code = '<?php
        $closure = function($param) use ($external) {
            return $param + $external;
        };';
        
        $ast = $this->parser->parse($code);
        
        $this->assertIsArray($ast);
        $this->assertNotEmpty($ast);
    }

    public function testParseWithArrowFunctions(): void
    {
        $code = '<?php
        $arrow = fn($x) => $x * 2;';
        
        $ast = $this->parser->parse($code);
        
        $this->assertIsArray($ast);
        $this->assertNotEmpty($ast);
    }
} 
