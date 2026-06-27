<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$quiz = App\Models\Quiz::find(3);
if ($quiz) {
    print_r($quiz->toArray());
    echo "\nIs Available: " . ($quiz->isAvailable() ? 'Yes' : 'No') . "\n";
} else {
    echo "Quiz 3 not found\n";
}
