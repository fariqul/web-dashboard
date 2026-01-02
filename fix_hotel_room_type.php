<?php
/**
 * Script untuk mengekstrak room type dari nama hotel
 * dan memindahkannya ke kolom room_type
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ServiceFee;

echo "=== Extract Room Type from Hotel Name ===\n\n";

// Room type keywords
$roomTypeKeywords = [
    'Deluxe', 'Superior', 'Standard', 'Executive', 'Premier', 'Suite', 
    'Junior Suite', 'Grand', 'Classic', 'Business', 'Premium', 'Comfort',
    'Family', 'Smart', 'Hollywood', 'Super'
];

// Function to extract room type
function extractRoomType($hotelName, $keywords) {
    // Build pattern to match room type at the end or middle of hotel name
    // Pattern: keyword followed by optional bed type, numbers, letters
    $keywordPattern = implode('|', array_map('preg_quote', $keywords));
    
    // Try to match room type pattern
    // e.g., "Deluxe 2", "Superior With 1 Double Bed 2", "Smart Hollywood 3", "Executive 1"
    $pattern = '/\b(' . $keywordPattern . ')(\s+(?:With\s+)?\d*\s*(?:Single|Double|Twin|King|Queen|Hollywood)?(?:\s*-?\s*Size)?\s*(?:Bed)?\s*\d*\s*[A-Z]?)?\s*$/i';
    
    if (preg_match($pattern, $hotelName, $matches)) {
        $roomType = trim($matches[0]);
        $cleanHotel = trim(substr($hotelName, 0, -strlen($matches[0])));
        return [$cleanHotel, $roomType];
    }
    
    // Try alternate pattern for mid-string room types
    $pattern2 = '/\b(' . $keywordPattern . ')(\s+(?:With\s+)?\d+\s*(?:Single|Double|Twin|King|Queen|Hollywood)?(?:\s*-?\s*Size)?\s*(?:Bed)?\s*\d*)/i';
    
    if (preg_match($pattern2, $hotelName, $matches)) {
        $roomType = trim($matches[0]);
        $cleanHotel = trim(str_replace($roomType, '', $hotelName));
        // Clean up leftover text
        $cleanHotel = preg_replace('/\s+/', ' ', $cleanHotel);
        $cleanHotel = trim($cleanHotel);
        return [$cleanHotel, $roomType];
    }
    
    return [null, null];
}

// Find hotels with empty room_type
$hotels = ServiceFee::where('service_type', 'hotel')
    ->where(function($q) {
        $q->whereNull('room_type')
          ->orWhere('room_type', '');
    })
    ->get();

echo "Found " . $hotels->count() . " hotels with empty room type\n\n";

$fixed = 0;
$notFixed = 0;

foreach ($hotels as $hotel) {
    $hotelName = $hotel->hotel_name;
    
    list($cleanHotelName, $extractedRoomType) = extractRoomType($hotelName, $roomTypeKeywords);
    
    if ($extractedRoomType && $cleanHotelName) {
        $hotel->update([
            'room_type' => $extractedRoomType,
            'hotel_name' => $cleanHotelName,
        ]);
        
        echo "✅ ID {$hotel->id}:\n";
        echo "   Before: {$hotelName}\n";
        echo "   Hotel:  {$cleanHotelName}\n";
        echo "   Room:   {$extractedRoomType}\n\n";
        $fixed++;
    } else {
        echo "❌ ID {$hotel->id}: Could not extract - {$hotelName}\n";
        $notFixed++;
    }
}

echo "\n=== Summary ===\n";
echo "Fixed: $fixed\n";
echo "Not fixed: $notFixed\n";
echo "Done!\n";
