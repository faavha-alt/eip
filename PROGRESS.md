# Progress — eip (Enterprise Integration Platform)

Dibuat: 2026-09-03

## Tasks

### Keputusan desain (selesai)
- [x] Inisialisasi repo EIP dari template
- [x] Finalisasi keputusan arsitektur & stack (Laravel 11 + Vue 3 + PostgreSQL, modular monolith, tanpa Filament)
- [x] Keputusan master pegawai milik EIP + kolom identitas resmi (NIP, NIK, id_simpeg)
- [x] Dokumen desain skema database inti (docs/01-skemadb-inti.md)
- [x] Dokumen rancangan integrasi awal + WA blast (docs/02-rancangan-integrasi.md)
- [x] Keputusan SSO: Google Workspace IdP (OIDC), tidak bangun SSO sendiri (docs/03-autentikasi-sso.md)
- [x] Mode akses: login terbuka domain kampus; role manual per akun batasi modul
- [x] Konsolidasi CLAUDE.md sebagai referensi global lengkap

### Belum dikerjakan (jalankan saat eksekusi kode)
- [ ] Review & finalisasi skema DB inti sesuai kebutuhan nyata kampus
- [ ] Tulis migrasi Laravel untuk tabel master (organisasi, unit_kerja, jabatan, pegawai, penempatan)
- [ ] Konfigurasi OIDC Google (klien, domain email yang diizinkan)
- [ ] Rancang skema role awal (RBAC)
- [ ] Tentukan struktur `unit_kerja` nyata kampus
- [ ] Sambungkan server WA blast sebagai notifikasi lintas modul

## Log sesi

### 2026-09-03
- Project diinisialisasi dari template.
- Keputusan arsitektur: EIP = Enterprise Integration Platform, modular monolith,
  Laravel 11 + PostgreSQL, frontend Vue 3 + Element Plus, tanpa Filament
  (workflow rumit butuh kendali UI penuh). WA blast via API pihak ketiga.
- Dokumen desain skema database inti dibuat (5 tabel master + pivot).
- Master pegawai dimiliki EIP; identitas resmi NIP/NIK/ID_SIMPEG sbg kolom
  referensi (akses sistem luar terbatas & tidak andal).
- Rancangan integrasi EIP + server WA blast (blueprint & fase implementasi).
- SSO: Google Workspace sbg IdP via OIDC, SSO lintas sistem, tidak bangun sendiri.
- Mode akses: login terbuka utk akun domain kampus; role ditetapkan manual per
  akun (RBAC di EIP) menentukan modul yang bisa diakses.
- Konsolidasi seluruh keputusan ke CLAUDE.md (referensi global) sbg dasar
  eksekusi kode di Claude Code.
