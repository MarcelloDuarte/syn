<?php

declare(strict_types=1);

namespace Syn\Macro\Storage;

use Syn\Parser\MacroDefinition;

class MacroRegistry
{
    private array $macros = [];

    public function addMacro(MacroDefinition $macro): void
    {
        $this->macros[] = $macro;
    }

    public function getMacros(): array
    {
        return $this->macros;
    }

    public function clear(): void
    {
        $this->macros = [];
    }

    public function count(): int
    {
        return count($this->macros);
    }

    public function isEmpty(): bool
    {
        return empty($this->macros);
    }
} 
