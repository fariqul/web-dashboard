<?php
// Test the fixed cleanAmountBfko function

function cleanAmountBfko($value) {
    if (is_numeric($value)) {
        return (float)$value;
    }
    
    $value = trim((string)$value);
    $value = str_replace(' ', '', $value);
    
    // Handle format with comma as thousand separator and dot as decimal (e.g., 300,000,000.00)
    if (preg_match('/^[\d,]+\.\d{2}$/', $value)) {
        $value = preg_replace('/\.\d{2}$/', '', $value);
        $value = str_replace(',', '', $value);
        return (float)$value;
    }
    
    // Handle Indonesian format with dots as thousand separator (3.734.355)
    if (preg_match('/^\d{1,3}(\.\d{3})+$/', $value)) {
        $value = str_replace('.', '', $value);
        return (float)$value;
    }
    
    // Handle format with comma as thousand separator only (3,734,355)
    if (preg_match('/^\d{1,3}(,\d{3})+$/', $value)) {
        $value = str_replace(',', '', $value);
        return (float)$value;
    }
    
    // Remove commas and other non-numeric chars except dot
    $cleaned = preg_replace('/[^\d.]/', '', $value);
    return (float)$cleaned;
}

// Test cases
$tests = [
    '  300,000,000.00 ' => 300000000,
    '  4,710,842.00 ' => 4710842,
    '  3.734.355 ' => 3734355,
    '  3,734,355 ' => 3734355,
    '4239758' => 4239758,
    '270,000,000.00' => 270000000,
];

echo "=== Testing cleanAmountBfko function ===\n\n";

foreach ($tests as $input => $expected) {
    $result = cleanAmountBfko($input);
    $status = $result == $expected ? '✅' : '❌';
    echo sprintf("%s Input: '%s' -> Result: %s (Expected: %s)\n", 
        $status, 
        trim($input), 
        number_format($result, 0, ',', '.'),
        number_format($expected, 0, ',', '.')
    );
}
