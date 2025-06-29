<?php

declare(strict_types=1);

namespace Syn\Parser\Combinators;

use Syn\Parser\ParseResult;

abstract class ParserCombinator
{
    abstract public function parse(array $tokens, int $position): ParseResult;

    public function then(ParserCombinator $other): SequenceParser
    {
        return new SequenceParser([$this, $other]);
    }

    public function or(ParserCombinator $other): ChoiceParser
    {
        return new ChoiceParser([$this, $other]);
    }

    public function many(): ManyParser
    {
        return new ManyParser($this);
    }

    public function optional(): OptionalParser
    {
        return new OptionalParser($this);
    }

    public function map(callable $fn): MapParser
    {
        return new MapParser($this, $fn);
    }

    public function filter(callable $predicate): FilterParser
    {
        return new FilterParser($this, $predicate);
    }
} 
