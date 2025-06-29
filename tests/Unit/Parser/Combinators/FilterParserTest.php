<?php

declare(strict_types=1);

namespace Syn\Tests\Unit\Parser\Combinators;

use PHPUnit\Framework\TestCase;
use Syn\Parser\Combinators\FilterParser;
use Syn\Parser\Combinators\TokenParser;
use Syn\Parser\ParseResult;

class FilterParserTest extends TestCase
{
    public function testFilterWithMatchingPredicate(): void
    {
        $tokenParser = new TokenParser(T_VARIABLE);
        $predicate = fn($token) => str_starts_with($token[1], '$');
        $filterParser = new FilterParser($tokenParser, $predicate);
        
        $tokens = [[T_VARIABLE, '$test', 1], '='];
        $result = $filterParser->parse($tokens, 0);
        
        $this->assertTrue($result->isSuccess());
        $this->assertSame([T_VARIABLE, '$test', 1], $result->getValue());
        $this->assertSame(1, $result->getPosition());
    }

    public function testFilterWithNonMatchingPredicate(): void
    {
        $tokenParser = new TokenParser(T_VARIABLE);
        $predicate = fn($token) => str_starts_with($token[1], '$');
        $filterParser = new FilterParser($tokenParser, $predicate);
        
        $tokens = [[T_VARIABLE, 'test', 1], '='];
        $result = $filterParser->parse($tokens, 0);
        
        $this->assertFalse($result->isSuccess());
        $this->assertSame(0, $result->getPosition());
        $this->assertStringContainsString('Predicate failed', $result->getError());
        $this->assertStringContainsString('test', $result->getError());
    }

    public function testFilterWithUnderlyingParserFailure(): void
    {
        $tokenParser = new TokenParser(T_VARIABLE);
        $predicate = fn($token) => true; // Always accept
        $filterParser = new FilterParser($tokenParser, $predicate);
        
        $tokens = [[T_STRING, 'notVariable', 1], '='];
        $result = $filterParser->parse($tokens, 0);
        
        $this->assertFalse($result->isSuccess());
        $this->assertSame(0, $result->getPosition());
        // Should return the underlying parser's error, not predicate error
        $this->assertStringNotContainsString('Predicate failed', $result->getError());
    }

    public function testFilterWithStringTokens(): void
    {
        $tokenParser = new TokenParser('->');
        $predicate = fn($token) => strlen($token) === 2;
        $filterParser = new FilterParser($tokenParser, $predicate);
        
        $tokens = ['->', 'test'];
        $result = $filterParser->parse($tokens, 0);
        
        $this->assertTrue($result->isSuccess());
        $this->assertSame('->', $result->getValue());
    }

    public function testFilterWithStringTokensFailingPredicate(): void
    {
        $tokenParser = new TokenParser('->');
        $predicate = fn($token) => strlen($token) > 5;
        $filterParser = new FilterParser($tokenParser, $predicate);
        
        $tokens = ['->', 'test'];
        $result = $filterParser->parse($tokens, 0);
        
        $this->assertFalse($result->isSuccess());
        $this->assertStringContainsString('Predicate failed', $result->getError());
    }

    public function testFilterWithComplexPredicate(): void
    {
        $tokenParser = new TokenParser(T_LNUMBER);
        $predicate = fn($token) => (int)$token[1] % 2 === 0; // Even numbers only
        $filterParser = new FilterParser($tokenParser, $predicate);
        
        // Test even number
        $tokens1 = [[T_LNUMBER, '42', 1], '+'];
        $result1 = $filterParser->parse($tokens1, 0);
        
        $this->assertTrue($result1->isSuccess());
        $this->assertSame([T_LNUMBER, '42', 1], $result1->getValue());
        
        // Test odd number
        $tokens2 = [[T_LNUMBER, '43', 1], '+'];
        $result2 = $filterParser->parse($tokens2, 0);
        
        $this->assertFalse($result2->isSuccess());
        $this->assertStringContainsString('Predicate failed', $result2->getError());
    }

    public function testFilterWithArrayPredicate(): void
    {
        $tokenParser = new TokenParser(T_STRING);
        $allowedValues = ['function', 'class', 'interface'];
        $predicate = fn($token) => in_array($token[1], $allowedValues);
        $filterParser = new FilterParser($tokenParser, $predicate);
        
        // Test allowed value
        $tokens1 = [[T_STRING, 'function', 1], '('];
        $result1 = $filterParser->parse($tokens1, 0);
        
        $this->assertTrue($result1->isSuccess());
        $this->assertSame([T_STRING, 'function', 1], $result1->getValue());
        
        // Test disallowed value
        $tokens2 = [[T_STRING, 'variable', 1], '('];
        $result2 = $filterParser->parse($tokens2, 0);
        
        $this->assertFalse($result2->isSuccess());
    }

    public function testFilterWithCallableObject(): void
    {
        $tokenParser = new TokenParser(T_VARIABLE);
        
        $validator = new class {
            public function __invoke($token): bool
            {
                return strlen($token[1]) > 3;
            }
        };
        
        $filterParser = new FilterParser($tokenParser, $validator);
        
        // Test long variable name
        $tokens1 = [[T_VARIABLE, '$longName', 1], '='];
        $result1 = $filterParser->parse($tokens1, 0);
        
        $this->assertTrue($result1->isSuccess());
        
        // Test short variable name
        $tokens2 = [[T_VARIABLE, '$x', 1], '='];
        $result2 = $filterParser->parse($tokens2, 0);
        
        $this->assertFalse($result2->isSuccess());
    }

    public function testFilterWithStaticMethod(): void
    {
        $tokenParser = new TokenParser(T_CONSTANT_ENCAPSED_STRING);
        $filterParser = new FilterParser($tokenParser, [self::class, 'isValidString']);
        
        $tokens = [[T_CONSTANT_ENCAPSED_STRING, '"hello"', 1], ';'];
        $result = $filterParser->parse($tokens, 0);
        
        $this->assertTrue($result->isSuccess());
    }

    public static function isValidString($token): bool
    {
        return str_starts_with($token[1], '"') && str_ends_with($token[1], '"');
    }

    public function testFilterPreservesOriginalPosition(): void
    {
        $tokenParser = new TokenParser('->');
        $predicate = fn($token) => false; // Always fail
        $filterParser = new FilterParser($tokenParser, $predicate);
        
        $tokens = ['test', '->', 'method'];
        $result = $filterParser->parse($tokens, 1);
        
        $this->assertFalse($result->isSuccess());
        $this->assertSame(1, $result->getPosition());
    }

    public function testFilterAtEndOfTokens(): void
    {
        $tokenParser = new TokenParser('->');
        $predicate = fn($token) => true;
        $filterParser = new FilterParser($tokenParser, $predicate);
        
        $tokens = ['test'];
        $result = $filterParser->parse($tokens, 1);
        
        $this->assertFalse($result->isSuccess());
        $this->assertSame(1, $result->getPosition());
        $this->assertStringContainsString('Unexpected end of input', $result->getError());
    }

    public function testFilterWithNullValue(): void
    {
        $tokenParser = new class extends \Syn\Parser\Combinators\ParserCombinator {
            public function parse(array $tokens, int $position): ParseResult
            {
                return ParseResult::success(null, $position + 1);
            }
        };
        
        $predicate = fn($value) => $value === null;
        $filterParser = new FilterParser($tokenParser, $predicate);
        
        $tokens = ['test'];
        $result = $filterParser->parse($tokens, 0);
        
        $this->assertTrue($result->isSuccess());
        $this->assertNull($result->getValue());
    }

    public function testFilterWithBooleanValue(): void
    {
        $tokenParser = new class extends \Syn\Parser\Combinators\ParserCombinator {
            public function parse(array $tokens, int $position): ParseResult
            {
                return ParseResult::success(true, $position + 1);
            }
        };
        
        $predicate = fn($value) => is_bool($value);
        $filterParser = new FilterParser($tokenParser, $predicate);
        
        $tokens = ['test'];
        $result = $filterParser->parse($tokens, 0);
        
        $this->assertTrue($result->isSuccess());
        $this->assertTrue($result->getValue());
    }

    public function testFilterErrorMessageIncludesValue(): void
    {
        $tokenParser = new TokenParser(T_LNUMBER);
        $predicate = fn($token) => false;
        $filterParser = new FilterParser($tokenParser, $predicate);
        
        $tokens = [[T_LNUMBER, '123', 1]];
        $result = $filterParser->parse($tokens, 0);
        
        $this->assertFalse($result->isSuccess());
        $error = $result->getError();
        $this->assertStringContainsString('Predicate failed', $error);
        $this->assertStringContainsString('123', $error);
    }
} 
