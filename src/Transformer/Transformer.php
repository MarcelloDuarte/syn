<?php

declare(strict_types=1);

namespace Syn\Transformer;

use Syn\Core\Configuration;
use Syn\Macro\MacroLoader;
use Syn\Parser\Parser;

class Transformer
{
    private Configuration $config;
    private MacroLoader $macroLoader;
    private Parser $parser;

    public function __construct(Configuration $config, ?MacroLoader $macroLoader = null)
    {
        $this->config = $config;
        $this->macroLoader = $macroLoader ?? new MacroLoader();
        $this->parser = new Parser();
    }

    public function transform(string $code, string $sourceFile): string
    {
        // Ensure we have complete PHP code for proper tokenization
        if (!str_starts_with(trim($code), '<?php')) {
            $code = '<?php ' . $code;
        }
        
        $tokens = $this->parser->tokenize($code);
        
        // Preserve the opening tag if present
        $openingTag = '';
        if (is_array($tokens[0]) && $tokens[0][0] === T_OPEN_TAG) {
            $openingTag = $tokens[0][1];
            array_shift($tokens);
        }
        
        $transformedTokens = $this->applyMacros($tokens, $sourceFile);
        
        return $openingTag . $this->tokensToString($transformedTokens);
    }

    private function applyMacros(array $tokens, string $sourceFile): array
    {
        $maxIterations = 3; // Safety limit to prevent infinite loops
        $iteration = 0;
        
        while ($iteration < $maxIterations) {
            $iteration++;
            $hasChanges = false;
            
            // Get all loaded macros
            $macros = $this->macroLoader->getMacros();
            
            foreach ($macros as $macro) {
                $newTokens = [];
                $i = 0;
                
                while ($i < count($tokens)) {
                    // Try to find macros for token sequences (multi-token patterns)
                    $matchingMacros = $this->macroLoader->findMacrosForTokenSequence($tokens, $i);
                    if (empty($matchingMacros)) {
                        // Fall back to single token matching
                        $token = $tokens[$i];
                        $singleTokenMacros = $this->macroLoader->findMacrosForToken($token);
                        // Convert to new format
                        foreach ($singleTokenMacros as $singleMacro) {
                            // Calculate the correct number of tokens to consume
                            $patternTokens = $this->macroLoader->tokenizePattern($singleMacro->getPattern());
                            $consumedCount = count($patternTokens);
                            
                            $matchingMacros[] = [
                                'macro' => $singleMacro,
                                'captures' => [],
                                'consumed' => $consumedCount
                            ];
                        }
                    }
                    
                    $macroApplied = false;
                    foreach ($matchingMacros as $macroMatch) {
                        $macro = $macroMatch['macro'];
                        $captures = $macroMatch['captures'];
                        $consumed = $macroMatch['consumed'];
                        
                        $replacementTokens = $this->applyMacroWithCaptures($macro, $captures);
                        if ($replacementTokens !== null) {
                            // Add replacement tokens
                            foreach ($replacementTokens as $token) {
                                $newTokens[] = $token;
                            }
                            
                            // Skip the consumed tokens
                            $i += $consumed;
                            $hasChanges = true;
                            $macroApplied = true;
                            break;
                        }
                    }
                    
                    if (!$macroApplied) {
                        // No match, keep the original token
                        $newTokens[] = $tokens[$i];
                        $i++;
                    }
                }
                
                $tokens = $newTokens;
            }
            
            // If no changes were made, we're done
            if (!$hasChanges) {
                break;
            }
        }
        
        return $tokens;
    }

    private function applyMacroWithCaptures($macro, array $captures): ?array
    {
        if ($macro->hasCaptures()) {
            return $this->generateReplacementWithCaptures($macro, $captures);
        } else {
            // Use the old logic for simple replacements
            $replacementTokens = $this->parseReplacement($macro->getReplacement());
            return $this->filterTokens($replacementTokens);
        }
    }

    private function generateReplacementWithCaptures($macro, array $captures): array
    {
        $parsedReplacement = $macro->getParsedReplacement();
        $result = [];
        
        for ($i = 0; $i < count($parsedReplacement); $i++) {
            $token = $parsedReplacement[$i];
            
            if (is_string($token) && str_starts_with($token, '__VAR_') && str_ends_with($token, '__')) {
                // Extract variable name: __VAR_condition__ -> condition
                $varName = substr($token, 6, -2); // Remove __VAR_ and __
                
                if (isset($captures[$varName])) {
                    $capturedTokens = $captures[$varName];
                    
                    // Check if the captured tokens start with whitespace
                    $startsWithWhitespace = false;
                    if (!empty($capturedTokens)) {
                        $firstToken = $capturedTokens[0];
                        if (is_array($firstToken) && $firstToken[0] === T_WHITESPACE) {
                            $startsWithWhitespace = true;
                        }
                    }
                    
                    // If captured tokens start with whitespace, remove preceding space from result
                    if ($startsWithWhitespace && !empty($result)) {
                        $lastToken = end($result);
                        if ($lastToken === ' ') {
                            array_pop($result);
                        }
                    }
                    
                    // Insert captured tokens
                    foreach ($capturedTokens as $capturedToken) {
                        $result[] = $capturedToken;
                    }
                    
                    // Check if the captured tokens end with whitespace and next token is a space
                    $endsWithWhitespace = false;
                    if (!empty($capturedTokens)) {
                        $lastToken = end($capturedTokens);
                        if (is_array($lastToken) && $lastToken[0] === T_WHITESPACE) {
                            $endsWithWhitespace = true;
                        }
                    }
                    
                    // If captured tokens end with whitespace, check if next token is a space and skip it
                    if ($endsWithWhitespace && $i + 1 < count($parsedReplacement)) {
                        $nextToken = $parsedReplacement[$i + 1];
                        if ($nextToken === ' ') {
                            $i++; // Skip the next space token
                        }
                    }
                } else {
                    // Variable not found, keep as is (shouldn't happen in normal cases)
                    $result[] = $token;
                }
            } else {
                $result[] = $token;
            }
        }
        
        return $result;
    }

    private function filterTokens(array $tokens): array
    {
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

    private function parseReplacement(string $replacement): array
    {
        // Convert replacement string back to tokens
        return token_get_all($replacement);
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

    public function setMacroLoader(MacroLoader $macroLoader): void
    {
        $this->macroLoader = $macroLoader;
    }

    public function getMacroLoader(): MacroLoader
    {
        return $this->macroLoader;
    }
} 

