<?php

namespace App\Services\Installation;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Throwable;

final class MailConnectionTester
{
    /**
     * @param  array<string, mixed>  $data
     * @return array{ok: bool, message: string}
     */
    public function test(array $data): array
    {
        $mailer = (string) ($data['mailer'] ?? 'smtp');
        $host = (string) ($data['host'] ?? '127.0.0.1');
        $port = (int) ($data['port'] ?? 1025);
        $username = $data['username'] ?? null;
        $password = $data['password'] ?? null;
        $fromAddress = (string) ($data['from_address'] ?? 'noreply@paroki-hspmtb.local');
        $fromName = (string) ($data['from_name'] ?? config('app.name'));
        $to = (string) ($data['to'] ?? $fromAddress);

        Config::set('mail.default', $mailer);
        Config::set('mail.mailers.smtp.host', $host);
        Config::set('mail.mailers.smtp.port', $port);
        Config::set('mail.mailers.smtp.username', $username);
        Config::set('mail.mailers.smtp.password', $password);
        Config::set('mail.from.address', $fromAddress);
        Config::set('mail.from.name', $fromName);

        try {
            Mail::raw('Ini adalah email uji dari proses instalasi Website Paroki HSPMTB.', function ($message) use ($fromAddress, $fromName, $to) {
                $message->to($to)->subject('Email Uji Instalasi - Website Paroki HSPMTB');
                $message->from($fromAddress, $fromName);
            });

            return ['ok' => true, 'message' => 'Email uji berhasil dikirim.'];
        } catch (Throwable $exception) {
            return ['ok' => false, 'message' => 'Gagal mengirim email: '.$exception->getMessage()];
        }
    }
}
