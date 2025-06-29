<?php

declare(strict_types=1);

namespace Syn\Tests\Unit\Macro\Tokenization;

use PHPUnit\Framework\TestCase;
use Syn\Macro\Tokenization\PatternTokenizer;

class PatternTokenizerTest extends TestCase
{
    private PatternTokenizer $tokenizer;

    protected function setUp(): void
    {
        $this->tokenizer = new PatternTokenizer();
    }

    public function testTokenizeSimplePattern(): void
    {
        $result = $this->tokenizer->tokenizePattern('$->');
        
        $this->assertSame(['$', '->'], $result);
    }

    public function testTokenizeComplexPattern(): void
    {
        $result = $this->tokenizer->tokenizePattern('$variable->method()');
        
        $this->assertSame(['$variable', '->', 'method', '(', ')'], $result);
    }

    public function testTokenizePatternWithWhitespace(): void
    {
        $result = $this->tokenizer->tokenizePattern('if ( $condition )');
        
        $this->assertSame(['if', ' ', '(', ' ', '$condition', ' ', ')'], $result);
    }

    public function testTokenizePatternWithStrings(): void
    {
        $result = $this->tokenizer->tokenizePattern('echo "hello world"');
        
        $this->assertSame(['echo', ' ', '"hello world"'], $result);
    }

    public function testTokenizeEmptyPattern(): void
    {
        $result = $this->tokenizer->tokenizePattern('');
        
        $this->assertSame([], $result);
    }

    public function testTokenizeGenericWithCaptureTokens(): void
    {
        $input = 'unless (__CAPTURE_condition__) { __CAPTURE_body__ }';
        
        $result = $this->tokenizer->tokenizeGeneric($input);
        
        $expected = [
            'unless',
            '(',
            '__CAPTURE_condition__',
            ')',
            '{',
            '__CAPTURE_body__',
            '}'
        ];
        
        $this->assertSame($expected, $result);
    }

    public function testTokenizeGenericWithoutCaptureTokens(): void
    {
        $input = 'if (condition) { body }';
        
        $result = $this->tokenizer->tokenizeGeneric($input);
        
        $expected = [
            'if',
            '(',
            'condition',
            ')',
            '{',
            'body',
            '}'
        ];
        
        $this->assertSame($expected, $result);
    }

    public function testTokenizeGenericFiltersEmptyTokens(): void
    {
        $input = 'a   b';
        
        $result = $this->tokenizer->tokenizeGeneric($input);
        
        $this->assertSame(['a', 'b'], $result);
    }

    public function testTokenizeGenericWithComplexCaptureTokens(): void
    {
        $input = 'macro(__CAPTURE_param1__, __CAPTURE_param2__)';
        
        $result = $this->tokenizer->tokenizeGeneric($input);
        
        $expected = [
            'macro',
            '(',
            '__CAPTURE_param1__',
            ',',
            '__CAPTURE_param2__',
            ')'
        ];
        
        $this->assertSame($expected, $result);
    }

    public function testTokenizeGenericHandlesNestedDelimiters(): void
    {
        $input = 'func([{nested}])';
        
        $result = $this->tokenizer->tokenizeGeneric($input);
        
        $expected = [
            'func',
            '(',
            '[',
            '{',
            'nested',
            '}',
            ']',
            ')'
        ];
        
        $this->assertSame($expected, $result);
    }

    public function testTokenizeGenericWithMixedContent(): void
    {
        $input = 'test __CAPTURE_var__ more';
        
        $result = $this->tokenizer->tokenizeGeneric($input);
        
        $expected = [
            'test',
            '__CAPTURE_var__',
            'more'
        ];
        
        $this->assertSame($expected, $result);
    }
} 
