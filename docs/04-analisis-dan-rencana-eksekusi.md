# EIP — Analisis Sistem & Rencana Eksekusi

> Dokumen analis/perancang sistem: menilai kondisi *existing*, mengunci
> keputusan arsitektur, dan menurunkannya jadi langkah eksekusi berurutan.

Status: **v0.2** — dibuat 2026-09-03, diperbarui 2026-09-04 (mulai eksekusi kode).

---

## 1. Ruang lingkup — **RESOLVED 2026-09-04**

**Fakultas MIPA, Universitas Sebelas Maret (UNS).** Domain `eip.mipa.uns.ac.id`
disiapkan pengguna (+ hosting). Ini skala **1 fakultas** (bukan 1 universitas
penuh) — asumsi tim 1–2 orang & estimasi jumlah pegawai/unit di §0 lama
terpakai. Seed data contoh (`MasterDataSeeder`) memakai struktur nyata: UNS
(organisasi) → Fakultas MIPA (unit_kerja) → prodi Informatika/Matematika/
Fisika/Kimia/Biologi + Bagian Tata Usaha.

Sebelumnya, dua kelas masalah ini dipertimbangkan:

| | 1 Fakultas | 1 Universitas |
|---|---|---|
| Pegawai | ~50–500 | ribuan |
| Unit kerja | ~5–30 | ratusan |
| Pengajuan/bulan | puluhan | ratusan–ribuan |
| Admin/operator | 1–2 | tim |

Asumsi kerja dokumen ini: **1 fakultas, tim 1–2 orang, in-house** — kini
terkonfirmasi (Fakultas MIPA UNS). ~~BLOCKER #1~~ selesai.

---

## 2. Analisis sistem *existing*

### 2.1 Yang diketahui vs tidak

| Sistem | Terkonfirmasi | Belum diketahui (WAJIB digali) |
|---|---|---|
| **Gaji** | Laravel + MySQL, beroperasi | Punya API? Ada tabel pegawai lokal + kolom kunci? Kode bisa dimodifikasi? Siapa maintain? |
| **Aset** | Laravel + MySQL, beroperasi | idem + cara PIC aset merujuk pegawai |
| **Logistik BHP** | Laravel + MySQL, beroperasi | idem + relasi ke unit_kerja |
| **WA blast** | "sudah dirancang" | Sudah jalan atau masih desain? Kontrak API final? |

### 2.2 Temuan

1. **Sistem lama hampir pasti belum punya API.** Pola CRUD Blade + data pegawai
   diketik ulang / impor Excel per app. "Sistem lama membaca pegawai dari EIP"
   **belum ada jalannya hari ini** — harus dibangun ke tiap app (3× pekerjaan,
   di kode yang mungkin bukan kita tulis).
2. **Sumber fragmentasi utama = data pegawai terduplikasi 3–4 salinan** yang
   tidak sinkron. Ini masalah #1 yang EIP pecahkan; nilainya paling tinggi.
3. **"WA blast sudah dirancang" ≠ sudah ada.** Pastikan statusnya; kalau masih
   desain, itu dependensi eksternal berisiko untuk Fase 2–3.
4. **Risiko terbesar proyek = integrasi ke 3 sistem lama**, bukan modul baru.
   Effort & jadwalnya paling tidak pasti → dijadikan pilot lebih awal (Fase 2).

### 2.3 Opsi integrasi ke sistem lama (termurah dulu)

| Pola | Effort | Cocok bila |
|---|---|---|
| **A.** Sync 1 arah EIP Core → tabel `pegawai_ref` di DB tiap app (job terjadwal) | Rendah | Sistem lama satu server MySQL, DB boleh dibaca, kode app minim diubah |
| **B.** App lama dimodifikasi memanggil EIP Core API (+ cache lokal) | Sedang | Kode app lama bisa diubah |
| **C.** App lama repoint FK ke tabel `pegawai` EIP Core (shared DB) | Tinggi risiko | Hampir tak pernah layak — kopling terlalu erat, JANGAN |

**Rencana:** pola **A** untuk transisi cepat, migrasi ke **B** per app saat ada waktu.

---

## 3. Keputusan arsitektur (final 2026-09-03)

**Aplikasi terpisah per domain.** EIP Core + tiap domain baru = aplikasi
mandiri (Laravel 13 + MySQL + SPA Vue sendiri, deploy sendiri), terintegrasi
lewat EIP Core API.

```
EIP Core (app): master pegawai/unit_kerja/jabatan/organisasi · RBAC · API /api/v1/ · portal SSO · audit_log
  ▲  ▲  ▲  ▲
  │  │  │  └── (baca)  Gaji · Aset · Logistik (lama)  — via sync/API, pola identik
  │  │  └───── (baca)  App Perencanaan  (DB + SPA sendiri)
  │  └──────── (baca)  App Pengadaan    (DB + SPA sendiri)
  └─── (tulis master via API) ── App Kepegawaian (DB + SPA sendiri) — PENULIS TUNGGAL
                                 App Akademik (nanti, DB + SPA sendiri)
WA blast (terpisah) ← dipanggil app yang butuh notifikasi
```

### 3.1 Aturan yang mengikat

1. **EIP Core satu-satunya pemilik DB master** (`organisasi`, `unit_kerja`,
   `jabatan`, `pegawai`, `penempatan`). Tidak ada FK lintas-DB antar app.
2. **App kepegawaian = penulis tunggal** data pegawai/penempatan, lewat
   EIP Core API (service token). Semua app lain + sistem lama **hanya baca**.
   Bila data beda, **EIP Core yang benar** — app lain sinkron ulang.
3. **NIP/NIK/ID_SIMPEG** = kolom referensi di EIP Core. Sumber resmi =
   SIMPEG/Dukcapil; EIP menyalin & mencocokkan, bukan menerbitkan.
4. **RBAC terpusat di EIP Core.** App baca peran user via API / klaim token.
   Login terbuka domain kampus; role manual per akun.
5. **Auth:** tiap app OIDC client sendiri ke Google Workspace. Service-to-
   service via Sanctum / signed JWT.
6. **Shared library** (composer package internal, mis. `eip/client`): HTTP
   client EIP Core, DTO (`PegawaiData`, `UnitKerjaData`, …), middleware auth +
   resolusi role, helper cache master lokal. **Wajib** dipakai tiap app.
7. **Alur lintas-app** (mis. approval pengadaan yang butuh data perencanaan):
   via **event/webhook + queue Redis**, bukan transaksi terdistribusi.
8. **Workflow = state machine** di backend app pemilik proses; approval ikut
   hierarki `unit_kerja` yang dibaca dari EIP Core.

### 3.2 Keberatan analis yang tercatat

Untuk konteks **tim 1–2 orang + skala fakultas**, rekomendasi analis adalah
**modular monolith yang dirancang siap-pecah** (satu app, batas modul ketat),
karena biaya distribusi (CI/CD, monitoring, OIDC, backup per app; alur lintas-
app jadi komunikasi antar-proses; debugging terdistribusi) dibayar di depan
tanpa keuntungan "kemandirian tim" yang belum ada. Keputusan pemilik proyek:
tetap **aplikasi terpisah** demi batas fisik & kesiapan serah-terima ke tim
lain. Dicatat agar trade-off ini eksplisit; mitigasi ada di §3.1 poin 6–7.

---

## 4. Kepemilikan data & pola integrasi

| Data | Pemilik (tulis) | Konsumen | Pola |
|---|---|---|---|
| `pegawai`, `penempatan` | app kepegawaian → EIP Core | semua app + sistem lama | tulis via API; baca via API + cache |
| `unit_kerja`, `jabatan`, `organisasi` | EIP Core | semua | baca via API + cache |
| `role` / `permission` | EIP Core | semua app | API / klaim token |
| `rencana` | app perencanaan | pengadaan | event/webhook |
| `pengajuan` + approval | app pengadaan | — | state machine internal |
| status kirim WA | server WA blast | app pemanggil | callback |

---

## 5. Fase (selaras nilai & risiko)

| Fase | Hasil | Alasan urutan |
|---|---|---|
| **0** | EIP Core: `unit_kerja`+`pegawai` (+`jabatan`,`organisasi`), OIDC, RBAC, `audit_log`, API `/api/v1/`, portal, shared library | fondasi semua |
| **1** | App Kepegawaian: CRUD pegawai + penempatan + direktori | nilai tertinggi, hapus data ganda |
| **2** | API master + sync ke **1 sistem lama** pilot (mis. logistik) + sambung **WA blast** | de-risk integrasi lebih awal |
| **3** | App Pengadaan: pengajuan + approval berjenjang + notifikasi WA | alur manual paling menyakitkan |
| **4** | App Perencanaan + kaitkan ke pengadaan | |
| **5** | Sisa sistem lama (gaji, aset) baca dari EIP Core | pola sudah terbukti di Fase 2 |
| **6** | App Akademik (nanti) | pola master & approval sudah matang |

---

## 6. Langkah eksekusi berurutan (besok, "satu-satu")

### Langkah 1 — EIP Core: scaffold & fondasi ✅ (2026-09-04)
- [x] `composer create-project laravel/laravel eip-core` → **Laravel 13.30.1**
      di `eip-core/` (subfolder repo ini, bukan repo git terpisah)
- [x] `laravel/boost` (dev) + guidelines AI ter-install (`eip-core/CLAUDE.md`)
- [x] `.env.example` (committed): MySQL `eip_core`, Redis queue, locale id,
      `APP_URL=https://eip.mipa.uns.ac.id`, placeholder OIDC + `ALLOWED_EMAIL_DOMAIN`.
      `.env` lokal (gitignored) pakai **sqlite** — sandbox dev ini tidak ada
      server MySQL; ganti ke MySQL nyata saat hosting siap.
- [x] Konvensi: struktur Laravel standar (`app/Models`, `app/Enums`, factory/
      seeder/test via `php artisan make:`) — TIDAK pakai `app/Domain/*` (itu
      pola utk app *domain* nanti, bukan EIP Core yang cuma master+RBAC+API)

### Langkah 2 — EIP Core: skema master + audit ✅ (2026-09-04)
- [x] Migrasi (nama tabel Indonesia sesuai `docs/01`, bukan hasil pluralisasi
      Inggris Eloquent): `organisasi`, `pegawai`, `unit_kerja` (self-ref tree +
      `organisasi_id` + `kepala_id`→pegawai), `jabatan`, `penempatan` (FK ke
      ketiganya). FK: `restrictOnDelete` utk integritas hierarki, `cascadeOnDelete`
      pegawai→penempatan, `nullOnDelete` utk kepala/parent opsional.
- [x] `timestamps` + `softDeletes()` semua tabel master
- [x] `audit_logs` — **tabel sendiri** (bukan paket pihak ketiga, sesuai Boost
      guideline "jangan tambah dependency tanpa approval"): polymorphic
      `auditable_type/id`, `old_values`/`new_values` JSON, `user_id`
- [x] Enum PHP backed (`App\Enums\*`) utk tiap kolom kategori (jenis, status)
- [x] Model + relasi (parent/children, kepala, penempatan) + factory tiap model
- [x] `MasterDataSeeder`: UNS → Fakultas MIPA → 5 prodi + TU, 5 jabatan dasar,
      24 pegawai contoh dgn penempatan
- [x] Test `tests/Feature/MasterDataSchemaTest.php` (5 test): tree unit_kerja,
      relasi penempatan, unique constraint (nip/kode), FK restrict on forceDelete
- [x] `vendor/bin/pint` bersih; `php artisan test` 7/7 lulus

### Langkah 2b — Skema dikoreksi dari data pegawai nyata ✅ (2026-09-04)

`docs/data_pegawai.xlsx` (data mentah FMIPA, TIDAK ikut git — PII) dibaca &
dianalisis. **Koreksi penting** thd asumsi awal:

- Prodi FMIPA yg BENAR: S-1 Biologi, Farmasi, Fisika, Ilmu Lingkungan, Kimia,
  Matematika, Statistika, Profesi Apoteker (+ prodi pascasarjana S-2/S-3
  sbg *homebase* kedua dosen). **"Informatika" di seed awal SALAH — dihapus.**
- `status_kepegawaian` nyata: `pns`/`non_pns`/`kontrak_profesional`/`purna_tugas`
  (bukan tebakan awal `honor`/`dosen_tt`/`dosen_yayasan`).
- Dimensi **jenis_pegawai** (dosen/tendik) TERPISAH dari status_kepegawaian —
  kolom baru `jenis_pegawai`.
- Kolom baru (migrasi additive, `doctrine/dbal` ditambah utk `->change()`):
  `id_sumber` (kunci impor idempoten), `npwp` (tanpa unique — presisi Excel
  rawan collide), `no_seri_kepeg`, `pendidikan_terakhir`, `golongan_ruang`,
  `tmt_golongan`. `jenis_kelamin` dibuat **nullable** — sumber data sama
  sekali tidak menyediakan jenis kelamin.
- Jabatan struktural (58 jenis: Dekan, Wadek, Kaprodi, Ka.Lab, dst) dan
  jabatan fungsional (32 jenis: Guru Besar..Lektor..Asisten Ahli utk dosen;
  Analis/Pengadministrasi/Pengelola/Pranata/Teknisi/Pramu utk tendik) —
  jauh lebih kaya dari 5 jabatan tebakan awal.
- `MasterDataSeeder` disederhanakan: HANYA Organisasi UNS + UnitKerja
  Fakultas MIPA (fakta publik terverifikasi). Prodi/jabatan sungguhan
  dibentuk otomatis oleh importer dari nilai asli sumber (supaya nama
  pasti cocok, tidak ada katalog tebakan terpisah yg berisiko mismatch).

**`php artisan pegawai:import {path} [--dry-run]`** dibuat:
- Filter baris valid via kolom `ID` numerik (sheet sumber punya tabel rekap
  statistik yg menempel di bawah tabel utama — ikut kebaca kalau filter
  cuma dari kolom Nama).
- Upsert `unit_kerja`/`jabatan` on-the-fly by nama asli sumber; upsert
  `pegawai` by `id_sumber`; upsert `penempatan` (utama + homebase kedua bila
  beda dari unit utama) — **idempotent**, aman dijalankan ulang.
- Jabatan struktural dicatat ke katalog TAPI **tidak** otomatis dibuatkan
  penempatan (unit tujuan tak bisa dipastikan dari judul jabatan saja) —
  dilaporkan sbg daftar utk assignment manual.
- Diuji di DB dev lokal (sqlite): **190 pegawai, 20 unit_kerja, 89 jabatan,
  231 penempatan**, 0 warning, idempoten (re-run = 0 baru/190 update).
- Keterbatasan tercatat: `jenis_kelamin` semua null (tidak ada di sumber),
  `gelar_depan/belakang` tidak diparse dari nama (nama disimpan utuh apa
  adanya), `no_hp` kosong di 183/190 (perlu pengumpulan terpisah utk WA
  blast), 58 jabatan struktural perlu assignment unit manual.
- **Belum dijalankan ke produksi** — menunggu konfirmasi pemilik data.

### Langkah 3 — EIP Core: auth OIDC + RBAC
- [ ] Socialite Google (OIDC), batasi `hd` / domain email kampus (BLOCKER #7)
- [ ] Tabel `users` (email = kunci ke `pegawai`), `roles`, `permissions`,
      `role_user` (paket `spatie/laravel-permission` boleh)
- [ ] Alur "email belum terdaftar sbg pegawai" → halaman/undangan admin
- [ ] Sanctum: token service-to-service (per app konsumen)

### Langkah 4 — EIP Core: API `/api/v1/`
- [ ] `GET /pegawai`, `/pegawai/{id}`, `/unit-kerja`, `/jabatan`, `/organisasi`
      (read, token-protected, pagination + filter + `updated_since` utk sync)
- [ ] `POST/PUT /pegawai`, `/penempatan` — **hanya token app kepegawaian**
      (policy/ability terpisah)
- [ ] `GET /me/roles` / sertakan role di klaim token utk app lain
- [ ] Resource classes + versioning + dokumentasi (mis. Scramble/OpenAPI)

### Langkah 5 — EIP Core: portal SSO + shared library
- [ ] Halaman portal: daftar app + tombol login Google (satu sesi)
- [ ] Package internal `eip/client`: `EipCoreClient`, DTO, middleware auth,
      cache master → publish ke path repo / Packagist privat

### Langkah 6 — App Kepegawaian
- [ ] Scaffold `eip-kepegawaian` (Laravel 13 + SPA Vue 3 + Element Plus),
      pasang `eip/client`, OIDC client Google
- [ ] DB lokal `eip_kepegawaian`: data non-master (draft, dokumen SK, cuti —
      tergantung BLOCKER #8)
- [ ] Fitur: CRUD pegawai (tulis ke EIP Core API), kelola penempatan,
      direktori pegawai (baca + cache)
- [ ] Uji: satu jalur tulis benar-benar terjaga

### Langkah 7 — Integrasi pilot (1 sistem lama)
- [ ] Pilih sistem termudah (mis. logistik) — konfirmasi BLOCKER #2/#3
- [ ] Pola A: job sync EIP Core → `pegawai_ref` di DB sistem itu (`updated_since`)
- [ ] Uji matching by kolom kunci; tangani pegawai tak ter-match

### Langkah 8 — Sambung WA blast
- [ ] Konfirmasi kontrak API (BLOCKER #4); klien di `eip/client`
- [ ] Queue job kirim + endpoint callback status

### Langkah 9+ — Pengadaan → Perencanaan → sisa sistem lama → Akademik
- [ ] Per Fase 3–6; tiap app: scaffold + `eip/client` + OIDC + state machine
      (pengadaan) sesuai `docs/02-rancangan-integrasi.md`

---

## 7. Blocker & pertanyaan terbuka

**Blocker — jawab sebelum / saat Fase 0–2:**
1. ~~Ruang lingkup~~ **RESOLVED**: Fakultas MIPA UNS (`eip.mipa.uns.ac.id`). Estimasi
   jumlah pegawai & unit riil masih perlu dikonfirmasi utk validasi seed/skema.
2. Sistem lama: (a) satu server MySQL yang sama? (b) DB boleh dibaca? (c) kode
   bisa dimodifikasi? (d) siapa yang memelihara?
3. Cara tiap sistem lama meng-*identify* pegawai sekarang (kolom kunci) — untuk
   strategi matching.
4. WA blast: sudah berjalan atau masih desain? Kontrak API final?
5. Struktur `unit_kerja` nyata (contoh pohon fakultas); apakah hierarki approval
   = hierarki unit persis.

**Penting — Fase 1–3:**
6. Daftar role awal + siapa yang menetapkan.
7. Domain email yang boleh login (cegah Gmail pribadi).
8. Apakah cuti / SK / kontrak masuk lingkup app kepegawaian.
9. Sumber nomor HP pegawai untuk WA.
10. Kontrak event/webhook antar-app (format, retry, idempotency).
