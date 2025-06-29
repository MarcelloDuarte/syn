<?php

declare(strict_types=1);

namespace Syn\Tests\Unit\Macro\Capture;

use PHPUnit\Framework\TestCase;
use Syn\Macro\Capture\BalancedCapture;
use Syn\Macro\Capture\DelimiterMatcher;
use Syn\Macro\Tokenization\TokenNormalizer;

class BalancedCaptureTest extends TestCase
{
    private BalancedCapture $balancedCapture;
    private DelimiterMatcher $delimiterMatcher;
    private TokenNormalizer $tokenNormalizer;

    protected function setUp(): void
    {
        $this->delimiterMatcher = new DelimiterMatcher();
        $this->tokenNormalizer = new TokenNormalizer();
        $this->balancedCapture = new BalancedCapture($this->delimiterMatcher, $this->tokenNormalizer);
    }

    public function testCaptureUntilClosingWithSimpleParentheses(): void
    {
        $tokens = ['(', 'content', ')'];
        
        $result = $this->balancedCapture->captureUntilClosing($tokens, 1, ')');
        
        $this->assertSame(['content'], $result);
    }

    public function testCaptureUntilClosingWithNestedParentheses(): void
    {
        $tokens = ['(', 'outer', '(', 'inner', ')', 'more', ')'];
        
        $result = $this->balancedCapture->captureUntilClosing($tokens, 1, ')');
        
        $this->assertSame(['outer', '(', 'inner', ')', 'more'], $result);
    }

    public function testCaptureUntilClosingWithBraces(): void
    {
        $tokens = ['{', 'content', '}'];
        
        $result = $this->balancedCapture->captureUntilClosing($tokens, 1, '}');
        
        $this->assertSame(['content'], $result);
    }

    public function testCaptureUntilClosingWithNestedBraces(): void
    {
        $tokens = ['{', 'outer', '{', 'inner', '}', 'more', '}'];
        
        $result = $this->balancedCapture->captureUntilClosing($tokens, 1, '}');
        
        $this->assertSame(['outer', '{', 'inner', '}', 'more'], $result);
    }

    public function testCaptureUntilClosingWithBrackets(): void
    {
        $tokens = ['[', 'element1', ',', 'element2', ']'];
        
        $result = $this->balancedCapture->captureUntilClosing($tokens, 1, ']');
        
        $this->assertSame(['element1', ',', 'element2'], $result);
    }

    public function testCaptureUntilClosingWithArrayTokens(): void
    {
        $tokens = [
            '(',
            [T_VARIABLE, '$var', 1],
            [T_WHITESPACE, ' ', 1],
            '===',
            [T_WHITESPACE, ' ', 1],
            [T_LNUMBER, '1', 1],
            ')'
        ];
        
        $result = $this->balancedCapture->captureUntilClosing($tokens, 1, ')');
        
        $expected = [
            [T_VARIABLE, '$var', 1],
            [T_WHITESPACE, ' ', 1],
            '===',
            [T_WHITESPACE, ' ', 1],
            [T_LNUMBER, '1', 1]
        ];
        
        $this->assertSame($expected, $result);
    }

    public function testCaptureUntilClosingWithMixedTokenTypes(): void
    {
        $tokens = [
            '{',
            [T_ECHO, 'echo', 1],
            [T_WHITESPACE, ' ', 1],
            [T_CONSTANT_ENCAPSED_STRING, '"test"', 1],
            ';',
            '}'
        ];
        
        $result = $this->balancedCapture->captureUntilClosing($tokens, 1, '}');
        
        $expected = [
            [T_ECHO, 'echo', 1],
            [T_WHITESPACE, ' ', 1],
            [T_CONSTANT_ENCAPSED_STRING, '"test"', 1],
            ';'
        ];
        
        $this->assertSame($expected, $result);
    }

    public function testCaptureUntilClosingWithNoMatchingClosing(): void
    {
        $tokens = ['(', 'content', 'without', 'closing'];
        
        $result = $this->balancedCapture->captureUntilClosing($tokens, 1, ')');
        
        $this->assertSame(['content', 'without', 'closing'], $result);
    }

    public function testCaptureUntilClosingWithEmptyContent(): void
    {
        $tokens = ['(', ')'];
        
        $result = $this->balancedCapture->captureUntilClosing($tokens, 1, ')');
        
        $this->assertSame([], $result);
    }

    public function testCaptureUntilClosingWithComplexNesting(): void
    {
        $tokens = [
            '{',
            'if', '(', 'condition', ')', '{',
            'nested', 'content',
            '}',
            'more', 'content',
            '}'
        ];
        
        $result = $this->balancedCapture->captureUntilClosing($tokens, 1, '}');
        
        $expected = [
            'if', '(', 'condition', ')', '{',
            'nested', 'content',
            '}',
            'more', 'content'
        ];
        
        $this->assertSame($expected, $result);
    }

    public function testCaptureUntilClosingStartingFromBeginning(): void
    {
        $tokens = ['content', 'more', ')'];
        
        $result = $this->balancedCapture->captureUntilClosing($tokens, 0, ')');
        
        $this->assertSame(['content', 'more'], $result);
    }

    public function testCaptureUntilClosingWithMultipleNestingLevels(): void
    {
        $tokens = [
            '(',
            'level1', '(', 'level2', '(', 'level3', ')', 'back2', ')', 'back1',
            ')'
        ];
        
        $result = $this->balancedCapture->captureUntilClosing($tokens, 1, ')');
        
        $expected = [
            'level1', '(', 'level2', '(', 'level3', ')', 'back2', ')', 'back1'
        ];
        
        $this->assertSame($expected, $result);
    }
} 
