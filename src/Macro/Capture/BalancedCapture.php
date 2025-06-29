<?php

declare(strict_types=1);

namespace Syn\Macro\Capture;

use Syn\Macro\Tokenization\TokenNormalizer;

class BalancedCapture
{
    private DelimiterMatcher $delimiterMatcher;
    private TokenNormalizer $tokenNormalizer;

    public function __construct(
        DelimiterMatcher $delimiterMatcher,
        TokenNormalizer $tokenNormalizer
    ) {
        $this->delimiterMatcher = $delimiterMatcher;
        $this->tokenNormalizer = $tokenNormalizer;
    }

    public function captureUntilClosing(array $tokens, int $start, string $closeChar): array
    {
        $captured = [];
        $openChar = $this->delimiterMatcher->getOpeningDelimiter($closeChar);
        $depth = 0;
        $i = $start;
        
        while ($i < count($tokens)) {
            $token = $tokens[$i];
            $tokenStr = $this->tokenNormalizer->getTokenValue($token);
            
            if ($tokenStr === $openChar) {
                $depth++;
                $captured[] = $token;
            } elseif ($tokenStr === $closeChar) {
                if ($depth === 0) {
                    // Found the matching closing delimiter
                    break;
                } else {
                    $depth--;
                    $captured[] = $token;
                }
            } else {
                $captured[] = $token;
            }
            
            $i++;
        }
        
        return $captured;
    }
} 
