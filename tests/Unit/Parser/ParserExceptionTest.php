<?php

declare(strict_types=1);

namespace Syn\Tests\Unit\Parser;

use PHPUnit\Framework\TestCase;
use Syn\Parser\ParserException;

class ParserExceptionTest extends TestCase
{
    public function testBasicException(): void
    {
        $message = 'Test parser error';
        $code = 123;
        
        $exception = new ParserException($message, $code);
        
        $this->assertSame($message, $exception->getMessage());
        $this->assertSame($code, $exception->getCode());
        $this->assertNull($exception->getSourceFile());
        $this->assertNull($exception->getSourceLine());
        $this->assertNull($exception->getSourceColumn());
        $this->assertNull($exception->getPrevious());
    }

    public function testExceptionWithPrevious(): void
    {
        $previousException = new \RuntimeException('Previous error');
        $exception = new ParserException('Parser error', 0, $previousException);
        
        $this->assertSame($previousException, $exception->getPrevious());
    }

    public function testExceptionWithSourceFile(): void
    {
        $sourceFile = '/path/to/test.syn';
        $exception = new ParserException('Error', 0, null, $sourceFile);
        
        $this->assertSame($sourceFile, $exception->getSourceFile());
    }

    public function testExceptionWithSourceLine(): void
    {
        $sourceLine = 42;
        $exception = new ParserException('Error', 0, null, null, $sourceLine);
        
        $this->assertSame($sourceLine, $exception->getSourceLine());
    }

    public function testExceptionWithSourceColumn(): void
    {
        $sourceColumn = 15;
        $exception = new ParserException('Error', 0, null, null, null, $sourceColumn);
        
        $this->assertSame($sourceColumn, $exception->getSourceColumn());
    }

    public function testExceptionWithAllSourceInfo(): void
    {
        $sourceFile = '/path/to/macro.syn';
        $sourceLine = 10;
        $sourceColumn = 25;
        
        $exception = new ParserException(
            'Syntax error',
            100,
            null,
            $sourceFile,
            $sourceLine,
            $sourceColumn
        );
        
        $this->assertSame($sourceFile, $exception->getSourceFile());
        $this->assertSame($sourceLine, $exception->getSourceLine());
        $this->assertSame($sourceColumn, $exception->getSourceColumn());
    }

    public function testGetFormattedMessageWithoutSourceInfo(): void
    {
        $message = 'Basic error message';
        $exception = new ParserException($message);
        
        $formattedMessage = $exception->getFormattedMessage();
        
        $this->assertSame($message, $formattedMessage);
    }

    public function testGetFormattedMessageWithSourceFile(): void
    {
        $message = 'Parse error';
        $sourceFile = '/path/to/file.syn';
        $exception = new ParserException($message, 0, null, $sourceFile);
        
        $formattedMessage = $exception->getFormattedMessage();
        
        $this->assertSame("File: {$sourceFile} - {$message}", $formattedMessage);
    }

    public function testGetFormattedMessageWithSourceLine(): void
    {
        $message = 'Parse error';
        $sourceLine = 15;
        $exception = new ParserException($message, 0, null, null, $sourceLine);
        
        $formattedMessage = $exception->getFormattedMessage();
        
        $this->assertSame("{$message} (line {$sourceLine})", $formattedMessage);
    }

    public function testGetFormattedMessageWithSourceLineAndColumn(): void
    {
        $message = 'Parse error';
        $sourceLine = 15;
        $sourceColumn = 8;
        $exception = new ParserException($message, 0, null, null, $sourceLine, $sourceColumn);
        
        $formattedMessage = $exception->getFormattedMessage();
        
        $this->assertSame("{$message} (line {$sourceLine}, column {$sourceColumn})", $formattedMessage);
    }

    public function testGetFormattedMessageWithAllInfo(): void
    {
        $message = 'Unexpected token';
        $sourceFile = '/project/macros/test.syn';
        $sourceLine = 42;
        $sourceColumn = 10;
        
        $exception = new ParserException(
            $message,
            0,
            null,
            $sourceFile,
            $sourceLine,
            $sourceColumn
        );
        
        $formattedMessage = $exception->getFormattedMessage();
        $expected = "File: {$sourceFile} - {$message} (line {$sourceLine}, column {$sourceColumn})";
        
        $this->assertSame($expected, $formattedMessage);
    }

    public function testGetFormattedMessageWithFileAndLine(): void
    {
        $message = 'Syntax error';
        $sourceFile = '/path/to/file.syn';
        $sourceLine = 5;
        
        $exception = new ParserException($message, 0, null, $sourceFile, $sourceLine);
        
        $formattedMessage = $exception->getFormattedMessage();
        $expected = "File: {$sourceFile} - {$message} (line {$sourceLine})";
        
        $this->assertSame($expected, $formattedMessage);
    }

    public function testGetFormattedMessageWithColumnButNoLine(): void
    {
        $message = 'Parse error';
        $sourceColumn = 20;
        $exception = new ParserException($message, 0, null, null, null, $sourceColumn);
        
        $formattedMessage = $exception->getFormattedMessage();
        
        // Column without line should not be included
        $this->assertSame($message, $formattedMessage);
    }

    public function testExceptionIsInstanceOfException(): void
    {
        $exception = new ParserException('Test');
        
        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertInstanceOf(\Throwable::class, $exception);
    }

    public function testExceptionWithEmptyMessage(): void
    {
        $exception = new ParserException('');
        
        $this->assertSame('', $exception->getMessage());
        $this->assertSame('', $exception->getFormattedMessage());
    }

    public function testExceptionWithZeroValues(): void
    {
        $exception = new ParserException('Error', 0, null, null, 0, 0);
        
        $this->assertSame(0, $exception->getCode());
        $this->assertSame(0, $exception->getSourceLine());
        $this->assertSame(0, $exception->getSourceColumn());
        
        // Line 0 should still be included in formatted message
        $formattedMessage = $exception->getFormattedMessage();
        $this->assertStringContainsString('(line 0, column 0)', $formattedMessage);
    }

    public function testExceptionWithNegativeValues(): void
    {
        $exception = new ParserException('Error', -1, null, null, -5, -10);
        
        $this->assertSame(-1, $exception->getCode());
        $this->assertSame(-5, $exception->getSourceLine());
        $this->assertSame(-10, $exception->getSourceColumn());
    }

    public function testExceptionChaining(): void
    {
        $rootCause = new \InvalidArgumentException('Root cause');
        $middleCause = new \RuntimeException('Middle cause', 0, $rootCause);
        $parserException = new ParserException('Parser error', 0, $middleCause);
        
        $this->assertSame($middleCause, $parserException->getPrevious());
        $this->assertSame($rootCause, $parserException->getPrevious()->getPrevious());
    }
} 
