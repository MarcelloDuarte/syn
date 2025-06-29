<?php

declare(strict_types=1);

namespace Syn\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Syn\Core\Configuration;
use Syn\Core\Processor;

abstract class PhptTestCase extends TestCase
{
    protected Configuration $config;
    protected Processor $processor;

    protected function setUp(): void
    {
        $this->config = new Configuration();
        $this->processor = new Processor($this->config);
    }

    protected function runPhptTest(string $phptFile): void
    {
        if (!file_exists($phptFile)) {
            $this->fail("PHPT file not found: {$phptFile}");
        }

        $content = file_get_contents($phptFile);
        if ($content === false) {
            $this->fail("Could not read PHPT file: {$phptFile}");
        }

        $sections = $this->parsePhptSections($content);
        
        // Extract test components
        $input = $sections['FILE'] ?? '';
        $macros = $sections['MACROS'] ?? '';
        $expected = $sections['EXPECT'] ?? '';
        $expectedRegex = $sections['EXPECTF'] ?? '';
        $expectedError = $sections['EXPECT_ERROR'] ?? '';
        $skip = $sections['SKIPIF'] ?? '';
        $cleanup = $sections['CLEANUP'] ?? '';

        // Check if test should be skipped
        if ($skip && $this->shouldSkip($skip)) {
            $this->markTestSkipped("Test skipped: {$skip}");
        }

        // Set up macros if provided
        if ($macros) {
            $this->setupMacros($macros);
        }

        try {
            // Process the input
            $result = $this->processInput($input);
            
            // Check for expected errors
            if ($expectedError) {
                $this->fail("Expected error but none occurred: {$expectedError}");
            }

            // Validate output
            if ($expectedRegex) {
                $this->assertMatchesRegularExpression($expectedRegex, $result);
            } elseif ($expected) {
                $this->assertEquals($expected, $result);
            }

        } catch (\Exception $e) {
            if ($expectedError) {
                $this->assertStringContainsString($expectedError, $e->getMessage());
            } else {
                throw $e;
            }
        } finally {
            // Run cleanup if provided
            if ($cleanup) {
                $this->runCleanup($cleanup);
            }
        }
    }

    private function parsePhptSections(string $content): array
    {
        $sections = [];
        $lines = explode("\n", $content);
        $currentSection = null;
        $currentContent = '';

        foreach ($lines as $line) {
            if (preg_match('/^--([A-Z_]+)--$/', $line, $matches)) {
                if ($currentSection) {
                    $sections[$currentSection] = trim($currentContent);
                }
                $currentSection = $matches[1];
                $currentContent = '';
            } else {
                if ($currentSection) {
                    $currentContent .= $line . "\n";
                }
            }
        }

        if ($currentSection) {
            $sections[$currentSection] = trim($currentContent);
        }

        return $sections;
    }

    private function shouldSkip(string $skipCondition): bool
    {
        // Simple skip condition evaluation
        // In a real implementation, you might want more sophisticated evaluation
        return !empty($skipCondition);
    }

    private function setupMacros(string $macros): void
    {
        // Create a fresh configuration to avoid state pollution
        $this->config = new Configuration();
        
        // Create a temporary macro file
        $tempFile = tempnam(sys_get_temp_dir(), 'syn_macro_');
        file_put_contents($tempFile, $macros);
        
        $this->config->addMacroFile($tempFile);
        
        // Create fresh processor with new config
        $this->processor = new Processor($this->config);
    }

    private function processInput(string $input): string
    {
        // Create a temporary input file
        $tempInput = tempnam(sys_get_temp_dir(), 'syn_input_') . '.syn.php';
        file_put_contents($tempInput, $input);
        
        // Create a temporary output file
        $tempOutput = tempnam(sys_get_temp_dir(), 'syn_output_') . '.php';
        
        // Process the file
        $this->processor->process($tempInput, $tempOutput);
        
        // Read the result
        $result = file_get_contents($tempOutput);
        
        // Clean up temporary files
        unlink($tempInput);
        unlink($tempOutput);
        
        return $result ?: '';
    }

    private function runCleanup(string $cleanup): void
    {
        // Execute cleanup code
        eval($cleanup);
    }

    protected function assertPhptFileExists(string $phptFile): void
    {
        $this->assertFileExists($phptFile, "PHPT test file not found: {$phptFile}");
    }
} 
