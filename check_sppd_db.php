<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\SppdTransaction;

echo "=== SPPD Database Check ===\n";
echo "Total records: " . SppdTransaction::count() . "\n\n";

// Check empty customer names
$emptyNames = SppdTransaction::where('customer_name', '')->orWhereNull('customer_name')->get();
echo "Records with empty customer_name: " . $emptyNames->count() . "\n";
foreach ($emptyNames as $r) {
    echo "  ID:{$r->id} | trip:{$r->trip_number} | name:[{$r->customer_name}] | sheet:{$r->sheet}\n";
}

echo "\n";

// Check columns in database
$db = new SQLite3('database/database.sqlite');
$result = $db->query("PRAGMA table_info(sppd_transactions)");
echo "=== Table Schema ===\n";
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    echo "  {$row['name']} | type:{$row['type']} | notnull:{$row['notnull']} | default:{$row['dflt_value']}\n";
}

echo "\n";

// Top customers (check for blanks)
echo "=== Top 10 Customers by Trips ===\n";
$topCustomers = SppdTransaction::selectRaw('customer_name, COUNT(*) as trip_count')
    ->groupBy('customer_name')
    ->orderByDesc('trip_count')
    ->limit(10)
    ->get();
foreach ($topCustomers as $c) {
    echo "  [{$c->customer_name}] => {$c->trip_count} trips\n";
}

echo "\n";

// Check trip_destination_full nullability
$emptyDest = SppdTransaction::where('trip_destination_full', '')->orWhereNull('trip_destination_full')->count();
echo "Records with empty trip_destination_full: {$emptyDest}\n";

// Check if trip_destination column exists
$result2 = $db->query("SELECT name FROM pragma_table_info('sppd_transactions') WHERE name LIKE '%destination%'");
echo "\nDestination-related columns:\n";
while ($row = $result2->fetchArray(SQLITE3_ASSOC)) {
    echo "  {$row['name']}\n";
}
