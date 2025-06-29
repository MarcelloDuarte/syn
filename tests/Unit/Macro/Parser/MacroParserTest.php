<?php

declare(strict_types=1);

namespace Syn\Tests\Unit\Macro\Parser;

use PHPUnit\Framework\TestCase;
use Syn\Macro\Parser\MacroParser;
use Syn\Macro\Capture\DelimiterMatcher;
use Syn\Parser\MacroDefinition;

class MacroParserTest extends TestCase
{
    private MacroParser $parser;

    protected function setUp(): void
    {
        $delimiterMatcher = new DelimiterMatcher();
        $this->parser = new MacroParser($delimiterMatcher);
    }

    public function testParseMacroFromLineWithSimpleMacro(): void
    {
        $line = '$(macro) { $-> } >> { $this-> }';
        
        $result = $this->parser->parseMacroFromLine($line, 'test.syn', 1);
        
        $this->assertInstanceOf(MacroDefinition::class, $result);
        $this->assertSame('$->', $result->getPattern());
        $this->assertSame('$this->', $result->getReplacement());
        $this->assertSame('test.syn', $result->getFile());
        $this->assertSame(1, $result->getLine());
    }

    public function testParseMacroFromLineWithComplexMacro(): void
    {
        $line = '$(macro) { unless ($(layer() as condition)) { $(layer() as body) } } >> { if (!($(condition))) { $(body) } }';
        
        $result = $this->parser->parseMacroFromLine($line, 'test.syn', 5);
        
        $this->assertInstanceOf(MacroDefinition::class, $result);
        $this->assertSame('unless ($(layer() as condition)) { $(layer() as body) }', $result->getPattern());
        $this->assertSame('if (!($(condition))) { $(body) }', $result->getReplacement());
        $this->assertSame('test.syn', $result->getFile());
        $this->assertSame(5, $result->getLine());
    }

    public function testParseMacroFromLineWithNestedBraces(): void
    {
        $line = '$(macro) { func({ nested: true }) } >> { enhanced_func({ nested: true, extra: 1 }) }';
        
        $result = $this->parser->parseMacroFromLine($line, null, 10);
        
        $this->assertInstanceOf(MacroDefinition::class, $result);
        $this->assertSame('func({ nested: true })', $result->getPattern());
        $this->assertSame('enhanced_func({ nested: true, extra: 1 })', $result->getReplacement());
        $this->assertNull($result->getFile());
        $this->assertSame(10, $result->getLine());
    }

    public function testParseMacroFromLineWithWhitespace(): void
    {
        $line = '  $(macro)   {   pattern   }   >>   {   replacement   }  ';
        
        $result = $this->parser->parseMacroFromLine($line, 'test.syn', 1);
        
        $this->assertInstanceOf(MacroDefinition::class, $result);
        $this->assertSame('pattern', $result->getPattern());
        $this->assertSame('replacement', $result->getReplacement());
    }

    public function testParseMacroFromLineWithEmptyLine(): void
    {
        $result = $this->parser->parseMacroFromLine('', 'test.syn', 1);
        
        $this->assertNull($result);
    }

    public function testParseMacroFromLineWithComment(): void
    {
        $result1 = $this->parser->parseMacroFromLine('# This is a comment', 'test.syn', 1);
        $result2 = $this->parser->parseMacroFromLine('// This is also a comment', 'test.syn', 2);
        
        $this->assertNull($result1);
        $this->assertNull($result2);
    }

    public function testParseMacroFromLineWithInvalidFormat(): void
    {
        $result1 = $this->parser->parseMacroFromLine('not a macro', 'test.syn', 1);
        $result2 = $this->parser->parseMacroFromLine('$(macro) pattern >> replacement', 'test.syn', 2);
        $result3 = $this->parser->parseMacroFromLine('$(macro) { pattern }', 'test.syn', 3);
        
        $this->assertNull($result1);
        $this->assertNull($result2);
        $this->assertNull($result3);
    }

    public function testParseMacroFromLineWithMissingClosingBrace(): void
    {
        $line = '$(macro) { pattern >> { replacement }';
        
        $result = $this->parser->parseMacroFromLine($line, 'test.syn', 1);
        
        $this->assertNull($result);
    }

    public function testParseMacroFromLineWithMissingSeparator(): void
    {
        $line = '$(macro) { pattern } { replacement }';
        
        $result = $this->parser->parseMacroFromLine($line, 'test.syn', 1);
        
        $this->assertNull($result);
    }

    public function testParseMacroFromLineWithMissingReplacementBrace(): void
    {
        $line = '$(macro) { pattern } >> replacement';
        
        $result = $this->parser->parseMacroFromLine($line, 'test.syn', 1);
        
        $this->assertNull($result);
    }

    public function testParseMacroFromLineWithDeeplyNestedBraces(): void
    {
        $line = '$(macro) { outer { middle { inner } middle } outer } >> { transformed { nested { deep } nested } transformed }';
        
        $result = $this->parser->parseMacroFromLine($line, 'test.syn', 1);
        
        $this->assertInstanceOf(MacroDefinition::class, $result);
        $this->assertSame('outer { middle { inner } middle } outer', $result->getPattern());
        $this->assertSame('transformed { nested { deep } nested } transformed', $result->getReplacement());
    }

    public function testParseMacroFromLineWithSpecialCharacters(): void
    {
        $line = '$(macro) { echo "Hello, World!"; } >> { print("Hello, World!"); }';
        
        $result = $this->parser->parseMacroFromLine($line, 'test.syn', 1);
        
        $this->assertInstanceOf(MacroDefinition::class, $result);
        $this->assertSame('echo "Hello, World!";', $result->getPattern());
        $this->assertSame('print("Hello, World!");', $result->getReplacement());
    }

    public function testParseMacroFromLineWithMultipleArrows(): void
    {
        $line = '$(macro) { $->method() } >> { $this->method() }';
        
        $result = $this->parser->parseMacroFromLine($line, 'test.syn', 1);
        
        $this->assertInstanceOf(MacroDefinition::class, $result);
        $this->assertSame('$->method()', $result->getPattern());
        $this->assertSame('$this->method()', $result->getReplacement());
    }

    public function testParseMacroFromLineTrimsWhitespaceFromPatternAndReplacement(): void
    {
        $line = '$(macro) {   $->   } >> {   $this->   }';
        
        $result = $this->parser->parseMacroFromLine($line, 'test.syn', 1);
        
        $this->assertInstanceOf(MacroDefinition::class, $result);
        $this->assertSame('$->', $result->getPattern());
        $this->assertSame('$this->', $result->getReplacement());
    }
} 
