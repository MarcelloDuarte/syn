<?php

declare(strict_types=1);

namespace Syn\Tests\Unit\Parser;

use PHPUnit\Framework\TestCase;
use Syn\Parser\ParseResult;

class ParseResultTest extends TestCase
{
    public function testSuccessResult(): void
    {
        $value = 'test value';
        $position = 42;
        
        $result = ParseResult::success($value, $position);
        
        $this->assertTrue($result->isSuccess());
        $this->assertFalse($result->isFailure());
        $this->assertSame($value, $result->getValue());
        $this->assertSame($position, $result->getPosition());
        $this->assertNull($result->getError());
    }

    public function testFailureResult(): void
    {
        $position = 10;
        $error = 'Parse error occurred';
        
        $result = ParseResult::failure($position, $error);
        
        $this->assertFalse($result->isSuccess());
        $this->assertTrue($result->isFailure());
        $this->assertSame($position, $result->getPosition());
        $this->assertSame($error, $result->getError());
    }

    public function testGetValueFromFailureThrowsException(): void
    {
        $result = ParseResult::failure(0, 'error');
        
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot get value from failed parse result');
        
        $result->getValue();
    }

    public function testSuccessWithDifferentValueTypes(): void
    {
        $testCases = [
            ['string', 'test string'],
            [42, 'integer'],
            [3.14, 'float'],
            [true, 'boolean'],
            [['array', 'values'], 'array'],
            [(object)['prop' => 'value'], 'object'],
            [null, 'null'],
        ];

        foreach ($testCases as [$value, $description]) {
            $result = ParseResult::success($value, 0);
            $this->assertSame($value, $result->getValue(), "Failed for $description");
        }
    }

    public function testMapOnSuccessResult(): void
    {
        $originalValue = 10;
        $result = ParseResult::success($originalValue, 5);
        
        $mappedResult = $result->map(fn($x) => $x * 2);
        
        $this->assertTrue($mappedResult->isSuccess());
        $this->assertSame(20, $mappedResult->getValue());
        $this->assertSame(5, $mappedResult->getPosition());
    }

    public function testMapOnFailureResult(): void
    {
        $result = ParseResult::failure(10, 'error');
        
        $mappedResult = $result->map(fn($x) => $x * 2);
        
        $this->assertFalse($mappedResult->isSuccess());
        $this->assertSame($result, $mappedResult); // Should return the same instance
        $this->assertSame(10, $mappedResult->getPosition());
        $this->assertSame('error', $mappedResult->getError());
    }

    public function testMapWithComplexTransformation(): void
    {
        $originalValue = ['a', 'b', 'c'];
        $result = ParseResult::success($originalValue, 0);
        
        $mappedResult = $result->map(function($array) {
            return array_map('strtoupper', $array);
        });
        
        $this->assertTrue($mappedResult->isSuccess());
        $this->assertSame(['A', 'B', 'C'], $mappedResult->getValue());
    }

    public function testFlatMapOnSuccessResult(): void
    {
        $originalValue = 5;
        $result = ParseResult::success($originalValue, 10);
        
        $flatMappedResult = $result->flatMap(function($x) {
            if ($x > 0) {
                return ParseResult::success($x * 3, 15);
            }
            return ParseResult::failure(10, 'negative value');
        });
        
        $this->assertTrue($flatMappedResult->isSuccess());
        $this->assertSame(15, $flatMappedResult->getValue());
        $this->assertSame(15, $flatMappedResult->getPosition());
    }

    public function testFlatMapOnFailureResult(): void
    {
        $result = ParseResult::failure(5, 'original error');
        
        $flatMappedResult = $result->flatMap(function($x) {
            return ParseResult::success($x * 2, 10);
        });
        
        $this->assertFalse($flatMappedResult->isSuccess());
        $this->assertSame($result, $flatMappedResult); // Should return the same instance
        $this->assertSame('original error', $flatMappedResult->getError());
    }

    public function testFlatMapReturningFailure(): void
    {
        $originalValue = -5;
        $result = ParseResult::success($originalValue, 0);
        
        $flatMappedResult = $result->flatMap(function($x) {
            if ($x > 0) {
                return ParseResult::success($x * 2, 5);
            }
            return ParseResult::failure(3, 'value must be positive');
        });
        
        $this->assertFalse($flatMappedResult->isSuccess());
        $this->assertSame(3, $flatMappedResult->getPosition());
        $this->assertSame('value must be positive', $flatMappedResult->getError());
    }

    public function testChainedOperations(): void
    {
        $result = ParseResult::success(2, 0)
            ->map(fn($x) => $x * 3)  // 6
            ->flatMap(fn($x) => ParseResult::success($x + 4, 10))  // 10
            ->map(fn($x) => (string)$x);  // "10"
        
        $this->assertTrue($result->isSuccess());
        $this->assertSame("10", $result->getValue());
        $this->assertSame(10, $result->getPosition());
    }

    public function testChainedOperationsWithFailure(): void
    {
        $result = ParseResult::success(2, 0)
            ->map(fn($x) => $x * 3)  // 6
            ->flatMap(fn($x) => ParseResult::failure(5, 'intentional failure'))
            ->map(fn($x) => (string)$x);  // This should not execute
        
        $this->assertFalse($result->isSuccess());
        $this->assertSame(5, $result->getPosition());
        $this->assertSame('intentional failure', $result->getError());
    }

    public function testPositionValues(): void
    {
        $positions = [0, 1, 100, 999, PHP_INT_MAX];
        
        foreach ($positions as $position) {
            $successResult = ParseResult::success('value', $position);
            $failureResult = ParseResult::failure($position, 'error');
            
            $this->assertSame($position, $successResult->getPosition());
            $this->assertSame($position, $failureResult->getPosition());
        }
    }

    public function testErrorMessages(): void
    {
        $errorMessages = [
            'Simple error',
            'Error with numbers: 123',
            'Error with special chars: !@#$%',
            'Multi-line\nerror\nmessage',
            '',  // Empty error message
        ];
        
        foreach ($errorMessages as $error) {
            $result = ParseResult::failure(0, $error);
            $this->assertSame($error, $result->getError());
        }
    }
} 
