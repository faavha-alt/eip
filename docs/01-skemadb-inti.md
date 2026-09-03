# EIP — Desain Skema Database Inti

> Dokumen fondasi data master Enterprise Integration Platform (EIP).
> Tabel di sini adalah **sumber kebenaran** yang dirujuk semua modul lain
> (gaji, aset, pengadaan, logistik BHP, WA blast, akademik).

Status: **Draft v0.2** — perlu review sebelum implementasi.

## Keputusan master pegawai

- **Master data pegawai dimiliki oleh EIP sendiri** (Pola A/C pragmatis).
  Sistem eksternal (SIMPEG/akademik lama) aksesnya terbatas dan master datanya
  tidak andal, sehingga tidak dijadikan sumber live.
- Identitas resmi ASN (**NIP, NIK, ID_SIMPEG**) disimpan sebagai kolom
  referensi pada tabel `pegawai`, diisi saat input/sinkronisasi berkala, untuk
  keperluan resmi & pencocokan (`matching`) ke sistem SIMPEG bila akses terbuka.
- Semua modul EIP memakai data pegawai yang dikelola EIP (satu jalur tulis:
  hanya modul kepegawaian).

---

## 1. Prinsip desain

- **Satu database** (PostgreSQL), diorganisasi per domain. Tidak dibuat tabel
  terpisah antar-modul yang sama maknanya (mis. jangan dua tabel `pegawai`).
- **Master data** (`pegawai`, `unit_kerja`, `jabatan`, `organisasi`) dikelola
  oleh modul pemilik dan **direferensikan** oleh modul lain melalui
  `foreign key` (bukan diduplikasi).
- Identitas global memakai **BIGINT auto-increment** atau **ULID**.
  Rekomendasi: `ULID` (pilihan) agar idempotent & mudah digabung lintas sistem.
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

## 4. Pola referensi antar-modul (agar integrasi rapi)

Modul lain **tidak boleh** membuat tabel `pegawai` sendiri. Contoh pola:

| Modul | Relasi ke data master |
|---|---|
| Gaji | `gaji_header.pegawai_id` → pegawai; referensi `unit_kerja_id` |
| Aset | `aset.penanggung_jawab_id` → pegawai; `aset.unit_kerja_id` → unit_kerja |
| Pengadaan | `pengajuan.pemohon_id` → pegawai; `pengajuan.unit_kerja_id` → unit_kerja |
| Logistik BHP | `pemakaian.bhp` + `pemakai.pegawai_id` |
| WA Blast | tabel kontak/penerima bisa **membaca** dari pegawai, atau salinan untuk arsip kirim |
| Akademik (nanti) | `dosen` dipetakan dari pegawai (bukan tabel duplikat); `prodi` = unit_kerja |

---

## 5. Aturan penting untuk tim kecil

1. **Master dulu, modul belakangan.** Pastikan migrasi tabel 3.1–3.5 selesai
   dan stabil sebelum menulis modul gaji/aset/pengadaan.
2. **Satu jalur tulis data pegawai.** Hanya modul kepegawaian yang insert/
   update `pegawai`; modul lain hanya baca. Mencegah konflik data.
3. **API versioned** (`/api/v1/`) sejak awal.
4. **Audit trail**: pertimbangkan tabel/package `audit_log` untuk perubahan
   data sensitif (gaji, status pegawai).

---

## 6. Langkah berikut

- [ ] Review skema di atas (tambah kolom sesuai kebutuhan nyata)
- [ ] Tulis migrasi Laravel untuk tabel master
- [ ] Buat definisi workflow per modul (lihat dokumen terpisah)
