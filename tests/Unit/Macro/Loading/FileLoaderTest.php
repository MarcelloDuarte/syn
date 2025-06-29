<?php

declare(strict_types=1);

namespace Syn\Tests\Unit\Macro\Loading;

use PHPUnit\Framework\TestCase;
use Syn\Macro\Loading\FileLoader;
use Syn\Macro\Parser\MacroParser;
use Syn\Macro\Storage\MacroRegistry;
use Syn\Macro\Capture\DelimiterMatcher;

class FileLoaderTest extends TestCase
{
    private FileLoader $fileLoader;
    private MacroRegistry $registry;

    protected function setUp(): void
    {
        $delimiterMatcher = new DelimiterMatcher();
        $this->registry = new MacroRegistry();
        $macroParser = new MacroParser($delimiterMatcher);
        $this->fileLoader = new FileLoader($macroParser, $this->registry);
    }

    public function testLoadFromStringWithValidMacros(): void
    {
        $content = "$(macro) { \$-> } >> { \$this-> }\n$(macro) { __debug } >> { var_dump }";
        
        $this->fileLoader->loadFromString($content, 'test.syn');
        
        $macros = $this->registry->getMacros();
        $this->assertCount(2, $macros);
        $this->assertSame('$->', $macros[0]->getPattern());
        $this->assertSame('$this->', $macros[0]->getReplacement());
        $this->assertSame('__debug', $macros[1]->getPattern());
        $this->assertSame('var_dump', $macros[1]->getReplacement());
    }

    public function testLoadFromStringWithEmptyLines(): void
    {
        $content = "\n\n$(macro) { pattern } >> { replacement }\n\n";
        
        $this->fileLoader->loadFromString($content);
        
        $macros = $this->registry->getMacros();
        $this->assertCount(1, $macros);
        $this->assertSame('pattern', $macros[0]->getPattern());
        $this->assertSame('replacement', $macros[0]->getReplacement());
    }

    public function testLoadFromStringWithComments(): void
    {
        $content = "# Comment\n// Another comment\n$(macro) { pattern } >> { replacement }";
        
        $this->fileLoader->loadFromString($content);
        
        $macros = $this->registry->getMacros();
        $this->assertCount(1, $macros);
        $this->assertSame('pattern', $macros[0]->getPattern());
        $this->assertSame('replacement', $macros[0]->getReplacement());
    }

    public function testLoadFromStringWithInvalidMacros(): void
    {
        $content = "invalid line\n$(macro) { valid } >> { macro }";
        
        $this->fileLoader->loadFromString($content);
        
        $macros = $this->registry->getMacros();
        $this->assertCount(1, $macros);
        $this->assertSame('valid', $macros[0]->getPattern());
        $this->assertSame('macro', $macros[0]->getReplacement());
    }

    public function testLoadFromStringWithEmptyContent(): void
    {
        $this->fileLoader->loadFromString('');
        
        $this->assertCount(0, $this->registry->getMacros());
    }

    public function testLoadFromFileWithValidFile(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'macro_test');
        file_put_contents($tempFile, '$(macro) { pattern } >> { replacement }');
        
        $this->fileLoader->loadFromFile($tempFile);
        
        $macros = $this->registry->getMacros();
        $this->assertCount(1, $macros);
        $this->assertSame('pattern', $macros[0]->getPattern());
        $this->assertSame('replacement', $macros[0]->getReplacement());
        
        unlink($tempFile);
    }

    public function testLoadFromFileWithNonExistentFile(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Macro file not found: /non/existent/file.syn');
        
        $this->fileLoader->loadFromFile('/non/existent/file.syn');
    }

    public function testLoadFromStringWithoutSourceFile(): void
    {
        $content = '$(macro) { pattern } >> { replacement }';
        
        $this->fileLoader->loadFromString($content);
        
        $macros = $this->registry->getMacros();
        $this->assertCount(1, $macros);
        $this->assertSame('pattern', $macros[0]->getPattern());
        $this->assertSame('replacement', $macros[0]->getReplacement());
        $this->assertNull($macros[0]->getFile());
    }
}
