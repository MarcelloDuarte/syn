<?php

declare(strict_types=1);

namespace Syn\Parser\Combinators;

use Syn\Parser\ParseResult;

class MapParser extends ParserCombinator
{
    private ParserCombinator $parser;
    private \Closure $fn;

    public function __construct(ParserCombinator $parser, callable $fn)
    {
        $this->parser = $parser;
        $this->fn = \Closure::fromCallable($fn);
    }

    public function parse(array $tokens, int $position): ParseResult
    {
        $result = $this->parser->parse($tokens, $position);
        
        if ($result->isFailure()) {
            return $result;
        }
        
        return $result->map($this->fn);
    }
} 
