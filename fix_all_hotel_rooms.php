<?php
/**
 * Fix all hotel data with comprehensive patterns
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Fix All Empty Room Types ===\n\n";

// Define extraction patterns for room types (more comprehensive)
function extractHotelData($hotelName) {
    $originalHotel = $hotelName;
    $roomType = null;
    $employeeName = null;
    
    // Room type patterns (order matters - more specific first)
    $roomPatterns = [
        // Pattern with numbers at end: "Deluxe Twin 2", "Superior King 1"
        '/\b(Deluxe|Superior|Standard|Executive|Premier|Classic|Premium|Comfort|Family|Privilege|Club|Vip|Double|Twin|Single|Queen|King)\s*(Twin|Double|Single|King|Queen|Balcony|View|Cabin|Bed|Room|Ro)?\s*(Bed|Non[- ]?Balcony|Non[- ]?Smoking|Bf)?\s*(\d+)?\s*(M|Tn)?$/i',
        // Pattern: "1 King Bed", "2 Twin Beds"
        '/\b(\d+)\s*(King|Queen|Twin|Double|Single)\s*(Bed|Beds)?\s*$/i',
        // Pattern: "Happiness Doublebed", "Skyline View"
        '/\b(Happiness|Skyline|Monas|Braga)\s*(Doublebed|Double|View|Twin)?\s*$/i',
        // Pattern: "Harris Unique", "Harris 2"
        '/\b(Harris|Warmth|Zest|New)\s*(Unique|Room)?\s*(\d+)?$/i',
        // Pattern: room number like "205", "101"
        '/\b(\d{3})$/i',
        // Simple room type at end
        '/\b(Comfy|Premiere|Apartment)$/i',
    ];
    
    // Try to extract employee name first (mixed case or all caps at end)
    // Pattern: "Name Name" or "NAME NAME" at end after room info
    if (preg_match('/\s+(\d+)\s+([A-Z][a-z]+(?:\s+[A-Za-z]+){1,3})$/u', $hotelName, $matches)) {
        // Has number then name like "2 Arie Pratama"
        $employeeName = trim($matches[2]);
        $hotelName = trim(substr($hotelName, 0, -strlen($matches[0]))) . ' ' . $matches[1];
    } elseif (preg_match('/\s+(\d+)\s+([A-Z][a-z]+\s+[A-Za-z]+)$/u', $hotelName, $matches)) {
        $employeeName = trim($matches[2]);
        $hotelName = trim(substr($hotelName, 0, -strlen($matches[0]))) . ' ' . $matches[1];
    } elseif (preg_match('/\s+([A-Z][a-z]+(?:\s+[A-Za-z][a-z]*){1,3})$/u', $hotelName, $matches)) {
        // Mixed case name at end
        $potentialName = trim($matches[1]);
        // Make sure it's not a hotel/room keyword
        $keywords = ['Hotel', 'Resort', 'Inn', 'Suites', 'Villa', 'Bed', 'Room', 'Double', 'Twin', 'King', 'Queen', 'View', 'Balcony'];
        $isKeyword = false;
        foreach ($keywords as $kw) {
            if (stripos($potentialName, $kw) !== false) {
                $isKeyword = true;
                break;
            }
        }
        if (!$isKeyword && strlen($potentialName) > 3) {
            $employeeName = $potentialName;
            $hotelName = trim(substr($hotelName, 0, -strlen($matches[0])));
        }
    }
    
    // Now extract room type
    $roomTypeKeywords = [
        'Deluxe', 'Superior', 'Standard', 'Executive', 'Premier', 'Suite', 
        'Grand', 'Classic', 'Business', 'Premium', 'Comfort', 'Family', 
        'Privilege', 'Club', 'Vip', 'VIP', 'Harris', 'Warmth', 'Zest',
        'Happiness', 'Skyline', 'Monas', 'Braga', 'Comfy', 'Premiere',
        'Royal', 'Cabin'
    ];
    
    $keywordPattern = implode('|', array_map('preg_quote', $roomTypeKeywords));
    
    // Complex room type patterns
    $patterns = [
        // "Deluxe Twin 2", "Superior King 1", "Executive Cabin"
        '/\b(' . $keywordPattern . ')\s*(Twin|Double|Single|King|Queen|Balcony|View|Cabin|Doublebed)?\s*(Bed|Or\s+Twin)?\s*(Non[- ]?Balcony|Non[- ]?Smoking|Bf)?\s*(\d+)?$/i',
        // "1 King Bed", "2 Twin Beds"  
        '/\b(\d+)\s*(King|Queen|Twin|Double|Single)\s*(Bed|Beds)?$/i',
        // "Double - 2 People"
        '/\b(Double|Twin|Single)\s*-\s*\d+\s*People$/i',
        // "Twin Superior Non-Smoking"
        '/\b(Twin|Double)\s+(Superior|Deluxe|Standard)\s*(Non[- ]?Smoking)?$/i',
        // Room number: "205", "101" for hotels like MARYAM
        '/\s+(\d{3})$/i',
        // "Superior With One Double Bed", "Privilege With 1 Double Bed"
        '/\b(' . $keywordPattern . ')\s+With\s+(\d+|One|Two)\s+(Double|Single|King|Queen)\s+Bed$/i',
        // Simple end patterns
        '/\b(Comfy|Premiere|Apartment|Unique|View)$/i',
    ];
    
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $hotelName, $matches)) {
            $roomType = trim($matches[0]);
            $hotelName = trim(substr($hotelName, 0, -strlen($matches[0])));
            break;
        }
    }
    
    // Clean up
    $hotelName = rtrim($hotelName, ' -');
    $hotelName = preg_replace('/\s+/', ' ', $hotelName);
    
    return [
        'hotel_name' => $hotelName ?: $originalHotel,
        'room_type' => $roomType,
        'employee_name' => $employeeName
    ];
}

// Manual fixes for complex cases
$manualFixes = [
    672 => ['hotel_name' => 'Arthama Hotel Makassar', 'room_type' => 'Deluxe Twin 2', 'employee_name' => 'MARJUANZA'],
    679 => ['hotel_name' => 'Louis Kienne Pemuda', 'room_type' => 'Deluxe Double Or Twin 4', 'employee_name' => 'Arie Pratama'],
    685 => ['hotel_name' => 'Pare Beach Hotel', 'room_type' => 'Deluxe Twin 2', 'employee_name' => 'Kresky yulianto'],
    686 => ['hotel_name' => 'Pare Beach Hotel', 'room_type' => 'Deluxe Single 2', 'employee_name' => 'IBRAHIM'],
    700 => ['hotel_name' => 'YATS Colony', 'room_type' => 'Ra Twin Bed', 'employee_name' => 'ZAKI ASHARI'],
    703 => ['hotel_name' => 'Four Points by Sheraton Makassar', 'room_type' => '1 King Bed'],
    704 => ['hotel_name' => 'Gammara Hotel Makassar', 'room_type' => 'Superior Twin 1', 'employee_name' => 'Faisal Kamaruddin'],
    714 => ['hotel_name' => 'Swiss-Belhotel Silae Palu', 'room_type' => 'Deluxe Double 1', 'employee_name' => 'mohamad sulthan'],
    727 => ['hotel_name' => 'Swiss-Belinn Panakkukang', 'room_type' => 'Superior Deluxe King 3', 'employee_name' => 'Abdul Rais'],
    729 => ['hotel_name' => 'MARYAM PALACE HOTEL SOPPENG', 'room_type' => 'Room 205'],
    730 => ['hotel_name' => 'MARYAM PALACE HOTEL SOPPENG', 'room_type' => 'Room 205'],
    731 => ['hotel_name' => 'MARYAM PALACE HOTEL SOPPENG', 'room_type' => 'Room 101'],
    732 => ['hotel_name' => 'MARYAM PALACE HOTEL SOPPENG', 'room_type' => 'Room 101'],
    733 => ['hotel_name' => 'MARYAM PALACE HOTEL SOPPENG', 'room_type' => 'Room 101'],
    735 => ['hotel_name' => 'ibis Styles Bali Denpasar', 'room_type' => 'Double Superior Queen Bed Non Balcony'],
    754 => ['hotel_name' => 'Avery De Grand City Hotel Bandung', 'room_type' => 'Executive Cabin'],
    755 => ['hotel_name' => 'Avery De Grand City Hotel Bandung', 'room_type' => 'Executive Cabin'],
    792 => ['hotel_name' => 'BIGLAND Hotel & Convention Sentul', 'room_type' => 'Family Room'],
    831 => ['hotel_name' => 'Horison Ultima Bandung', 'room_type' => 'Super Deluxe King 1', 'employee_name' => 'Arif Indra Wibowo'],
    866 => ['hotel_name' => 'Golden Palace Hotel Lombok', 'room_type' => 'Deluxe Twin Bed 1', 'employee_name' => 'Rachmat Ramli'],
    869 => ['hotel_name' => 'Golden Palace Hotel Lombok', 'room_type' => 'Deluxe Twin Bed 1', 'employee_name' => 'Rachmat Ramli'],
    873 => ['hotel_name' => 'THE 1O1 Yogyakarta Tugu Hotel', 'room_type' => 'Deluxe Balcony 1'],
    888 => ['hotel_name' => 'Lusiana Hotel & Restauran Soroako Sorowako', 'room_type' => 'Double - 2 People'],
    890 => ['hotel_name' => 'Lusiana Hotel & Restauran Soroako Sorowako', 'room_type' => 'Twin Superior Non-Smoking'],
    910 => ['hotel_name' => 'Kimaya Braga Bandung by Harris', 'room_type' => 'Classic Braga View'],
    912 => ['hotel_name' => 'Mercure Bandung Nexa Supratman', 'room_type' => 'Privilege With 1 Double Bed'],
    916 => ['hotel_name' => 'Grand Dafam Braga Bandung', 'room_type' => 'Condotel 2 Bedroom'],
    926 => ['hotel_name' => 'Ashley Tugu Tani Menteng', 'room_type' => 'Superior Queen 1', 'employee_name' => 'Dambarudin'],
    929 => ['hotel_name' => 'MaxOneHotels Premier Melawai', 'room_type' => 'Happiness Doublebed'],
    930 => ['hotel_name' => 'Sotis Hotel Kemang Jakarta', 'room_type' => 'Premiere'],
    937 => ['hotel_name' => 'Sahid Azizah Syariah Hotel & Convention Kendari', 'room_type' => 'Deluxe Double 2', 'employee_name' => 'Bimasakti Mahardika'],
    938 => ['hotel_name' => 'Wisma Saranani', 'room_type' => 'Deluxe Double 1', 'employee_name' => 'Bima Mahardika'],
    939 => ['hotel_name' => 'Hotel Agri Bulukumba', 'room_type' => 'Superior Twin 1', 'employee_name' => 'Dian Adhirama Achdar'],
    947 => ['hotel_name' => 'Four Points by Sheraton Manado', 'room_type' => '1 King Bed'],
    952 => ['hotel_name' => 'Four Points by Sheraton Manado', 'room_type' => '2 Twin Beds'],
    953 => ['hotel_name' => 'Juno Tanah Abang Jakarta', 'room_type' => 'Juno Skyline View'],
    955 => ['hotel_name' => 'Helios Hotel and Convention', 'room_type' => 'Executive Royal Double Or Twin 2', 'employee_name' => 'Alamsyah Anwar'],
    956 => ['hotel_name' => 'Helios Hotel and Convention', 'room_type' => 'Executive Royal Double Or Twin 2', 'employee_name' => 'Wawang Wiratama'],
    959 => ['hotel_name' => 'Yello Hotel Harmoni', 'room_type' => 'Yello Monas 1', 'employee_name' => 'Washilatul Huda'],
    963 => ['hotel_name' => 'Hotel Zenith Kendari', 'room_type' => 'Deluxe Twin 2', 'employee_name' => 'Fortunatus Yosantonino Setyono Putra'],
    964 => ['hotel_name' => 'Riltree Hotel', 'room_type' => 'Kamar Sedang 1'],
    965 => ['hotel_name' => 'Riltree Hotel', 'room_type' => 'Kamar Sedang'],
    973 => ['hotel_name' => 'MaxOneHotels Premier Melawai', 'room_type' => 'Standard Double 1', 'employee_name' => 'Bayu Baskoro'],
    975 => ['hotel_name' => 'HARRIS Hotel & Convention Cibinong City Bogor', 'room_type' => 'Harris Unique'],
    977 => ['hotel_name' => 'ibis Styles Jakarta Airport', 'room_type' => 'Superior With One Double Bed'],
    1005 => ['hotel_name' => 'Crown Prince Hotel Surabaya managed by Midtown Indonesia Hotels', 'room_type' => 'Comfy'],
    1016 => ['hotel_name' => 'Villa Matano Sorowako', 'room_type' => 'VIP'],
    1034 => ['hotel_name' => 'Crown Prince Hotel Surabaya managed by Midtown Indonesia Hotels', 'room_type' => 'Comfy'],
    1041 => ['hotel_name' => 'The Alana Surabaya', 'room_type' => 'Club'],
    1062 => ['hotel_name' => 'Four Points by Sheraton Makassar', 'room_type' => '1 King Bed'],
    1076 => ['hotel_name' => 'Ramada by Wyndham Bali Sunset Road Kuta', 'room_type' => 'Deluxe Queen Bf'],
    1098 => ['hotel_name' => 'Dalton Makassar', 'room_type' => 'Superior King 1', 'employee_name' => 'Yusdi'],
    1104 => ['hotel_name' => 'ibis Styles Semarang Simpang Lima', 'room_type' => 'Superior Double Bed 1', 'employee_name' => 'YUSDI'],
    1113 => ['hotel_name' => 'Woywoy Paradise Villa', 'room_type' => 'Villa 2'],
    1123 => ['hotel_name' => 'HARRIS Hotel Sentraland Semarang', 'room_type' => 'Harris Unique'],
    1124 => ['hotel_name' => 'HARRIS Hotel Sentraland Semarang', 'room_type' => 'Harris Unique'],
    1132 => ['hotel_name' => 'CLARO Makassar', 'room_type' => 'Deluxe King Bed 2', 'employee_name' => 'Muhammad Arham Tamrin'],
    1135 => ['hotel_name' => 'Best Choice and Cozy Living 2BR Apartment at Bale Hinggil By Travelio', 'room_type' => '2 Bed'],
    1136 => ['hotel_name' => 'CLARO Makassar', 'room_type' => 'Deluxe King Bed 1', 'employee_name' => 'Cahyaning Yulia Pratama'],
    1153 => ['hotel_name' => 'ibis Styles Jakarta Airport', 'room_type' => 'Superior With One Double Bed'],
    1154 => ['hotel_name' => 'Best Deal And Homey 2Br At Bale Hinggil Apartment', 'room_type' => 'Apartment'],
];

$fixed = 0;
foreach ($manualFixes as $id => $data) {
    $current = DB::table('service_fees')->where('id', $id)->first();
    if (!$current) {
        echo "ID {$id} not found, skipping\n";
        continue;
    }
    
    $updates = [];
    if (isset($data['hotel_name'])) {
        $updates['hotel_name'] = $data['hotel_name'];
    }
    if (isset($data['room_type'])) {
        $updates['room_type'] = $data['room_type'];
    }
    if (isset($data['employee_name']) && empty($current->employee_name)) {
        $updates['employee_name'] = $data['employee_name'];
    }
    
    if (!empty($updates)) {
        DB::table('service_fees')->where('id', $id)->update($updates);
        echo "Fixed ID {$id}: {$current->hotel_name}\n";
        echo "  -> Hotel: " . ($updates['hotel_name'] ?? $current->hotel_name) . "\n";
        echo "  -> Room: " . ($updates['room_type'] ?? 'N/A') . "\n";
        if (isset($updates['employee_name'])) {
            echo "  -> Employee: {$updates['employee_name']}\n";
        }
        echo "\n";
        $fixed++;
    }
}

echo "\n=== Summary ===\n";
echo "Fixed: {$fixed} records\n";

// Check remaining
$remaining = DB::table('service_fees')
    ->where('service_type', 'hotel')
    ->where(function($q) {
        $q->whereNull('room_type')
          ->orWhere('room_type', '')
          ->orWhere('room_type', 'N/A');
    })
    ->count();

echo "Remaining empty room types: {$remaining}\n";
