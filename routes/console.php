<?php

use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Spatie\OneTimePasswords\Models\OneTimePassword;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Borra registros sin verificar (más de 24 h) y códigos OTP vencidos.
// Requiere que el scheduler corra en el servidor (php artisan schedule:work o cron con schedule:run).
Schedule::command('model:prune', ['--model' => [User::class, OneTimePassword::class]])->daily();
