<?php

declare(strict_types=1);

namespace Syn\Tests\Unit\Macro\Capture;

use PHPUnit\Framework\TestCase;
use Syn\Macro\Capture\DelimiterMatcher;

class DelimiterMatcherTest extends TestCase
{
    private DelimiterMatcher $matcher;

    protected function setUp(): void
    {
        $this->matcher = new DelimiterMatcher();
    }

    public function testIsClosingDelimiterWithValidDelimiters(): void
    {
        $this->assertTrue($this->matcher->isClosingDelimiter(')'));
        $this->assertTrue($this->matcher->isClosingDelimiter('}'));
        $this->assertTrue($this->matcher->isClosingDelimiter(']'));
    }

    public function testIsClosingDelimiterWithInvalidDelimiters(): void
    {
        $this->assertFalse($this->matcher->isClosingDelimiter('('));
        $this->assertFalse($this->matcher->isClosingDelimiter('{'));
        $this->assertFalse($this->matcher->isClosingDelimiter('['));
        $this->assertFalse($this->matcher->isClosingDelimiter(';'));
        $this->assertFalse($this->matcher->isClosingDelimiter('->'));
    }

    public function testGetOpeningDelimiterWithValidClosingDelimiters(): void
    {
        $this->assertSame('(', $this->matcher->getOpeningDelimiter(')'));
        $this->assertSame('{', $this->matcher->getOpeningDelimiter('}'));
        $this->assertSame('[', $this->matcher->getOpeningDelimiter(']'));
    }

    public function testGetOpeningDelimiterWithInvalidClosingDelimiters(): void
    {
        $this->assertSame('', $this->matcher->getOpeningDelimiter('('));
        $this->assertSame('', $this->matcher->getOpeningDelimiter(';'));
        $this->assertSame('', $this->matcher->getOpeningDelimiter('->'));
        $this->assertSame('', $this->matcher->getOpeningDelimiter('unknown'));
    }

    public function testFindMatchingBraceWithSimpleBraces(): void
    {
        $text = 'function() { return true; }';
        $startPos = strpos($text, '{');
        
        $result = $this->matcher->findMatchingBrace($text, $startPos);
        
        $this->assertSame(strlen($text) - 1, $result);
    }

    public function testFindMatchingBraceWithNestedBraces(): void
    {
        $text = 'if (condition) { if (nested) { inner(); } outer(); }';
        $startPos = strpos($text, '{');
        
        $result = $this->matcher->findMatchingBrace($text, $startPos);
        
        $this->assertSame(strlen($text) - 1, $result);
    }

    public function testFindMatchingBraceWithDeeplyNestedBraces(): void
    {
        $text = '{ { { inner } middle } outer }';
        $startPos = 0;
        
        $result = $this->matcher->findMatchingBrace($text, $startPos);
        
        $this->assertSame(strlen($text) - 1, $result);
    }

    public function testFindMatchingBraceWithNoMatchingBrace(): void
    {
        $text = 'function() { return true;';
        $startPos = strpos($text, '{');
        
        $result = $this->matcher->findMatchingBrace($text, $startPos);
        
        $this->assertSame(-1, $result);
    }

    public function testFindMatchingBraceWithEmptyBraces(): void
    {
        $text = '{}';
        $startPos = 0;
        
        $result = $this->matcher->findMatchingBrace($text, $startPos);
        
        $this->assertSame(1, $result);
    }

    public function testFindMatchingBraceWithComplexNesting(): void
    {
        $text = 'class Test { public function method() { if (true) { echo "nested"; } } }';
        $startPos = strpos($text, '{'); // First brace after 'class Test'
        
        $result = $this->matcher->findMatchingBrace($text, $startPos);
        
        $this->assertSame(strlen($text) - 1, $result);
    }

    public function testFindMatchingBraceWithMultipleSeparateBraces(): void
    {
        $text = '{ first } { second }';
        $firstBracePos = 0;
        $secondBracePos = strpos($text, '{', 1);
        
        $firstResult = $this->matcher->findMatchingBrace($text, $firstBracePos);
        $secondResult = $this->matcher->findMatchingBrace($text, $secondBracePos);
        
        $this->assertSame(8, $firstResult); // Position of first closing brace
        $this->assertSame(strlen($text) - 1, $secondResult); // Position of second closing brace
    }
} 
