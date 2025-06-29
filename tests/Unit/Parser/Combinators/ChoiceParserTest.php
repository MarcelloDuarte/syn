<?php

declare(strict_types=1);

namespace Syn\Tests\Unit\Parser\Combinators;

use PHPUnit\Framework\TestCase;
use Syn\Parser\Combinators\ChoiceParser;
use Syn\Parser\Combinators\TokenParser;
use Syn\Parser\ParseResult;

class ChoiceParserTest extends TestCase
{
    public function testChoiceWithFirstAlternativeMatching(): void
    {
        $parser1 = new TokenParser('->');
        $parser2 = new TokenParser('<-');
        $choiceParser = new ChoiceParser([$parser1, $parser2]);
        
        $tokens = ['->', 'method'];
        $result = $choiceParser->parse($tokens, 0);
        
        $this->assertTrue($result->isSuccess());
        $this->assertSame('->', $result->getValue());
        $this->assertSame(1, $result->getPosition());
    }

    public function testChoiceWithSecondAlternativeMatching(): void
    {
        $parser1 = new TokenParser('->');
        $parser2 = new TokenParser('<-');
        $choiceParser = new ChoiceParser([$parser1, $parser2]);
        
        $tokens = ['<-', 'method'];
        $result = $choiceParser->parse($tokens, 0);
        
        $this->assertTrue($result->isSuccess());
        $this->assertSame('<-', $result->getValue());
        $this->assertSame(1, $result->getPosition());
    }

    public function testChoiceWithNoAlternativesMatching(): void
    {
        $parser1 = new TokenParser('->');
        $parser2 = new TokenParser('<-');
        $choiceParser = new ChoiceParser([$parser1, $parser2]);
        
        $tokens = ['=', 'method'];
        $result = $choiceParser->parse($tokens, 0);
        
        $this->assertFalse($result->isSuccess());
        $this->assertSame(0, $result->getPosition());
        // Should return the last error
        $this->assertStringContainsString('Expected', $result->getError());
    }

    public function testChoiceWithMultipleAlternatives(): void
    {
        $parser1 = new TokenParser('->');
        $parser2 = new TokenParser('<-');
        $parser3 = new TokenParser('=>');
        $parser4 = new TokenParser('::');
        $choiceParser = new ChoiceParser([$parser1, $parser2, $parser3, $parser4]);
        
        // Test third alternative
        $tokens = ['=>', 'value'];
        $result = $choiceParser->parse($tokens, 0);
        
        $this->assertTrue($result->isSuccess());
        $this->assertSame('=>', $result->getValue());
        $this->assertSame(1, $result->getPosition());
    }

    public function testChoiceWithTokenTypes(): void
    {
        $parser1 = new TokenParser(T_VARIABLE);
        $parser2 = new TokenParser(T_STRING);
        $parser3 = new TokenParser(T_LNUMBER);
        $choiceParser = new ChoiceParser([$parser1, $parser2, $parser3]);
        
        // Test string token
        $tokens = [[T_STRING, 'identifier', 1], '('];
        $result = $choiceParser->parse($tokens, 0);
        
        $this->assertTrue($result->isSuccess());
        $this->assertSame([T_STRING, 'identifier', 1], $result->getValue());
        $this->assertSame(1, $result->getPosition());
    }

    public function testChoiceWithSpecificTokenValues(): void
    {
        $parser1 = new TokenParser(T_STRING, 'function');
        $parser2 = new TokenParser(T_STRING, 'class');
        $parser3 = new TokenParser(T_STRING, 'interface');
        $choiceParser = new ChoiceParser([$parser1, $parser2, $parser3]);
        
        // Test second alternative
        $tokens = [[T_STRING, 'class', 1], 'Name'];
        $result = $choiceParser->parse($tokens, 0);
        
        $this->assertTrue($result->isSuccess());
        $this->assertSame([T_STRING, 'class', 1], $result->getValue());
        
        // Test non-matching value
        $tokens2 = [[T_STRING, 'variable', 1], 'Name'];
        $result2 = $choiceParser->parse($tokens2, 0);
        
        $this->assertFalse($result2->isSuccess());
    }

    public function testChoiceWithEmptyAlternatives(): void
    {
        $choiceParser = new ChoiceParser([]);
        
        $tokens = ['->', 'method'];
        $result = $choiceParser->parse($tokens, 0);
        
        $this->assertFalse($result->isSuccess());
        $this->assertSame(0, $result->getPosition());
        $this->assertStringContainsString('No parser matched', $result->getError());
    }

    public function testChoiceFromMiddlePosition(): void
    {
        $parser1 = new TokenParser('=');
        $parser2 = new TokenParser('+');
        $choiceParser = new ChoiceParser([$parser1, $parser2]);
        
        $tokens = ['$', 'var', '+', '42'];
        $result = $choiceParser->parse($tokens, 2);
        
        $this->assertTrue($result->isSuccess());
        $this->assertSame('+', $result->getValue());
        $this->assertSame(3, $result->getPosition());
    }

    public function testChoiceAtEndOfTokens(): void
    {
        $parser1 = new TokenParser('->');
        $parser2 = new TokenParser('<-');
        $choiceParser = new ChoiceParser([$parser1, $parser2]);
        
        $tokens = ['test'];
        $result = $choiceParser->parse($tokens, 1);
        
        $this->assertFalse($result->isSuccess());
        $this->assertSame(1, $result->getPosition());
        $this->assertStringContainsString('Unexpected end of input', $result->getError());
    }

    public function testChoiceReturnsFirstMatch(): void
    {
        // Both parsers could match the same token
        $parser1 = new TokenParser(T_STRING);
        $parser2 = new TokenParser(T_STRING, 'test');
        $choiceParser = new ChoiceParser([$parser1, $parser2]);
        
        $tokens = [[T_STRING, 'test', 1], '('];
        $result = $choiceParser->parse($tokens, 0);
        
        $this->assertTrue($result->isSuccess());
        $this->assertSame([T_STRING, 'test', 1], $result->getValue());
        // Should use first parser (more general one)
    }

    public function testChoiceWithCustomParsers(): void
    {
        $digitParser = new class extends \Syn\Parser\Combinators\ParserCombinator {
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
        
        $letterParser = new class extends \Syn\Parser\Combinators\ParserCombinator {
            public function parse(array $tokens, int $position): ParseResult
            {
                if ($position >= count($tokens)) {
                    return ParseResult::failure($position, "End of tokens");
                }
                
                $token = $tokens[$position];
                if (is_string($token) && ctype_alpha($token)) {
                    return ParseResult::success(strtoupper($token), $position + 1);
                }
                
                return ParseResult::failure($position, "Not a letter");
            }
        };
        
        $choiceParser = new ChoiceParser([$digitParser, $letterParser]);
        
        // Test digit
        $tokens1 = ['5', 'other'];
        $result1 = $choiceParser->parse($tokens1, 0);
        
        $this->assertTrue($result1->isSuccess());
        $this->assertSame(5, $result1->getValue());
        
        // Test letter
        $tokens2 = ['a', 'other'];
        $result2 = $choiceParser->parse($tokens2, 0);
        
        $this->assertTrue($result2->isSuccess());
        $this->assertSame('A', $result2->getValue());
        
        // Test neither
        $tokens3 = ['!', 'other'];
        $result3 = $choiceParser->parse($tokens3, 0);
        
        $this->assertFalse($result3->isSuccess());
    }

    public function testChoiceWithNestedChoice(): void
    {
        $parser1 = new TokenParser('->');
        $parser2 = new TokenParser('<-');
        $innerChoice = new ChoiceParser([$parser1, $parser2]);
        
        $parser3 = new TokenParser('=>');
        $outerChoice = new ChoiceParser([$innerChoice, $parser3]);
        
        // Test inner choice first alternative
        $tokens1 = ['->', 'test'];
        $result1 = $outerChoice->parse($tokens1, 0);
        
        $this->assertTrue($result1->isSuccess());
        $this->assertSame('->', $result1->getValue());
        
        // Test inner choice second alternative
        $tokens2 = ['<-', 'test'];
        $result2 = $outerChoice->parse($tokens2, 0);
        
        $this->assertTrue($result2->isSuccess());
        $this->assertSame('<-', $result2->getValue());
        
        // Test outer choice second alternative
        $tokens3 = ['=>', 'test'];
        $result3 = $outerChoice->parse($tokens3, 0);
        
        $this->assertTrue($result3->isSuccess());
        $this->assertSame('=>', $result3->getValue());
    }

    public function testChoiceErrorHandling(): void
    {
        $parser1 = new TokenParser(T_VARIABLE);
        $parser2 = new TokenParser(T_STRING);
        $choiceParser = new ChoiceParser([$parser1, $parser2]);
        
        $tokens = [[T_LNUMBER, '42', 1], '+'];
        $result = $choiceParser->parse($tokens, 0);
        
        $this->assertFalse($result->isSuccess());
        $this->assertSame(0, $result->getPosition());
        // Should contain information about the last failed parser
        $error = $result->getError();
        $this->assertStringContainsString('Expected token type', $error);
    }

    public function testChoiceWithSingleAlternative(): void
    {
        $parser = new TokenParser('->');
        $choiceParser = new ChoiceParser([$parser]);
        
        // Test matching
        $tokens1 = ['->', 'test'];
        $result1 = $choiceParser->parse($tokens1, 0);
        
        $this->assertTrue($result1->isSuccess());
        $this->assertSame('->', $result1->getValue());
        
        // Test non-matching
        $tokens2 = ['<-', 'test'];
        $result2 = $choiceParser->parse($tokens2, 0);
        
        $this->assertFalse($result2->isSuccess());
    }

    public function testChoiceWithManyAlternatives(): void
    {
        $parsers = [];
        for ($i = 0; $i < 10; $i++) {
            $parsers[] = new TokenParser("token$i");
        }
        $choiceParser = new ChoiceParser($parsers);
        
        // Test middle alternative
        $tokens = ['token5', 'other'];
        $result = $choiceParser->parse($tokens, 0);
        
        $this->assertTrue($result->isSuccess());
        $this->assertSame('token5', $result->getValue());
        
        // Test last alternative
        $tokens2 = ['token9', 'other'];
        $result2 = $choiceParser->parse($tokens2, 0);
        
        $this->assertTrue($result2->isSuccess());
        $this->assertSame('token9', $result2->getValue());
        
        // Test non-matching
        $tokens3 = ['token10', 'other'];
        $result3 = $choiceParser->parse($tokens3, 0);
        
        $this->assertFalse($result3->isSuccess());
    }

    public function testChoicePreservesPosition(): void
    {
        $parser1 = new TokenParser('missing');
        $parser2 = new TokenParser('also_missing');
        $choiceParser = new ChoiceParser([$parser1, $parser2]);
        
        $tokens = ['present', 'tokens'];
        $result = $choiceParser->parse($tokens, 0);
        
        $this->assertFalse($result->isSuccess());
        $this->assertSame(0, $result->getPosition());
    }

    public function testChoiceWithEmptyTokens(): void
    {
        $parser1 = new TokenParser('->');
        $parser2 = new TokenParser('<-');
        $choiceParser = new ChoiceParser([$parser1, $parser2]);
        
        $tokens = [];
        $result = $choiceParser->parse($tokens, 0);
        
        $this->assertFalse($result->isSuccess());
        $this->assertSame(0, $result->getPosition());
        $this->assertStringContainsString('Unexpected end of input', $result->getError());
    }
} 
