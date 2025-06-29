--TEST--
Simple macro replacement: $-> -> $this->
--DESCRIPTION--
Test that the simple $-> -> $this-> macro replacement works correctly
--MACROS--
$(macro) { $-> } >> { $this-> }
--FILE--
<?php

class Test {
    private $name = "test";
    
    public function getName() {
        return $->name;
    }
}
--EXPECT--
<?php

class Test {
    private $name = "test";
    
    public function getName() {
        return $this->name;
    }
} 