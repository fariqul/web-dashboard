<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ServiceFee;

ServiceFee::truncate();
echo "Semua data Service Fee telah dihapus.\n";
