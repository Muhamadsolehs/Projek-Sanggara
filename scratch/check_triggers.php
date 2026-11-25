<?php
require __DIR__ . '/../backend/vendor/autoload.php';
$app = require_once __DIR__ . '/../backend/bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$triggers = DB::select("SHOW TRIGGERS FROM db_sanggara");
echo json_encode($triggers, JSON_PRETTY_PRINT);
