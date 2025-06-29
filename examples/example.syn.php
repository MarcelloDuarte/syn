<?php

// Example PHP file using Syn custom syntax
// This file demonstrates how to use the macros defined in macros.syn

class Example
{
    private $name;
    private $value;

    public function __construct($name, $value)
    {
        $this->name = $name;
        $this->value = $value;
    }

    public function getName()
    {
        return $->name; // This will be expanded to $this->name
    }

    public function getValue()
    {
        return $->value; // This will be expanded to $this->value
    }

    public function testConditionals()
    {
        $x = 5;
        
        unless ($x === 1) {
            echo "x is not 1\n";
        }
        
        unless ($x > 10) {
            echo "x is not greater than 10\n";
        }
    }

    public function testVariableSwapping()
    {
        $a = "hello";
        $b = "world";
        
        echo "Before: a = $a, b = $b\n";
        
        __swap($a, $b);
        
        echo "After: a = $a, b = $b\n";
    }

    public function testDebugging()
    {
        $data = [1, 2, 3, 4, 5];
        
        __debug($data);
        __debug($this->getName());
    }

    public function testAssertions()
    {
        $value = 42;
        
        __assert($value > 0, "Value must be positive");
        __assert($value < 100, "Value must be less than 100");
    }
}

// Example enum usage
enum Status {
    Active,
    Inactive,
    Pending
}

// Test the example
$example = new Example("test", 123);
$example->testConditionals();
$example->testVariableSwapping();
$example->testDebugging();
$example->testAssertions();

echo "Status: " . Status::Active . "\n"; 
