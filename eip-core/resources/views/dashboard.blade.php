<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard — {{ config('app.name') }}</title>
    <style>
        body { font-family: system-ui, sans-serif; background: #f5f6f8; color: #1b1b18; margin: 0; padding: 2rem; }
        .wrap { max-width: 640px; margin: 0 auto; }
        .card { background: #fff; border: 1px solid #e3e3e0; border-radius: 12px; padding: 1.75rem; box-shadow: 0 1px 3px rgba(0,0,0,.06); }
        h1 { font-size: 1.15rem; margin: 0 0 1.25rem; }
        dl { display: grid; grid-template-columns: 140px 1fr; row-gap: .5rem; font-size: .9rem; }
        dt { color: #706f6c; }
        dd { margin: 0; }
        .badge { display: inline-block; background: #eef2ff; color: #3730a3; border-radius: 999px; padding: .15rem .6rem; font-size: .75rem; margin: 0 .25rem .25rem 0; }
        form.logout { margin-top: 1.5rem; }
        button { background: none; border: 1px solid #dadce0; border-radius: 8px; padding: .5rem 1rem; font-size: .85rem; cursor: pointer; }
        button:hover { background: #f8f9fa; }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="card">
            <h1>EIP — Fakultas MIPA UNS</h1>
            <dl>
                <dt>Nama</dt><dd>{{ $user->name }}</dd>
                <dt>Email</dt><dd>{{ $user->email }}</dd>
                <dt>Pegawai</dt><dd>{{ $user->pegawai?->nama_lengkap ?? '—' }}</dd>
                <dt>Peran</dt>
                <dd>
                    @forelse ($user->roles as $role)
                        <span class="badge">{{ $role->kode }}</span>
                    @empty
                        <span style="color:#706f6c">Belum ada peran ditetapkan</span>
                    @endforelse
                </dd>
            </dl>
            <form class="logout" method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit">Keluar</button>
            </form>
        </div>
    </div>
</body>
</html>
