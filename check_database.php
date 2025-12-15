<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=====================================\n";
echo "SQLite Database Information\n";
echo "=====================================\n\n";

// Database file info
$dbPath = database_path('database.sqlite');
echo "📁 Database Location:\n";
echo "   $dbPath\n\n";

if (file_exists($dbPath)) {
    $size = filesize($dbPath);
    $sizeInMB = round($size / 1024 / 1024, 2);
    echo "📊 File Size: " . number_format($size) . " bytes ($sizeInMB MB)\n";
    echo "📅 Last Modified: " . date("Y-m-d H:i:s", filemtime($dbPath)) . "\n\n";
} else {
    echo "❌ Database file tidak ditemukan!\n\n";
    exit;
}

// Get all tables
echo "📋 Tables in Database:\n";
echo str_repeat("-", 70) . "\n";

$tables = DB::select("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name");

foreach ($tables as $table) {
    if (in_array($table->name, ['sqlite_sequence'])) continue; // Skip system tables
    
    $tableName = $table->name;
    
    // Get row count
    $count = DB::table($tableName)->count();
    
    echo sprintf("%-35s %10s rows\n", "  • $tableName", number_format($count));
}

echo str_repeat("-", 70) . "\n\n";

// Summary
echo "📈 Total Records by Module:\n";
echo str_repeat("-", 70) . "\n";

$modules = [
    'BFKO Data' => 'bfko_data',
    'CC Card Transactions' => 'cc_transactions', 
    'Service Fee Transactions' => 'service_fees',
    'SPPD Transactions' => 'sppd_transactions',
    'Sheet Additional Fees' => 'sheet_additional_fees',
    'Users' => 'users'
];

foreach ($modules as $label => $table) {
    try {
        $count = DB::table($table)->count();
        echo sprintf("  %-30s : %s\n", $label, number_format($count));
    } catch (Exception $e) {
        echo sprintf("  %-30s : Table not found\n", $label);
    }
}

echo "\n";
echo "💡 Cara Membuka Database:\n";
echo "   1. Download DB Browser for SQLite: https://sqlitebrowser.org/\n";
echo "   2. Buka file: $dbPath\n";
echo "   3. Bisa browse, edit, query data seperti phpMyAdmin\n\n";

echo "✅ Semua data import tersimpan di file database.sqlite\n";
echo "   Backup file ini untuk backup seluruh data!\n\n";
