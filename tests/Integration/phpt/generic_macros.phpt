--TEST--
Generic macro system: multiple patterns
--DESCRIPTION--
Test that the generic macro system can handle various patterns correctly
--MACROS--
$(macro) { $-> } >> { $this-> }
$(macro) { __debug } >> { var_dump }
$(macro) { __swap } >> { list }
--FILE--
<?php

class Test {
    private $name = "test";
    
    public function getName() {
        return $->name;
    }
    
    public function debugValue($value) {
        __debug($value);
    }
    
    public function swapValues($a, $b) {
        __swap($a, $b);
    }
}
--EXPECT--
<?php

class Test {
    private $name = "test";
    
    public function getName() {
        return $this->name;
    }
    
    public function debugValue($value) {
        var_dump($value);
    }
    
    public function swapValues($a, $b) {
        list($a, $b);
    }
} 