<?php

declare(strict_types=1);

namespace Syn\Macro;

use Syn\Parser\MacroDefinition;
use Syn\Parser\Parser;
use Syn\Parser\ParserException;
use Symfony\Component\Finder\Finder;

class MacroLoader
{
    private Parser $parser;
    private array $macros = [];

    public function __construct()
    {
        $this->parser = new Parser();
    }

    public function loadFromFile(string $filePath): void
    {
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException("Macro file not found: {$filePath}");
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new \RuntimeException("Could not read macro file: {$filePath}");
        }

        $this->loadFromString($content, $filePath);
    }

    public function loadFromDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            throw new \InvalidArgumentException("Macro directory not found: {$directory}");
        }

        $finder = new Finder();
        $finder->files()
            ->in($directory)
            ->name('*.syn');

        foreach ($finder as $file) {
            $this->loadFromFile($file->getPathname());
        }
    }

    public function loadFromString(string $content, ?string $sourceFile = null): void
    {
        $lines = explode("\n", $content);
        $currentLine = 1;

        foreach ($lines as $line) {
            $line = trim($line);
            
            if (empty($line) || str_starts_with($line, '//') || str_starts_with($line, '#')) {
                $currentLine++;
                continue;
            }

            if (str_contains($line, '$(macro)')) {
                $macro = $this->parseMacroFromLine($line, $sourceFile, $currentLine);
                if ($macro) {
                    $this->addMacro($macro);
                }
            }

            $currentLine++;
        }
    }

    public function addMacro(MacroDefinition $macro): void
    {
        $this->macros[] = $macro;
        
        // Sort by priority (higher priority first)
        usort($this->macros, function (MacroDefinition $a, MacroDefinition $b) {
            return $b->getPriority() - $a->getPriority();
        });
    }

    public function getMacros(): array
    {
        return $this->macros;
    }

    public function clear(): void
    {
        $this->macros = [];
    }

    private function parseMacroFromLine(string $line, ?string $sourceFile, int $lineNumber): ?MacroDefinition
    {
        try {
            // Handle nested braces by using a more sophisticated parsing approach
            if (preg_match('/\$\s*\(\s*macro\s*\)\s*\{/', $line, $matches, PREG_OFFSET_CAPTURE)) {
                $startPos = $matches[0][1] + strlen($matches[0][0]);
                
                // Find the matching closing brace for the pattern
                $patternEnd = $this->findMatchingBrace($line, $startPos - 1);
                if ($patternEnd === -1) {
                    return null;
                }
                
                $pattern = trim(substr($line, $startPos, $patternEnd - $startPos));
                
                // Look for the >> separator
                $separatorPos = strpos($line, '>>', $patternEnd);
                if ($separatorPos === false) {
                    return null;
                }
                
                // Find the opening brace for the replacement
                $replacementStart = strpos($line, '{', $separatorPos);
                if ($replacementStart === false) {
                    return null;
                }
                
                // Find the matching closing brace for the replacement
                $replacementEnd = $this->findMatchingBrace($line, $replacementStart);
                if ($replacementEnd === -1) {
                    return null;
                }
                
                $replacement = trim(substr($line, $replacementStart + 1, $replacementEnd - $replacementStart - 1));
                
                return new MacroDefinition(
                    $pattern,
                    $replacement,
                    [],
                    null,
                    $sourceFile,
                    $lineNumber
                );
            }
        } catch (ParserException $e) {
            // Log error but continue processing other macros
        }

        return null;
    }
    
    private function findMatchingBrace(string $text, int $startPos): int
    {
        $depth = 0;
        $len = strlen($text);
        
        for ($i = $startPos; $i < $len; $i++) {
            $char = $text[$i];
            
            if ($char === '{') {
                $depth++;
            } elseif ($char === '}') {
                $depth--;
                if ($depth === 0) {
                    return $i;
                }
            }
        }
        
        return -1; // No matching brace found
    }

    public function findMacrosForToken(mixed $token): array
    {
        $matchingMacros = [];
        
        foreach ($this->macros as $macro) {
            if ($this->macroMatchesToken($macro, $token)) {
                $matchingMacros[] = $macro;
            }
        }
        
        return $matchingMacros;
    }

    private function macroMatchesToken(MacroDefinition $macro, mixed $token): bool
    {
        $pattern = $macro->getPattern();
        
        // Get the first token of the pattern
        $patternTokens = $this->tokenizePattern($pattern);
        if (empty($patternTokens)) {
            return false;
        }
        
        $firstPatternToken = $patternTokens[0];
        
        // Handle different token types
        if (is_string($token)) {
            // String token (like operators, keywords)
            return $firstPatternToken === $token;
        } elseif (is_array($token) && isset($token[1])) {
            // Array token (like variables, strings, etc.)
            return $firstPatternToken === $token[1];
        }
        
        return false;
    }

    public function findMacrosForTokenSequence(array $tokens, int $position): array
    {
        $matchingMacros = [];
        
        // First check if there's a token at this position
        if ($position >= count($tokens)) {
            return $matchingMacros;
        }
        
        $currentToken = $tokens[$position];
        $currentTokenStr = is_array($currentToken) ? $currentToken[1] : $currentToken;
        
        foreach ($this->macros as $macro) {
            // Only try to match if the first token of the pattern matches the current token
            $patternTokens = [];
            if ($macro->hasCaptures()) {
                $patternTokens = $macro->getParsedPattern();
            } else {
                $patternTokens = $this->tokenizePattern($macro->getPattern());
            }
            
            if (empty($patternTokens)) {
                continue;
            }
            
            $firstPatternToken = $patternTokens[0];
            
            // Skip if the first token doesn't match
            if (!$this->tokensMatch($firstPatternToken, $currentToken)) {
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
                    $patternTokens = $this->tokenizePattern($macro->getPattern());
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
        $patternTokens = $this->tokenizePattern($pattern);
        
        // Check if we have enough tokens to match the pattern
        if ($position + count($patternTokens) > count($tokens)) {
            return false;
        }
        
        // Match each token in the pattern
        for ($i = 0; $i < count($patternTokens); $i++) {
            $patternToken = $patternTokens[$i];
            $currentToken = $tokens[$position + $i];
            
            if (!$this->tokensMatch($patternToken, $currentToken)) {
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
                    if ($this->isClosingDelimiter($nextPatternToken)) {
                        // We're capturing everything until the closing delimiter
                        // For balanced constructs, we want to capture ALL tokens including whitespace
                        $captureTokens = $this->captureUntilClosing($tokens, $currentPos, $nextPatternToken);
                        $currentPos += count($captureTokens);
                        
                        // Skip to the closing delimiter
                        while ($currentPos < count($tokens)) {
                            $currentToken = $tokens[$currentPos];
                            $currentTokenStr = is_array($currentToken) ? $currentToken[1] : $currentToken;
                            if ($currentTokenStr === $nextPatternToken) {
                                break;
                            }
                            $currentPos++;
                        }
                    } else {
                        // For non-balanced captures, skip whitespace first
                        while ($currentPos < count($tokens) && $this->isWhitespace($tokens[$currentPos])) {
                            $currentPos++;
                        }
                        
                        // Capture tokens until we find the next pattern token
                        while ($currentPos < count($tokens)) {
                            $currentToken = $tokens[$currentPos];
                            
                            // Check if current token matches the next pattern token
                            if ($this->tokensMatch($nextPatternToken, $currentToken)) {
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
                while ($currentPos < count($tokens) && $this->isWhitespace($tokens[$currentPos]) && $patternToken !== ' ') {
                    $currentPos++;
                }
                
                // Regular token matching
                if ($currentPos >= count($tokens)) {
                    return null; // Not enough tokens
                }
                
                $currentToken = $tokens[$currentPos];
                $currentTokenStr = is_array($currentToken) ? $currentToken[1] : $currentToken;
                
                if (!$this->tokensMatch($patternToken, $currentToken)) {
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
    
    private function isClosingDelimiter(string $token): bool
    {
        return in_array($token, [')', '}', ']']);
    }
    
    private function captureUntilClosing(array $tokens, int $start, string $closeChar): array
    {
        $captured = [];
        $openChar = $this->getOpeningDelimiter($closeChar);
        $depth = 0;
        $i = $start;
        
        while ($i < count($tokens)) {
            $token = $tokens[$i];
            $tokenStr = is_array($token) ? $token[1] : $token;
            
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
    
    private function getOpeningDelimiter(string $closingToken): string
    {
        $delimiters = [
            ')' => '(',
            '}' => '{',
            ']' => '['
        ];
        
        return $delimiters[$closingToken] ?? '';
    }
    
    private function tokensToString(array $tokens): string
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

    public function tokenizePattern(string $pattern): array
    {
        // Use PHP's tokenizer to properly tokenize the pattern
        // This ensures we get the same tokens as the actual PHP code
        $tokens = token_get_all('<?php ' . $pattern);
        
        // Remove the opening tag and convert to simple format
        $result = [];
        foreach ($tokens as $token) {
            if (is_array($token)) {
                // Skip T_OPEN_TAG
                if ($token[0] === T_OPEN_TAG) {
                    continue;
                }
                $result[] = $token[1];
            } else {
                $result[] = $token;
            }
        }
        
        return $result;
    }

    private function tokensMatch(string $patternToken, mixed $token): bool
    {
        if (is_string($token)) {
            return $patternToken === $token;
        } elseif (is_array($token) && isset($token[1])) {
            return $patternToken === $token[1];
        }
        return false;
    }

    private function isWhitespace($token): bool
    {
        if (is_array($token)) {
            return in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT]);
        }
        return false;
    }
} 

