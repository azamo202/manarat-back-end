<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Force login as user 1 just in case
$user = App\Models\User::find(1);
$app->make('auth')->guard('sanctum')->setUser($user);

$request = Illuminate\Http\Request::create('/api/quizzes/3', 'GET');
$response = $kernel->handle($request);
echo "Status: " . $response->getStatusCode() . "\n";
echo "Content: " . substr($response->getContent(), 0, 500) . "\n";
