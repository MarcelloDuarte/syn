<?php

declare(strict_types=1);

namespace Syn\Macro\Matching;

use Syn\Parser\MacroDefinition;
use Syn\Macro\Tokenization\PatternTokenizer;
use Syn\Macro\Tokenization\TokenNormalizer;
use Syn\Macro\Capture\BalancedCapture;
use Syn\Macro\Capture\DelimiterMatcher;

class SequenceMatcher
{
    private PatternTokenizer $patternTokenizer;
    private TokenNormalizer $tokenNormalizer;
    private BalancedCapture $balancedCapture;
    private DelimiterMatcher $delimiterMatcher;

    public function __construct(
        PatternTokenizer $patternTokenizer,
        TokenNormalizer $tokenNormalizer,
        BalancedCapture $balancedCapture,
        DelimiterMatcher $delimiterMatcher
    ) {
        $this->patternTokenizer = $patternTokenizer;
        $this->tokenNormalizer = $tokenNormalizer;
        $this->balancedCapture = $balancedCapture;
        $this->delimiterMatcher = $delimiterMatcher;
    }

    public function findMacrosForTokenSequence(array $macros, array $tokens, int $position): array
    {
        $matchingMacros = [];
        
        // First check if there's a token at this position
        if ($position >= count($tokens)) {
            return $matchingMacros;
        }
        
        $currentToken = $tokens[$position];
        
        foreach ($macros as $macro) {
            // Only try to match if the first token of the pattern matches the current token
            $patternTokens = [];
            if ($macro->hasCaptures()) {
                $patternTokens = $macro->getParsedPattern();
            } else {
                $patternTokens = $this->patternTokenizer->tokenizePattern($macro->getPattern());
            }
            
            if (empty($patternTokens)) {
                continue;
            }
            
            $firstPatternToken = $patternTokens[0];
            
            // Skip if the first token doesn't match
            if (!$this->tokenNormalizer->tokensMatch($firstPatternToken, $currentToken)) {
                continue;
            }
            
            if ($macro->hasCaptures()) {
                $matchResult = $this->matchPatternWithCaptures($macro, $tokens, $position);
                if ($matchResult !== null) {
                    $matchingMacros[] = [
                        'macro' => $macro,
                        'captures' => $matchResult['captures'],
                        'consumed' => $matchResult['consumed']
                    ];
                }
            } else {
                if ($this->macroMatchesTokenSequence($macro, $tokens, $position)) {
                    $patternTokens = $this->patternTokenizer->tokenizePattern($macro->getPattern());
                    $matchingMacros[] = [
                        'macro' => $macro,
                        'captures' => [],
                        'consumed' => count($patternTokens)
                    ];
                }
            }
        }
        
        return $matchingMacros;
    }

    private function macroMatchesTokenSequence(MacroDefinition $macro, array $tokens, int $position): bool
    {
        // Use parsed pattern if available (for macros with captures)
        if ($macro->hasCaptures()) {
            return $this->matchPatternWithCaptures($macro, $tokens, $position) !== null;
        }
        
        // Fall back to original logic for simple patterns
        $pattern = $macro->getPattern();
        $patternTokens = $this->patternTokenizer->tokenizePattern($pattern);
        
        // Check if we have enough tokens to match the pattern
        if ($position + count($patternTokens) > count($tokens)) {
            return false;
        }
        
        // Match each token in the pattern
        for ($i = 0; $i < count($patternTokens); $i++) {
            $patternToken = $patternTokens[$i];
            $currentToken = $tokens[$position + $i];
            
            if (!$this->tokenNormalizer->tokensMatch($patternToken, $currentToken)) {
                return false;
            }
        }
        
        return true;
    }

    private function matchPatternWithCaptures(MacroDefinition $macro, array $tokens, int $position): ?array
    {
        $parsedPattern = $macro->getParsedPattern();
        $captures = $macro->getCaptures();
        $capturedValues = [];
        $currentPos = $position;
        $patternPos = 0;
        
        while ($patternPos < count($parsedPattern) && $currentPos < count($tokens)) {
            $patternToken = $parsedPattern[$patternPos];
            
            // Check if this is a capture placeholder
            if (is_string($patternToken) && str_starts_with($patternToken, '__CAPTURE_')) {
                $captureName = $captures[$patternToken];
                
                // Find the end of this capture by looking for the next pattern token
                $nextPatternPos = $patternPos + 1;
                $captureTokens = [];
                
                if ($nextPatternPos < count($parsedPattern)) {
                    $nextPatternToken = $parsedPattern[$nextPatternPos];
                    
                    // Handle balanced constructs generically
                    if ($this->delimiterMatcher->isClosingDelimiter($nextPatternToken)) {
                        // We're capturing everything until the closing delimiter
                        // For balanced constructs, we want to capture ALL tokens including whitespace
                        $captureTokens = $this->balancedCapture->captureUntilClosing($tokens, $currentPos, $nextPatternToken);
                        $currentPos += count($captureTokens);
                        
                        // Skip to the closing delimiter
                        while ($currentPos < count($tokens)) {
                            $currentToken = $tokens[$currentPos];
                            $currentTokenStr = $this->tokenNormalizer->getTokenValue($currentToken);
                            if ($currentTokenStr === $nextPatternToken) {
                                break;
                            }
                            $currentPos++;
                        }
                    } else {
                        // For non-balanced captures, skip whitespace first
                        while ($currentPos < count($tokens) && $this->tokenNormalizer->isWhitespace($tokens[$currentPos])) {
                            $currentPos++;
                        }
                        
                        // Capture tokens until we find the next pattern token
                        while ($currentPos < count($tokens)) {
                            $currentToken = $tokens[$currentPos];
                            
                            // Check if current token matches the next pattern token
                            if ($this->tokenNormalizer->tokensMatch($nextPatternToken, $currentToken)) {
                                break;
                            }
                            
                            $captureTokens[] = $currentToken;
                            $currentPos++;
                        }
                    }
                } else {
                    // This is the last capture - capture remaining tokens
                    while ($currentPos < count($tokens)) {
                        $captureTokens[] = $tokens[$currentPos];
                        $currentPos++;
                    }
                }
                
                $capturedValues[$captureName] = $captureTokens;
                $patternPos++;
            } else {
                // Skip whitespace tokens in the input unless the pattern explicitly expects whitespace
                while ($currentPos < count($tokens) && $this->tokenNormalizer->isWhitespace($tokens[$currentPos]) && $patternToken !== ' ') {
                    $currentPos++;
                }
                
                // Regular token matching
                if ($currentPos >= count($tokens)) {
                    return null; // Not enough tokens
                }
                
                $currentToken = $tokens[$currentPos];
                
                if (!$this->tokenNormalizer->tokensMatch($patternToken, $currentToken)) {
                    return null; // Token mismatch
                }
                
                $currentPos++;
                $patternPos++;
            }
        }
        
        // Check if we've matched the entire pattern
        if ($patternPos < count($parsedPattern)) {
            return null; // Pattern not fully matched
        }
        
        return [
            'captures' => $capturedValues,
            'consumed' => $currentPos - $position
        ];
    }
} 
