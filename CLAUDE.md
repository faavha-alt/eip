# eip — Enterprise Integration Platform

Platform integrasi sistem internal kampus. Memadukan modul kepegawaian, gaji,
perencanaan, aset, pengadaan, logistik BHP, WA blast, dan (nantinya) akademik
dalam satu aplikasi terintegrasi.

## Arsitektur

- **Modular monolith**: satu backend, satu database, satu frontend. Tiap domain
  menjadi modul dalam codebase yang sama dengan batas jelas.
- **Backend**: Laravel 11 + REST API (`/api/v1/`), pola Service layer per domain
  (Controller tipis → Service logika bisnis → Repository/Model). Dilarang
  controllers gemuk.
- **Database**: PostgreSQL satu instance. Master data dirujuk lintas modul,
  tidak diduplikasi.
- **Auth**: Laravel Sanctum (token API).
- **Frontend**: Vue 3 + Vite + TypeScript + Pinia + Vue Router (SPA terpisah).
- **UI kit**: Element Plus.
- **Tidak pakai Filament** (keputusan: workflow rumit di beberapa tempat,
  butuh kendali penuh UI).
- **WA Blast**: Laravel Queue + Redis, pakai API pihak ketiga
  (Fonnte/Wablas/Qontak), bukan gateway sendiri.
- **Workflow**: State Machine terpusat di backend (daftar status + transisi
  sah per modul).

## Data master inti

`pegawai`, `unit_kerja` (pohon hierarki), `jabatan`, `organisasi`, pivot
`penempatan`. Dirujuk oleh gaji/aset/pengadaan/logistik/WA blast/akademik.
Detail: `docs/01-skemadb-inti.md`.

## Konvensi

- Bahasa kode: Inggris; komentar/dokumen: Indonesia.
- Penamaan DB `snake_case`, tabel jamak, model singular.
- Satu jalur tulis data pegawai (hanya modul kepegawaian); modul lain baca.
- API versioned `/api/v1/`.
- Timeline & soft delete pada tabel yang butuh audit.

## Status

Lihat `PROGRESS.md` untuk progres detail dan riwayat sesi kerja.
