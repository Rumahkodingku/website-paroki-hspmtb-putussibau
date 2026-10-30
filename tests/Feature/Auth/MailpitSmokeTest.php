<?php

use App\Models\User;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    if (config('mail.default') !== 'smtp') {
        $this->markTestSkipped('MAIL_MAILER is not smtp (Mailpit).');
    }

    try {
        Http::timeout(2)->get('http://127.0.0.1:8025/api/v1/messages');
    } catch (Throwable $e) {
        $this->markTestSkipped('Mailpit is not reachable.');
    }

    // Bersihkan inbox Mailpit agar assertion deterministik.
    Http::delete('http://127.0.0.1:8025/api/v1/messages');
})->group('mailpit');

test('reset password mengirim email ke Mailpit', function () {
    $user = User::factory()->create();

    $this->post(route('password.email'), ['email' => $user->email])
        ->assertSessionHasNoErrors();

    $messages = (array) Http::get('http://127.0.0.1:8025/api/v1/messages')->json('messages', []);

    $recipients = collect($messages)
        ->flatMap(fn (mixed $m): array => is_array($m) && isset($m['To']) && is_array($m['To']) ? $m['To'] : [])
        ->pluck('Address')
        ->all();

    expect($recipients)->toContain($user->email);
})->group('mailpit');

test('verifikasi email mengirim email ke Mailpit', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->post(route('verification.send'))
        ->assertRedirect();

    $messages = (array) Http::get('http://127.0.0.1:8025/api/v1/messages')->json('messages', []);

    $recipients = collect($messages)
        ->flatMap(fn (mixed $m): array => is_array($m) && isset($m['To']) && is_array($m['To']) ? $m['To'] : [])
        ->pluck('Address')
        ->all();

    expect($recipients)->toContain($user->email);
})->group('mailpit');
