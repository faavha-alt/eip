# Progress — eip (Enterprise Integration Platform)

Dibuat: 2026-09-03

## Gambaran nyata sistem (koreksi penting)

- **Sistem lama yg BEROPERASI & TETAP BERDIRI SENDIRI** (Laravel + MySQL):
  Gaji, Aset, Logistik BHP.
- **EIP = aplikasi BARU** (Laravel 13 + MySQL): memegang **master pegawai
  (satu rujukan)**, rumah modul baru (kepegawaian, perencanaan, pengadaan,
  akademik), pemanggil server WA blast.
- **Sistem lama membaca data pegawai dari EIP.** Arah: sistem lama → baca EIP.
- **DB EIP = MySQL** (seragam dgn sistem lama).
- **Arsitektur (final 2026-09-03): aplikasi terpisah per domain.** EIP Core
  (master + RBAC + API + portal SSO) + app kepegawaian/perencanaan/pengadaan/
  akademik masing-masing Laravel 13 + MySQL + SPA sendiri, deploy sendiri.
  Integrasi lewat EIP Core API + shared library. Detail & analisis:
  `docs/04-analisis-dan-rencana-eksekusi.md`.

## Tasks

### Keputusan desain (selesai)
- [x] Inisialisasi repo EIP dari template
- [x] Koreksi gambaran: sistem gaji/aset/logistik sudah ada (Laravel+MySQL) & tetap berdiri; EIP = aplikasi pusat baru
- [x] Finalisasi stack EIP: Laravel 13 + Vue 3 + **MySQL** + Redis + Element Plus; tanpa Filament
- [x] Keputusan master pegawai milik EIP Core + kolom identitas resmi (NIP, NIK, id_simpeg)
- [x] Keputusan DB EIP = MySQL (seragam sistem lama)
- [x] **Arsitektur final: aplikasi terpisah per domain** (EIP Core + app kepegawaian/perencanaan/pengadaan/akademik). Membatalkan rancangan "modular monolith dalam EIP". Keberatan analis tercatat di docs/04.
- [x] EIP Core = pemilik tunggal DB master; app kepegawaian = penulis tunggal via API; semua lain baca
- [x] RBAC terpusat di EIP Core; tiap app OIDC client sendiri; shared library composer internal utk klien Core/DTO/auth
- [x] Analisis sistem existing + pola integrasi (A: sync 1 arah → B: API call) + fase diselaraskan nilai/risiko
- [x] Dokumen desain skema database inti (docs/01-skemadb-inti.md)
- [x] Dokumen rancangan integrasi (EIP + sistem lama + WA blast) (docs/02-rancangan-integrasi.md)
- [x] Keputusan SSO: Google Workspace IdP (OIDC), tidak bangun SSO sendiri (docs/03-autentikasi-sso.md)
- [x] Mode akses: login terbuka domain kampus; role manual per akun batasi modul
- [x] Dokumen analisis & rencana eksekusi (docs/04-analisis-dan-rencana-eksekusi.md)
- [x] Konsolidasi CLAUDE.md sebagai referensi global lengkap

### Eksekusi kode — status per langkah (docs/04 §6)

- [x] **L1 — EIP Core: scaffold & fondasi** (2026-09-04). `eip-core/` (Laravel
      13.30.1) + `laravel/boost` guidelines. `.env.example` = MySQL/Redis/domain
      resmi; `.env` lokal = sqlite (sandbox dev, tanpa server MySQL).
- [x] **L2 — EIP Core: skema master + audit** (2026-09-04). Migrasi `organisasi`,
      `pegawai`, `unit_kerja`, `jabatan`, `penempatan`, `audit_logs` (tabel
      sendiri) + enum PHP + model/relasi + factory + `MasterDataSeeder`
      (struktur nyata UNS→FMIPA→5 prodi+TU) + 5 test (`MasterDataSchemaTest`,
      7/7 lulus) + Pint bersih.
- [ ] L3 — EIP Core: OIDC Google (batasi domain email) + RBAC (users/roles/permissions) + Sanctum s2s
- [ ] L4 — EIP Core: API `/api/v1/` (read master + `updated_since`; write pegawai khusus token kepegawaian; roles)
- [ ] L5 — EIP Core: portal SSO + shared library `eip/client` (HTTP client, DTO, middleware)
- [ ] L6 — App Kepegawaian: scaffold + SPA + CRUD pegawai/penempatan (tulis via Core API) + direktori
- [ ] L7 — Integrasi pilot 1 sistem lama (mis. logistik): job sync → `pegawai_ref` (pola A)
- [ ] L8 — Sambung WA blast (konfirmasi kontrak API dulu)
- [ ] L9+ — Pengadaan → Perencanaan → sisa sistem lama → Akademik

### Blocker sebelum/saat eksekusi (docs/04 §7)
- [x] ~~Ruang lingkup~~ **RESOLVED**: Fakultas MIPA UNS, domain `eip.mipa.uns.ac.id`
- [ ] Sistem lama: satu server MySQL? DB boleh dibaca? Kode bisa dimodifikasi? Siapa maintain?
- [ ] Cara sistem lama identify pegawai sekarang (kolom kunci) utk matching
- [ ] WA blast: sudah jalan atau masih desain? Kontrak API final?
- [ ] Struktur `unit_kerja` nyata (FMIPA) + apakah hierarki approval = hierarki unit persis
- [ ] Kredensial MySQL & Redis produksi (menyusul setelah hosting `eip.mipa.uns.ac.id` siap)

## Log sesi

### 2026-09-03
- Project diinisialisasi dari template.
- Awalnya stack dirancang Laravel 11 + PostgreSQL + monolith menyerap semua modul.
- KOREKSI: pengguna mengonfirmasi sistem gaji, aset, logistik SUDAH ADA
  (Laravel + MySQL) & beroperasi, tetap berdiri sendiri. EIP = aplikasi pusat
  baru sebagai sumber master pegawai yg dibaca sistem lama + rumah modul baru.
- DB EIP dikoreksi ke MySQL (seragam sistem lama). Laravel update ke 13 (versi
  terbaru).
- Keputusan master pegawai milik EIP; identitas resmi NIP/NIK/ID_SIMPEG sbg kolom
  referensi (akses sistem luar terbatas & tidak andal).
- Rancangan integrasi EIP + sistem lama + server WA blast.
- SSO: Google Workspace IdP via OIDC, SSO lintas sistem, tidak bangun sendiri.
- Mode akses: login terbuka utk akun domain kampus; role manual per akun (RBAC).
- Klarifikasi awal: modul baru EIP direncanakan modular monolith; sistem lama
  = existing yg diintegrasikan.
- Konsolidasi seluruh keputusan ke CLAUDE.md (referensi global) utk eksekusi kode.

### 2026-09-03 (sesi lanjutan — arsitektur & rencana eksekusi)
- Pertanyaan pengguna: yakin tidak ada microservice? → dibahas nuansanya
  (banyak app fisik terpisah menyerupai terdistribusi).
- Pengguna mengarahkan: kepegawaian/perencanaan/pengadaan/akademik nantinya
  berdiri sendiri-sendiri.
- Analis memberi analisis mendalam (skala fakultas, tim 1–2 orang, sistem
  existing) + rekomendasi **modular monolith siap-pecah**.
- **Keputusan pemilik proyek: tetap "aplikasi terpisah per domain"** (final).
  Keberatan analis dicatat di `docs/04` §3.2.
- Ditetapkan: EIP Core pemilik tunggal DB master; app kepegawaian penulis
  tunggal via API; RBAC terpusat di Core; tiap app OIDC client sendiri;
  shared library composer internal; alur lintas-app via event/webhook.
- Konfirmasi: **untuk data pegawai, EIP = sumber paling valid (SSOT)**;
  NIP/NIK/ID_SIMPEG sumber resmi tetap SIMPEG/Dukcapil (EIP hanya matching).
- Fase disusun ulang selaras nilai/risiko: integrasi sistem lama dinaikkan
  ke Fase 2 (pilot 1 sistem).
- Dibuat `docs/04-analisis-dan-rencana-eksekusi.md`; CLAUDE.md §1/§3/§4/§6/§8
  disesuaikan. **Belum ada kode — mulai eksekusi "satu-satu" sesi berikutnya.**

### 2026-09-04 (mulai eksekusi kode — L1 & L2 EIP Core)
- **Ruang lingkup RESOLVED**: pengguna mengonfirmasi **Fakultas MIPA UNS**,
  domain `eip.mipa.uns.ac.id` (hosting disiapkan pengguna). CLAUDE.md §7 &
  docs/04 §1 diperbarui.
- Scaffold `eip-core/`: Laravel 13.30.1 + `laravel/boost` (guidelines AI).
  `.env.example` (committed) = target nyata (MySQL, Redis, domain, placeholder
  OIDC); `.env` lokal (gitignored) = sqlite krn sandbox dev tanpa server MySQL.
- Skema master dibangun sesuai `docs/01`: migrasi `organisasi`, `pegawai`,
  `unit_kerja`, `jabatan`, `penempatan` (nama tabel Indonesia asli, bukan hasil
  pluralisasi Inggris Eloquent) + `audit_logs` (tabel sendiri, bukan paket
  pihak ketiga) + enum PHP backed + model/relasi + factory.
- `MasterDataSeeder`: struktur nyata UNS → Fakultas MIPA → prodi Informatika/
  Matematika/Fisika/Kimia/Biologi + Bagian TU, 5 jabatan dasar, 24 pegawai
  contoh dgn penempatan.
- Test `MasterDataSchemaTest` (5 test: tree unit_kerja, relasi penempatan,
  unique constraint, FK restrict on forceDelete) — 7/7 test proyek lulus,
  Pint bersih.
- **Belum push** — remote git belum dikonfigurasi (ditanyakan ke pengguna).
- Lanjut L3 (OIDC + RBAC) sesi berikutnya; kredensial MySQL/Redis produksi
  menyusul setelah hosting siap.
