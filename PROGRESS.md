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

### 2026-09-04 (lanjutan — L3: login Google OIDC + RBAC + Sanctum)
- **Auth manusia**: `laravel/socialite`. EIP Core = OIDC client sendiri ke
  Google Workspace. Login terbuka utk domain kampus (`ALLOWED_EMAIL_DOMAIN`),
  **tapi hanya diloloskan masuk kalau email sudah terdaftar sbg pegawai** —
  domain kampus sembarang tidak otomatis dapat akses (halaman "akun belum
  terdaftar" kalau belum ada di master pegawai, sesuai docs/03).
  `users`: `google_id`, `avatar`, `pegawai_id` (FK, di-resolve ulang tiap
  login by email), `is_active`, `last_login_at`; `password` jadi nullable
  (Google-only, tanpa password lokal).
- **RBAC**: `roles` + `role_user` (custom, BUKAN spatie/laravel-permission —
  blm perlu granular permission-per-aksi, cukup role→modul; lebih ringan
  utk tim kecil meski docs/04 mengizinkan spatie). `php artisan role:assign
  {email} {role}` — role manual per akun, belum ada UI admin.
- **Service-to-service**: `service_clients` (1 record per app konsumen:
  kepegawaian/gaji/aset/logistik/wa-blast), token Sanctum sendiri, **implement
  `Authenticatable` manual** (guard Sanctum mensyaratkan kontrak ini utk
  model non-`User`). `php artisan service-client:create {kode} {nama}` —
  terbit token sekali tampil. `GET /api/v1/users/roles?email=` (auth:sanctum)
  — resolve peran user by email utk dipanggil app lain.
- Diuji: 8 test baru (domain ditolak/diloloskan, belum-terdaftar tidak
  lolos, akun nonaktif ditolak, dashboard wajib login, role:assign+hasRole,
  API roles 401/200) + smoke test manual (service-client:create → curl API
  sungguhan → 200/401) + full pipeline import 190 pegawai tetap mulus.
  19/19 test lulus, Pint bersih. Deploy produksi mulus (additive).
- **Kredensial Google Cloud Console dibuat pengguna & dipasang** (`.env`
  produksi) — login Google **sungguhan diuji dan terverifikasi lewat DB**
  (bukan cuma laporan user): akun `favha@staff.uns.ac.id` berhasil masuk,
  otomatis terhubung ke pegawai "Zulfa Nurul Hakim".
- **Bug ditemukan saat tes sungguhan & diperbaiki**: `ALLOWED_EMAIL_DOMAIN`
  sempat diisi cuma `mipa.uns.ac.id` (asumsi awal, tidak diverifikasi ke
  data nyata) — padahal cek data pegawai riil: **181/189 pakai
  `@staff.uns.ac.id`, cuma 2/189 pakai `@mipa.uns.ac.id`**, 6 gmail pribadi.
  Hampir semua pegawai asli tertolak login. Diganti jadi
  `ALLOWED_EMAIL_DOMAINS` (jamak, multi-domain) = `staff.uns.ac.id,mipa.uns.ac.id`.
  Test regresi ditambah. Percobaan login pertama user sempat gagal krn bug
  ini (log server: exception Socialite, 0 user tercipta) meski awalnya
  dilaporkan "berhasil" — dicek ulang via DB, ternyata belum; setelah fix,
  dicek ulang via DB lagi dan BENAR berhasil.
- Role admin ditetapkan ke `favha@staff.uns.ac.id` via `role:assign`.

### 2026-09-04 (lanjutan — dashboard AeroDeck sebelum Langkah 4)
- Sebelum Langkah 4, diminta pengguna: tampilkan data pegawai lewat dashboard
  yang bagus. Dipakai skill **`ui-dashboard-aerodeck`** (glassmorphism Apple/
  Tesla) diadaptasi ke domain EIP — bukan konten dummy telemetry skill-nya.
- Tailwind v4 + Vite (sudah ada di scaffold Laravel, belum pernah dipakai)
  diaktifkan; Chart.js via npm. **Tanpa CDN** sesuai aturan skill (ini app
  produksi sungguhan, bukan artifact sekali pakai).
- `DashboardController`: semua angka LIVE dari DB (total/aktif pegawai,
  dosen vs tendik, unit kerja, jabatan struktural/fungsional), chart batang
  pegawai per unit (toggle Semua/Dosen/Tendik), panel "Kelengkapan Data"
  (NUPTK/no HP/jenis kelamin/golongan yg masih kosong — transparan, bukan
  disembunyikan), tabel semua unit kerja + kepala + jumlah pegawai.
- **Node.js dipasang di server produksi tanpa root** (`~/node`, binary resmi
  nodejs.org diekstrak manual — tidak ada sudo/nvm) — dibutuhkan skrip
  redeploy utk `npm ci && npm run build` setiap deploy ke depan.
- **Bug infra ditemukan & diperbaiki**: skrip `~/deploy-eip-core.sh` (yang
  dipanggil `ssh eip-web '~/deploy-eip-core.sh'`) adalah SALINAN MANUAL di
  `$HOME`, terpisah dari `eip-core/deploy/deploy-eip-core.sh` yang di-git —
  perubahan ke skrip via git TIDAK otomatis sampai ke salinan yg benar-benar
  dieksekusi. Ketahuan krn npm build "sukses" tanpa error tapi `public/build`
  tidak pernah muncul. **Diperbaiki jadi wrapper tipis**: `~/deploy-eip-core.sh`
  sekarang HANYA `git fetch && reset --hard` lalu `exec` skrip yg di-git —
  tidak akan basi lagi krn selalu tarik versi terbaru dulu sebelum jalan.
- Diuji: render dgn 190 pegawai asli (bukan cuma factory test) — HTML
  bersih, aset Vite (CSS+JS) 200 di produksi. 21/21 test lulus, Pint bersih.
- Lanjut: Langkah 4 (API `/api/v1/` master pegawai/unit_kerja/jabatan/
  organisasi utk dikonsumsi app lain).

### 2026-09-05 (Langkah 4 — API `/api/v1/` master data)
- **Otorisasi berbasis *ability* token Sanctum** (bukan role manusia),
  sesuai docs/04 §6 "satu jalur tulis": `master:read` (semua service
  client — sistem lama + app domain baru) vs `pegawai:write` (HANYA app
  kepegawaian). Middleware alias `abilities`/`ability` didaftarkan manual
  di `bootstrap/app.php` (Sanctum tak auto-register di Laravel 13).
- **Endpoint baca** (index+show, ability `master:read`): `/pegawai`,
  `/unit-kerja`, `/jabatan`, `/organisasi`. Fitur: pagination (`per_page`),
  filter `updated_since` (dasar sync inkremental sistem lama — pola A/B
  docs/04), filter tambahan (`unit_kerja_id` utk pegawai, `parent_id`/
  `jenis_unit` utk unit-kerja, `jenis` utk jabatan). `PegawaiResource`
  sertakan `penempatan_utama` (unit+jabatan aktif) via `whenLoaded`.
- **Endpoint tulis** (ability `pegawai:write`): POST/PUT `/pegawai`,
  POST/PUT `/penempatan` — utk app kepegawaian (belum ada, disiapkan lbh
  dulu).
- `service-client:create` dapat `--abilities` (default `master:read`).
- **Bug ditemukan+diperbaiki**: `store()` tidak `refresh()` model stlh
  `create()` — kolom dgn default DB (`is_active`, `status`) tampil `null`
  di response walau sebenarnya terisi di DB.
- Diuji: 10 test API baru + smoke test end-to-end sungguhan (curl thd
  server dev + **produksi**, data asli 190 pegawai): baca 200 + pagination
  benar (`total: 190`), tulis via token baca-saja 403, tulis via token
  `pegawai:write` 201. Service client percobaan di produksi dihapus stlh
  verifikasi. 31/31 test proyek lulus, Pint bersih.
- Lanjut: Langkah 2 (integrasi pilot 1 sistem lama, mis. logistik — bikin
  `service-client:create logistik` sungguhan + job sync di sisi sistem
  lama) ATAU Langkah 3 lanjutan (shared library `eip/client` blm dibuat)
  ATAU mulai app Kepegawaian (Langkah 1 blm ada, cuma modul master di
  EIP Core yg sudah jalan).

### 2026-09-05 (revisi arsitektur — Kepegawaian gabung ke EIP Core)
- Pengguna pilih mulai Langkah 1 (app Kepegawaian) duluan, lalu bertanya:
  lebih enak digabung ke EIP Core atau hosting terpisah?
- **Keputusan REVISI (membalik sebagian "aplikasi terpisah per domain"
  final 2026-09-03)**: Kepegawaian jadi **modul di dalam EIP Core**, BUKAN
  app terpisah. Alasan teknis: seluruh data Kepegawaian (pegawai,
  penempatan, riwayat_pendidikan, riwayat_pangkat_golongan,
  keluarga_pegawai, dokumen_pegawai) sudah dari awal disimpan di DB EIP
  Core sendiri, bukan DB terpisah — app terpisah akan jadi "app kosong"
  yg cuma UI pemanggil API terus-menerus. Alasan operasional: pengalaman
  langsung menyiapkan hosting EIP Core (SSH, DB, Node tanpa root, deploy
  script) menunjukkan beban ops signifikan utk tim 1-2 orang, tidak
  sepadan diulang utk app yg sebenarnya cuma antarmuka data yg sudah ada.
  Perencanaan/Pengadaan/Akademik TETAP direncanakan terpisah (py data
  sendiri yg genuinely beda: rencana, pengajuan).
- Frontend modul Kepegawaian: **Blade + Tailwind** (gaya AeroDeck sama dgn
  dashboard EIP Core), BUKAN Vue SPA — konsisten dgn keputusan dashboard
  kemarin, jauh lebih cepat drpd setup Vue+Vite+Pinia+Router terpisah.
  CRUD langsung via Eloquent (tanpa panggil API HTTP internal — krn satu
  app/DB yg sama). Akses tulis dibatasi role `admin-kepegawaian`/`admin`.
- CLAUDE.md §1/§3/§8 diperbarui reflect revisi ini.
- Cakupan v1 (default, blm dikonfirmasi eksplisit): CRUD pegawai +
  penempatan + direktori/pencarian. Riwayat/keluarga/dokumen menyusul.
- **Modul dibangun & di-deploy**: `app/Support/PegawaiRules` (validasi
  bersama API+modul), middleware `role:xxx` (`EnsureHasRole` — lolos kalau
  py role yg disebut ATAU `admin`), routes `/kepegawaian` (index/show
  publik-login, create/edit/update+penempatan digerbang role), 5 view
  (index/show/create/edit/_form) gaya AeroDeck. Business rule: penempatan
  baru `is_posisi_utama=true` otomatis menutup posisi utama lama (cegah 2
  aktif sekaligus — gap yg sempat dicatat sesi sebelumnya).
- Role `admin-kepegawaian` ditetapkan ke `favha@staff.uns.ac.id` di
  produksi. Sidebar EIP Core diperbarui: "Direktori Pegawai" & "Ringkasan"
  jadi link sungguhan dgn state aktif.
- Diuji: 10 test modul baru + render manual data asli 190 pegawai
  (index/show/create, termasuk pegawai jabatan struktural) — bersih.
  41/41 test proyek lulus. Deploy produksi mulus, 190 pegawai tak
  terganggu.
- Lanjut: isi riwayat_pendidikan/pangkat/keluarga/dokumen dari UI (msh
  cuma tampil, blm ada form input), ATAU integrasi pilot sistem lama,
  ATAU shared library `eip/client`, ATAU mulai app Perencanaan/Pengadaan.

### 2026-09-05 (lanjutan — lengkapi CRUD Kepegawaian penuh)
- Diminta pengguna "lanjut full semuanya": form tambah+hapus (soft delete)
  utk `riwayat_pendidikan`, `riwayat_pangkat_golongan`, `keluarga_pegawai`,
  `dokumen_pegawai` — sebelumnya cuma tampil "menyusul".
- **Sinkronisasi otomatis nilai "terkini"**: tambah riwayat pendidikan/
  pangkat baru otomatis update `pegawai.pendidikan_terakhir_id` /
  `golongan_ruang_id`+`tmt_golongan` kalau jenjang/TMT baru ≥ yg tercatat
  — riwayat simpan histori lengkap, pegawai simpan nilai terkini (pola
  BKN, docs/04).
- **Upload dokumen sungguhan**: disk `local` (`storage/app/private`,
  TIDAK ter-symlink ke public — isinya PII: SK/ijazah/KTP), unduh lewat
  route ber-otentikasi (bukan URL publik langsung).
- **Bug kritis ditemukan SEBELUM sempat terjadi**: skrip deploy
  (`rsync --delete`) belum exclude `storage/app/private`/`public` —
  dokumen yg diupload user akan terhapus di redeploy berikutnya (source
  git cuma py `.gitignore` placeholder). Diperbaiki di
  `eip-core/deploy/deploy-eip-core.sh` sebelum ada yg sempat upload
  dokumen sungguhan.
- Diuji: 6 test baru (sinkron nilai terkini, soft-delete keluarga,
  upload+unduh+hapus dokumen via `Storage::fake`, gerbang role) + render
  manual show page dgn data asli 190 pegawai. 47/47 test proyek lulus.
  Deploy produksi mulus, 190 pegawai tak terganggu.
- **Modul Kepegawaian kini genuinely lengkap** (Fase 1 selesai): CRUD
  pegawai + penempatan + riwayat pendidikan + riwayat pangkat/golongan +
  keluarga + dokumen, semua dari UI.
- Lanjut: Langkah 2 (integrasi pilot sistem lama), shared library
  `eip/client` (kini relevan utk Perencanaan/Pengadaan/Akademik yg
  genuinely terpisah), atau mulai app Perencanaan/Pengadaan.

### 2026-09-05 (lanjutan — sinkron data dgn rekap resmi SIMPEG)
- Pengguna dapat akses lihat SIMPEG (https://simpeg.uns.ac.id, perlu SSO
  login — dicoba WebFetch dulu, tidak bisa krn butuh sesi login; Claude in
  Chrome jg tidak tersedia di sesi CLI remote ini, cuma bisa dari Desktop
  app Windows lokal). Solusi aktual: pengguna sudah py rekap ekspor SIMPEG
  lengkap (`docs/rekap_pegawai.xlsx`, 7 sheet, PII — ikut `.gitignore`
  `*.xlsx` sama spt `data_pegawai.xlsx`).
- **Bug kritis ditemukan & diperbaiki**: `ImportPegawaiFromExcel` salah
  pakai format tanggal `'m/d/y'` (US) utk kolom "TMT Gol."/"TMT JF" yg
  ternyata `DD/MM/YY` (Indonesia) — terbukti pasti dari raw value spt
  "22/12/20" (hari 22 mustahil jadi bulan). Berdampak salah pada
  `pegawai.tmt_golongan` HAMPIR SEMUA 190 pegawai (hari&bulan tertukar,
  mis. 1 April jadi 4 Januari) + kemungkinan `penempatan.tgl_mulai` dari
  TMT JF. Diperbaiki jadi `'d/m/y'`, re-run `pegawai:import` lokal &
  produksi (idempoten via `id_sumber`, 190 update, 0 baru) — dikonfirmasi
  benar via cross-check ke SIMPEG (id_simpeg=1087: 2018-04-01, cocok).
- **Command baru `pegawai:sync-simpeg {path} {--dry-run}`**: cocokkan
  pegawai via `id_simpeg` = kolom "id_sumber" tiap sheet SIMPEG (divalidasi
  100% cocok NIP, 182/190 match — 8 sisanya memang "Data Kosong" di
  SIMPEG, id 9901-9908 bukan ID SIMPEG asli). Mengisi `jenis_kelamin` &
  `agama` (sblnya 100% kosong, SIMPEG = sumber resmi ASN utk field ini).
  Import idempoten riwayat pendidikan (771 baris, sheet Pendidikan
  Tinggi+Dasar) & riwayat pangkat/golongan (941 baris) ke tabel yg sudah
  ada. Nilai pegawai "terkini" (`pendidikan_terakhir_id`,
  `golongan_ruang_id`+`tmt_golongan`) DIBANDINGKAN ke riwayat SIMPEG
  paling baru tapi TIDAK ditimpa otomatis kalau beda — cuma dilaporkan
  sbg peringatan (10 kasus genuine tersisa setelah fix bug tanggal, turun
  dari 180 — campuran naik/turun jenjang, butuh tinjau manual/HR, bukan
  ditimpa otomatis krn bisa jadi SIMPEG yg lag, bukan data lama yg salah).
- **Tabel + model baru `riwayat_jabatan`** (struktural & fungsional, arsip
  SIMPEG read-only, 1174 baris dari sheet Jabatan Fungsional+Kelola) +
  kartu tampilan di halaman detail pegawai. Master `golongan_ruang`
  dilengkapi I/a-I/d & II/a-II/b, master `pendidikan` dilengkapi
  SD/SMP/D4 (jenjang yg muncul di riwayat SIMPEG tp blm ada di seed awal).
- Diuji: dry-run lokal & produksi menghasilkan angka identik sebelum
  ditulis sungguhan; 1 test baru (halaman detail dgn riwayat penuh
  termasuk jabatan) + fix 1 assertion count yg berubah krn master
  bertambah. 48/48 test lulus. Backup DB produksi sblm migrasi
  (`~/backups/eip_core_pre_simpeg_sync_*.sql`). File xlsx dihapus dari
  server setelah selesai (PII, jangan tersimpan permanen di sana).
- Lanjut: tinjau manual 10 kasus perbedaan nilai terkini (mgkn perlu
  konfirmasi ke SIMPEG/HR langsung); Langkah 2 integrasi pilot / shared
  library / app Perencanaan-Pengadaan.

### 2026-09-05 (lanjutan — integrasi pilot: wa-blast)
- **Fase 2 dimulai**: wa-blast (panel broadcast WA humas, project terpisah)
  disambungkan sbg konsumen `GET /api/v1/pegawai` (service client `wa-blast`,
  ability `master:read`, token diterbitkan via `service-client:create`).
- Fix kecil: `PegawaiController::RELATIONS` belum eager-load
  `penempatan.unitKerja`/`penempatan.jabatan` → `unit_kerja_nama`/
  `jabatan_nama` di response API selalu null. Diperbaiki + 2 asersi
  regresi (`ApiV1Test`), 48/48 test tetap hijau. Deploy produksi mulus.
- **Hairpin NAT**: wa-blast & EIP Core sehosting (203.6.149.150) tapi
  server tak bisa menghubungi domainnya sendiri lewat IP publik (timeout).
  Diselesaikan di sisi wa-blast (`CURLOPT_RESOLVE` ke `127.0.0.1`) —
  tak perlu perubahan di EIP Core.
- **Ditemukan data kotor**: sebagian `no_hp` pegawai (asal migrasi SIMPEG)
  bukan nomor telepon sama sekali (mis. `"5/4/222691635"`). wa-blast
  menambah validasi (wajib berawalan `628`) sebelum memakainya — TIDAK
  perlu perbaikan data di EIP Core (field `no_hp` memang bukan sumber
  utama nomor WA; wa-blast tetap acuan nomor).
- Sinkron pertama sukses: 190 pegawai → 190 kontak baru di wa-blast
  (1 nonaktif → diblacklist otomatis). Nama & unit kerja tersinkron;
  nomor HP TIDAK ikut disinkron balik (SSOT nomor tetap di wa-blast).
  Cron nightly `03:15` di wa-blast utk sinkron berkelanjutan.
- Lanjut: Perencanaan/Pengadaan/Akademik (app terpisah), atau shared
  library `eip/client`, atau integrasi sistem lama berikutnya (gaji/aset/
  logistik) dgn pola yang sama.
