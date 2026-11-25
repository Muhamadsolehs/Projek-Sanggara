<?php
require __DIR__ . '/../backend/vendor/autoload.php';
$app = require_once __DIR__ . '/../backend/bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

use App\Http\Controllers\Api\DataNotaController;

$controller = new DataNotaController();
$res = $controller->show('latest');
echo $res->getContent();
