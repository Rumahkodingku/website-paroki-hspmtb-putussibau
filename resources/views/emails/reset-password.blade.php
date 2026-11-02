<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Atur Ulang Kata Sandi</title>
</head>
<body style="margin:0;padding:0;background-color:#F7F8FA;font-family:'Mona Sans',ui-sans-serif,system-ui,-apple-system,'Segoe UI',sans-serif;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#F7F8FA;padding:32px 16px;">
    <tr>
        <td align="center">
            <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background-color:#FFFFFF;border:1px solid #E0E3E8;border-radius:14px;overflow:hidden;">
                <tr>
                    <td style="padding:32px 32px 16px;text-align:center;">
                        <img src="{{ asset('logo.png') }}" alt="Logo Paroki HSPMTB" width="48" height="48" style="display:block;margin:0 auto 12px;" />
                        <p style="margin:0;font-size:13px;font-weight:600;letter-spacing:0.08em;text-transform:uppercase;color:#AB020E;">Paroki Hati Santa Perawan Maria Tak Bernoda</p>
                        <p style="margin:4px 0 0;font-size:12px;color:#626B79;">Putussibau, Kapuas Hulu, Kalimantan Barat</p>
                    </td>
                </tr>
                <tr><td style="padding:0 32px;"><div style="height:1px;background-color:#ECEEF2;"></div></td></tr>
                <tr>
                    <td style="padding:32px;">
                        <h1 style="margin:0 0 16px;font-size:22px;font-weight:600;color:#171A21;line-height:1.3;">Atur Ulang Kata Sandi</h1>
                        <p style="margin:0 0 16px;font-size:16px;line-height:1.65;color:#343A46;">Halo {{ $user->name ?? 'Bapak/Ibu' }},</p>
                        <p style="margin:0 0 24px;font-size:16px;line-height:1.65;color:#343A46;">Kami menerima permintaan untuk mengatur ulang kata sandi akun Anda. Klik tombol di bawah untuk melanjutkan.</p>
                        <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 auto 24px;">
                            <tr>
                                <td style="border-radius:6px;background-color:#AB020E;">
                                    <a href="{{ $url }}" style="display:inline-block;padding:12px 24px;font-size:14px;font-weight:600;color:#FFFFFF;text-decoration:none;border-radius:6px;">Atur Ulang Kata Sandi</a>
                                </td>
                            </tr>
                        </table>
                        <p style="margin:0 0 8px;font-size:13px;color:#626B79;">Tautan ini akan kedaluwarsa dalam {{ $expire ?? 60 }} menit.</p>
                        <p style="margin:0 0 24px;font-size:13px;color:#626B79;">Jika Anda tidak meminta pengaturan ulang kata sandi, abaikan email ini. Kata sandi Anda tetap aman.</p>
                        <p style="margin:0 0 8px;font-size:12px;color:#77808D;">Jika tombol tidak berfungsi, salin dan tempel URL berikut ke peramban Anda:</p>
                        <p style="margin:0;font-size:12px;color:#AB020E;word-break:break-all;">{{ $url }}</p>
                    </td>
                </tr>
                <tr>
                    <td style="padding:24px 32px;background-color:#F7F8FA;border-top:1px solid #ECEEF2;text-align:center;">
                        <p style="margin:0;font-size:12px;color:#626B79;">&copy; {{ date('Y') }} Paroki Hati Santa Perawan Maria Tak Bernoda Putussibau</p>
                        <p style="margin:4px 0 0;font-size:12px;color:#77808D;">Putussibau, Kapuas Hulu, Kalimantan Barat</p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>