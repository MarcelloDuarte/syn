<?php

declare(strict_types=1);

namespace Syn\Tests\Unit\Macro\Matching;

use PHPUnit\Framework\TestCase;
use Syn\Macro\Matching\SequenceMatcher;
use Syn\Macro\Tokenization\PatternTokenizer;
use Syn\Macro\Tokenization\TokenNormalizer;
use Syn\Macro\Capture\BalancedCapture;
use Syn\Macro\Capture\DelimiterMatcher;
use Syn\Parser\MacroDefinition;

class SequenceMatcherTest extends TestCase
{
    private SequenceMatcher $sequenceMatcher;

    protected function setUp(): void
    {
        $patternTokenizer = new PatternTokenizer();
        $tokenNormalizer = new TokenNormalizer();
        $delimiterMatcher = new DelimiterMatcher();
        $balancedCapture = new BalancedCapture($delimiterMatcher, $tokenNormalizer);
        
        $this->sequenceMatcher = new SequenceMatcher(
            $patternTokenizer,
            $tokenNormalizer,
            $balancedCapture,
            $delimiterMatcher
        );
    }

    public function testFindMacrosForTokenSequenceWithSimpleMacro(): void
    {
        $macro = new MacroDefinition('$->', '$this->');
        $macros = [$macro];
        $tokens = ['$', '->', 'method', '(', ')'];
        
        $result = $this->sequenceMatcher->findMacrosForTokenSequence($macros, $tokens, 0);
        
        $this->assertCount(1, $result);
        $this->assertSame($macro, $result[0]['macro']);
        $this->assertSame([], $result[0]['captures']);
        $this->assertSame(2, $result[0]['consumed']);
    }

    public function testFindMacrosForTokenSequenceWithNoMatch(): void
    {
        $macro = new MacroDefinition('$->', '$this->');
        $macros = [$macro];
        $tokens = ['echo', '"hello"'];
        
        $result = $this->sequenceMatcher->findMacrosForTokenSequence($macros, $tokens, 0);
        
        $this->assertCount(0, $result);
    }

    public function testFindMacrosForTokenSequenceWithInvalidPosition(): void
    {
        $macro = new MacroDefinition('$->', '$this->');
        $macros = [$macro];
        $tokens = ['$', '->'];
        
        $result = $this->sequenceMatcher->findMacrosForTokenSequence($macros, $tokens, 5);
        
        $this->assertCount(0, $result);
    }

    public function testFindMacrosForTokenSequenceWithMultipleMacros(): void
    {
        $macro1 = new MacroDefinition('$->', '$this->');
        $macro2 = new MacroDefinition('__debug', 'var_dump');
        $macros = [$macro1, $macro2];
        $tokens = ['$', '->', 'method'];
        
        $result = $this->sequenceMatcher->findMacrosForTokenSequence($macros, $tokens, 0);
        
        $this->assertCount(1, $result);
        $this->assertSame($macro1, $result[0]['macro']);
    }

    public function testFindMacrosForTokenSequenceWithArrayTokens(): void
    {
        $macro = new MacroDefinition('$variable', '$this->variable');
        $macros = [$macro];
        $tokens = [
            [T_VARIABLE, '$variable', 1],
            [T_WHITESPACE, ' ', 1],
            '=',
            [T_LNUMBER, '1', 1]
        ];
        
        $result = $this->sequenceMatcher->findMacrosForTokenSequence($macros, $tokens, 0);
        
        $this->assertCount(1, $result);
        $this->assertSame($macro, $result[0]['macro']);
        $this->assertSame(1, $result[0]['consumed']);
    }

    public function testFindMacrosForTokenSequenceWithPartialMatch(): void
    {
        $macro = new MacroDefinition('$->method()', '$this->method()');
        $macros = [$macro];
        $tokens = ['$', '->', 'property']; // Different from expected 'method'
        
        $result = $this->sequenceMatcher->findMacrosForTokenSequence($macros, $tokens, 0);
        
        $this->assertCount(0, $result);
    }

    public function testFindMacrosForTokenSequenceWithEmptyMacroList(): void
    {
        $macros = [];
        $tokens = ['$', '->', 'method'];
        
        $result = $this->sequenceMatcher->findMacrosForTokenSequence($macros, $tokens, 0);
        
        $this->assertCount(0, $result);
    }

    public function testFindMacrosForTokenSequenceWithEmptyTokenList(): void
    {
        $macro = new MacroDefinition('$->', '$this->');
        $macros = [$macro];
        $tokens = [];
        
        $result = $this->sequenceMatcher->findMacrosForTokenSequence($macros, $tokens, 0);
        
        $this->assertCount(0, $result);
    }
} 
