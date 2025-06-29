<?php

declare(strict_types=1);

namespace Syn\Tests\Unit\Macro\Storage;

use PHPUnit\Framework\TestCase;
use Syn\Macro\Storage\MacroRegistry;
use Syn\Parser\MacroDefinition;

class MacroRegistryTest extends TestCase
{
    private MacroRegistry $registry;

    protected function setUp(): void
    {
        $this->registry = new MacroRegistry();
    }

    public function testAddMacro(): void
    {
        $macro = new MacroDefinition('$->', '$this->');
        
        $this->registry->addMacro($macro);
        
        $this->assertCount(1, $this->registry->getMacros());
        $this->assertSame($macro, $this->registry->getMacros()[0]);
    }

    public function testAddMultipleMacros(): void
    {
        $macro1 = new MacroDefinition('$->', '$this->');
        $macro2 = new MacroDefinition('__debug', 'var_dump');
        
        $this->registry->addMacro($macro1);
        $this->registry->addMacro($macro2);
        
        $macros = $this->registry->getMacros();
        $this->assertCount(2, $macros);
        $this->assertSame($macro1, $macros[0]);
        $this->assertSame($macro2, $macros[1]);
    }

    public function testGetMacrosReturnsEmptyArrayInitially(): void
    {
        $this->assertSame([], $this->registry->getMacros());
    }

    public function testClear(): void
    {
        $macro = new MacroDefinition('$->', '$this->');
        $this->registry->addMacro($macro);
        
        $this->assertCount(1, $this->registry->getMacros());
        
        $this->registry->clear();
        
        $this->assertCount(0, $this->registry->getMacros());
        $this->assertSame([], $this->registry->getMacros());
    }

    public function testCount(): void
    {
        $this->assertSame(0, $this->registry->count());
        
        $this->registry->addMacro(new MacroDefinition('$->', '$this->'));
        $this->assertSame(1, $this->registry->count());
        
        $this->registry->addMacro(new MacroDefinition('__debug', 'var_dump'));
        $this->assertSame(2, $this->registry->count());
        
        $this->registry->clear();
        $this->assertSame(0, $this->registry->count());
    }

    public function testIsEmpty(): void
    {
        $this->assertTrue($this->registry->isEmpty());
        
        $this->registry->addMacro(new MacroDefinition('$->', '$this->'));
        $this->assertFalse($this->registry->isEmpty());
        
        $this->registry->clear();
        $this->assertTrue($this->registry->isEmpty());
    }
} 
