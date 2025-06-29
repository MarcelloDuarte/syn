<?php

declare(strict_types=1);

namespace Syn\Tests\Unit\Parser\Combinators;

use PHPUnit\Framework\TestCase;
use Syn\Parser\Combinators\ParserCombinator;
use Syn\Parser\Combinators\TokenParser;
use Syn\Parser\Combinators\SequenceParser;
use Syn\Parser\Combinators\ChoiceParser;
use Syn\Parser\Combinators\ManyParser;
use Syn\Parser\Combinators\OptionalParser;
use Syn\Parser\Combinators\MapParser;
use Syn\Parser\Combinators\FilterParser;
use Syn\Parser\ParseResult;

class ParserCombinatorTest extends TestCase
{
    private ParserCombinator $parser;

    protected function setUp(): void
    {
        $this->parser = new TokenParser('->');
    }

    public function testThenReturnsSequenceParser(): void
    {
        $other = new TokenParser('(');
        $result = $this->parser->then($other);
        
        $this->assertInstanceOf(SequenceParser::class, $result);
    }

    public function testThenCombinesParsersProperly(): void
    {
        $other = new TokenParser('(');
        $sequenceParser = $this->parser->then($other);
        
        $tokens = ['->', '(', 'test'];
        $result = $sequenceParser->parse($tokens, 0);
        
        $this->assertTrue($result->isSuccess());
        $this->assertSame(['->', '('], $result->getValue());
        $this->assertSame(2, $result->getPosition());
    }

    public function testOrReturnsChoiceParser(): void
    {
        $other = new TokenParser('<-');
        $result = $this->parser->or($other);
        
        $this->assertInstanceOf(ChoiceParser::class, $result);
    }

    public function testOrCombinesParsersProperly(): void
    {
        $other = new TokenParser('<-');
        $choiceParser = $this->parser->or($other);
        
        // Test first alternative
        $tokens1 = ['->', 'test'];
        $result1 = $choiceParser->parse($tokens1, 0);
        
        $this->assertTrue($result1->isSuccess());
        $this->assertSame('->', $result1->getValue());
        
        // Test second alternative
        $tokens2 = ['<-', 'test'];
        $result2 = $choiceParser->parse($tokens2, 0);
        
        $this->assertTrue($result2->isSuccess());
        $this->assertSame('<-', $result2->getValue());
    }

    public function testManyReturnsManyParser(): void
    {
        $result = $this->parser->many();
        
        $this->assertInstanceOf(ManyParser::class, $result);
    }

    public function testManyParsesProperly(): void
    {
        $manyParser = $this->parser->many();
        
        $tokens = ['->', '->', '->', 'end'];
        $result = $manyParser->parse($tokens, 0);
        
        $this->assertTrue($result->isSuccess());
        $this->assertSame(['->', '->', '->'], $result->getValue());
        $this->assertSame(3, $result->getPosition());
    }

    public function testOptionalReturnsOptionalParser(): void
    {
        $result = $this->parser->optional();
        
        $this->assertInstanceOf(OptionalParser::class, $result);
    }

    public function testOptionalParsesProperly(): void
    {
        $optionalParser = $this->parser->optional();
        
        // Test with matching token
        $tokens1 = ['->', 'test'];
        $result1 = $optionalParser->parse($tokens1, 0);
        
        $this->assertTrue($result1->isSuccess());
        $this->assertSame('->', $result1->getValue());
        
        // Test with non-matching token
        $tokens2 = ['<-', 'test'];
        $result2 = $optionalParser->parse($tokens2, 0);
        
        $this->assertTrue($result2->isSuccess());
        $this->assertNull($result2->getValue());
        $this->assertSame(0, $result2->getPosition());
    }

    public function testMapReturnsMapParser(): void
    {
        $mapper = fn($x) => strtoupper($x);
        $result = $this->parser->map($mapper);
        
        $this->assertInstanceOf(MapParser::class, $result);
    }

    public function testMapTransformsValueProperly(): void
    {
        $mapper = fn($x) => strtoupper($x);
        $mapParser = $this->parser->map($mapper);
        
        $tokens = ['->', 'test'];
        $result = $mapParser->parse($tokens, 0);
        
        $this->assertTrue($result->isSuccess());
        $this->assertSame('->', $result->getValue()); // Note: -> uppercase is still ->
        $this->assertSame(1, $result->getPosition());
    }

    public function testMapWithComplexTransformation(): void
    {
        $tokenParser = new TokenParser(T_VARIABLE);
        $mapper = fn($token) => ['type' => 'variable', 'name' => $token[1]];
        $mapParser = $tokenParser->map($mapper);
        
        $tokens = [[T_VARIABLE, '$test', 1], '='];
        $result = $mapParser->parse($tokens, 0);
        
        $this->assertTrue($result->isSuccess());
        $this->assertSame(['type' => 'variable', 'name' => '$test'], $result->getValue());
    }

    public function testFilterReturnsFilterParser(): void
    {
        $predicate = fn($x) => strlen($x) > 1;
        $result = $this->parser->filter($predicate);
        
        $this->assertInstanceOf(FilterParser::class, $result);
    }

    public function testFilterAcceptsMatchingPredicate(): void
    {
        $predicate = fn($x) => strlen($x) > 1;
        $filterParser = $this->parser->filter($predicate);
        
        $tokens = ['->', 'test'];
        $result = $filterParser->parse($tokens, 0);
        
        $this->assertTrue($result->isSuccess());
        $this->assertSame('->', $result->getValue());
    }

    public function testFilterRejectsNonMatchingPredicate(): void
    {
        $predicate = fn($x) => strlen($x) > 5; // -> is only 2 chars
        $filterParser = $this->parser->filter($predicate);
        
        $tokens = ['->', 'test'];
        $result = $filterParser->parse($tokens, 0);
        
        $this->assertFalse($result->isSuccess());
        $this->assertSame(0, $result->getPosition());
        $this->assertStringContainsString('Predicate failed', $result->getError());
    }

    public function testChainedCombinators(): void
    {
        $parser = new TokenParser(T_VARIABLE);
        $chainedParser = $parser
            ->map(fn($token) => $token[1]) // Extract variable name
            ->filter(fn($name) => str_starts_with($name, '$')) // Must start with $
            ->optional(); // Make it optional
        
        // Test successful chain
        $tokens1 = [[T_VARIABLE, '$test', 1], '='];
        $result1 = $chainedParser->parse($tokens1, 0);
        
        $this->assertTrue($result1->isSuccess());
        $this->assertSame('$test', $result1->getValue());
        
        // Test filtered out value
        $tokens2 = [[T_VARIABLE, 'test', 1], '='];
        $result2 = $chainedParser->parse($tokens2, 0);
        
        $this->assertTrue($result2->isSuccess()); // Optional makes it succeed
        $this->assertNull($result2->getValue()); // But value is null
        $this->assertSame(0, $result2->getPosition()); // Position unchanged
    }

    public function testComplexCombination(): void
    {
        // Parse either -> or <- followed by an identifier
        $arrow = (new TokenParser('->'))
            ->or(new TokenParser('<-'));
        
        $identifier = new TokenParser(T_STRING);
        
        $combined = $arrow
            ->then($identifier)
            ->map(fn($parts) => ['arrow' => $parts[0], 'identifier' => $parts[1][1]])
            ->many();
        
        $tokens = [
            '->', [T_STRING, 'method1', 1],
            '<-', [T_STRING, 'method2', 1],
            '->', [T_STRING, 'method3', 1],
            'end'
        ];
        
        $result = $combined->parse($tokens, 0);
        
        $this->assertTrue($result->isSuccess());
        $expected = [
            ['arrow' => '->', 'identifier' => 'method1'],
            ['arrow' => '<-', 'identifier' => 'method2'],
            ['arrow' => '->', 'identifier' => 'method3']
        ];
        $this->assertSame($expected, $result->getValue());
        $this->assertSame(6, $result->getPosition());
    }
} 
