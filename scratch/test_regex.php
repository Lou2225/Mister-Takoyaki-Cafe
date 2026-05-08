<?php
$pattern = '/^[\pL\pN\s\-\.()\/\#&\']+$/u';
$value = 'Taco #1';
try {
    $result = preg_match($pattern, $value);
    echo "Result: " . $result . "\n";
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
