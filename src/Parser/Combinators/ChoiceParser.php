<?php

declare(strict_types=1);

namespace Syn\Parser\Combinators;

use Syn\Parser\ParseResult;

class ChoiceParser extends ParserCombinator
{
    private array $parsers;

    public function __construct(array $parsers)
    {
        $this->parsers = $parsers;
    }

    public function parse(array $tokens, int $position): ParseResult
    {
        $lastError = null;

        foreach ($this->parsers as $parser) {
            $result = $parser->parse($tokens, $position);
            
            if ($result->isSuccess()) {
                return $result;
            }
            
            $lastError = $result;
        }

        return $lastError ?? ParseResult::failure($position, "No parser matched");
    }
} 
