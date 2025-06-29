<?php

declare(strict_types=1);

namespace Syn\Macro\Tokenization;

class PatternTokenizer
{
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

    public function tokenizeGeneric(string $input): array
    {
        // Generic tokenizer that handles capture placeholders and delimiters
        $tokens = [];
        $i = 0;
        $len = strlen($input);
        $currentToken = '';
        
        while ($i < $len) {
            $char = $input[$i];
            
            // Check for capture placeholders
            if ($char === '_' && substr($input, $i, 10) === '__CAPTURE_') {
                // Save current token if any
                if (!empty($currentToken)) {
                    $tokens[] = trim($currentToken);
                    $currentToken = '';
                }
                
                // Find the end of the capture placeholder
                $endPos = strpos($input, '__', $i + 10);
                if ($endPos !== false) {
                    $captureToken = substr($input, $i, $endPos - $i + 2);
                    $tokens[] = $captureToken;
                    $i = $endPos + 2;
                    continue;
                }
            }
            
            // Handle delimiters and whitespace
            if (in_array($char, ['(', ')', '{', '}', '[', ']', ' ', "\t", "\n"])) {
                if (!empty($currentToken)) {
                    $tokens[] = trim($currentToken);
                    $currentToken = '';
                }
                if (!in_array($char, [' ', "\t", "\n"])) {
                    $tokens[] = $char;
                }
            } else {
                $currentToken .= $char;
            }
            
            $i++;
        }
        
        if (!empty($currentToken)) {
            $tokens[] = trim($currentToken);
        }
        
        return array_filter($tokens, fn($token) => $token !== '');
    }
} 
