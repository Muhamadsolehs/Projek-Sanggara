<?php
require __DIR__ . '/../backend/vendor/autoload.php';
$app = require_once __DIR__ . '/../backend/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== TRIGGERS ===\n";
$triggers = DB::select('SHOW TRIGGERS');
foreach ($triggers as $trg) {
    echo "  - {$trg->Trigger} on {$trg->Table} ({$trg->Timing} {$trg->Event})\n";
}
