<?php
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$file = 'data/Service fee/Rekapitulasi Service Fee Juli - September 2025.xlsx';
$spreadsheet = IOFactory::load($file);
$sheetNames = $spreadsheet->getSheetNames();

echo "=== SERVICE FEE EXCEL ANALYSIS ===\n\n";
echo "Total Sheets: " . count($sheetNames) . "\n";
echo "Sheet Names:\n";
foreach ($sheetNames as $idx => $name) {
    echo "  " . ($idx + 1) . ". " . $name . "\n";
}

// Analyze each sheet
foreach ($sheetNames as $sheetName) {
    echo "\n\n=== SHEET: " . $sheetName . " ===\n";
    
    $sheet = $spreadsheet->getSheetByName($sheetName);
    $rows = $sheet->toArray();
    
    echo "Total rows: " . count($rows) . "\n";
    
    // Show first 5 rows
    echo "\nFirst 5 rows:\n";
    for ($i = 0; $i < min(5, count($rows)); $i++) {
        echo "Row " . $i . ": ";
        $display = [];
        foreach ($rows[$i] as $j => $cell) {
            if ($cell !== null && $cell !== '') {
                $cellVal = is_string($cell) ? substr($cell, 0, 50) : $cell;
                $display[] = "[$j]=" . $cellVal;
            }
        }
        echo implode(" | ", $display) . "\n";
    }
    
    // Find header row
    echo "\nLooking for header row...\n";
    $headerRow = null;
    $headerRowIndex = null;
    for ($i = 0; $i < min(10, count($rows)); $i++) {
        $rowStr = strtolower(implode(' ', array_map('strval', $rows[$i])));
        if (strpos($rowStr, 'transaction time') !== false || 
            strpos($rowStr, 'booking id') !== false ||
            strpos($rowStr, 'booking_id') !== false) {
            $headerRow = $rows[$i];
            $headerRowIndex = $i;
            echo "Header found at row $i\n";
            break;
        }
    }
    
    if ($headerRow) {
        echo "Header columns:\n";
        foreach ($headerRow as $colIdx => $colName) {
            if ($colName !== null && $colName !== '') {
                echo "  Col $colIdx: " . $colName . "\n";
            }
        }
        
        // Show sample data
        echo "\nSample data (first 3 data rows):\n";
        for ($i = $headerRowIndex + 1; $i < min($headerRowIndex + 4, count($rows)); $i++) {
            echo "Row $i: ";
            $display = [];
            foreach ($rows[$i] as $j => $cell) {
                if ($cell !== null && $cell !== '') {
                    $cellVal = is_string($cell) ? substr($cell, 0, 30) : $cell;
                    $display[] = "[$j]=" . $cellVal;
                }
            }
            echo implode(" | ", $display) . "\n";
        }
        
        // Count data rows
        $dataCount = 0;
        for ($i = $headerRowIndex + 1; $i < count($rows); $i++) {
            $hasData = false;
            foreach ($rows[$i] as $cell) {
                if ($cell !== null && $cell !== '') {
                    $hasData = true;
                    break;
                }
            }
            if ($hasData) $dataCount++;
        }
        echo "\nData rows: $dataCount\n";
    } else {
        echo "No header found!\n";
    }
}
