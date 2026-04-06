<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $user = App\Models\User::first();
    if (!$user) {
        echo "No users found.\n";
        exit;
    }
    dump($user->email);
    
    $mail = new App\Mail\Auth\ForgotPasswordMail($user, ['otp' => '123456']);
    Illuminate\Support\Facades\Mail::to($user->email)->send($mail);
    echo "OK Mail Sent\n";
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
