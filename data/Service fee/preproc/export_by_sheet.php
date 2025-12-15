<?php
/**
 * Export Service Fee Data by Sheet
 * Script untuk export data service fee berdasarkan sheet tertentu
 */

// Database configuration
$host = 'localhost';
$dbname = 'your_database_name';
$username = 'your_username';
$password = 'your_password';

try {
    // Connect to database
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get sheet parameter from command line or default
    $sheet = $argv[1] ?? 'Juli 2025';
    
    echo "Exporting data for sheet: $sheet\n";
    
    // Query untuk mengambil data berdasarkan sheet
    $sql = "SELECT 
                booking_id,
                transaction_date,
                service_type,
                destination,
                employee_name,
                personel_number,
                origin,
                transaction_amount,
                service_fee,
                vat,
                total_billed,
                status,
                sheet,
                created_at
            FROM service_fees 
            WHERE sheet = :sheet
            ORDER BY transaction_date DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['sheet' => $sheet]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Export to CSV
    $filename = "export_" . str_replace(' ', '_', $sheet) . "_" . date('YmdHis') . ".csv";
    $filepath = __DIR__ . "/" . $filename;
    
    $fp = fopen($filepath, 'w');
    
    // Write header
    if (!empty($results)) {
        fputcsv($fp, array_keys($results[0]));
        
        // Write data
        foreach ($results as $row) {
            fputcsv($fp, $row);
        }
    }
    
    fclose($fp);
    
    echo "Export completed!\n";
    echo "Total records: " . count($results) . "\n";
    echo "File saved: $filepath\n";
    
} catch (PDOException $e) {
    echo "Database Error: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
