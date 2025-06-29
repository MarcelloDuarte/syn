<?php

declare(strict_types=1);

namespace Syn\Tests\Integration\Console;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Syn\Console\SyncCommand;

class SyncCommandTest extends TestCase
{
    private CommandTester $commandTester;
    private string $tempDir;
    private string $macroDir;
    private string $outputDir;

    protected function setUp(): void
    {
        $application = new Application();
        $application->add(new SyncCommand());
        
        $command = $application->find('sync');
        $this->commandTester = new CommandTester($command);
        
        // Create temporary directories for testing
        $this->tempDir = sys_get_temp_dir() . '/syn_test_' . uniqid();
        $this->macroDir = $this->tempDir . '/macros';
        $this->outputDir = $this->tempDir . '/output';
        
        mkdir($this->tempDir, 0777, true);
        mkdir($this->macroDir, 0777, true);
        mkdir($this->outputDir, 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
    }

    public function testProcessSingleFileWithSimpleMacro(): void
    {
        // Create a macro file
        $macroContent = '$(macro) { $-> } >> { $this-> }';
        file_put_contents($this->macroDir . '/simple.syn', $macroContent);
        
        // Create input file
        $inputFile = $this->tempDir . '/input.syn.php';
        $inputContent = '<?php echo $->name; $->method();';
        file_put_contents($inputFile, $inputContent);
        
        // Run command
        $exitCode = $this->commandTester->execute([
            'input' => $inputFile,
            '--out' => $this->outputDir . '/output.php',
            '--macro-dir' => $this->macroDir
        ]);
        
        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Processed 1 files successfully', $this->commandTester->getDisplay());
        
        // Check output file
        $outputFile = $this->outputDir . '/output.php';
        $this->assertFileExists($outputFile);
        
        $outputContent = file_get_contents($outputFile);
        $this->assertStringContainsString('$this->name', $outputContent);
        $this->assertStringContainsString('$this->method()', $outputContent);
    }

    public function testProcessSingleFileWithComplexMacro(): void
    {
        // Create a macro file with unless macro
        $macroContent = '$(macro) { unless ($(layer() as condition)) { $(layer() as body) } } >> { if (!($(condition))) { $(body) } }';
        file_put_contents($this->macroDir . '/unless.syn', $macroContent);
        
        // Create input file
        $inputFile = $this->tempDir . '/input.syn.php';
        $inputContent = '<?php unless ($x === 1) { echo "x is not 1"; }';
        file_put_contents($inputFile, $inputContent);
        
        // Run command
        $exitCode = $this->commandTester->execute([
            'input' => $inputFile,
            '--out' => $this->outputDir . '/output.php',
            '--macro-dir' => $this->macroDir
        ]);
        
        $this->assertSame(0, $exitCode);
        
        // Check output file
        $outputContent = file_get_contents($this->outputDir . '/output.php');
        $this->assertStringContainsString('if (!($x === 1))', $outputContent);
        $this->assertStringContainsString('echo "x is not 1"', $outputContent);
    }

    public function testProcessDirectoryWithMultipleFiles(): void
    {
        // Create macro file
        $macroContent = '$(macro) { $-> } >> { $this-> }';
        file_put_contents($this->macroDir . '/simple.syn', $macroContent);
        
        // Create input directory with multiple files
        $inputDir = $this->tempDir . '/src';
        mkdir($inputDir);
        
        file_put_contents($inputDir . '/file1.syn.php', '<?php $->test();');
        file_put_contents($inputDir . '/file2.syn.php', '<?php $->method();');
        file_put_contents($inputDir . '/file3.php', '<?php echo "no macros";'); // Regular PHP file
        
        // Run command
        $exitCode = $this->commandTester->execute([
            'input' => $inputDir,
            '--out' => $this->outputDir,
            '--macro-dir' => $this->macroDir
        ]);
        
        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('files successfully', $this->commandTester->getDisplay());
        
        // Check output files
        $this->assertFileExists($this->outputDir . '/file1.php');
        $this->assertFileExists($this->outputDir . '/file2.php');
        
        $output1 = file_get_contents($this->outputDir . '/file1.php');
        $output2 = file_get_contents($this->outputDir . '/file2.php');
        
        $this->assertStringContainsString('$this->test()', $output1);
        $this->assertStringContainsString('$this->method()', $output2);
    }

    public function testProcessWithSpecificMacroFile(): void
    {
        // Create specific macro file
        $macroFile = $this->tempDir . '/custom.syn';
        $macroContent = '$(macro) { __debug($(layer() as expr)) } >> { var_dump($(expr)) }';
        file_put_contents($macroFile, $macroContent);
        
        // Create input file
        $inputFile = $this->tempDir . '/input.syn.php';
        $inputContent = '<?php __debug($variable);';
        file_put_contents($inputFile, $inputContent);
        
        // Run command
        $exitCode = $this->commandTester->execute([
            'input' => $inputFile,
            '--out' => $this->outputDir . '/output.php',
            '--macro-file' => $macroFile
        ]);
        
        $this->assertSame(0, $exitCode);
        
        // Check output
        $outputContent = file_get_contents($this->outputDir . '/output.php');
        $this->assertStringContainsString('var_dump($variable)', $outputContent);
    }

    public function testVerboseOutput(): void
    {
        // Create macro and input files
        file_put_contents($this->macroDir . '/simple.syn', '$(macro) { $-> } >> { $this-> }');
        
        $inputFile = $this->tempDir . '/input.syn.php';
        file_put_contents($inputFile, '<?php $->test();');
        
        // Run command with verbose flag
        $exitCode = $this->commandTester->execute([
            'input' => $inputFile,
            '--out' => $this->outputDir . '/output.php',
            '--macro-dir' => $this->macroDir,
            '--verbose' => true
        ]);
        
        $this->assertSame(0, $exitCode);
        
        $display = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Processing Results', $display);
        $this->assertStringContainsString('File', $display);
        $this->assertStringContainsString('Status', $display);
        $this->assertStringContainsString('Lines', $display);
    }

    public function testPreserveLineNumbers(): void
    {
        // Create macro and input files
        file_put_contents($this->macroDir . '/simple.syn', '$(macro) { $-> } >> { $this-> }');
        
        $inputFile = $this->tempDir . '/input.syn.php';
        $inputContent = "<?php\n\$->test();\necho 'hello';\n\$->method();";
        file_put_contents($inputFile, $inputContent);
        
        // Run command with preserve line numbers
        $exitCode = $this->commandTester->execute([
            'input' => $inputFile,
            '--out' => $this->outputDir . '/output.php',
            '--macro-dir' => $this->macroDir,
            '--preserve-line-numbers' => true
        ]);
        
        $this->assertSame(0, $exitCode);
        
        // Check that output maintains line structure
        $outputContent = file_get_contents($this->outputDir . '/output.php');
        $inputLines = explode("\n", $inputContent);
        $outputLines = explode("\n", $outputContent);
        
        // Should have similar number of lines (allowing for some variation due to processing)
        $this->assertGreaterThanOrEqual(count($inputLines) - 2, count($outputLines));
    }

    public function testConfigurationFile(): void
    {
        // Create configuration file (PHP format as expected by Configuration::loadFromFile)
        $configFile = $this->tempDir . '/config.php';
        $configContent = '<?php return ' . var_export([
            'macro_directories' => [$this->macroDir],
            'preserve_line_numbers' => true,
            'verbose' => false
        ], true) . ';';
        file_put_contents($configFile, $configContent);
        
        // Create macro and input files
        file_put_contents($this->macroDir . '/simple.syn', '$(macro) { $-> } >> { $this-> }');
        
        $inputFile = $this->tempDir . '/input.syn.php';
        file_put_contents($inputFile, '<?php $->test();');
        
        // Run command with config file
        $exitCode = $this->commandTester->execute([
            'input' => $inputFile,
            '--out' => $this->outputDir . '/output.php',
            '--config' => $configFile
        ]);
        
        $this->assertSame(0, $exitCode);
        
        // Check output file exists and has correct content
        $outputContent = file_get_contents($this->outputDir . '/output.php');
        $this->assertStringContainsString('$this->test()', $outputContent);
    }

    public function testErrorHandlingMissingOutputPath(): void
    {
        $inputFile = $this->tempDir . '/input.syn.php';
        file_put_contents($inputFile, '<?php echo "test";');
        
        // Run command without output path
        $exitCode = $this->commandTester->execute([
            'input' => $inputFile
        ]);
        
        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('Output path is required', $this->commandTester->getDisplay());
    }

    public function testErrorHandlingNonExistentInputFile(): void
    {
        $nonExistentFile = $this->tempDir . '/nonexistent.syn.php';
        
        // Run command with non-existent input file
        $exitCode = $this->commandTester->execute([
            'input' => $nonExistentFile,
            '--out' => $this->outputDir . '/output.php'
        ]);
        
        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('ERROR', $this->commandTester->getDisplay());
    }

    public function testProcessingWithNonExistentMacroDirectory(): void
    {
        $inputFile = $this->tempDir . '/input.syn.php';
        file_put_contents($inputFile, '<?php $->test();');
        
        $nonExistentMacroDir = $this->tempDir . '/nonexistent_macros';
        
        // Run command with non-existent macro directory
        // This should succeed but not apply any macros (since no macros are loaded)
        $exitCode = $this->commandTester->execute([
            'input' => $inputFile,
            '--out' => $this->outputDir . '/output.php',
            '--macro-dir' => $nonExistentMacroDir
        ]);
        
        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Processed 1 files successfully', $this->commandTester->getDisplay());
        
        // Check that the file was processed but macros were not applied
        $outputContent = file_get_contents($this->outputDir . '/output.php');
        $this->assertStringContainsString('$->test()', $outputContent); // Macro not applied
    }

    public function testErrorHandlingWithVerboseStackTrace(): void
    {
        $inputFile = $this->tempDir . '/input.syn.php';
        file_put_contents($inputFile, '<?php $->test();');
        
        $nonExistentInputFile = $this->tempDir . '/nonexistent_input.syn.php';
        
        // Run command with verbose flag to see stack trace on a real error
        $exitCode = $this->commandTester->execute([
            'input' => $nonExistentInputFile,
            '--out' => $this->outputDir . '/output.php',
            '--verbose' => true
        ]);
        
        $this->assertSame(1, $exitCode);
        
        $display = $this->commandTester->getDisplay();
        $this->assertStringContainsString('ERROR', $display);
        // In verbose mode, should show stack trace
        $this->assertStringContainsString('#', $display); // Stack trace markers
    }

    public function testProcessingWithoutMacros(): void
    {
        // Create input file without any macros
        $inputFile = $this->tempDir . '/input.syn.php';
        $inputContent = '<?php echo "Hello World"; $var = 123;';
        file_put_contents($inputFile, $inputContent);
        
        // Run command without macro directory
        $exitCode = $this->commandTester->execute([
            'input' => $inputFile,
            '--out' => $this->outputDir . '/output.php'
        ]);
        
        $this->assertSame(0, $exitCode);
        
        // Check that file is processed (even without macros)
        $outputContent = file_get_contents($this->outputDir . '/output.php');
        $this->assertStringContainsString('Hello World', $outputContent);
        $this->assertStringContainsString('$var = 123', $outputContent);
    }

    public function testProcessingMultipleMacroFiles(): void
    {
        // Create multiple macro files
        file_put_contents($this->macroDir . '/arrows.syn', '$(macro) { $-> } >> { $this-> }');
        file_put_contents($this->macroDir . '/debug.syn', '$(macro) { __debug($(layer() as expr)) } >> { var_dump($(expr)) }');
        
        // Create input file using both macros
        $inputFile = $this->tempDir . '/input.syn.php';
        $inputContent = '<?php $->test(); __debug($variable);';
        file_put_contents($inputFile, $inputContent);
        
        // Run command
        $exitCode = $this->commandTester->execute([
            'input' => $inputFile,
            '--out' => $this->outputDir . '/output.php',
            '--macro-dir' => $this->macroDir
        ]);
        
        $this->assertSame(0, $exitCode);
        
        // Check both macros were applied
        $outputContent = file_get_contents($this->outputDir . '/output.php');
        $this->assertStringContainsString('$this->test()', $outputContent);
        $this->assertStringContainsString('var_dump($variable)', $outputContent);
    }

    public function testCommandHelp(): void
    {
        // Test help content through the command definition rather than execution
        $application = new Application();
        $application->add(new SyncCommand());
        $command = $application->find('sync');
        
        $help = $command->getHelp();
        $this->assertStringContainsString('processes PHP files with custom syntax', $help);
        $this->assertStringContainsString('Examples:', $help);
        $this->assertStringContainsString('input.syn.php', $help);
        $this->assertStringContainsString('--out=output.php', $help);
        $this->assertStringContainsString('--macro-dir', $help);
        $this->assertStringContainsString('--verbose', $help);
    }

    public function testCommandHelpExecution(): void
    {
        // Test that help can be displayed by testing the application's help command instead
        $application = new Application();
        $application->add(new SyncCommand());
        
        $helpCommand = $application->find('help');
        $helpTester = new CommandTester($helpCommand);
        
        $exitCode = $helpTester->execute([
            'command_name' => 'sync'
        ]);
        
        $this->assertSame(0, $exitCode);
        
        $display = $helpTester->getDisplay();
        $this->assertStringContainsString('sync', $display);
        $this->assertStringContainsString('input', $display);
        $this->assertStringContainsString('--out', $display);
    }

    public function testCommandConfiguration(): void
    {
        // Test the command configuration directly
        $application = new Application();
        $application->add(new SyncCommand());
        $command = $application->find('sync');
        
        $this->assertSame('sync', $command->getName());
        $this->assertStringContainsString('Process .syn and .syn.php files with macro definitions', $command->getDescription());
        
        // Check that required arguments and options are defined
        $definition = $command->getDefinition();
        
        $this->assertTrue($definition->hasArgument('input'));
        $this->assertTrue($definition->getArgument('input')->isRequired());
        
        $this->assertTrue($definition->hasOption('out'));
        $this->assertTrue($definition->hasOption('macro-dir'));
        $this->assertTrue($definition->hasOption('macro-file'));
        $this->assertTrue($definition->hasOption('preserve-line-numbers'));
        $this->assertTrue($definition->hasOption('config'));
    }

    public function testNestedDirectoryProcessing(): void
    {
        // Create macro file
        file_put_contents($this->macroDir . '/simple.syn', '$(macro) { $-> } >> { $this-> }');
        
        // Create nested directory structure
        $inputDir = $this->tempDir . '/src';
        $subDir = $inputDir . '/sub';
        mkdir($inputDir);
        mkdir($subDir);
        
        file_put_contents($inputDir . '/main.syn.php', '<?php $->main();');
        file_put_contents($subDir . '/helper.syn.php', '<?php $->helper();');
        
        // Run command
        $exitCode = $this->commandTester->execute([
            'input' => $inputDir,
            '--out' => $this->outputDir,
            '--macro-dir' => $this->macroDir
        ]);
        
        $this->assertSame(0, $exitCode);
        
        // Check nested output structure
        $this->assertFileExists($this->outputDir . '/main.php');
        $this->assertFileExists($this->outputDir . '/sub/helper.php');
        
        $mainContent = file_get_contents($this->outputDir . '/main.php');
        $helperContent = file_get_contents($this->outputDir . '/sub/helper.php');
        
        $this->assertStringContainsString('$this->main()', $mainContent);
        $this->assertStringContainsString('$this->helper()', $helperContent);
    }

    public function testErrorHandlingInvalidConfigurationFile(): void
    {
        $inputFile = $this->tempDir . '/input.syn.php';
        file_put_contents($inputFile, '<?php echo "test";');
        
        $nonExistentConfigFile = $this->tempDir . '/nonexistent_config.php';
        
        // Run command with non-existent config file
        $exitCode = $this->commandTester->execute([
            'input' => $inputFile,
            '--out' => $this->outputDir . '/output.php',
            '--config' => $nonExistentConfigFile
        ]);
        
        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('ERROR', $this->commandTester->getDisplay());
    }

    public function testErrorHandlingMalformedConfigurationFile(): void
    {
        $inputFile = $this->tempDir . '/input.syn.php';
        file_put_contents($inputFile, '<?php echo "test";');
        
        // Create malformed config file
        $configFile = $this->tempDir . '/malformed_config.php';
        file_put_contents($configFile, '<?php return "not an array";');
        
        // Run command with malformed config file
        $exitCode = $this->commandTester->execute([
            'input' => $inputFile,
            '--out' => $this->outputDir . '/output.php',
            '--config' => $configFile
        ]);
        
        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('ERROR', $this->commandTester->getDisplay());
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }
} 
