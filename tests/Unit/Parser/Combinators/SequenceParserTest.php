<?php

declare(strict_types=1);

namespace Syn\Tests\Unit\Parser\Combinators;

use PHPUnit\Framework\TestCase;
use Syn\Parser\Combinators\SequenceParser;
use Syn\Parser\Combinators\TokenParser;
use Syn\Parser\ParseResult;

class SequenceParserTest extends TestCase
{
    public function testParseSuccessfulSequence(): void
    {
        $parsers = [
            new TokenParser('('),
            new TokenParser(T_STRING, 'test'),
            new TokenParser(')')
        ];
        $sequenceParser = new SequenceParser($parsers);
        $tokens = ['(', [T_STRING, 'test', 1], ')', 'extra'];
        
        $result = $sequenceParser->parse($tokens, 0);
        
        $this->assertTrue($result->isSuccess());
        $this->assertSame([
            '(',
            [T_STRING, 'test', 1],
            ')'
        ], $result->getValue());
        $this->assertSame(3, $result->getPosition());
    }

    public function testParseFailedSequence(): void
    {
        $parsers = [
            new TokenParser('('),
            new TokenParser(T_STRING, 'test'),
            new TokenParser(')')
        ];
        $sequenceParser = new SequenceParser($parsers);
        $tokens = ['(', [T_STRING, 'wrong', 1], ')', 'extra'];
        
        $result = $sequenceParser->parse($tokens, 0);
        
        $this->assertFalse($result->isSuccess());
        $this->assertSame(1, $result->getPosition());
    }

    public function testParseEmptySequence(): void
    {
        $parsers = [];
        $sequenceParser = new SequenceParser($parsers);
        $tokens = ['any', 'tokens'];
        
        $result = $sequenceParser->parse($tokens, 0);
        
        $this->assertTrue($result->isSuccess());
        $this->assertSame([], $result->getValue());
        $this->assertSame(0, $result->getPosition());
    }

    public function testParseSingleParser(): void
    {
        $parsers = [new TokenParser('->')];
        $sequenceParser = new SequenceParser($parsers);
        $tokens = ['->', 'method'];
        
        $result = $sequenceParser->parse($tokens, 0);
        
        $this->assertTrue($result->isSuccess());
        $this->assertSame(['->'], $result->getValue());
        $this->assertSame(1, $result->getPosition());
    }

    public function testParseSequenceWithVariousTokens(): void
    {
        $parsers = [
            new TokenParser(T_VARIABLE),
            new TokenParser('='),
            new TokenParser(T_LNUMBER),
            new TokenParser(';')
        ];
        $sequenceParser = new SequenceParser($parsers);
        $tokens = [
            [T_VARIABLE, '$var', 1],
            '=',
            [T_LNUMBER, '42', 1],
            ';',
            'extra'
        ];
        
        $result = $sequenceParser->parse($tokens, 0);
        
        $this->assertTrue($result->isSuccess());
        $this->assertSame([
            [T_VARIABLE, '$var', 1],
            '=',
            [T_LNUMBER, '42', 1],
            ';'
        ], $result->getValue());
        $this->assertSame(4, $result->getPosition());
    }

    public function testParseSequenceFromMiddlePosition(): void
    {
        $parsers = [
            new TokenParser('('),
            new TokenParser(')')
        ];
        $sequenceParser = new SequenceParser($parsers);
        $tokens = ['prefix', '(', ')', 'suffix'];
        
        $result = $sequenceParser->parse($tokens, 1);
        
        $this->assertTrue($result->isSuccess());
        $this->assertSame(['(', ')'], $result->getValue());
        $this->assertSame(3, $result->getPosition());
    }

    public function testParseSequenceFailsAtFirstParser(): void
    {
        $parsers = [
            new TokenParser('expected'),
            new TokenParser('second')
        ];
        $sequenceParser = new SequenceParser($parsers);
        $tokens = ['unexpected', 'second'];
        
        $result = $sequenceParser->parse($tokens, 0);
        
        $this->assertFalse($result->isSuccess());
        $this->assertSame(0, $result->getPosition());
    }

    public function testParseSequenceFailsAtLastParser(): void
    {
        $parsers = [
            new TokenParser('first'),
            new TokenParser('expected')
        ];
        $sequenceParser = new SequenceParser($parsers);
        $tokens = ['first', 'unexpected'];
        
        $result = $sequenceParser->parse($tokens, 0);
        
        $this->assertFalse($result->isSuccess());
        $this->assertSame(1, $result->getPosition());
    }

    public function testParseSequenceWithInsufficientTokens(): void
    {
        $parsers = [
            new TokenParser('first'),
            new TokenParser('second'),
            new TokenParser('third')
        ];
        $sequenceParser = new SequenceParser($parsers);
        $tokens = ['first', 'second']; // Missing third token
        
        $result = $sequenceParser->parse($tokens, 0);
        
        $this->assertFalse($result->isSuccess());
        $this->assertSame(2, $result->getPosition());
    }

    public function testParseSequenceWithComplexTokenTypes(): void
    {
        $parsers = [
            new TokenParser(T_FUNCTION),
            new TokenParser(T_STRING),
            new TokenParser('('),
            new TokenParser(T_VARIABLE),
            new TokenParser(')'),
            new TokenParser('{'),
            new TokenParser('}')
        ];
        $sequenceParser = new SequenceParser($parsers);
        $tokens = [
            [T_FUNCTION, 'function', 1],
            [T_STRING, 'test', 1],
            '(',
            [T_VARIABLE, '$param', 1],
            ')',
            '{',
            '}'
        ];
        
        $result = $sequenceParser->parse($tokens, 0);
        
        $this->assertTrue($result->isSuccess());
        $this->assertCount(7, $result->getValue());
        $this->assertSame(7, $result->getPosition());
    }
} 
