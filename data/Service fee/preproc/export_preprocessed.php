<?php
/**
 * Export Preprocessed Service Fee Data
 * Script untuk export data yang sudah di-preprocess dari CSV
 */

// Configuration
$sourceDir = __DIR__;
$outputDir = __DIR__;

// Get parameters
$serviceType = $argv[1] ?? 'all'; // 'hotel', 'flight', or 'all'
$sheet = $argv[2] ?? null;

echo "=== Export Preprocessed Data ===\n";
echo "Service Type: $serviceType\n";
if ($sheet) {
    echo "Sheet: $sheet\n";
}
echo "\n";

// Function to read and combine CSV files
function readCSV($filepath) {
    if (!file_exists($filepath)) {
        return [];
    }
    
    $data = [];
    $handle = fopen($filepath, 'r');
    
    // Read header
    $header = fgetcsv($handle);
    
    // Read data
    while (($row = fgetcsv($handle)) !== false) {
        $data[] = array_combine($header, $row);
    }
    
    fclose($handle);
    return $data;
}

// Function to write CSV
function writeCSV($data, $filepath) {
    if (empty($data)) {
        echo "No data to export.\n";
        return false;
    }
    
    $handle = fopen($filepath, 'w');
    
    // Write header
    fputcsv($handle, array_keys($data[0]));
    
    // Write data
    foreach ($data as $row) {
        fputcsv($handle, $row);
    }
    
    fclose($handle);
    return true;
}

try {
    $allData = [];
    
    // Scan directory for preprocessed files
    $files = glob("$sourceDir/*_preprocessed_*.csv");
    
    if (empty($files)) {
        echo "No preprocessed files found!\n";
        exit(1);
    }
    
    echo "Found " . count($files) . " preprocessed file(s)\n\n";
    
    foreach ($files as $file) {
        $basename = basename($file);
        
        // Filter by service type
        if ($serviceType !== 'all') {
            if ($serviceType === 'hotel' && strpos($basename, 'hotel_') !== 0) {
                continue;
            }
            if ($serviceType === 'flight' && strpos($basename, 'flight_') !== 0) {
                continue;
            }
        }
        
        // Filter by sheet if specified
        if ($sheet && strpos($basename, $sheet) === false) {
            continue;
        }
        
        echo "Reading: $basename\n";
        $data = readCSV($file);
        echo "  Records: " . count($data) . "\n";
        
        $allData = array_merge($allData, $data);
    }
    
    if (empty($allData)) {
        echo "\nNo matching data found!\n";
        exit(0);
    }
    
    // Generate output filename
    $typeStr = $serviceType === 'all' ? 'combined' : $serviceType;
    $sheetStr = $sheet ? '_' . str_replace(' ', '_', $sheet) : '';
    $outputFile = $outputDir . "/export_{$typeStr}{$sheetStr}_" . date('YmdHis') . ".csv";
    
    // Write combined data
    echo "\nExporting combined data...\n";
    if (writeCSV($allData, $outputFile)) {
        echo "Export completed!\n";
        echo "Total records: " . count($allData) . "\n";
        echo "File saved: $outputFile\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n=== Done ===\n";
