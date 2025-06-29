<?php

declare(strict_types=1);

namespace Syn\Parser\Combinators;

use Syn\Parser\ParseResult;

class ManyParser extends ParserCombinator
{
    private ParserCombinator $parser;

    public function __construct(ParserCombinator $parser)
    {
        $this->parser = $parser;
    }

    public function parse(array $tokens, int $position): ParseResult
    {
        $values = [];
        $currentPosition = $position;

        while (true) {
            $result = $this->parser->parse($tokens, $currentPosition);
            
            if ($result->isFailure()) {
                break;
            }
            
            $values[] = $result->getValue();
            $currentPosition = $result->getPosition();
        }

        return ParseResult::success($values, $currentPosition);
    }
} 
