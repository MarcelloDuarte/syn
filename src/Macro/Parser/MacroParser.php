<?php

declare(strict_types=1);

namespace Syn\Macro\Parser;

use Syn\Parser\MacroDefinition;
use Syn\Macro\Capture\DelimiterMatcher;

class MacroParser
{
    private DelimiterMatcher $delimiterMatcher;

    public function __construct(DelimiterMatcher $delimiterMatcher)
    {
        $this->delimiterMatcher = $delimiterMatcher;
    }

    public function parseMacroFromLine(string $line, ?string $sourceFile, int $lineNumber): ?MacroDefinition
    {
        // Skip empty lines and comments
        $line = trim($line);
        if (empty($line) || str_starts_with($line, '#') || str_starts_with($line, '//')) {
            return null;
        }

        // Look for the pattern: $(macro) { pattern } >> { replacement }
        if (!preg_match('/^\$\(macro\)\s*\{/', $line)) {
            return null;
        }

        // Find the pattern part
        $patternStart = strpos($line, '{');
        if ($patternStart === false) {
            return null;
        }

        $patternEnd = $this->delimiterMatcher->findMatchingBrace($line, $patternStart);
        if ($patternEnd === -1) {
            return null;
        }

        $pattern = trim(substr($line, $patternStart + 1, $patternEnd - $patternStart - 1));

        // Find the replacement part
        $remaining = trim(substr($line, $patternEnd + 1));
        if (!str_starts_with($remaining, '>>')) {
            return null;
        }

        $remaining = trim(substr($remaining, 2));
        if (!str_starts_with($remaining, '{')) {
            return null;
        }

        $replacementStart = strpos($remaining, '{');
        $replacementEnd = $this->delimiterMatcher->findMatchingBrace($remaining, $replacementStart);
        if ($replacementEnd === -1) {
            return null;
        }

        $replacement = trim(substr($remaining, $replacementStart + 1, $replacementEnd - $replacementStart - 1));

        return new MacroDefinition(
            $pattern,
            $replacement,
            [],
            null,
            $sourceFile,
            $lineNumber
        );
    }
} 
