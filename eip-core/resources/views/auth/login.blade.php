<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }}</title>
    <style>
        body { font-family: system-ui, sans-serif; background: #f5f6f8; color: #1b1b18; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
        .card { background: #fff; border: 1px solid #e3e3e0; border-radius: 12px; padding: 2.5rem; max-width: 380px; width: 100%; text-align: center; box-shadow: 0 1px 3px rgba(0,0,0,.06); }
        h1 { font-size: 1.25rem; margin: 0 0 .25rem; }
        p.sub { color: #706f6c; font-size: .9rem; margin: 0 0 1.75rem; }
        .btn-google { display: inline-flex; align-items: center; gap: .6rem; padding: .65rem 1.25rem; border: 1px solid #dadce0; border-radius: 8px; background: #fff; color: #3c4043; text-decoration: none; font-size: .9rem; font-weight: 500; }
        .btn-google:hover { background: #f8f9fa; }
        .error { background: #fff2f2; color: #b3261e; border-radius: 8px; padding: .6rem .9rem; font-size: .85rem; margin-bottom: 1.25rem; }
    </style>
</head>
<body>
    <div class="card">
        <h1>EIP — Fakultas MIPA UNS</h1>
        <p class="sub">Masuk dengan akun Google domain kampus</p>

        @if (session('error'))
            <div class="error">{{ session('error') }}</div>
        @endif

        <a class="btn-google" href="{{ route('auth.google.redirect') }}">
            <svg width="18" height="18" viewBox="0 0 18 18"><path fill="#4285F4" d="M17.64 9.2c0-.64-.06-1.25-.16-1.84H9v3.48h4.84a4.14 4.14 0 0 1-1.8 2.72v2.26h2.9c1.7-1.57 2.7-3.88 2.7-6.62Z"/><path fill="#34A853" d="M9 18c2.43 0 4.47-.8 5.96-2.18l-2.9-2.26c-.8.54-1.83.86-3.06.86-2.35 0-4.34-1.59-5.05-3.72H.96v2.33A9 9 0 0 0 9 18Z"/><path fill="#FBBC05" d="M3.95 10.7A5.4 5.4 0 0 1 3.66 9c0-.59.1-1.16.29-1.7V4.97H.96A9 9 0 0 0 0 9c0 1.45.35 2.83.96 4.03l2.99-2.33Z"/><path fill="#EA4335" d="M9 3.58c1.32 0 2.51.45 3.44 1.35l2.58-2.58C13.46.89 11.43 0 9 0A9 9 0 0 0 .96 4.97l2.99 2.33C4.66 5.17 6.65 3.58 9 3.58Z"/></svg>
            Masuk dengan Google
        </a>
    </div>
</body>
</html>
