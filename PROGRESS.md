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

### 2026-09-04 (lanjutan — SSH server, GitHub, deploy produksi pertama)
- **Server produksi**: SSH ke `eipmipa@eip.mipa.uns.ac.id:1103` (CloudPanel,
  server sama dgn app lain: 203.6.149.150). Key dedicated `id_ed25519_eipmipa_web`
  + alias `eip-web` di `~/.ssh/config` (mesin dev). PHP 8.4.23, MySQL 8.4
  (Percona), Redis tersedia. Docroot tetap: `htdocs/eip.mipa.uns.ac.id/public`.
- **GitHub**: remote `origin` dibuat → `git@github.com-eip:faavha-alt/eip.git`,
  deploy key **write** dari mesin dev (`id_ed25519_eip_github`). Repo di-push
  (5 commit riwayat desain + scaffold `eip-core`).
- **Kredensial DB produksi diterima dari pengguna** (`eipmipa`/`eipmipa`,
  MySQL `127.0.0.1:3306`) — dipasang di `.env` server, **tidak** disimpan di
  git/chat berulang.
- **Deploy pertama ke produksi**: transfer kode via tar+SSH (blm ada remote
  saat itu) → `composer install --no-dev` → `.env` produksi (`APP_ENV=production`,
  `APP_DEBUG=false`) → `php artisan migrate --force` (9 tabel, **tanpa seed** —
  seeder demo tidak boleh masuk DB nyata) → `config/route/view:cache`.
  Verifikasi: `https://eip.mipa.uns.ac.id` HTTP 200.
- **Sambungkan server ke GitHub utk redeploy**: key GitHub KEDUA dibuat
  **di server** (`id_ed25519_github_eip`, private key tidak pernah keluar
  server), didaftarkan sbg deploy key **read-only** (terpisah dari key mesin
  dev yang punya write access). Server clone penuh ke `~/repo`.
- **`eip-core/deploy/deploy-eip-core.sh`** (di server, disalin dari repo):
  krn `eip-core/` cuma subfolder monorepo (docroot CloudPanel sudah tetap ke
  `htdocs/eip.mipa.uns.ac.id/`), redeploy = `git fetch`+`reset --hard` di
  `~/repo`, lalu `rsync` isi `eip-core/` ke docroot (exclude `.env`, `vendor`,
  cache/log), lalu composer install + migrate + cache. **Bukan** `git pull`
  langsung di docroot. Sudah diuji end-to-end (idempotent, situs tetap 200).
- **Cara redeploy selanjutnya**: push ke `master` dari mesin dev, lalu
  `ssh eip-web '~/deploy-eip-core.sh'`.

### 2026-09-04 (lanjutan — skema dikoreksi dari data pegawai nyata + importer)
- **`docs/data_pegawai.xlsx` dibaca & dianalisis** (190 pegawai asli; sheet
  sumber juga memuat tabel rekap statistik yg sempat salah kebaca sbg data
  pegawai — sudah difilter). Detail lengkap: `docs/04` §Langkah 2b.
- **Koreksi skema penting**: prodi FMIPA yg benar (S-1 Biologi/Farmasi/Fisika/
  Ilmu Lingkungan/Kimia/Matematika/Statistika/Profesi Apoteker — bukan
  "Informatika" yg sempat salah diseed), `status_kepegawaian` diganti ke
  kategori nyata (pns/non_pns/kontrak_profesional/purna_tugas), kolom baru
  `jenis_pegawai` (dosen/tendik, terpisah dari status), `npwp`,
  `no_seri_kepeg`, `pendidikan_terakhir`, `golongan_ruang`, `tmt_golongan`,
  `id_sumber`. `jenis_kelamin` jadi nullable (sumber tak menyediakan).
- `MasterDataSeeder` disederhanakan: HANYA UNS + Fakultas MIPA (fakta
  publik). Prodi & jabatan sungguhan dibentuk otomatis oleh importer dari
  nama asli sumber.
- **`php artisan pegawai:import {path} [--dry-run]`** dibuat & diuji di DB
  dev lokal: 190 pegawai, 20 unit_kerja, 89 jabatan, 231 penempatan, 0
  warning, **idempotent**. 58 jabatan struktural (Dekan/Wadek/Kaprodi/dst)
  dicatat ke katalog tapi SENGAJA belum di-assign ke unit (perlu tinjauan
  manual, terlalu berisiko ditebak otomatis dari judul jabatan).
- Kode di-commit ke git; **data (.xlsx, hasil import) TIDAK ikut git**.
- **Pengguna konfirmasi lanjut ke produksi.** Migrasi kolom baru + seed
  UNS/FMIPA + import dijalankan di `eip.mipa.uns.ac.id`: hasil identik dgn
  dev (190 pegawai, 89 jabatan, 231 penempatan). File .xlsx sementara di
  server (`~/tmp/`, non-publik) dihapus setelah data masuk DB.
- **Bug ditemukan & diperbaiki**: `pegawai:import` sempat membuat 2 record
  duplikat "Bagian Tata Usaha FMIPA" (kode auto-generate parent Subbagian
  tidak konsisten dgn jalur normal). Diperbaiki di kode + data existing
  (dev & produksi) di-merge manual.
- **`php artisan pegawai:assign-struktural {--dry-run}`** dibuat: assignment
  58 jabatan struktural (Dekan/Wadek/Kaprodi/Ka.Lab/dst) ke unit yg tepat,
  dikunci per orang via NIP + nama jabatan persis. 3 orang pegang jabatan
  tingkat UNS (Wakil Rektor, Kepala Subdirektorat) → dibuatkan unit
  "Rektorat Universitas Sebelas Maret" terpisah dari hierarki FMIPA.
  58/58 ter-assign di dev & produksi.
- **Status akhir produksi**: 190 pegawai, 21 unit_kerja, 89 jabatan, 289
  penempatan (231 fungsional + 58 struktural). Situs tetap sehat (HTTP 200).
- **Parser gelar depan/belakang** ditambah ke `pegawai:import` (`nama_lengkap`
  sumber datang dgn gelar tertanam, mis. "Prof. Dr. Nama, S.Si., M.Si.").
  Whitelist token gelar depan (Prof./Dr./Ir./Drs./Dra./apt./dr./dst,
  termasuk bentuk gabungan Dr.rer.nat./Dr.Eng., termasuk varian ejaan
  sumber yg tak konsisten spasi/titik). Gelar belakang = semua stlh koma
  pertama. Diterapkan via re-run import (idempotent) di dev & produksi:
  107/190 py gelar depan, 179/190 py gelar belakang. 2 nama masih
  mengandung titik ("I.F. Nurcahyo", "R. Muhammad...") — BUKAN gelar,
  itu bagian nama asli, sengaja dibiarkan.
- **Master `status_kepegawaian`/`pendidikan`/`golongan_ruang` DIKERJAKAN
  (bukan ditunda)** — pengguna minta langsung, bukan "nanti". Dinaikkan dari
  kolom string/enum jadi tabel master tersendiri (pola sama dgn organisasi/
  unit_kerja/jabatan), referensi via FK, bisa dikelola via UI nanti:
  - `status_kepegawaian` (4 baris: pns/non_pns/kontrak_profesional/purna_tugas)
  - `pendidikan` (6 baris: sma_slta..d3..s1..profesi..s2..s3, + `jenjang` utk urutan)
  - `golongan_ruang` (11 baris: II/c..IV/e, + `tingkat` utk urutan). Nilai
    sumber "X" (placeholder non-PNS) dipetakan ke null, bukan record master.
  - Migrasi: buat tabel + seed nilai referensi → tambah FK nullable ke
    `pegawai` → backfill dari kolom string lama → hapus kolom lama. Enum
    `StatusKepegawaian`/`PendidikanTerakhir` dihapus, diganti Model.
  - **Backup produksi diambil dulu** (`~/backups/eip_core_pre_master_migration_*.sql`)
    sebelum migrasi jalan — auto-mode classifier sempat menahan eksekusi
    krn migrasi ini men-drop kolom di tabel berisi data asli; dikonfirmasi
    manual oleh pengguna sebelum lanjut.
  - Diuji dev (migrate:fresh+seed+import+assign-struktural, 9/9 test lulus)
    lalu produksi: hasil identik — 190 pegawai, 0 null status/pendidikan,
    8 null golongan (utk 8 pegawai Kontrak Profesional yg memang bukan PNS).

### 2026-09-04 (lanjutan — riset referensi + lengkapi skema pegawai Tier 1+2)
- **Riset mendalam** (WebSearch, disetujui pakai): BKN SIMPEG/SIASN & DRH
  (Daftar Riwayat Hidup — riwayat pangkat/jabatan/pendidikan/keluarga,
  bukan snapshot), SISTER Kemdiktisaintek (~34 kategori data dosen), KP4
  tunjangan keluarga (10% pasangan + 2%/anak maks 2), HRIS umum.
- **Temuan penting**: **NUPTK menggantikan NIDN/NIDK/NUP** sejak
  pertengahan 2024 (regulasi Kemdiktisaintek) — identitas dosen yg
  berlaku SEKARANG utk SISTER/PDDIKTI/sertifikasi/jabatan fungsional.
  Kolom ini tidak ada sebelumnya & lebih relevan drpd NIDN yg sudah tak
  berlaku, mengingat >70% pegawai FMIPA adalah dosen.
- **Skema pegawai dilengkapi (Tier 1+2), SEMUA nullable/boleh kosong**
  (disiapkan lebih dulu, data menyusul kapan tersedia — bukan wajib
  sekarang):
  - Kolom baru di `pegawai`: `nuptk`, `agama`, `status_perkawinan`
    (enum tetap, bukan tabel master — beda dgn status_kepegawaian/
    pendidikan/golongan yg memang dinamis), `alamat_domisili`,
    `tmt_cpns`, `tmt_pns`, `no_bpjs_kesehatan`,
    `no_bpjs_ketenagakerjaan`, `no_taspen`.
  - 4 tabel baru (pola BKN: nilai TERKINI di `pegawai` + riwayat
    terpisah, tiap perubahan = baris baru): `riwayat_pendidikan`,
    `riwayat_pangkat_golongan`, `keluarga_pegawai` (dasar tunjangan
    KP4 + rangkap kontak darurat), `dokumen_pegawai` (arsip SK/ijazah).
  - **Sengaja di luar lingkup**: sertifikasi dosen + ID riset (Scopus/
    SINTA/Google Scholar) — domain modul akademik masa depan, bukan
    master kepegawaian; dicatat, tidak dibangun sekarang.
- Diuji: migrate:fresh+seed+import 190 pegawai nyata+assign-struktural
  tetap mulus (kolom baru semua null, wajar). 11/11 test lulus (2 baru).
  Deploy ke produksi mulus (murni additive, tanpa drop kolom) — 190
  pegawai tidak terganggu, tabel baru kosong sesuai rencana.
- Lanjut: L3 (OIDC Google + RBAC + Sanctum).
