<?php

declare(strict_types=1);

namespace Syn\Macro\Capture;

class DelimiterMatcher
{
    public function isClosingDelimiter(string $token): bool
    {
        return in_array($token, [')', '}', ']']);
    }

    public function getOpeningDelimiter(string $closingToken): string
    {
        $delimiters = [
            ')' => '(',
            '}' => '{',
            ']' => '['
        ];
        
        return $delimiters[$closingToken] ?? '';
    }

    public function findMatchingBrace(string $text, int $startPos): int
    {
        $braceCount = 1;
        $length = strlen($text);
        
        for ($i = $startPos + 1; $i < $length; $i++) {
            if ($text[$i] === '{') {
                $braceCount++;
            } elseif ($text[$i] === '}') {
                $braceCount--;
                if ($braceCount === 0) {
                    return $i;
                }
            }
        }
        
        return -1; // No matching brace found
    }
} 
