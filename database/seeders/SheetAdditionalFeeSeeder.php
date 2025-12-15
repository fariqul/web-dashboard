<?php

namespace Database\Seeders;

use App\Models\SheetAdditionalFee;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SheetAdditionalFeeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $fees = [
            [
                'sheet_name' => 'Juli 2025',
                'biaya_adm_bunga' => 0,
                'biaya_transfer' => 0,
                'iuran_tahunan' => 0,
            ],
            [
                'sheet_name' => 'Agustus 2025',
                'biaya_adm_bunga' => 0,
                'biaya_transfer' => 7500,
                'iuran_tahunan' => 0,
            ],
            [
                'sheet_name' => 'September 2025 - CC 5657',
                'biaya_adm_bunga' => 12243872,
                'biaya_transfer' => 0,
                'iuran_tahunan' => 0,
            ],
            [
                'sheet_name' => 'September 2025 - CC 9386',
                'biaya_adm_bunga' => 0,
                'biaya_transfer' => 7500,
                'iuran_tahunan' => 600000,
            ],
        ];

        foreach ($fees as $fee) {
            SheetAdditionalFee::updateOrCreate(
                ['sheet_name' => $fee['sheet_name']],
                $fee
            );
        }

        $this->command->info('✅ Sheet additional fees seeded successfully!');
    }
}
