<?php

declare(strict_types=1);

namespace Syn\Parser\Combinators;

use Syn\Parser\ParseResult;

class SequenceParser extends ParserCombinator
{
    private array $parsers;

    public function __construct(array $parsers)
    {
        $this->parsers = $parsers;
    }

    public function parse(array $tokens, int $position): ParseResult
    {
        $values = [];
        $currentPosition = $position;

        foreach ($this->parsers as $parser) {
            $result = $parser->parse($tokens, $currentPosition);
            
            if ($result->isFailure()) {
                return $result;
            }
            
            $values[] = $result->getValue();
            $currentPosition = $result->getPosition();
        }

        return ParseResult::success($values, $currentPosition);
    }
} 
