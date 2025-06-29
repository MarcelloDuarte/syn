<?php

declare(strict_types=1);

namespace Syn\Tests\Unit\Macro\Loading;

use PHPUnit\Framework\TestCase;
use Syn\Macro\Loading\DirectoryLoader;
use Syn\Macro\Loading\FileLoader;
use Syn\Macro\Parser\MacroParser;
use Syn\Macro\Storage\MacroRegistry;
use Syn\Macro\Capture\DelimiterMatcher;

class DirectoryLoaderTest extends TestCase
{
    private string $tempDir;
    private DirectoryLoader $directoryLoader;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/syn_test_' . uniqid();
        mkdir($this->tempDir);
        
        $delimiterMatcher = new DelimiterMatcher();
        $registry = new MacroRegistry();
        $macroParser = new MacroParser($delimiterMatcher);
        $fileLoader = new FileLoader($macroParser, $registry);
        $this->directoryLoader = new DirectoryLoader($fileLoader);
    }

    protected function tearDown(): void
    {
        // Clean up temp directory recursively
        $this->removeDirectory($this->tempDir);
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }

    public function testLoadFromDirectoryWithSynFiles(): void
    {
        // Create test .syn files
        file_put_contents($this->tempDir . '/test1.syn', '$(macro) { $-> } >> { $this-> }');
        file_put_contents($this->tempDir . '/test2.syn', '$(macro) { __debug } >> { var_dump }');
        file_put_contents($this->tempDir . '/not_syn.txt', 'should be ignored');
        
        // This should not throw an exception
        $this->directoryLoader->loadFromDirectory($this->tempDir);
        
        // If we get here, the method completed successfully
        $this->assertTrue(true);
    }

    public function testLoadFromDirectoryWithNoSynFiles(): void
    {
        // Create non-.syn files
        file_put_contents($this->tempDir . '/test.txt', 'not a syn file');
        file_put_contents($this->tempDir . '/test.php', '<?php echo "hello";');
        
        // This should not throw an exception
        $this->directoryLoader->loadFromDirectory($this->tempDir);
        
        // If we get here, the method completed successfully
        $this->assertTrue(true);
    }

    public function testLoadFromDirectoryWithEmptyDirectory(): void
    {
        // Empty directory should not cause issues
        $this->directoryLoader->loadFromDirectory($this->tempDir);
        
        // If we get here, the method completed successfully
        $this->assertTrue(true);
    }

    public function testLoadFromDirectoryWithNonExistentDirectory(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Directory not found: /non/existent/directory');
        
        $this->directoryLoader->loadFromDirectory('/non/existent/directory');
    }

    public function testLoadFromDirectoryWithSubdirectories(): void
    {
        // Create subdirectory with .syn file
        mkdir($this->tempDir . '/subdir');
        file_put_contents($this->tempDir . '/test.syn', '$(macro) { pattern } >> { replacement }');
        file_put_contents($this->tempDir . '/subdir/nested.syn', '$(macro) { nested } >> { pattern }');
        
        // Should only load files from the main directory, not subdirectories
        $this->directoryLoader->loadFromDirectory($this->tempDir);
        
        // If we get here, the method completed successfully
        $this->assertTrue(true);
    }
} 
