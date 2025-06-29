<?php

declare(strict_types=1);

namespace Syn\Tests\Unit\Parser\Combinators;

use PHPUnit\Framework\TestCase;
use Syn\Parser\Combinators\OptionalParser;
use Syn\Parser\Combinators\TokenParser;
use Syn\Parser\ParseResult;

class OptionalParserTest extends TestCase
{
    public function testOptionalWithSuccessfulParse(): void
    {
        $tokenParser = new TokenParser('->');
        $optionalParser = new OptionalParser($tokenParser);
        
        $tokens = ['->', 'method'];
        $result = $optionalParser->parse($tokens, 0);
        
        $this->assertTrue($result->isSuccess());
        $this->assertSame('->', $result->getValue());
        $this->assertSame(1, $result->getPosition());
    }

    public function testOptionalWithFailedParse(): void
    {
        $tokenParser = new TokenParser('->');
        $optionalParser = new OptionalParser($tokenParser);
        
        $tokens = ['<-', 'method'];
        $result = $optionalParser->parse($tokens, 0);
        
        $this->assertTrue($result->isSuccess());
        $this->assertNull($result->getValue());
        $this->assertSame(0, $result->getPosition()); // Position unchanged
    }

    public function testOptionalWithTokenType(): void
    {
        $tokenParser = new TokenParser(T_VARIABLE);
        $optionalParser = new OptionalParser($tokenParser);
        
        // Test with matching token
        $tokens1 = [[T_VARIABLE, '$test', 1], '='];
        $result1 = $optionalParser->parse($tokens1, 0);
        
        $this->assertTrue($result1->isSuccess());
        $this->assertSame([T_VARIABLE, '$test', 1], $result1->getValue());
        $this->assertSame(1, $result1->getPosition());
        
        // Test with non-matching token
        $tokens2 = [[T_STRING, 'test', 1], '='];
        $result2 = $optionalParser->parse($tokens2, 0);
        
        $this->assertTrue($result2->isSuccess());
        $this->assertNull($result2->getValue());
        $this->assertSame(0, $result2->getPosition());
    }

    public function testOptionalAtEndOfTokens(): void
    {
        $tokenParser = new TokenParser('->');
        $optionalParser = new OptionalParser($tokenParser);
        
        $tokens = ['test'];
        $result = $optionalParser->parse($tokens, 1);
        
        $this->assertTrue($result->isSuccess());
        $this->assertNull($result->getValue());
        $this->assertSame(1, $result->getPosition()); // Position unchanged
    }

    public function testOptionalWithEmptyTokens(): void
    {
        $tokenParser = new TokenParser('->');
        $optionalParser = new OptionalParser($tokenParser);
        
        $tokens = [];
        $result = $optionalParser->parse($tokens, 0);
        
        $this->assertTrue($result->isSuccess());
        $this->assertNull($result->getValue());
        $this->assertSame(0, $result->getPosition());
    }

    public function testOptionalFromMiddlePosition(): void
    {
        $tokenParser = new TokenParser('=');
        $optionalParser = new OptionalParser($tokenParser);
        
        // Test successful match from middle
        $tokens1 = ['$', 'var', '=', '42'];
        $result1 = $optionalParser->parse($tokens1, 2);
        
        $this->assertTrue($result1->isSuccess());
        $this->assertSame('=', $result1->getValue());
        $this->assertSame(3, $result1->getPosition());
        
        // Test failed match from middle
        $tokens2 = ['$', 'var', '->', '42'];
        $result2 = $optionalParser->parse($tokens2, 2);
        
        $this->assertTrue($result2->isSuccess());
        $this->assertNull($result2->getValue());
        $this->assertSame(2, $result2->getPosition());
    }

    public function testOptionalWithComplexParser(): void
    {
        // Create a parser that matches specific token values
        $tokenParser = new TokenParser(T_STRING, 'function');
        $optionalParser = new OptionalParser($tokenParser);
        
        // Test with matching value
        $tokens1 = [[T_STRING, 'function', 1], '('];
        $result1 = $optionalParser->parse($tokens1, 0);
        
        $this->assertTrue($result1->isSuccess());
        $this->assertSame([T_STRING, 'function', 1], $result1->getValue());
        
        // Test with non-matching value
        $tokens2 = [[T_STRING, 'class', 1], '('];
        $result2 = $optionalParser->parse($tokens2, 0);
        
        $this->assertTrue($result2->isSuccess());
        $this->assertNull($result2->getValue());
        $this->assertSame(0, $result2->getPosition());
    }

    public function testOptionalNeverFails(): void
    {
        $tokenParser = new TokenParser('nonexistent');
        $optionalParser = new OptionalParser($tokenParser);
        
        $testCases = [
            [[], 0],
            [['test'], 0],
            [['test'], 1],
            [['a', 'b', 'c'], 2],
        ];
        
        foreach ($testCases as [$tokens, $position]) {
            $result = $optionalParser->parse($tokens, $position);
            
            $this->assertTrue($result->isSuccess(), "Optional should never fail at position $position");
            $this->assertSame($position, $result->getPosition(), "Position should be preserved when optional fails");
        }
    }

    public function testOptionalWithSequentialParsing(): void
    {
        $tokenParser = new TokenParser('->');
        $optionalParser = new OptionalParser($tokenParser);
        
        $tokens = ['->', '->', 'method'];
        
        // Parse first occurrence
        $result1 = $optionalParser->parse($tokens, 0);
        $this->assertTrue($result1->isSuccess());
        $this->assertSame('->', $result1->getValue());
        $this->assertSame(1, $result1->getPosition());
        
        // Parse second occurrence
        $result2 = $optionalParser->parse($tokens, $result1->getPosition());
        $this->assertTrue($result2->isSuccess());
        $this->assertSame('->', $result2->getValue());
        $this->assertSame(2, $result2->getPosition());
        
        // Try to parse third (non-existent) occurrence
        $result3 = $optionalParser->parse($tokens, $result2->getPosition());
        $this->assertTrue($result3->isSuccess());
        $this->assertNull($result3->getValue());
        $this->assertSame(2, $result3->getPosition());
    }

    public function testOptionalWithNestedOptional(): void
    {
        $tokenParser = new TokenParser('->');
        $optionalParser = new OptionalParser($tokenParser);
        $nestedOptionalParser = new OptionalParser($optionalParser);
        
        // Test with matching token
        $tokens1 = ['->', 'test'];
        $result1 = $nestedOptionalParser->parse($tokens1, 0);
        
        $this->assertTrue($result1->isSuccess());
        $this->assertSame('->', $result1->getValue());
        
        // Test with non-matching token
        $tokens2 = ['<-', 'test'];
        $result2 = $nestedOptionalParser->parse($tokens2, 0);
        
        $this->assertTrue($result2->isSuccess());
        $this->assertNull($result2->getValue());
    }

    public function testOptionalWithCustomParser(): void
    {
        $customParser = new class extends \Syn\Parser\Combinators\ParserCombinator {
            public function parse(array $tokens, int $position): ParseResult
            {
                if ($position >= count($tokens)) {
                    return ParseResult::failure($position, "End of tokens");
                }
                
                $token = $tokens[$position];
                if (is_string($token) && strlen($token) > 3) {
                    return ParseResult::success($token, $position + 1);
                }
                
                return ParseResult::failure($position, "Token too short");
            }
        };
        
        $optionalParser = new OptionalParser($customParser);
        
        // Test with long token
        $tokens1 = ['longtoken', 'short'];
        $result1 = $optionalParser->parse($tokens1, 0);
        
        $this->assertTrue($result1->isSuccess());
        $this->assertSame('longtoken', $result1->getValue());
        
        // Test with short token
        $tokens2 = ['ab', 'longtoken'];
        $result2 = $optionalParser->parse($tokens2, 0);
        
        $this->assertTrue($result2->isSuccess());
        $this->assertNull($result2->getValue());
        $this->assertSame(0, $result2->getPosition());
    }

    public function testOptionalAlwaysSucceeds(): void
    {
        $tokenParser = new TokenParser(T_VARIABLE);
        $optionalParser = new OptionalParser($tokenParser);
        
        // Test various scenarios that would normally fail
        $testCases = [
            [[], 0],
            [[], 5],
            [['not', 'a', 'variable'], 0],
            [[[T_STRING, 'string', 1]], 0],
            [[[T_VARIABLE, '$var', 1]], 1], // Past the variable
        ];
        
        foreach ($testCases as $i => [$tokens, $position]) {
            $result = $optionalParser->parse($tokens, $position);
            
            $this->assertTrue($result->isSuccess(), "Test case $i should succeed");
            // Value can be either the matched token or null
            $this->assertTrue(
                $result->getValue() === null || is_array($result->getValue()),
                "Value should be null or array in test case $i"
            );
        }
    }
} 
