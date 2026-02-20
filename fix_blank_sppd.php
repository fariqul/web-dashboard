<?php
$db = new SQLite3('database/database.sqlite');
$db->exec("DELETE FROM sppd_transactions WHERE customer_name = '' OR customer_name IS NULL");
echo "Deleted: " . $db->changes() . " records\n";
echo "Remaining: " . $db->querySingle("SELECT COUNT(*) FROM sppd_transactions") . " records\n";

// Verify top customers
$result = $db->query("SELECT customer_name, COUNT(*) as cnt FROM sppd_transactions GROUP BY customer_name ORDER BY cnt DESC LIMIT 5");
echo "\nTop 5 customers after cleanup:\n";
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    echo "  [{$row['customer_name']}] => {$row['cnt']} trips\n";
}
