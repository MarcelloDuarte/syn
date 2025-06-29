<?php

declare(strict_types=1);

namespace Syn\Macro;

use Syn\Parser\MacroDefinition;
use Syn\Macro\Storage\MacroRegistry;
use Syn\Macro\Loading\FileLoader;
use Syn\Macro\Loading\DirectoryLoader;
use Syn\Macro\Parser\MacroParser;
use Syn\Macro\Tokenization\PatternTokenizer;
use Syn\Macro\Tokenization\TokenNormalizer;
use Syn\Macro\Matching\SequenceMatcher;
use Syn\Macro\Capture\DelimiterMatcher;
use Syn\Macro\Capture\BalancedCapture;

class MacroLoader
{
    private MacroRegistry $registry;
    private FileLoader $fileLoader;
    private DirectoryLoader $directoryLoader;
    private PatternTokenizer $patternTokenizer;
    private TokenNormalizer $tokenNormalizer;
    private SequenceMatcher $sequenceMatcher;

    public function __construct()
    {
        // Build the dependency graph
        $this->registry = new MacroRegistry();
        $this->patternTokenizer = new PatternTokenizer();
        $this->tokenNormalizer = new TokenNormalizer();
        
        $delimiterMatcher = new DelimiterMatcher();
        $balancedCapture = new BalancedCapture($delimiterMatcher, $this->tokenNormalizer);
        
        $this->sequenceMatcher = new SequenceMatcher(
            $this->patternTokenizer,
            $this->tokenNormalizer,
            $balancedCapture,
            $delimiterMatcher
        );
        
        $macroParser = new MacroParser($delimiterMatcher);
        $this->fileLoader = new FileLoader($macroParser, $this->registry);
        $this->directoryLoader = new DirectoryLoader($this->fileLoader);
    }

    public function loadFromFile(string $filePath): void
    {
        $this->fileLoader->loadFromFile($filePath);
    }

    public function loadFromDirectory(string $directory): void
    {
        $this->directoryLoader->loadFromDirectory($directory);
    }

    public function loadFromString(string $content, ?string $sourceFile = null): void
    {
        $this->fileLoader->loadFromString($content, $sourceFile);
    }

    public function addMacro(MacroDefinition $macro): void
    {
        $this->registry->addMacro($macro);
    }

    public function getMacros(): array
    {
        return $this->registry->getMacros();
    }

    public function clear(): void
    {
        $this->registry->clear();
    }

    public function findMacrosForToken(mixed $token): array
    {
        $matchingMacros = [];
        
        foreach ($this->registry->getMacros() as $macro) {
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
        $patternTokens = $this->patternTokenizer->tokenizePattern($pattern);
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
        return $this->sequenceMatcher->findMacrosForTokenSequence(
            $this->registry->getMacros(),
            $tokens,
            $position
        );
    }

    public function tokenizePattern(string $pattern): array
    {
        return $this->patternTokenizer->tokenizePattern($pattern);
    }
} 

