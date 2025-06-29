--TEST--
Unless macro: unless (condition) { body } -> if (!(condition)) { body }
--DESCRIPTION--
Test that the unless macro correctly transforms conditional logic
--MACROS--
$(macro) { unless ($(layer() as condition)) { $(layer() as body) } } >> { if (!($(condition))) { $(body) } }
--FILE--
<?php

$x = 5;

unless ($x === 1) {
    echo "x is not 1";
}

unless ($x > 10) {
    echo "x is not greater than 10";
}
--EXPECT--
<?php

$x = 5;

if (!($x === 1)) {
    echo "x is not 1";
}

if (!($x > 10)) {
    echo "x is not greater than 10";
} 