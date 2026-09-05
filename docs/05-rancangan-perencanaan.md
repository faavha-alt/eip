# EIP — Rancangan App Perencanaan

> Dokumen desain app **Perencanaan** — aplikasi terpisah pertama yang dibangun
> setelah EIP Core + Kepegawaian (lihat keputusan arsitektur `CLAUDE.md` §1/§3).
> Status: **Draft v0.1** — hasil requirement gathering, BELUM masuk desain
> skema final/kode. Dicatat di sini supaya tidak hilang & jadi rujukan.

---

## 1. Posisi dalam arsitektur

**App terpisah** (Laravel 13 + MySQL + SPA sendiri, deploy sendiri), BUKAN
modul EIP Core — dipertimbangkan eksplisit & ditolak (beda dgn Kepegawaian:
data Perencanaan genuinely belum ada di mana pun, dan menggabungkan berisiko
bikin EIP Core jatuh ke masalah yg sama spt dikritik dari sistem Aset lama:
"jadi satu semua, terlalu besar, tidak fokus"). Baca master pegawai/unit_kerja
dari EIP Core API (lewat shared library `eip/client`, belum dibangun).

**Lintas-domain** — BUKAN khusus aset. Menangani kebutuhan Aset (barang
modal) maupun Persediaan/BHP (barang habis pakai), dan kemungkinan kategori
lain di masa depan. Ini beda dari sistem Aset lama (`iams-fmipa-uns`) yang
menyatukan perencanaan+pengadaan+pencatatan aset jadi satu — di arsitektur
baru, Perencanaan & Pengadaan lintas-domain, sementara Aset & Persediaan
(Logistik BHP) diperamping jadi HANYA pencatatan (lihat CLAUDE.md §1).

**Sumber requirement**: dibaca langsung kode nyata sistem Aset lama
(`/ai/projects/iams-fmipa-uns`) & Persediaan (`/ai/projects/logistik`) —
aturan bisnisnya sudah teruji pemakaian nyata & diambil sbg referensi
(BUKAN kodenya yg dipindah — lihat §6 alasan build-baru).

---

## 2. Alur bisnis end-to-end

```
Prodi ajukan kebutuhan (Pengajuan)
        │  bebas, TANPA approval, TAPI dibatasi pagu (hard block)
        ▼
[PERENCANAAN]  Pengajuan terkumpul per unit/periode/kategori
        │  dikelompokkan per kategori → tentukan vendor yg relevan
        ▼
[PENGADAAN]  Proses beli (wizard, per kategori → vendor)
        │  harga riil dari vendor didapat
        ▼
   Harga beda dari estimasi? ──ya──▶ status "Perlu Penyesuaian"
        │ tidak                           │
        │                    Prodi sendiri menyesuaikan jumlah/harga
        │                    (tetap tunduk kontrol pagu)
        ▼                                 │
   Direalisasikan ◀────────────────────────┘
        │
        ▼
[ASET / PERSEDIAAN]  Dicatat sbg barang baru (registry / stok masuk)
```

Pengajuan **TIDAK melalui approval gate** — prodi bebas mengajukan barang
apa saja. Kontrolnya murni di sisi pagu (lihat §4), bukan di sisi konten
pengajuan.

---

## 3. Entitas inti (draft, belum final)

### `kategori_kebutuhan` (master, admin-manageable)

Pola sama seperti `golongan_ruang`/`pendidikan` di EIP Core: tabel
kode+nama sederhana, bukan enum hardcode — supaya bisa ditambah admin
tanpa deploy kode baru.

Contoh isi awal (dikonfirmasi pengguna, "paling umum"): **Alat Lab**,
**Komputer**, **Mebeler**. Extensible.

Dipakai dua kali: (1) mengklasifikasi Pengajuan, (2) di Pengadaan untuk
menentukan vendor yang relevan per kategori. Karena dipakai lintas app
(Perencanaan & Pengadaan), taksonomi ini perlu jadi **referensi bersama**
— opsi: (a) tabel master di Perencanaan, dibaca Pengadaan via API
(Perencanaan "memiliki" konsep ini krn muncul pertama di alur), atau
(b) naik jadi master lintas-domain di EIP Core (spt `unit_kerja`/
`jabatan`) kalau ternyata dipakai modul lain juga nanti. **Belum
diputuskan** — default sementara: opsi (a), Perencanaan yang punya.

### `periode_anggaran`

Mis. "Tahun Anggaran 2027". Wadah waktu tempat pagu & pengajuan berlaku.

### `pagu_anggaran` (ledger/riwayat, BUKAN nilai tunggal)

**Pola sama seperti `riwayat_pangkat_golongan` di EIP Core**: pagu BISA
DIREVISI DI TENGAH PERIODE (dikonfirmasi pengguna) — jadi bukan satu
kolom nilai yang di-update di tempat, tapi ledger append-only. "Pagu
saat ini" = baris terbaru per (unit_kerja, periode).

| Kolom | Keterangan |
|---|---|
| `unit_kerja_id` | Referensi ke EIP Core (via API/cache lokal) — **termasuk Fakultas sendiri**, bukan cuma prodi (dikonfirmasi pengguna: pagu dibagi ke semua unit penerima, Fakultas salah satunya) |
| `periode_anggaran_id` | |
| `nominal` | Pagu berlaku sejak revisi ini |
| `berlaku_sejak` | Tanggal efektif revisi |
| `ditetapkan_oleh` | Admin yang input (pagu ditentukan langsung Fakultas, diinput admin — TANPA alur pengajuan/approval tersendiri utk penetapan pagu itu sendiri) |
| `keterangan` | Alasan revisi (opsional) |

Sisa pagu unit = pagu terkini (baris terbaru) − total nilai Pengajuan
aktif (blm dibatalkan) milik unit itu dlm periode berjalan.

### `pengajuan`

| Kolom | Keterangan |
|---|---|
| `unit_kerja_id` | Prodi/unit pengaju |
| `periode_anggaran_id` | |
| `kategori_kebutuhan_id` | Utk routing vendor nanti di Pengadaan |
| `nama_barang`, `jumlah`, `estimasi_harga_satuan` | Diisi bebas oleh prodi |
| `status` | `diajukan` → `dalam_pengadaan` → `perlu_penyesuaian` (kalau harga riil beda) → `disesuaikan` → `direalisasikan`. Kemungkinan jg `dibatalkan`. |
| `harga_realisasi` | Diisi setelah dapat harga riil dari Pengadaan (beda kolom dari estimasi, spy ada jejak "rencana vs realisasi") |

**Bisa diedit bebas** oleh prodi selama status masih `diajukan` (belum
masuk proses Pengadaan). Setelah `perlu_penyesuaian`, **prodi sendiri**
yang menyesuaikan jumlah/harga (dikonfirmasi pengguna — bukan admin
pengadaan atas nama prodi), tetap tunduk validasi pagu (§4).

---

## 4. Aturan kontrol pagu (KUNCI — hard constraint)

**Dikonfirmasi eksplisit oleh pengguna**: "Kontrol pagu otomatis,
pengajuan tidak bisa melebihi pagu. Kalau pagu habis sudah tidak bisa
menambah barang."

- Validasi **real-time, server-side**, saat create/update Pengajuan:
  `total nilai pengajuan aktif unit (termasuk yg baru/diubah) ≤ sisa pagu
  unit tsb pada periode berjalan`. Gagal validasi → ditolak (bukan cuma
  peringatan).
- Berlaku juga saat **penyesuaian** pasca-harga-riil (§3) — kalau harga
  riil naik dan bikin melebihi pagu, prodi harus mengurangi jumlah/ganti
  barang supaya kembali pas, bukan otomatis ditolak dari sistem
  Pengadaan (perlu dipikirkan UX-nya: prodi diberi tahu — kemungkinan via
  wa-blast yg sudah tersambung — utk masuk & menyesuaikan).
- **Belum diputuskan**: apakah nilai yg dipakai utk cek pagu adalah
  `jumlah × estimasi_harga_satuan` (saat diajukan) atau ada toleransi
  (mis. buffer %) mengingat estimasi harga bisa meleset. Default
  sementara: pakai estimasi apa adanya, ketat.

---

## 5. Keterkaitan dengan Pengadaan (app terpisah lain)

- **Kategori kebutuhan** dipakai Pengadaan utk mengelompokkan Pengajuan
  ke vendor yg sesuai (tiap vendor biasa spesialis kategori tertentu).
- **Vendor** kemungkinan besar tetap "milik" Pengadaan (bukan
  Perencanaan) — sesuai posisi dalam alur (vendor baru relevan saat
  eksekusi beli, bukan saat merencanakan kebutuhan). Perlu dikonfirmasi
  saat desain Pengadaan dimulai.
- **UI Pengadaan**: wizard step-by-step dgn draft per langkah (keputusan
  sesi sebelumnya, lihat PROGRESS.md 2026-09-05).
- Integrasi antar app (Perencanaan ↔ Pengadaan): API + event/webhook
  (pola CLAUDE.md §3.4), BUKAN FK lintas-DB — detail kontrak API belum
  dirancang.

---

## 6. Kenapa build baru, bukan pindah dari sistem Aset lama

Dibaca langsung kode `iams-fmipa-uns`: modul "Pengadaan" di situ
(`ProcurementBatchController` + `RealizationController`) menyatu erat
dgn pembuatan `Asset` (`finalize()` langsung insert baris `Asset`, satu
DB/app yg sama) — memisahkannya jadi app independen butuh bongkar-pasang
setara nulis ulang, sementara sistemnya aktif dipakai produksi. **Tidak
ada modul "Perencanaan" berdiri sendiri** di sana yg bisa diangkat —
konsepnya tersebar di "Pengajuan Aset" (request bottom-up, ternyata belum
pernah dipakai krn belum disosialisasikan, bukan salah desain) & "Anggaran"
(pagu Fakultas→Prodi, model 2-lapis kaku — direvisi di rancangan ini jadi
lebih fleksibel: pagu ke semua unit penerima termasuk Fakultas sendiri).

Aturan bisnis yg **diambil sbg referensi** (bukan kode): vendor wajib di
level header pengadaan (bukan per-item), BAST berbasis unit (bisa gabung
aset dari beberapa pengadaan), kode aset otomatis+QR.

**Data lama bisa diimpor nanti** (dikonfirmasi pengguna) — skema di atas
sebaiknya menyiapkan kolom referensi ke ID sistem lama (pola `id_sumber`
spt migrasi SIMPEG di EIP Core) begitu Fase 5/6 (refactor Aset/Persediaan)
tiba, supaya ETL data lama tertelusuri.

---

## 7. Hal yang masih terbuka (belum diputuskan)

- Kepemilikan taksonomi `kategori_kebutuhan`: di Perencanaan saja, atau
  naik jadi master lintas-domain di EIP Core?
- Kepemilikan `vendor`: di Pengadaan (dugaan kuat) — belum dikonfirmasi.
- Toleransi/buffer validasi pagu saat estimasi harga meleset.
- Notifikasi "Perlu Penyesuaian" ke prodi — pakai wa-blast (sudah
  tersambung ke EIP Core, bukan ke Perencanaan — perlu jalur baru)?
- Anggaran (pagu) apakah genuinely "milik" Perencanaan sepenuhnya, atau
  ada pertimbangan lain (msh didiskusikan sesi sebelumnya, blm final).
- Skema DB detail (nama tabel final, tipe kolom, index) — draft di §3
  masih level konsep, belum migration-ready.

---

## 8. Status & langkah berikut

Requirement gathering (2026-09-05) — lihat log lengkap di `PROGRESS.md`.
Belum ada kode dibangun. Langkah berikut yg mungkin: (a) selesaikan hal
terbuka §7, (b) mulai shared library `eip/client` sbg fondasi teknis,
(c) scaffold app `perencanaan/` (Laravel 13, pola sama eip-core/wa-blast).
