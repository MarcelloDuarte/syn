<?php

declare(strict_types=1);

namespace Syn\Tests\Unit\Parser\Combinators;

use PHPUnit\Framework\TestCase;
use Syn\Parser\Combinators\TokenParser;
use Syn\Parser\ParseResult;

class TokenParserTest extends TestCase
{
    public function testParseMatchingStringToken(): void
    {
        $parser = new TokenParser('->');
        $tokens = ['->', 'method', '('];
        
        $result = $parser->parse($tokens, 0);
        
        $this->assertTrue($result->isSuccess());
        $this->assertSame('->', $result->getValue());
        $this->assertSame(1, $result->getPosition());
    }

    public function testParseNonMatchingStringToken(): void
    {
        $parser = new TokenParser('->');
        $tokens = ['<-', 'method', '('];
        
        $result = $parser->parse($tokens, 0);
        
        $this->assertFalse($result->isSuccess());
        $this->assertSame(0, $result->getPosition());
        $this->assertStringContainsString('Expected', $result->getError());
    }

    public function testParseMatchingTokenType(): void
    {
        $parser = new TokenParser(T_VARIABLE);
        $tokens = [
            [T_VARIABLE, '$test', 1],
            '=',
            [T_LNUMBER, '42', 1]
        ];
        
        $result = $parser->parse($tokens, 0);
        
        $this->assertTrue($result->isSuccess());
        $this->assertSame([T_VARIABLE, '$test', 1], $result->getValue());
        $this->assertSame(1, $result->getPosition());
    }

    public function testParseNonMatchingTokenType(): void
    {
        $parser = new TokenParser(T_VARIABLE);
        $tokens = [
            [T_STRING, 'test', 1],
            '=',
            [T_LNUMBER, '42', 1]
        ];
        
        $result = $parser->parse($tokens, 0);
        
        $this->assertFalse($result->isSuccess());
        $this->assertSame(0, $result->getPosition());
    }

    public function testParseMatchingTokenTypeAndValue(): void
    {
        $parser = new TokenParser(T_STRING, 'function');
        $tokens = [
            [T_STRING, 'function', 1],
            [T_STRING, 'test', 1],
            '('
        ];
        
        $result = $parser->parse($tokens, 0);
        
        $this->assertTrue($result->isSuccess());
        $this->assertSame([T_STRING, 'function', 1], $result->getValue());
        $this->assertSame(1, $result->getPosition());
    }

    public function testParseNonMatchingTokenValue(): void
    {
        $parser = new TokenParser(T_STRING, 'function');
        $tokens = [
            [T_STRING, 'class', 1],
            [T_STRING, 'test', 1],
            '{'
        ];
        
        $result = $parser->parse($tokens, 0);
        
        $this->assertFalse($result->isSuccess());
        $this->assertSame(0, $result->getPosition());
    }

    public function testParseAtEndOfTokens(): void
    {
        $parser = new TokenParser('->');
        $tokens = ['$', 'var'];
        
        $result = $parser->parse($tokens, 2);
        
        $this->assertFalse($result->isSuccess());
        $this->assertSame(2, $result->getPosition());
        $this->assertStringContainsString('Unexpected end of input', $result->getError());
    }

    public function testParseBeyondEndOfTokens(): void
    {
        $parser = new TokenParser('->');
        $tokens = ['$', 'var'];
        
        $result = $parser->parse($tokens, 5);
        
        $this->assertFalse($result->isSuccess());
        $this->assertSame(5, $result->getPosition());
    }

    public function testParseWithEmptyTokenArray(): void
    {
        $parser = new TokenParser('->');
        $tokens = [];
        
        $result = $parser->parse($tokens, 0);
        
        $this->assertFalse($result->isSuccess());
        $this->assertSame(0, $result->getPosition());
    }

    public function testParseFromMiddlePosition(): void
    {
        $parser = new TokenParser('=');
        $tokens = ['$', 'var', '=', '42'];
        
        $result = $parser->parse($tokens, 2);
        
        $this->assertTrue($result->isSuccess());
        $this->assertSame('=', $result->getValue());
        $this->assertSame(3, $result->getPosition());
    }

    public function testParseWithWhitespaceToken(): void
    {
        $parser = new TokenParser(T_WHITESPACE);
        $tokens = [
            [T_VARIABLE, '$var', 1],
            [T_WHITESPACE, ' ', 1],
            '='
        ];
        
        $result = $parser->parse($tokens, 1);
        
        $this->assertTrue($result->isSuccess());
        $this->assertSame([T_WHITESPACE, ' ', 1], $result->getValue());
        $this->assertSame(2, $result->getPosition());
    }

    public function testParseVariousStringTokens(): void
    {
        $testCases = [
            '->',
            '(',
            ')',
            '{',
            '}',
            '[',
            ']',
            ';',
            ',',
            '.',
            '::',
            '=>'
        ];

        foreach ($testCases as $token) {
            $parser = new TokenParser($token);
            $tokens = [$token, 'other'];
            
            $result = $parser->parse($tokens, 0);
            
            $this->assertTrue($result->isSuccess(), "Failed for token: $token");
            $this->assertSame($token, $result->getValue());
        }
    }

    public function testParseVariousTokenTypes(): void
    {
        $testCases = [
            [T_VARIABLE, '$variable'],
            [T_STRING, 'identifier'],
            [T_LNUMBER, '123'],
            [T_DNUMBER, '3.14'],
            [T_CONSTANT_ENCAPSED_STRING, '"string"'],
            [T_FUNCTION, 'function'],
            [T_CLASS, 'class'],
            [T_IF, 'if'],
            [T_ELSE, 'else'],
            [T_RETURN, 'return']
        ];

        foreach ($testCases as [$tokenType, $tokenValue]) {
            $parser = new TokenParser($tokenType);
            $tokens = [[$tokenType, $tokenValue, 1], 'other'];
            
            $result = $parser->parse($tokens, 0);
            
            $this->assertTrue($result->isSuccess(), "Failed for token type: $tokenType");
            $this->assertSame([$tokenType, $tokenValue, 1], $result->getValue());
        }
    }

    public function testErrorMessages(): void
    {
        $parser = new TokenParser('->');
        $tokens = ['<-', 'other'];
        
        $result = $parser->parse($tokens, 0);
        
        $this->assertFalse($result->isSuccess());
        $this->assertStringContainsString('Expected', $result->getError());
        $this->assertStringContainsString('->', $result->getError());
    }
} 
