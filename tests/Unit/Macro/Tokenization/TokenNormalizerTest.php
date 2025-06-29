<?php

declare(strict_types=1);

namespace Syn\Tests\Unit\Macro\Tokenization;

use PHPUnit\Framework\TestCase;
use Syn\Macro\Tokenization\TokenNormalizer;

class TokenNormalizerTest extends TestCase
{
    private TokenNormalizer $normalizer;

    protected function setUp(): void
    {
        $this->normalizer = new TokenNormalizer();
    }

    public function testTokensMatchWithStringTokens(): void
    {
        $this->assertTrue($this->normalizer->tokensMatch('->', '->'));
        $this->assertTrue($this->normalizer->tokensMatch('(', '('));
        $this->assertTrue($this->normalizer->tokensMatch('{', '{'));
        
        $this->assertFalse($this->normalizer->tokensMatch('->', '<-'));
        $this->assertFalse($this->normalizer->tokensMatch('(', ')'));
    }

    public function testTokensMatchWithArrayTokens(): void
    {
        $arrayToken = [T_VARIABLE, '$variable', 1];
        
        $this->assertTrue($this->normalizer->tokensMatch('$variable', $arrayToken));
        $this->assertFalse($this->normalizer->tokensMatch('$other', $arrayToken));
        
        $stringToken = [T_STRING, 'function', 1];
        $this->assertTrue($this->normalizer->tokensMatch('function', $stringToken));
        $this->assertFalse($this->normalizer->tokensMatch('class', $stringToken));
    }

    public function testTokensMatchWithInvalidArrayToken(): void
    {
        $invalidToken = [T_VARIABLE]; // Missing token value
        
        $this->assertFalse($this->normalizer->tokensMatch('$variable', $invalidToken));
    }

    public function testIsWhitespaceWithArrayTokens(): void
    {
        $whitespaceToken = [T_WHITESPACE, ' ', 1];
        $commentToken = [T_COMMENT, '// comment', 1];
        $docCommentToken = [T_DOC_COMMENT, '/** doc */', 1];
        $variableToken = [T_VARIABLE, '$var', 1];
        
        $this->assertTrue($this->normalizer->isWhitespace($whitespaceToken));
        $this->assertTrue($this->normalizer->isWhitespace($commentToken));
        $this->assertTrue($this->normalizer->isWhitespace($docCommentToken));
        $this->assertFalse($this->normalizer->isWhitespace($variableToken));
    }

    public function testIsWhitespaceWithStringTokens(): void
    {
        $this->assertFalse($this->normalizer->isWhitespace('->'));
        $this->assertFalse($this->normalizer->isWhitespace('('));
    }

    public function testTokensToString(): void
    {
        $tokens = [
            '$',
            [T_VARIABLE, 'variable', 1],
            '->',
            [T_STRING, 'method', 1],
            '(',
            ')'
        ];
        
        $result = $this->normalizer->tokensToString($tokens);
        
        $this->assertSame('$variable->method()', $result);
    }

    public function testTokensToStringWithEmptyArray(): void
    {
        $this->assertSame('', $this->normalizer->tokensToString([]));
    }

    public function testGetTokenValueWithStringToken(): void
    {
        $this->assertSame('->', $this->normalizer->getTokenValue('->'));
        $this->assertSame('(', $this->normalizer->getTokenValue('('));
    }

    public function testGetTokenValueWithArrayToken(): void
    {
        $token = [T_VARIABLE, '$variable', 1];
        $this->assertSame('$variable', $this->normalizer->getTokenValue($token));
        
        $stringToken = [T_STRING, 'function', 1];
        $this->assertSame('function', $this->normalizer->getTokenValue($stringToken));
    }

    public function testGetTokenValueWithInvalidToken(): void
    {
        $invalidToken = [T_VARIABLE]; // Missing value
        $this->assertSame('', $this->normalizer->getTokenValue($invalidToken));
        
        $this->assertSame('', $this->normalizer->getTokenValue(null));
        $this->assertSame('', $this->normalizer->getTokenValue(123));
    }
} 
