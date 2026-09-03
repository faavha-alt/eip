# Progress — eip (Enterprise Integration Platform)

Dibuat: 2026-09-03

## Gambaran nyata sistem (koreksi penting)

- **Sistem lama yg BEROPERASI & TETAP BERDIRI SENDIRI** (Laravel + MySQL):
  Gaji, Aset, Logistik BHP.
- **EIP = aplikasi BARU** (Laravel 13 + MySQL): memegang **master pegawai
  (satu rujukan)**, rumah modul baru (kepegawaian, perencanaan, pengadaan,
  akademik), pemanggil server WA blast.
- **Sistem lama membaca data pegawai dari EIP.** Arah: sistem lama → baca EIP.
- **DB EIP = MySQL** (seragam dgn sistem lama). Modular monolith DALAM EIP.

## Tasks

### Keputusan desain (selesai)
- [x] Inisialisasi repo EIP dari template
- [x] Koreksi gambaran: sistem gaji/aset/logistik sudah ada (Laravel+MySQL) & tetap berdiri; EIP = aplikasi pusat baru
- [x] Finalisasi stack EIP: Laravel 13 + Vue 3 + **MySQL** + Redis + Element Plus; modular monolith dalam EIP; tanpa Filament
- [x] Keputusan master pegawai milik EIP + kolom identitas resmi (NIP, NIK, id_simpeg)
- [x] Keputusan DB EIP = MySQL (seragam sistem lama)
- [x] Dokumen desain skema database inti (docs/01-skemadb-inti.md)
- [x] Dokumen rancangan integrasi (EIP + sistem lama + WA blast) (docs/02-rancangan-integrasi.md)
- [x] Keputusan SSO: Google Workspace IdP (OIDC), tidak bangun SSO sendiri (docs/03-autentikasi-sso.md)
- [x] Mode akses: login terbuka domain kampus; role manual per akun batasi modul
- [x] Konsolidasi CLAUDE.md sebagai referensi global lengkap

### Belum dikerjakan (jalankan saat eksekusi kode)
- [ ] Review & finalisasi skema DB inti sesuai kebutuhan nyata kampus
- [ ] Tulis migrasi Laravel utk tabel master (organisasi, unit_kerja, jabatan, pegawai, penempatan)
- [ ] Konfigurasi OIDC Google (klien, domain email yang diizinkan)
- [ ] Rancang skema role awal (RBAC)
- [ ] Tentukan struktur `unit_kerja` nyata kampus
- [ ] Konfirmasi mekanisme integrasi tiap sistem lama (gaji/aset/logistik): salinan lokal+sync vs query live, akses API/DB nyata
- [ ] Sambungkan server WA blast sebagai notifikasi lintas modul

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
- Konsolidasi seluruh keputusan ke CLAUDE.md (referensi global) utk eksekusi kode.
