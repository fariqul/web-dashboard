<?php
// Test BFKO CSV Import

require_once __DIR__ . '/vendor/autoload.php';

$file = __DIR__ . '/data/bfko/preproc/bfko_format_ideal.csv';

if (!file_exists($file)) {
    echo "File tidak ditemukan: $file\n";
    exit(1);
}

$handle = fopen($file, 'r');
$header = fgetcsv($handle);

echo "Header: " . implode(' | ', $header) . "\n\n";

$count = 0;
$samples = [];

while (($data = fgetcsv($handle)) !== false) {
    if (!empty($data[0]) && !empty($data[1]) && !empty($data[4]) && !empty($data[5]) && !empty($data[6])) {
        $count++;
        if ($count <= 3) {
            $samples[] = [
                'nip' => $data[0],
                'nama' => $data[1],
                'jabatan' => $data[2] ?? '',
                'bulan' => $data[4],
                'tahun' => $data[5],
                'nilai' => $data[6]
            ];
        }
    }
}

fclose($handle);

echo "Valid rows: $count\n\n";
echo "Sample data:\n";
foreach ($samples as $i => $s) {
    echo ($i+1) . ". NIP: {$s['nip']}, Nama: {$s['nama']}, Bulan: {$s['bulan']}, Tahun: {$s['tahun']}, Nilai: {$s['nilai']}\n";
}
