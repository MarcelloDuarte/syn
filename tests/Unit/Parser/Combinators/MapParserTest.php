<?php

declare(strict_types=1);

namespace Syn\Tests\Unit\Parser\Combinators;

use PHPUnit\Framework\TestCase;
use Syn\Parser\Combinators\MapParser;
use Syn\Parser\Combinators\TokenParser;
use Syn\Parser\ParseResult;

class MapParserTest extends TestCase
{
    public function testMapWithSuccessfulParse(): void
    {
        $tokenParser = new TokenParser(T_VARIABLE);
        $mapper = fn($token) => strtoupper($token[1]);
        $mapParser = new MapParser($tokenParser, $mapper);
        
        $tokens = [[T_VARIABLE, '$test', 1], '='];
        $result = $mapParser->parse($tokens, 0);
        
        $this->assertTrue($result->isSuccess());
        $this->assertSame('$TEST', $result->getValue());
        $this->assertSame(1, $result->getPosition());
    }

    public function testMapWithFailedParse(): void
    {
        $tokenParser = new TokenParser(T_VARIABLE);
        $mapper = fn($token) => strtoupper($token[1]);
        $mapParser = new MapParser($tokenParser, $mapper);
        
        $tokens = [[T_STRING, 'notVariable', 1], '='];
        $result = $mapParser->parse($tokens, 0);
        
        $this->assertFalse($result->isSuccess());
        $this->assertSame(0, $result->getPosition());
        // Should return original error, not try to map
        $this->assertStringContainsString('Expected token type', $result->getError());
    }

    public function testMapWithStringToken(): void
    {
        $tokenParser = new TokenParser('->');
        $mapper = fn($token) => "ARROW:$token";
        $mapParser = new MapParser($tokenParser, $mapper);
        
        $tokens = ['->', 'method'];
        $result = $mapParser->parse($tokens, 0);
        
        $this->assertTrue($result->isSuccess());
        $this->assertSame('ARROW:->', $result->getValue());
    }

    public function testMapWithComplexTransformation(): void
    {
        $tokenParser = new TokenParser(T_LNUMBER);
        $mapper = fn($token) => [
            'type' => 'number',
            'value' => (int)$token[1],
            'isEven' => (int)$token[1] % 2 === 0
        ];
        $mapParser = new MapParser($tokenParser, $mapper);
        
        $tokens = [[T_LNUMBER, '42', 1], '+'];
        $result = $mapParser->parse($tokens, 0);
        
        $this->assertTrue($result->isSuccess());
        $expected = [
            'type' => 'number',
            'value' => 42,
            'isEven' => true
        ];
        $this->assertSame($expected, $result->getValue());
    }

    public function testMapWithArrayToString(): void
    {
        $tokenParser = new TokenParser(T_CONSTANT_ENCAPSED_STRING);
        $mapper = fn($token) => trim($token[1], '"\'');
        $mapParser = new MapParser($tokenParser, $mapper);
        
        $tokens = [[T_CONSTANT_ENCAPSED_STRING, '"hello world"', 1], ';'];
        $result = $mapParser->parse($tokens, 0);
        
        $this->assertTrue($result->isSuccess());
        $this->assertSame('hello world', $result->getValue());
    }

    public function testMapWithNullValue(): void
    {
        $tokenParser = new class extends \Syn\Parser\Combinators\ParserCombinator {
            public function parse(array $tokens, int $position): ParseResult
            {
                return ParseResult::success(null, $position + 1);
            }
        };
        
        $mapper = fn($value) => $value ?? 'default';
        $mapParser = new MapParser($tokenParser, $mapper);
        
        $tokens = ['test'];
        $result = $mapParser->parse($tokens, 0);
        
        $this->assertTrue($result->isSuccess());
        $this->assertSame('default', $result->getValue());
    }

    public function testMapWithBooleanTransformation(): void
    {
        $tokenParser = new TokenParser(T_STRING);
        $mapper = fn($token) => $token[1] === 'true';
        $mapParser = new MapParser($tokenParser, $mapper);
        
        // Test true case
        $tokens1 = [[T_STRING, 'true', 1], ';'];
        $result1 = $mapParser->parse($tokens1, 0);
        
        $this->assertTrue($result1->isSuccess());
        $this->assertTrue($result1->getValue());
        
        // Test false case
        $tokens2 = [[T_STRING, 'false', 1], ';'];
        $result2 = $mapParser->parse($tokens2, 0);
        
        $this->assertTrue($result2->isSuccess());
        $this->assertFalse($result2->getValue());
    }

    public function testMapWithCallableObject(): void
    {
        $tokenParser = new TokenParser(T_VARIABLE);
        
        $transformer = new class {
            public function __invoke($token)
            {
                return [
                    'original' => $token,
                    'name' => $token[1],
                    'length' => strlen($token[1])
                ];
            }
        };
        
        $mapParser = new MapParser($tokenParser, $transformer);
        
        $tokens = [[T_VARIABLE, '$variable', 1], '='];
        $result = $mapParser->parse($tokens, 0);
        
        $this->assertTrue($result->isSuccess());
        $expected = [
            'original' => [T_VARIABLE, '$variable', 1],
            'name' => '$variable',
            'length' => 9
        ];
        $this->assertSame($expected, $result->getValue());
    }

    public function testMapWithStaticMethod(): void
    {
        $tokenParser = new TokenParser(T_LNUMBER);
        $mapParser = new MapParser($tokenParser, [self::class, 'doubleNumber']);
        
        $tokens = [[T_LNUMBER, '21', 1], '*'];
        $result = $mapParser->parse($tokens, 0);
        
        $this->assertTrue($result->isSuccess());
        $this->assertSame(42, $result->getValue());
    }

    public static function doubleNumber($token): int
    {
        return (int)$token[1] * 2;
    }

    public function testMapWithInstanceMethod(): void
    {
        $tokenParser = new TokenParser(T_STRING);
        $mapParser = new MapParser($tokenParser, [$this, 'formatToken']);
        
        $tokens = [[T_STRING, 'test', 1], '('];
        $result = $mapParser->parse($tokens, 0);
        
        $this->assertTrue($result->isSuccess());
        $this->assertSame('FORMATTED: test', $result->getValue());
    }

    public function formatToken($token): string
    {
        return 'FORMATTED: ' . $token[1];
    }

    public function testMapChaining(): void
    {
        $tokenParser = new TokenParser(T_LNUMBER);
        $mapParser = new MapParser($tokenParser, fn($token) => (int)$token[1]);
        $mapParser2 = new MapParser($mapParser, fn($num) => $num * 2);
        $mapParser3 = new MapParser($mapParser2, fn($num) => "Result: $num");
        
        $tokens = [[T_LNUMBER, '5', 1], '+'];
        $result = $mapParser3->parse($tokens, 0);
        
        $this->assertTrue($result->isSuccess());
        $this->assertSame('Result: 10', $result->getValue());
    }

    public function testMapPreservesPosition(): void
    {
        $tokenParser = new TokenParser('->');
        $mapper = fn($token) => strtoupper($token);
        $mapParser = new MapParser($tokenParser, $mapper);
        
        $tokens = ['test', '->', 'method'];
        $result = $mapParser->parse($tokens, 1);
        
        $this->assertTrue($result->isSuccess());
        $this->assertSame('->', $result->getValue()); // -> uppercase is still ->
        $this->assertSame(2, $result->getPosition());
    }

    public function testMapAtEndOfTokens(): void
    {
        $tokenParser = new TokenParser('->');
        $mapper = fn($token) => strtoupper($token);
        $mapParser = new MapParser($tokenParser, $mapper);
        
        $tokens = ['test'];
        $result = $mapParser->parse($tokens, 1);
        
        $this->assertFalse($result->isSuccess());
        $this->assertSame(1, $result->getPosition());
        $this->assertStringContainsString('Unexpected end of input', $result->getError());
    }

    public function testMapWithArrayResult(): void
    {
        $tokenParser = new TokenParser(T_STRING);
        $mapper = fn($token) => [$token[1], strlen($token[1])];
        $mapParser = new MapParser($tokenParser, $mapper);
        
        $tokens = [[T_STRING, 'hello', 1], '('];
        $result = $mapParser->parse($tokens, 0);
        
        $this->assertTrue($result->isSuccess());
        $this->assertSame(['hello', 5], $result->getValue());
    }

    public function testMapWithObjectResult(): void
    {
        $tokenParser = new TokenParser(T_VARIABLE);
        $mapper = fn($token) => (object)[
            'name' => $token[1],
            'type' => 'variable'
        ];
        $mapParser = new MapParser($tokenParser, $mapper);
        
        $tokens = [[T_VARIABLE, '$obj', 1], '='];
        $result = $mapParser->parse($tokens, 0);
        
        $this->assertTrue($result->isSuccess());
        $value = $result->getValue();
        $this->assertIsObject($value);
        $this->assertSame('$obj', $value->name);
        $this->assertSame('variable', $value->type);
    }

    public function testMapWithIdentityFunction(): void
    {
        $tokenParser = new TokenParser('->');
        $mapper = fn($token) => $token; // Identity function
        $mapParser = new MapParser($tokenParser, $mapper);
        
        $tokens = ['->', 'test'];
        $result = $mapParser->parse($tokens, 0);
        
        $this->assertTrue($result->isSuccess());
        $this->assertSame('->', $result->getValue());
        $this->assertSame(1, $result->getPosition());
    }

    public function testMapWithEmptyStringResult(): void
    {
        $tokenParser = new TokenParser(T_STRING);
        $mapper = fn($token) => '';
        $mapParser = new MapParser($tokenParser, $mapper);
        
        $tokens = [[T_STRING, 'anything', 1], ';'];
        $result = $mapParser->parse($tokens, 0);
        
        $this->assertTrue($result->isSuccess());
        $this->assertSame('', $result->getValue());
    }
} 
