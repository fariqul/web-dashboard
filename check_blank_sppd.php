<?php
$db = new SQLite3('database/database.sqlite');

echo "=== Records with blank customer_name ===\n";
$result = $db->query("SELECT id, trip_number, customer_name, origin, destination, trip_destination_full, trip_begins_on, paid_amount, sheet, reason_for_trip FROM sppd_transactions WHERE customer_name = '' OR customer_name IS NULL");

while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    echo "ID: {$row['id']}\n";
    echo "  trip_number: {$row['trip_number']}\n";
    echo "  customer_name: [{$row['customer_name']}]\n";
    echo "  origin: [{$row['origin']}]\n";
    echo "  destination: [{$row['destination']}]\n";
    echo "  trip_destination_full: [{$row['trip_destination_full']}]\n";
    echo "  reason_for_trip: [{$row['reason_for_trip']}]\n";
    echo "  trip_begins_on: {$row['trip_begins_on']}\n";
    echo "  paid_amount: {$row['paid_amount']}\n";
    echo "  sheet: {$row['sheet']}\n";
    echo "---\n";
}

echo "\n=== Should we delete these 17 blank records? ===\n";
echo "Count: " . $db->querySingle("SELECT COUNT(*) FROM sppd_transactions WHERE customer_name = '' OR customer_name IS NULL") . "\n";
