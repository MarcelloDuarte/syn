<?php

declare(strict_types=1);

namespace Syn\Parser\Combinators;

use Syn\Parser\ParseResult;

class FilterParser extends ParserCombinator
{
    private ParserCombinator $parser;
    private \Closure $predicate;

    public function __construct(ParserCombinator $parser, callable $predicate)
    {
        $this->parser = $parser;
        $this->predicate = \Closure::fromCallable($predicate);
    }

    public function parse(array $tokens, int $position): ParseResult
    {
        $result = $this->parser->parse($tokens, $position);
        
        if ($result->isFailure()) {
            return $result;
        }
        
        $value = $result->getValue();
        if (($this->predicate)($value)) {
            return $result;
        }
        
        return ParseResult::failure($position, "Predicate failed for value: " . var_export($value, true));
    }
} 
