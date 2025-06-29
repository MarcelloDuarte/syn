<?php

declare(strict_types=1);

namespace Syn\Tests\Unit\Core;

use PHPUnit\Framework\TestCase;
use Syn\Core\Configuration;

class ConfigurationTest extends TestCase
{
    private Configuration $config;

    protected function setUp(): void
    {
        $this->config = new Configuration();
    }

    public function testDefaultValues(): void
    {
        $this->assertEmpty($this->config->getMacroDirectories());
        $this->assertEmpty($this->config->getMacroFiles());
        $this->assertFalse($this->config->isPreserveLineNumbers());
        $this->assertFalse($this->config->isVerbose());
        $this->assertEmpty($this->config->getPlugins());
    }

    public function testSetMacroDirectories(): void
    {
        $directories = ['/path/to/macros', '/another/path'];
        $this->config->setMacroDirectories($directories);
        
        $this->assertEquals($directories, $this->config->getMacroDirectories());
    }

    public function testAddMacroDirectory(): void
    {
        $this->config->addMacroDirectory('/path/to/macros');
        $this->config->addMacroDirectory('/another/path');
        
        $this->assertEquals(['/path/to/macros', '/another/path'], $this->config->getMacroDirectories());
    }

    public function testSetMacroFiles(): void
    {
        $files = ['macros.syn', 'other.syn'];
        $this->config->setMacroFiles($files);
        
        $this->assertEquals($files, $this->config->getMacroFiles());
    }

    public function testAddMacroFile(): void
    {
        $this->config->addMacroFile('macros.syn');
        $this->config->addMacroFile('other.syn');
        
        $this->assertEquals(['macros.syn', 'other.syn'], $this->config->getMacroFiles());
    }

    public function testPreserveLineNumbers(): void
    {
        $this->config->setPreserveLineNumbers(true);
        $this->assertTrue($this->config->isPreserveLineNumbers());
        
        $this->config->setPreserveLineNumbers(false);
        $this->assertFalse($this->config->isPreserveLineNumbers());
    }

    public function testVerbose(): void
    {
        $this->config->setVerbose(true);
        $this->assertTrue($this->config->isVerbose());
        
        $this->config->setVerbose(false);
        $this->assertFalse($this->config->isVerbose());
    }

    public function testSetPlugins(): void
    {
        $plugins = ['Plugin1', 'Plugin2'];
        $this->config->setPlugins($plugins);
        
        $this->assertEquals($plugins, $this->config->getPlugins());
    }

    public function testAddPlugin(): void
    {
        $this->config->addPlugin('Plugin1');
        $this->config->addPlugin('Plugin2');
        
        $this->assertEquals(['Plugin1', 'Plugin2'], $this->config->getPlugins());
    }

    public function testCustomSettings(): void
    {
        $this->config->setCustomSetting('test_key', 'test_value');
        $this->assertEquals('test_value', $this->config->getCustomSetting('test_key'));
        $this->assertEquals('default', $this->config->getCustomSetting('non_existent', 'default'));
    }

    public function testToArray(): void
    {
        $this->config->setMacroDirectories(['/path/to/macros']);
        $this->config->setMacroFiles(['macros.syn']);
        $this->config->setPreserveLineNumbers(true);
        $this->config->setVerbose(true);
        $this->config->setPlugins(['Plugin1']);
        $this->config->setCustomSetting('test', 'value');
        
        $expected = [
            'macro_directories' => ['/path/to/macros'],
            'macro_files' => ['macros.syn'],
            'preserve_line_numbers' => true,
            'verbose' => true,
            'plugins' => ['Plugin1'],
            'custom' => ['test' => 'value'],
        ];
        
        $this->assertEquals($expected, $this->config->toArray());
    }
} 
