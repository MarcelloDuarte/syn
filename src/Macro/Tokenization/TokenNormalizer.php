<?php

declare(strict_types=1);

namespace Syn\Macro\Tokenization;

class TokenNormalizer
{
    public function tokensMatch(string $patternToken, mixed $token): bool
    {
        if (is_string($token)) {
            return $patternToken === $token;
        } elseif (is_array($token) && isset($token[1])) {
            return $patternToken === $token[1];
        }
        return false;
    }

    public function isWhitespace($token): bool
    {
        if (is_array($token)) {
            return in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT]);
        }
        return false;
    }

    public function tokensToString(array $tokens): string
    {
        $result = '';
        foreach ($tokens as $token) {
            if (is_string($token)) {
                $result .= $token;
            } elseif (is_array($token)) {
                $result .= $token[1];
            }
        }
        return $result;
    }

    public function getTokenValue(mixed $token): string
    {
        if (is_string($token)) {
            return $token;
        } elseif (is_array($token) && isset($token[1])) {
            return $token[1];
        }
        return '';
    }
} 
