<?php

declare(strict_types=1);

namespace Syn\Tests\Unit\Parser\Combinators;

use PHPUnit\Framework\TestCase;
use Syn\Parser\Combinators\ManyParser;
use Syn\Parser\Combinators\TokenParser;
use Syn\Parser\ParseResult;

class ManyParserTest extends TestCase
{
    public function testManyWithNoMatches(): void
    {
        $tokenParser = new TokenParser('->');
        $manyParser = new ManyParser($tokenParser);
        
        $tokens = ['<-', 'method'];
        $result = $manyParser->parse($tokens, 0);
        
        $this->assertTrue($result->isSuccess());
        $this->assertSame([], $result->getValue());
        $this->assertSame(0, $result->getPosition()); // Position unchanged
    }

    public function testManyWithOneMatch(): void
    {
        $tokenParser = new TokenParser('->');
        $manyParser = new ManyParser($tokenParser);
        
        $tokens = ['->', 'method'];
        $result = $manyParser->parse($tokens, 0);
        
        $this->assertTrue($result->isSuccess());
        $this->assertSame(['->'], $result->getValue());
        $this->assertSame(1, $result->getPosition());
    }

    public function testManyWithMultipleMatches(): void
    {
        $tokenParser = new TokenParser('->');
        $manyParser = new ManyParser($tokenParser);
        
        $tokens = ['->', '->', '->', 'method'];
        $result = $manyParser->parse($tokens, 0);
        
        $this->assertTrue($result->isSuccess());
        $this->assertSame(['->', '->', '->'], $result->getValue());
        $this->assertSame(3, $result->getPosition());
    }

    public function testManyWithTokenTypes(): void
    {
        $tokenParser = new TokenParser(T_VARIABLE);
        $manyParser = new ManyParser($tokenParser);
        
        $tokens = [
            [T_VARIABLE, '$a', 1],
            [T_VARIABLE, '$b', 1],
            [T_VARIABLE, '$c', 1],
            [T_STRING, 'end', 1]
        ];
        
        $result = $manyParser->parse($tokens, 0);
        
        $this->assertTrue($result->isSuccess());
        $expected = [
            [T_VARIABLE, '$a', 1],
            [T_VARIABLE, '$b', 1],
            [T_VARIABLE, '$c', 1]
        ];
        $this->assertSame($expected, $result->getValue());
        $this->assertSame(3, $result->getPosition());
    }

    public function testManyWithEmptyTokens(): void
    {
        $tokenParser = new TokenParser('->');
        $manyParser = new ManyParser($tokenParser);
        
        $tokens = [];
        $result = $manyParser->parse($tokens, 0);
        
        $this->assertTrue($result->isSuccess());
        $this->assertSame([], $result->getValue());
        $this->assertSame(0, $result->getPosition());
    }

    public function testManyFromMiddlePosition(): void
    {
        $tokenParser = new TokenParser('=');
        $manyParser = new ManyParser($tokenParser);
        
        $tokens = ['$', 'var', '=', '=', '=', '42'];
        $result = $manyParser->parse($tokens, 2);
        
        $this->assertTrue($result->isSuccess());
        $this->assertSame(['=', '=', '='], $result->getValue());
        $this->assertSame(5, $result->getPosition());
    }

    public function testManyAtEndOfTokens(): void
    {
        $tokenParser = new TokenParser('->');
        $manyParser = new ManyParser($tokenParser);
        
        $tokens = ['test'];
        $result = $manyParser->parse($tokens, 1);
        
        $this->assertTrue($result->isSuccess());
        $this->assertSame([], $result->getValue());
        $this->assertSame(1, $result->getPosition());
    }

    public function testManyWithSpecificTokenValue(): void
    {
        $tokenParser = new TokenParser(T_STRING, 'test');
        $manyParser = new ManyParser($tokenParser);
        
        $tokens = [
            [T_STRING, 'test', 1],
            [T_STRING, 'test', 1],
            [T_STRING, 'other', 1],
            [T_STRING, 'test', 1]
        ];
        
        $result = $manyParser->parse($tokens, 0);
        
        $this->assertTrue($result->isSuccess());
        $expected = [
            [T_STRING, 'test', 1],
            [T_STRING, 'test', 1]
        ];
        $this->assertSame($expected, $result->getValue());
        $this->assertSame(2, $result->getPosition()); // Stops at 'other'
    }

    public function testManyAlwaysSucceeds(): void
    {
        $tokenParser = new TokenParser('nonexistent');
        $manyParser = new ManyParser($tokenParser);
        
        $testCases = [
            [[], 0],
            [['test'], 0],
            [['test'], 1],
            [['a', 'b', 'c'], 2],
        ];
        
        foreach ($testCases as [$tokens, $position]) {
            $result = $manyParser->parse($tokens, $position);
            
            $this->assertTrue($result->isSuccess(), "Many should never fail");
            $this->assertIsArray($result->getValue(), "Many should always return array");
            $this->assertSame($position, $result->getPosition(), "Position should be preserved when no matches");
        }
    }

    public function testManyWithMixedTokens(): void
    {
        $tokenParser = new TokenParser('->');
        $manyParser = new ManyParser($tokenParser);
        
        $tokens = ['->', '<-', '->', '=', '->', 'end'];
        $result = $manyParser->parse($tokens, 0);
        
        $this->assertTrue($result->isSuccess());
        $this->assertSame(['->'], $result->getValue());
        $this->assertSame(1, $result->getPosition()); // Stops at '<-'
    }

    public function testManyWithAllTokensMatching(): void
    {
        $tokenParser = new TokenParser('x');
        $manyParser = new ManyParser($tokenParser);
        
        $tokens = ['x', 'x', 'x', 'x'];
        $result = $manyParser->parse($tokens, 0);
        
        $this->assertTrue($result->isSuccess());
        $this->assertSame(['x', 'x', 'x', 'x'], $result->getValue());
        $this->assertSame(4, $result->getPosition());
    }

    public function testManyWithCustomParser(): void
    {
        $customParser = new class extends \Syn\Parser\Combinators\ParserCombinator {
            public function parse(array $tokens, int $position): ParseResult
            {
                if ($position >= count($tokens)) {
                    return ParseResult::failure($position, "End of tokens");
                }
                
                $token = $tokens[$position];
                if (is_string($token) && ctype_digit($token)) {
                    return ParseResult::success((int)$token, $position + 1);
                }
                
                return ParseResult::failure($position, "Not a digit");
            }
        };
        
        $manyParser = new ManyParser($customParser);
        
        $tokens = ['1', '2', '3', 'a', '4'];
        $result = $manyParser->parse($tokens, 0);
        
        $this->assertTrue($result->isSuccess());
        $this->assertSame([1, 2, 3], $result->getValue());
        $this->assertSame(3, $result->getPosition()); // Stops at 'a'
    }

    public function testManyWithNestedMany(): void
    {
        $tokenParser = new TokenParser('->');
        $manyParser = new ManyParser($tokenParser);
        $nestedManyParser = new ManyParser($manyParser);
        
        $tokens = ['->', 'test'];
        $result = $nestedManyParser->parse($tokens, 0);
        
        $this->assertTrue($result->isSuccess());
        $this->assertSame([['->']], $result->getValue());
        $this->assertSame(1, $result->getPosition());
    }

    public function testManyWithSequentialParsing(): void
    {
        $tokenParser = new TokenParser('x');
        $manyParser = new ManyParser($tokenParser);
        
        $tokens = ['x', 'x', 'y', 'x', 'x', 'x'];
        
        // Parse first sequence
        $result1 = $manyParser->parse($tokens, 0);
        $this->assertTrue($result1->isSuccess());
        $this->assertSame(['x', 'x'], $result1->getValue());
        $this->assertSame(2, $result1->getPosition());
        
        // Skip 'y' and parse second sequence
        $result2 = $manyParser->parse($tokens, 3);
        $this->assertTrue($result2->isSuccess());
        $this->assertSame(['x', 'x', 'x'], $result2->getValue());
        $this->assertSame(6, $result2->getPosition());
    }

    public function testManyWithLargeSequence(): void
    {
        $tokenParser = new TokenParser('item');
        $manyParser = new ManyParser($tokenParser);
        
        $tokens = array_fill(0, 100, 'item');
        $tokens[] = 'end';
        
        $result = $manyParser->parse($tokens, 0);
        
        $this->assertTrue($result->isSuccess());
        $this->assertCount(100, $result->getValue());
        $this->assertSame(100, $result->getPosition());
        $this->assertSame(array_fill(0, 100, 'item'), $result->getValue());
    }

    public function testManyPreservesTokenStructure(): void
    {
        $tokenParser = new TokenParser(T_LNUMBER);
        $manyParser = new ManyParser($tokenParser);
        
        $tokens = [
            [T_LNUMBER, '1', 1],
            [T_LNUMBER, '2', 2],
            [T_LNUMBER, '3', 3],
            [T_STRING, 'end', 4]
        ];
        
        $result = $manyParser->parse($tokens, 0);
        
        $this->assertTrue($result->isSuccess());
        $expected = [
            [T_LNUMBER, '1', 1],
            [T_LNUMBER, '2', 2],
            [T_LNUMBER, '3', 3]
        ];
        $this->assertSame($expected, $result->getValue());
        
        // Verify token structure is preserved
        foreach ($result->getValue() as $i => $token) {
            $this->assertIsArray($token);
            $this->assertSame(T_LNUMBER, $token[0]);
            $this->assertSame((string)($i + 1), $token[1]);
            $this->assertSame($i + 1, $token[2]);
        }
    }

    public function testManyWithZeroMatches(): void
    {
        $tokenParser = new TokenParser('missing');
        $manyParser = new ManyParser($tokenParser);
        
        $tokens = ['present', 'tokens', 'here'];
        $result = $manyParser->parse($tokens, 0);
        
        $this->assertTrue($result->isSuccess());
        $this->assertSame([], $result->getValue());
        $this->assertSame(0, $result->getPosition());
        $this->assertCount(0, $result->getValue());
    }

    public function testManyWithOptionalParserPreventsInfiniteLoop(): void
    {
        $tokenParser = new TokenParser('->');
        $optionalParser = new \Syn\Parser\Combinators\OptionalParser($tokenParser);
        $manyOptionalParser = new ManyParser($optionalParser);
        
        // This would cause infinite loop without position advancement check
        $tokens = ['<-', 'test'];
        $result = $manyOptionalParser->parse($tokens, 0);
        
        $this->assertTrue($result->isSuccess());
        $this->assertSame([], $result->getValue()); // No matches, but no infinite loop
        $this->assertSame(0, $result->getPosition());
    }
} 
