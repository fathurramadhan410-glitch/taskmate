<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $judulEmail ?? 'Notifikasi TaskMate' }}</title>
</head>
<body style="margin:0;padding:0;background-color:#f3f6fc;font-family:'Segoe UI',Arial,sans-serif;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f3f6fc;padding:24px 0;">
    <tr>
        <td align="center">
            <table role="presentation" width="600" cellpadding="0" cellspacing="0"
                style="max-width:600px;width:100%;background:#ffffff;border-radius:14px;overflow:hidden;box-shadow:0 4px 24px rgba(16,42,100,.08);">
                <tr>
                    <td style="background:linear-gradient(135deg,#16337F 0%,#2F80ED 55%,#00B8D9 100%);padding:28px 32px;">
                        <h1 style="margin:0;color:#ffffff;font-size:20px;">TaskMate</h1>
                        <p style="margin:4px 0 0;color:rgba(255,255,255,.85);font-size:13px;">
                            Student Collaboration &amp; Task Management
                        </p>
                    </td>
                </tr>
                <tr>
                    <td style="padding:32px;">
                        <p style="margin:0 0 8px;color:#152442;font-size:15px;">Halo {{ $nama }},</p>
                        <p style="margin:0 0 20px;color:#3d4d6b;font-size:14px;line-height:1.7;">{{ $pesan }}</p>

                        @if ($task)
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                                style="background:#f6f9ff;border:1px solid #e4e9f2;border-radius:10px;margin-bottom:20px;">
                                <tr><td style="padding:16px;">
                                    <p style="margin:0 0 6px;font-size:12px;color:#5a6b87;">DETAIL TUGAS</p>
                                    <p style="margin:0 0 10px;font-size:15px;font-weight:700;color:#152442;">{{ $task->judul }}</p>
                                    <p style="margin:0;font-size:13px;color:#3d4d6b;">
                                        Status: <b>{{ status_label($task->status) }}</b> &nbsp;·&nbsp;
                                        Deadline: <b>{{ format_waktu($task->deadline) }} WITA</b>
                                    </p>
                                </td></tr>
                            </table>
                        @endif

                        <p style="margin:0;color:#5a6b87;font-size:12px;line-height:1.7;">
                            Email ini dikirim otomatis oleh sistem TaskMate. Pengingat deadline dikirim setiap
                            pukul 07.00 WITA pada H-3, H-1, dan hari-H selama tugas belum berstatus Done.
                        </p>
                    </td>
                </tr>
                <tr>
                    <td style="background:#f6f9ff;padding:16px 32px;border-top:1px solid #e4e9f2;">
                        <p style="margin:0;color:#8b99b5;font-size:11px;">
                            © {{ date('Y') }} TaskMate — Kelompok 5, Sistem Informasi Kota Cerdas, Politeknik Negeri Banjarmasin.
                        </p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
