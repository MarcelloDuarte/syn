<?php

declare(strict_types=1);

namespace Syn\Parser\Combinators;

use Syn\Parser\ParseResult;

class OptionalParser extends ParserCombinator
{
    private ParserCombinator $parser;

    public function __construct(ParserCombinator $parser)
    {
        $this->parser = $parser;
    }

    public function parse(array $tokens, int $position): ParseResult
    {
        $result = $this->parser->parse($tokens, $position);
        
        if ($result->isSuccess()) {
            return $result;
        }
        
        return ParseResult::success(null, $position);
    }
} 
