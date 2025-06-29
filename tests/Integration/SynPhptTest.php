<?php

declare(strict_types=1);

namespace Syn\Tests\Integration;

class SynPhptTest extends PhptTestCase
{
    /**
     * @dataProvider phptFileProvider
     */
    public function testPhptFile(string $phptFile): void
    {
        $this->runPhptTest($phptFile);
    }

    public static function phptFileProvider(): array
    {
        $phptDir = __DIR__ . '/phpt';
        $tests = [];

        if (is_dir($phptDir)) {
            $files = glob($phptDir . '/*.phpt');
            foreach ($files as $file) {
                $basename = basename($file, '.phpt');
                $tests[$basename] = [$file];
            }
        }

        return $tests;
    }

    public function testSimpleMacroReplacement(): void
    {
        $phptFile = __DIR__ . '/phpt/simple_macro.phpt';
        $this->runPhptTest($phptFile);
    }

    public function testUnlessMacro(): void
    {
        $phptFile = __DIR__ . '/phpt/unless_macro.phpt';
        $this->runPhptTest($phptFile);
    }

    public function testGenericMacros(): void
    {
        $phptFile = __DIR__ . '/phpt/generic_macros.phpt';
        $this->runPhptTest($phptFile);
    }
} 
