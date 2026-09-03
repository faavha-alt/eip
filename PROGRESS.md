# Progress — eip (Enterprise Integration Platform)

Dibuat: 2026-09-03

## Tasks

- [x] Inisialisasi repo EIP dari template
- [x] Finalisasi keputusan arsitektur & stack (Laravel 11 + Vue 3 + PostgreSQL, modular monolith, tanpa Filament)
- [x] Catat keputusan arsitektur ke CLAUDE.md
- [x] Buat dokumen desain skema database inti (docs/01-skemadb-inti.md)
- [ ] Review skema database inti (tambah kolom sesuai kebutuhan nyata)
- [x] Tambah kolom identitas resmi (NIP, NIK, id_simpeg) + catat keputusan master pegawai milik EIP
- [x] Buat dokumen rancangan integrasi awal (docs/02-rancangan-integrasi.md)
- [x] Keputusan SSO: Google Workspace sbg IdP (OIDC) lintas sistem, tidak bangun SSO sendiri (docs/03-autentikasi-sso.md)
- [ ] Tulis migrasi Laravel untuk tabel master (organisasi, unit_kerja, jabatan, pegawai, penempatan)
- [ ] Tentukan task berikutnya

## Log sesi

### 2026-09-03
- Project diinisialisasi dari template.
- Keputusan arsitektur: EIP = Enterprise Integration Platform, modular monolith,
  Laravel 11 + PostgreSQL, frontend Vue 3 + Element Plus, tanpa Filament
  (workflow rumit butuh kendali UI penuh). WA blast via API pihak ketiga.
- Dokumen desain skema database inti dibuat (5 tabel master + pivot).
