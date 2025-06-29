<?php

declare(strict_types=1);

namespace Syn\Macro\Loading;

use Syn\Macro\Parser\MacroParser;
use Syn\Macro\Storage\MacroRegistry;

class FileLoader
{
    private MacroParser $macroParser;
    private MacroRegistry $registry;

    public function __construct(MacroParser $macroParser, MacroRegistry $registry)
    {
        $this->macroParser = $macroParser;
        $this->registry = $registry;
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

    public function loadFromString(string $content, ?string $sourceFile = null): void
    {
        $lines = explode("\n", $content);
        
        foreach ($lines as $lineNumber => $line) {
            $macro = $this->macroParser->parseMacroFromLine($line, $sourceFile, $lineNumber + 1);
            if ($macro !== null) {
                $this->registry->addMacro($macro);
            }
        }
    }
} 
