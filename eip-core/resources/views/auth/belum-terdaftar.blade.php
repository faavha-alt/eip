<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Akun belum terdaftar — {{ config('app.name') }}</title>
    <style>
        body { font-family: system-ui, sans-serif; background: #f5f6f8; color: #1b1b18; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
        .card { background: #fff; border: 1px solid #e3e3e0; border-radius: 12px; padding: 2.5rem; max-width: 420px; width: 100%; text-align: center; box-shadow: 0 1px 3px rgba(0,0,0,.06); }
        h1 { font-size: 1.15rem; margin: 0 0 .75rem; }
        p { color: #454440; font-size: .9rem; line-height: 1.5; }
        code { background: #f1f1ef; padding: .1rem .35rem; border-radius: 4px; }
        a.back { display: inline-block; margin-top: 1.5rem; color: #706f6c; font-size: .85rem; text-decoration: underline; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Login Google berhasil, tapi akun belum terdaftar</h1>
        <p>
            Email <code>{{ $email }}</code> berhasil masuk domain kampus, tapi
            belum terdaftar sebagai pegawai di EIP. Hubungi admin kepegawaian
            untuk didaftarkan lebih dulu.
        </p>
        <a class="back" href="{{ route('login') }}">Kembali ke halaman login</a>
    </div>
</body>
</html>
