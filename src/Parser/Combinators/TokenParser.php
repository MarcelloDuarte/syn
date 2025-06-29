<?php

declare(strict_types=1);

namespace Syn\Parser\Combinators;

use Syn\Parser\ParseResult;

class TokenParser extends ParserCombinator
{
    private int|string $tokenType;
    private ?string $tokenValue;

    public function __construct(int|string $tokenType, ?string $tokenValue = null)
    {
        $this->tokenType = $tokenType;
        $this->tokenValue = $tokenValue;
    }

    public function parse(array $tokens, int $position): ParseResult
    {
        if ($position >= count($tokens)) {
            return ParseResult::failure($position, "Unexpected end of input");
        }

        $token = $tokens[$position];

        // Handle string tokens (single characters)
        if (is_string($this->tokenType)) {
            if (is_string($token) && $token === $this->tokenType) {
                return ParseResult::success($token, $position + 1);
            }
            return ParseResult::failure($position, "Expected '{$this->tokenType}', got " . $this->tokenToString($token));
        }

        // Handle token types
        if (!is_array($token) || $token[0] !== $this->tokenType) {
            return ParseResult::failure($position, "Expected token type {$this->tokenType}, got " . $this->tokenToString($token));
        }

        // If a specific value is expected, check it
        if ($this->tokenValue !== null && $token[1] !== $this->tokenValue) {
            return ParseResult::failure($position, "Expected '{$this->tokenValue}', got '{$token[1]}'");
        }

        return ParseResult::success($token, $position + 1);
    }

    private function tokenToString(mixed $token): string
    {
        if (is_string($token)) {
            return "'{$token}'";
        }
        if (is_array($token)) {
            return "token({$token[0]}, '{$token[1]}')";
        }
        return var_export($token, true);
    }
} 
