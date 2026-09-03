# EIP — Desain Skema Database Inti

> Dokumen fondasi data master Enterprise Integration Platform (EIP).
> Tabel di sini adalah **sumber kebenaran** yang dirujuk modul-modul EIP dan
> sistem-sistem lama (gaji, aset, logistik) yang membaca data pegawai dari EIP.

Status: **Draft v0.4** — dikoreksi: arsitektur "aplikasi terpisah per domain"
(lihat `docs/04`). Tabel di dokumen ini **milik DB EIP Core**. App domain baru
(kepegawaian, perencanaan, pengadaan, akademik) punya DB sendiri dan mengakses
master ini **via EIP Core API**, bukan FK lintas-DB.

## Keputusan master pegawai

- **Master data pegawai dimiliki oleh EIP sendiri**.
  Sistem eksternal (SIMPEG/akademik lama) aksesnya terbatas dan master datanya
  tidak andal, sehingga tidak dijadikan sumber live.
- Identitas resmi ASN (**NIP, NIK, ID_SIMPEG**) disimpan sebagai kolom
  referensi pada tabel `pegawai`, diisi saat input/sinkronisasi berkala, untuk
  keperluan resmi & pencocokan (`matching`) ke sistem SIMPEG bila akses terbuka.
- Modul EIP memakai data pegawai yang dikelola EIP (satu jalur tulis: hanya modul
  kepegawaian). **Sistem lama (gaji/aset/logistik) membaca master pegawai dari
  EIP** (via API, atau salinan lokal + sinkronisasi berkala).

---

## 1. Prinsip desain

- **DB EIP Core: MySQL** (seragam dengan sistem lama yang juga Laravel + MySQL),
  memuat SELURUH master data. Tiap app domain punya DB MySQL sendiri untuk data
  non-master miliknya. Tidak ada tabel `pegawai` kedua di app manapun.
- **Master data** (`pegawai`, `unit_kerja`, `jabatan`, `organisasi`) hidup di
  DB EIP Core. App domain **membacanya via EIP Core API** (+ cache lokal
  secukupnya), TIDAK lewat FK lintas-DB. Penulisan `pegawai`/`penempatan` hanya
  dari app kepegawaian, lewat API.
- Identitas global memakai **BIGINT auto-increment** (default MySQL) atau **ULID**
  bila perlu idempotent lintas sistem.
- Semua tabel membawa **timestamps** dan, bila perlu, **soft delete**
  (`deleted_at`) untuk audit.
- Konvensi penamaan: `snake_case`, jamak untuk tabel, singular untuk model.

---

## 2. Diagram konseptual data master

```
         ┌──────────────────────┐
         │     organisasi       │  (kampus / unit akar)
         └──────────┬───────────┘
                    │ (children - hierarki)
         ┌──────────▼───────────┐
         │      unit_kerja      │  (fakultas/prodi/biro → pohon)
         └────┬──────┬──────────┘
              │      │
    pegawai───┘      │
      (personil)     │
              ┌──────▼──────────┐
              │     jabatan     │  (rektor, dekan, ketua prodi, dsb)
              └─────────────────┘

Dasar relasi: unit_kerja & jabatan dihubungkan lewat tabel pivot
"penempatan" pada pegawai (posisi terikat unit).
```

---

## 3. Tabel master inti

### 3.1 `organisasi`
Akar struktur kelembagaan. Untuk kampus: biasanya satu root (universitas),
bisa memiliki banyak jika multi-entitas.

| kolom | tipe | keterangan |
|---|---|---|
| id | bigint / ulid | PK |
| parent_id | FK → organisasi | null = root |
| nama | varchar | nama organisasi |
| kode | varchar unique | kode singkat |
| jenis | enum | `universitas`, `lembaga`, `yayasan` dll |
| alamat, telepon, email | varchar | kontak |
| is_active | boolean | |
| timestamps | | |

### 3.2 `unit_kerja`
Struktur hierarkis fakultas/prodi/biro/unit — **pohon** (self-reference).

| kolom | tipe | keterangan |
|---|---|---|
| id | bigint / ulid | PK |
| parent_id | FK → unit_kerja | null = level teratas |
| organisasi_id | FK → organisasi | pemilik unit |
| nama | varchar | |
| kode | varchar unique | mis. `FS`,`PRODI-TI` |
| jenis_unit | enum | `fakultas`,`jurusan`,`prodi`,`biro`,`bagian`,`seksi` |
| kepala | FK → pegawai | pimpinan (nullable hingga pegawai dibuat) |
| is_active | boolean | |
| timestamps | | |

> Catatan: hierarki dipakai juga oleh modul akademik (prodi/jurusan) dan
> pengadaan (unit pengusul). Jangan buat tabel prodi terpisah di akademik —
> gunakan `unit_kerja` dengan `jenis_unit`.

### 3.3 `jabatan`
Jabatan struktural/fungsional sebagai **konsep**, tidak terikat pegawai.

| kolom | tipe | keterangan |
|---|---|---|
| id | bigint / ulid | PK |
| nama | varchar | mis. "Dekan", "Ketua Prodi TI", "Staff Keuangan" |
| kode | varchar unique | |
| jenis | enum | `struktural`, `fungsional`, `fungsional_umum` |
| level | int | tingkat dalam hierarki (untuk urutan approval) |
| eselon | varchar nullable | bila konteks ASN/PNS |
| deskripsi | text | |
| is_active | boolean | |
| timestamps | | |

### 3.4 `pegawai`
Personil inti — dirujuk oleh **gaji**, **aset** (PIC), **pengadaan/logistik**
(peminta), **akademik** (dosen), **WA blast** (penerima).

| kolom | tipe | keterangan |
|---|---|---|
| id | bigint / ulid | PK |
| nip | varchar unique nullable | NIP ASN (identitas resmi) |
| nik | varchar unique nullable | NIK KTP (identitas resmi) |
| id_simpeg | varchar unique nullable | kunci relasi ke sistem SIMPEG (pencocokan/matching) |
| nama_lengkap | varchar | |
| gelar_depan, gelar_belakang | varchar | |
| jenis_kelamin | enum | `L`,`P` |
| tempat_lahir, tanggal_lahir | | |
| email | varchar unique nullable | **juga untuk login & WA blast** |
| no_hp | varchar nullable | **untuk WA blast** |
| status_kepegawaian | enum | `pns`,`honor`,`kontrak`,`dosen_tt`,`dosen_yayasan` dst |
| foto | string nullable | |
| tanggal_masuk | date | |
| tanggal_keluar | date nullable | |
| is_active | boolean | |
| timestamps | | |

### 3.5 `penempatan` (pivot pegawai ↔ unit_kerja ↔ jabatan)
Seorang pegawai bisa punya **riwayat posisi**; posisi aktif = status aktif.

| kolom | tipe | keterangan |
|---|---|---|
| id | bigint / ulid | PK |
| pegawai_id | FK → pegawai | |
| unit_kerja_id | FK → unit_kerja | |
| jabatan_id | FK → jabatan | |
| tgl_mulai | date | |
| tgl_selesai | date nullable | null = masih menjabat |
| is_posisi_utama | boolean | penanda posisi utama (jika rangkap) |
| status | enum | `aktif`, `nonaktif` |
| timestamps | | |

---

## 4. Pola referensi (app domain & sistem lama)

Tidak ada app yang membuat tabel `pegawai` sendiri. Karena tiap domain = app +
DB terpisah, **semua referensi ke master lewat EIP Core API** (bukan FK lintas-
DB). App menyimpan hanya `*_id` master + cache atribut secukupnya:

| App domain | Referensi ke master (via EIP Core API) |
|---|---|
| Perencanaan | `rencana.pemohon_id`, `rencana.unit_kerja_id` (id master, di-resolve via API) |
| Pengadaan | `pengajuan.pemohon_id`, `pengajuan.unit_kerja_id`; hierarki approval dari `unit_kerja` API |
| Akademik (nanti) | `dosen` dipetakan dari `pegawai`; `prodi` = `unit_kerja` (via API) |
| WA Blast | kontak/penerima di-resolve dari `pegawai` API; boleh salin utk arsip kirim |

**Sistem lama (gaji, aset, logistik)** berpola **sama persis**: aplikasi/DB
terpisah, **membaca master pegawai dari EIP Core** — bukan saling menulis.
Relasi secara logika (via EIP Core API / sinkronisasi), bukan FK antar-DB:

| Sistem lama | Butuh dari EIP | Cara |
|---|---|---|
| Gaji | pegawai, unit_kerja | API EIP `/api/v1/pegawai`, read / salinan |
| Aset | pegawai (PIC), unit_kerja | API EIP |
| Logistik | pegawai (pemakai), unit_kerja | API EIP |

---

## 5. Aturan penting untuk tim kecil

1. **Master dulu, modul belakangan.** Migrasi tabel master selesai & stabil
   dulu sebelum modul lain.
2. **Satu jalur tulis data pegawai.** Hanya **app kepegawaian** yang insert/
   update `pegawai`/`penempatan`, lewat EIP Core API (service token). App &
   sistem lain hanya baca. Bila data beda, EIP Core yang benar.
3. **API versioned** (`/api/v1/`) sejak awal — termasuk API master pegawai utk
   sistem lama.
4. **Audit trail**: pertimbangkan tabel/package `audit_log` utk perubahan data
   sensitif (pegawai, status).

---

## 6. Langkah berikut

- [ ] Review skema di atas (tambah kolom sesuai kebutuhan nyata)
- [ ] Tulis migrasi Laravel untuk tabel master
- [ ] Buat definisi workflow per modul (lihat dokumen terpisah)
